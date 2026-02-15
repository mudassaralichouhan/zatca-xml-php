#!/bin/bash

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Change to project directory
cd "$PROJECT_DIR"

# Define environments to clean up
ENVIRONMENTS=("developer-portal" "simulation" "core")

# Run cleanup for each environment
for env in "${ENVIRONMENTS[@]}"; do
    echo "=========================================="
    echo "Running cleanup for environment: $env"
    echo "=========================================="
    
    # Run the PHP cleanup script for this environment
    php "$SCRIPT_DIR/cleanup-unconfirmed-accounts.php" "$env"
    
    # Check exit code
    if [ $? -eq 0 ]; then
        echo "$(date): Cleanup completed successfully for $env"
    else
        echo "$(date): Cleanup failed for $env with exit code $?"
        exit 1
    fi
done

echo "=========================================="
echo "$(date): All environment cleanup completed successfully"
echo "=========================================="
