<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\AccountCleanupService;
use App\Logging\Logger;

// Get mode from command line argument or default to 'dev'
$mode = $argv[1] ?? 'dev';

try {
    echo "Starting unconfirmed accounts cleanup for mode: $mode..." . PHP_EOL;

    // Cleanup accounts older than 3 days using your existing method
    $stats = AccountCleanupService::cleanupUnconfirmedAccounts($mode, 3);

    echo "Cleanup completed successfully!" . PHP_EOL;
    echo "Deleted accounts: " . $stats['deleted_count'] . PHP_EOL;
    echo "Cutoff date: " . $stats['cutoff_date'] . PHP_EOL;
    echo "Days old threshold: " . $stats['days_old'] . PHP_EOL;

    if ($stats['deleted_count'] > 0) {
        echo "Deleted accounts details:" . PHP_EOL;
        foreach ($stats['deleted_accounts'] as $account) {
            echo "- ID: {$account['id']}, Email: {$account['email']}, Created: {$account['created_at']}" . PHP_EOL;
        }
    }

} catch (Exception $e) {
    echo "Error during cleanup: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
