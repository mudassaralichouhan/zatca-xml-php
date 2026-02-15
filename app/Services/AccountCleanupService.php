<?php

namespace App\Services;

use App\Logging\Logger;
use App\Database\Database;
use Illuminate\Database\Capsule\Manager as DB;

class AccountCleanupService
{
    /**
     * Delete unconfirmed accounts older than specified days
     *
     * @param int $daysOld Number of days old accounts should be to be deleted
     * @return array Statistics about the cleanup operation
     */
    public static function cleanupUnconfirmedAccounts(string $mode, int $daysOld = 3): array
    {
        ZatcaModeHeaderService::mapMode($mode);
        Database::get($mode);

        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

        // Get accounts to be deleted for logging
        $accountsToDelete = DB::table('users')
            ->where('is_confirmed', 0)
            ->where('created_at', '<', $cutoffDate)
            ->select(['id', 'email', 'created_at'])
            ->get();

        $deletedCount = 0;
        $deletedAccounts = [];

        foreach ($accountsToDelete as $account) {
            try {
                // Delete the account
                $deleted = DB::table('users')
                    ->where('id', $account->id)
                    ->delete();

                if ($deleted) {
                    $deletedCount++;
                    $deletedAccounts[] = [
                        'id' => $account->id,
                        'email' => $account->email,
                        'created_at' => $account->created_at
                    ];
                }
            } catch (\Exception $e) {
            }
        }

        $stats = [
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate,
            'days_old' => $daysOld,
            'deleted_accounts' => $deletedAccounts
        ];

        return $stats;
    }

    /**
     * Get statistics about unconfirmed accounts
     *
     * @return array Statistics about unconfirmed accounts
     */
    public static function getUnconfirmedAccountStats(): array
    {
        $totalUnconfirmed = DB::table('users')
            ->where('is_confirmed', 0)
            ->count();

        $oldUnconfirmed = DB::table('users')
            ->where('is_confirmed', 0)
            ->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-3 days')))
            ->count();

        $veryOldUnconfirmed = DB::table('users')
            ->where('is_confirmed', 0)
            ->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->count();

        return [
            'total_unconfirmed' => $totalUnconfirmed,
            'old_unconfirmed_3_days' => $oldUnconfirmed,
            'very_old_unconfirmed_7_days' => $veryOldUnconfirmed,
            'cleanup_threshold_days' => 3
        ];
    }
}
