<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'file_name',
        'total_records',
        'successful_records',
        'failed_records',
        'status',
    ];

    protected $casts = [
        'total_records' => 'integer',
        'successful_records' => 'integer',
        'failed_records' => 'integer',
        'status' => ImportStatus::class,
        'created_at' => 'datetime',
    ];

    /**
     * @return HasMany<ImportLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }
}
