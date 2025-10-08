# GEO Testing Platform - Cron Jobs

Automated testing schedules for systematic data collection and patent evidence generation.

## Available Cron Jobs

### 1. Biweekly Monte Carlo Testing
**Script**: `run_biweekly_monte_carlo.sh`
**Schedule**: Every other day at 2:00 AM for 2 weeks
**Purpose**: Build comprehensive dataset with multiple Monte Carlo experiments
**Auto-disable**: Yes (after 14 days)

#### Setup
```bash
# The cron job is already configured:
0 2 */2 * * /var/www/geolibrary/geo-testing-platform/cron_jobs/run_biweekly_monte_carlo.sh

# View schedule
crontab -l

# Monitor progress
tail -f ../logs/cron_biweekly.log
```

#### Features
- Automatically tracks start date
- Runs Monte Carlo test with 5 iterations per method
- Self-disables after 2 weeks (14 days)
- Logs all activity to `logs/cron_biweekly.log`
- Saves results to `results/monte_carlo_YYYYMMDD_HHMMSS.json`

#### Manual Execution
```bash
# Test the script manually
./run_biweekly_monte_carlo.sh

# Reset the 2-week counter
rm ../.monte_carlo_start_date
```

### 2. Daily Testing (Template)
**Script**: `run_daily_tests.sh`
**Purpose**: Run daily GEO tests across different queries/domains

### 3. Weekly Analysis (Template)
**Script**: `run_weekly_analysis.sh`
**Purpose**: Aggregate weekly results and generate reports

## Cron Schedule Reference

```
# Format:
# ┌───────────── minute (0 - 59)
# │ ┌───────────── hour (0 - 23)
# │ │ ┌───────────── day of month (1 - 31)
# │ │ │ ┌───────────── month (1 - 12)
# │ │ │ │ ┌───────────── day of week (0 - 6) (Sunday to Saturday)
# │ │ │ │ │
# │ │ │ │ │
# * * * * * command

# Examples:
0 2 */2 * *    # Every other day at 2 AM
0 2 * * *      # Daily at 2 AM
0 8 * * 0      # Sundays at 8 AM
```

## Logs

All cron job logs are stored in `../logs/`:
- `cron_biweekly.log` - Biweekly Monte Carlo runs
- `cron_daily.log` - Daily test runs
- `cron_weekly.log` - Weekly analysis runs

## Monitoring

```bash
# Check if cron jobs are running
ps aux | grep run_monte_carlo

# View latest log output
tail -20 ../logs/cron_biweekly.log

# List all completed results
ls -lth ../results/

# Check cron job status
crontab -l | grep monte_carlo
```

## Notes

- All scripts use the virtual environment: `source ../venv/bin/activate`
- Results are timestamped and never overwritten
- Scripts check for dependencies before running
- Error messages are logged for debugging
- The biweekly script automatically removes its cron entry after 14 days
