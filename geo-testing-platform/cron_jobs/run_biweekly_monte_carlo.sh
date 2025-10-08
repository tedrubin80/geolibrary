#!/bin/bash
#
# Run Monte Carlo GEO tests every other day for 2 weeks
# This script will automatically disable itself after 2 weeks (14 days)
#

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
START_DATE_FILE="$PROJECT_DIR/.monte_carlo_start_date"

# Get current date in seconds since epoch
CURRENT_DATE=$(date +%s)

# Check if start date file exists
if [ ! -f "$START_DATE_FILE" ]; then
    # First run - create start date file
    echo "$CURRENT_DATE" > "$START_DATE_FILE"
    echo "$(date): First run - Starting 2-week Monte Carlo experiment schedule" >> "$PROJECT_DIR/logs/cron_biweekly.log"
else
    # Check if 2 weeks (14 days = 1209600 seconds) have passed
    START_DATE=$(cat "$START_DATE_FILE")
    DAYS_ELAPSED=$(( ($CURRENT_DATE - $START_DATE) / 86400 ))

    if [ $DAYS_ELAPSED -ge 14 ]; then
        echo "$(date): 2 weeks completed - Disabling biweekly Monte Carlo runs" >> "$PROJECT_DIR/logs/cron_biweekly.log"

        # Remove cron job
        crontab -l | grep -v "run_biweekly_monte_carlo.sh" | crontab -

        echo "$(date): Cron job removed. Total runs completed over $DAYS_ELAPSED days." >> "$PROJECT_DIR/logs/cron_biweekly.log"
        exit 0
    fi
fi

# Run the Monte Carlo test
echo "$(date): Starting Monte Carlo run (Day $(( ($CURRENT_DATE - $(cat $START_DATE_FILE)) / 86400 )) of 14)" >> "$PROJECT_DIR/logs/cron_biweekly.log"

cd "$PROJECT_DIR"

# Activate virtual environment and run test
source venv/bin/activate
python run_monte_carlo.py >> logs/cron_biweekly.log 2>&1

# Check exit status
if [ $? -eq 0 ]; then
    echo "$(date): Monte Carlo run completed successfully" >> "$PROJECT_DIR/logs/cron_biweekly.log"
else
    echo "$(date): ERROR - Monte Carlo run failed" >> "$PROJECT_DIR/logs/cron_biweekly.log"
fi

# Calculate days remaining
DAYS_REMAINING=$(( 14 - $(( ($CURRENT_DATE - $(cat $START_DATE_FILE)) / 86400 )) ))
echo "$(date): $DAYS_REMAINING days remaining in experiment schedule" >> "$PROJECT_DIR/logs/cron_biweekly.log"
echo "----------------------------------------" >> "$PROJECT_DIR/logs/cron_biweekly.log"
