<?php

use App\Models\ReceiptTemplate;
use App\Models\Store;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can create receipt template', function () {
    $templateData = [
        'name' => 'Standard Template',
        'description' => 'Template standar untuk struk',
        'template_data' => [
            'header' => ['show_logo' => true],
            'body' => ['show_item_details' => true],
            'footer' => ['show_thank_you' => true],
        ],
        'is_default' => false,
        'is_active' => true,
    ];

    ReceiptTemplate::create($templateData);

    $this->assertDatabaseHas('receipt_templates', [
        'name' => 'Standard Template',
    ]);
});

it('can validate template data', function () {
    $validTemplate = ReceiptTemplate::factory()->create([
        'template_data' => [
            'header' => [],
            'body' => [],
            'footer' => [],
        ],
    ]);

    expect($validTemplate->validateTemplateData())->toBeTrue();
});

it('fails validation for incomplete template data', function () {
    $invalidTemplate = ReceiptTemplate::factory()->create([
        'template_data' => [
            'header' => [],
        ],
    ]);

    expect($invalidTemplate->validateTemplateData())->toBeFalse();
});

it('can get default template', function () {
    ReceiptTemplate::factory()->create(['is_default' => false, 'is_active' => true]);
    $defaultTemplate = ReceiptTemplate::factory()->default()->create(['is_active' => true]);

    $found = ReceiptTemplate::getDefaultTemplate();

    expect($found->id)->toBe($defaultTemplate->id);
});

it('can get active templates', function () {
    ReceiptTemplate::factory()->count(2)->create(['is_active' => true]);
    ReceiptTemplate::factory()->inactive()->create();

    $activeTemplates = ReceiptTemplate::getActiveTemplates();

    expect($activeTemplates)->toHaveCount(2);
});

it('can get templates for specific store', function () {
    $store = Store::factory()->create();

    ReceiptTemplate::factory()->create(['store_id' => null, 'is_active' => true]);
    ReceiptTemplate::factory()->forStore($store)->create(['is_active' => true]);

    $templates = ReceiptTemplate::getActiveTemplates($store->id);

    expect($templates)->toHaveCount(2);
});

it('receipt template belongs to store', function () {
    $store = Store::factory()->create();
    $template = ReceiptTemplate::factory()->forStore($store)->create();

    expect($template->store->id)->toBe($store->id);
});

it('can update receipt template', function () {
    $template = ReceiptTemplate::factory()->create();

    $template->update([
        'name' => 'Updated Template',
        'description' => 'Updated description',
    ]);

    expect($template->name)->toBe('Updated Template');
});

it('can delete non-default template', function () {
    $template = ReceiptTemplate::factory()->create(['is_default' => false]);

    $template->delete();

    $this->assertDatabaseMissing('receipt_templates', ['id' => $template->id]);
});
