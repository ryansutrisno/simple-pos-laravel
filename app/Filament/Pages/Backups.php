<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class Backups extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.backups';

    public static function getNavigationLabel(): string
    {
        return 'Backup & Restore';
    }

    public function getHeading(): string
    {
        return 'Backup & Restore';
    }

    public function getBackups(): array
    {
        try {
            $disk = Storage::disk('backups');
            $files = $disk->allFiles();

            return collect($files)
                ->filter(fn ($file) => str_ends_with($file, '.zip'))
                ->map(function ($file) use ($disk) {
                    return [
                        'id' => md5($file),
                        'name' => basename($file),
                        'path' => $file,
                        'size' => $disk->size($file),
                        'created_at' => Carbon::createFromTimestamp($disk->lastModified($file)),
                    ];
                })
                ->sortByDesc('created_at')
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    public function deleteBackup(string $path): void
    {
        try {
            Storage::disk('backups')->delete($path);
            Notification::make()
                ->title('Backup Dihapus')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Menghapus')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function createBackup(): void
    {
        try {
            Artisan::call('backup:run', ['--only-db' => true]);
            Notification::make()
                ->title('Backup Berhasil')
                ->body('Database berhasil di-backup.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Backup Gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function createFullBackup(): void
    {
        try {
            Artisan::call('backup:run');
            Notification::make()
                ->title('Backup Lengkap Berhasil')
                ->body('Database dan files berhasil di-backup.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Backup Gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cleanup(): void
    {
        try {
            Artisan::call('backup:clean');
            Notification::make()
                ->title('Pembersihan Berhasil')
                ->body('Backup lama telah dibersihkan.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Pembersihan Gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function restore(string $name): void
    {
        try {
            $exitCode = Artisan::call('backup:restore', [
                'file' => $name,
            ]);

            if ($exitCode === 0) {
                Notification::make()
                    ->title('Restore Berhasil')
                    ->body('Database berhasil direstore. Silakan clear cache jika diperlukan.')
                    ->success()
                    ->send();
            } else {
                $output = Artisan::output();
                Notification::make()
                    ->title('Restore Gagal')
                    ->body('Error: '.$output)
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Restore Gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $files = Storage::disk('backups')->allFiles();
            $count = count(array_filter($files, fn ($file) => str_ends_with($file, '.zip')));

            return $count > 0 ? (string) $count : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
