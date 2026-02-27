<?php

use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('backups');
});

it('can run backup command', function () {
    $this->artisan('backup:run', ['--only-db' => true])
        ->assertSuccessful();
});

it('can run full backup command', function () {
    $this->artisan('backup:run')
        ->assertSuccessful();
});

it('can run cleanup command', function () {
    $this->artisan('backup:clean')
        ->assertSuccessful();
});

it('backup page is accessible', function () {
    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/backups');

    $response->assertOk();
});

it('backup file can be downloaded', function () {
    $user = \App\Models\User::factory()->create();

    // Create a fake backup file
    Storage::disk('backups')->put('Laravel/test-backup.zip', 'fake content');

    $response = $this->actingAs($user)->get('/admin/backups/download/test-backup.zip');

    $response->assertOk();
    $response->assertDownload('test-backup.zip');
});

it('shows backup list in admin panel', function () {
    $user = \App\Models\User::factory()->create();

    // Create fake backup files
    Storage::disk('backups')->put('Laravel/2026-02-27-05-00-00.zip', 'fake content 1');
    Storage::disk('backups')->put('Laravel/2026-02-27-06-00-00.zip', 'fake content 2');

    $response = $this->actingAs($user)->get('/admin/backups');

    $response->assertOk();
    // The page should load without errors
    $response->assertSee('Backup');
});
