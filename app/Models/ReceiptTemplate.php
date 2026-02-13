<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'template_data',
        'is_default',
        'is_active',
        'store_id',
    ];

    protected $casts = [
        'template_data' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the store that owns the receipt template.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the default template.
     */
    public static function getDefaultTemplate(): ?self
    {
        return static::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get active templates for a store.
     */
    public static function getActiveTemplates(?int $storeId = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->when($storeId, function ($query, $storeId) {
                $query->where(function ($q) use ($storeId) {
                    $q->where('store_id', $storeId)
                        ->orWhereNull('store_id');
                });
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Validate template data.
     */
    public function validateTemplateData(): bool
    {
        $required = ['header', 'body', 'footer'];
        $templateData = $this->template_data;

        foreach ($required as $section) {
            if (! isset($templateData[$section])) {
                return false;
            }
        }

        return true;
    }
}
