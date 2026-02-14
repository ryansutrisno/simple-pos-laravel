<?php

use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can assign role to user', function () {
    $role = Role::firstOrCreate(['name' => 'test_role', 'guard_name' => 'web']);

    $this->user->assignRole($role);

    expect($this->user->hasRole('test_role'))->toBeTrue();
});

it('can assign permission directly to user', function () {
    $permission = Permission::firstOrCreate(['name' => 'test_permission', 'guard_name' => 'web']);

    $this->user->givePermissionTo($permission);

    expect($this->user->hasPermissionTo('test_permission'))->toBeTrue();
});

it('can check user permission via role', function () {
    $permission = Permission::firstOrCreate(['name' => 'view_product', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'test_manager', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $this->user->assignRole($role);

    expect($this->user->hasPermissionTo('view_product'))->toBeTrue();
});

it('shield seeder creates required roles and permissions', function () {
    $seeder = new ShieldSeeder;
    $seeder->run();

    $roles = ['super_admin', 'admin', 'manager', 'kasir', 'panel_user'];

    foreach ($roles as $roleName) {
        expect(Role::where('name', $roleName)->exists())->toBeTrue();
    }

    expect(Permission::count())->toBeGreaterThan(0);
});

it('super admin role exists and has permissions', function () {
    $seeder = new ShieldSeeder;
    $seeder->run();

    $superAdminRole = Role::where('name', 'super_admin')->first();

    expect($superAdminRole)->not->toBeNull();
    expect($superAdminRole->permissions->count())->toBeGreaterThan(0);
});

it('admin role has full permissions', function () {
    $seeder = new ShieldSeeder;
    $seeder->run();

    $adminRole = Role::where('name', 'admin')->first();

    expect($adminRole)->not->toBeNull();
    expect($adminRole->permissions->count())->toBeGreaterThan(0);
});

it('manager role has limited permissions', function () {
    $seeder = new ShieldSeeder;
    $seeder->run();

    $managerRole = Role::where('name', 'manager')->first();

    expect($managerRole)->not->toBeNull();
    expect($managerRole->hasPermissionTo('view_product'))->toBeTrue();
    expect($managerRole->hasDirectPermission('view_role'))->toBeFalse();
});

it('kasir role has minimal permissions', function () {
    $seeder = new ShieldSeeder;
    $seeder->run();

    $kasirRole = Role::where('name', 'kasir')->first();

    expect($kasirRole)->not->toBeNull();
    expect($kasirRole->hasPermissionTo('view_product'))->toBeTrue();
    expect($kasirRole->hasDirectPermission('create_product'))->toBeFalse();
    expect($kasirRole->hasDirectPermission('delete_product'))->toBeFalse();
});

it('user can be assigned role and have permissions', function () {
    $seeder = new ShieldSeeder;
    $seeder->run();

    $role = Role::where('name', 'kasir')->first();
    $this->user->assignRole($role);

    expect($this->user->roles)->toHaveCount(1);
    expect($this->user->roles->first()->name)->toBe('kasir');
});

it('can sync roles to user', function () {
    $seeder = new ShieldSeeder;
    $seeder->run();

    $kasirRole = Role::where('name', 'kasir')->first();
    $managerRole = Role::where('name', 'manager')->first();

    $this->user->syncRoles([$kasirRole]);
    expect($this->user->hasRole('kasir'))->toBeTrue();

    $this->user->syncRoles([$managerRole]);
    expect($this->user->hasRole('manager'))->toBeTrue();
    expect($this->user->hasRole('kasir'))->toBeFalse();
});

it('can remove role from user', function () {
    $seeder = new ShieldSeeder;
    $seeder->run();

    $role = Role::where('name', 'kasir')->first();
    $this->user->assignRole($role);

    expect($this->user->hasRole('kasir'))->toBeTrue();

    $this->user->removeRole($role);

    expect($this->user->hasRole('kasir'))->toBeFalse();
});
