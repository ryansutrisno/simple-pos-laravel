<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Store extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'printer_device_id',
        'logo_path',
        'receipt_header_message',
        'receipt_footer_message',
        'receipt_tagline',
        'show_cashier_name',
        'show_barcode',
        'show_qr_code',
        'receipt_template_id',
        'receipt_width',
    ];

    /**
     * Get the receipt templates for the store.
     */
    public function receiptTemplates(): HasMany
    {
        return $this->hasMany(ReceiptTemplate::class);
    }

    /**
     * Get the active receipt template.
     */
    public function activeReceiptTemplate(): BelongsTo
    {
        return $this->belongsTo(ReceiptTemplate::class, 'receipt_template_id');
    }

    /**
     * Get the logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }

    /**
     * Scope for active stores.
     */
    public function scopeActive($query)
    {
        return $query->where('id', '>', 0);
    }
}
