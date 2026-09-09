<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetInstalledSoftware extends Model
{
    protected $table = 'asset_installed_softwares';

    protected $fillable = [
        'asset_id',
        'name',
        'vendor',
        'version',
        'software_license_id',
        'installed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'installed_at' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(SoftwareLicense::class, 'software_license_id');
    }
}
