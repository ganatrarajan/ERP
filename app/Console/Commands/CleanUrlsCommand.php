<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\UrlHelper;

class CleanUrlsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean hardcoded IP and domain URLs in database tables to relative paths';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Cleaning stored URLs in database...');

        $tables = [
            'schools' => ['logo'],
            'students' => ['photo'],
            'users' => ['profile_photo'],
            'homeworks' => ['attachment'],
            'notices' => ['attachment'],
            'student_documents' => ['file_path'],
            'user_documents' => ['file_path'],
        ];

        foreach ($tables as $table => $columns) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!DB::getSchemaBuilder()->hasColumn($table, $column)) {
                    continue;
                }

                $records = DB::table($table)
                    ->whereNotNull($column)
                    ->where($column, '!=', '')
                    ->get(['id', $column]);

                $updatedCount = 0;
                foreach ($records as $record) {
                    $original = $record->$column;
                    $cleaned = UrlHelper::cleanRelativePath($original);

                    if ($cleaned !== $original) {
                        DB::table($table)
                            ->where('id', $record->id)
                            ->update([$column => $cleaned]);
                        $updatedCount++;
                    }
                }

                $this->info("Table `{$table}` column `{$column}`: updated {$updatedCount} records.");
            }
        }

        $this->info('URL sanitization complete!');
        return 0;
    }
}
