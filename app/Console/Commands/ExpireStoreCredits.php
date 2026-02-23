<?php

namespace App\Console\Commands;

use App\Services\StoreCreditService;
use Illuminate\Console\Command;

class ExpireStoreCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store-credits:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and expire store credits that have passed their expiry date';

    /**
     * Execute the console command.
     */
    public function handle(StoreCreditService $storeCreditService): int
    {
        $this->info('Checking for expired store credits...');

        $expiredCount = $storeCreditService->checkAndExpireCredits();

        if ($expiredCount > 0) {
            $this->info("Successfully expired {$expiredCount} store credit(s).");
        } else {
            $this->info('No store credits to expire.');
        }

        return self::SUCCESS;
    }
}
