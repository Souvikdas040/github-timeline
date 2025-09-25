#!/bin/bash

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
CRON_JOB_FILE="$SCRIPT_DIR/cron.php"
LOG_FILE="$SCRIPT_DIR/cron.log"

# Escape characters that might be problematic in crontab
ESCAPED_CRON_JOB_FILE=$(echo "$CRON_JOB_FILE" | sed 's/[\/&]/\\&/g')
ESCAPED_LOG_FILE=$(echo "$LOG_FILE" | sed 's/[\/&]/\\&/g')

# The cron job entry
# Runs cron.php every 5 minutes and logs stdout/stderr to cron.log
CRON_ENTRY="*/5 * * * * php $ESCAPED_CRON_JOB_FILE >> $ESCAPED_LOG_FILE 2>&1"

# Check if the cron job already exists to avoid duplicates
(crontab -l 2>/dev/null | grep -F "$CRON_ENTRY")
if [ $? -eq 0 ]; then
    echo "Cron job already exists:"
    echo "$CRON_ENTRY"
else
    # Add the cron job
    (crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -
    if [ $? -eq 0 ]; then
        echo "Cron job added successfully:"
        echo "$CRON_ENTRY"
    else
        echo "Failed to add cron job."
    fi
fi

echo "To verify, run: crontab -l"
echo "Check $LOG_FILE for cron job output."