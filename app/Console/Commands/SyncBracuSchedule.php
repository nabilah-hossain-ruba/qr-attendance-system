<?php

namespace App\Console\Commands;

use App\Services\BracuScheduleSync;
use Illuminate\Console\Command;

class SyncBracuSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bracu:sync-schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download course sections, class schedules and lecturers from BRACU Connect';

    /**
     * Execute the console command.
     */
    public function handle(BracuScheduleSync $sync): int
    {
        $this->info('Downloading BRACU schedule from ' . config('bracu.schedule_feed_url') . ' ...');

        try {
            $stats = $sync->sync();
        } catch (\Throwable $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->table(
            ['Created', 'Updated', 'With lecturer', 'Skipped'],
            [[$stats['created'], $stats['updated'], $stats['with_lecturer'], $stats['skipped']]]
        );

        return self::SUCCESS;
    }
}
