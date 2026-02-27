<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class RestoreBackup extends Command
{
    protected $signature = 'backup:restore {file : Nama file backup zip}';

    protected $description = 'Restore database dari file backup';

    public function handle(): int
    {
        $filename = $this->argument('file');
        $disk = Storage::disk('backups');

        // Find the file in Laravel subdirectory
        $path = 'Laravel/'.$filename;

        if (! $disk->exists($path)) {
            $this->error("File backup tidak ditemukan: {$filename}");

            return self::FAILURE;
        }

        $this->warn('⚠️  PERINGATAN: Restore akan MENGGANTI data saat ini dengan data dari backup!');
        $this->warn('Pastikan Anda sudah membuat backup terlebih dahulu.');

        if (! $this->confirm('Apakah Anda yakin ingin melanjutkan?')) {
            $this->info('Restore dibatalkan.');

            return self::SUCCESS;
        }

        $this->info('Memulai restore...');

        try {
            // Get the full path
            $fullPath = Storage::disk('backups')->path($path);
            $extractPath = storage_path('app/backup-temp/restore-'.time());

            // Extract zip
            $zip = new \ZipArchive;
            if ($zip->open($fullPath) === true) {
                $zip->extractTo($extractPath);
                $zip->close();
            } else {
                $this->error('Gagal membuka file backup.');

                return self::FAILURE;
            }

            // Find SQL file
            $sqlFiles = glob($extractPath.'/**/*.sql');
            if (empty($sqlFiles)) {
                $this->error('File SQL tidak ditemukan dalam backup.');

                return self::FAILURE;
            }

            $sqlFile = $sqlFiles[0];
            $this->info('File SQL ditemukan: '.basename($sqlFile));

            // Get database config
            $dbConfig = config('database.connections.'.config('database.default'));

            // Confirm database
            $this->warn("Database target: {$dbConfig['database']}");
            if (! $this->confirm('Restore ke database ini?')) {
                $this->info('Restore dibatalkan.');

                return self::SUCCESS;
            }

            // Restore based on driver
            if ($dbConfig['driver'] === 'mysql') {
                $command = [
                    'mysql',
                    '-h', $dbConfig['host'],
                    '-P', $dbConfig['port'] ?? '3306',
                    '-u', $dbConfig['username'],
                    '-p'.$dbConfig['password'],
                    $dbConfig['database'],
                ];
            } elseif ($dbConfig['driver'] === 'pgsql') {
                $command = [
                    'psql',
                    '-h', $dbConfig['host'],
                    '-p', $dbConfig['port'] ?? '5432',
                    '-U', $dbConfig['username'],
                    '-d', $dbConfig['database'],
                ];
                putenv('PGPASSWORD='.$dbConfig['password']);
            } elseif ($dbConfig['driver'] === 'sqlite') {
                $command = [
                    'sqlite3',
                    $dbConfig['database'],
                ];
            } else {
                $this->error("Driver database {$dbConfig['driver']} belum didukung.");

                return self::FAILURE;
            }

            $process = new Process($command);
            $process->setInput(file_get_contents($sqlFile));
            $process->run();

            // Cleanup
            array_map('unlink', glob($extractPath.'/**/*'));
            rmdir($extractPath.'/db-dumps');
            rmdir($extractPath);

            if ($process->isSuccessful()) {
                $this->info('✅ Restore berhasil!');
                $this->info('Silakan clear cache dengan: php artisan cache:clear');

                return self::SUCCESS;
            } else {
                $this->error('Restore gagal: '.$process->getErrorOutput());

                return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
