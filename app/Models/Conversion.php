<?php

namespace App\Models;

use Database\Factories\ConversionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Conversion extends Model
{
    /** @use HasFactory<ConversionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_filename',
        'output_path',
        'hand_count',
        'warning_count',
        'input_bytes',
        'format_breakdown',
        'warnings',
    ];

    protected function casts(): array
    {
        return [
            'format_breakdown' => 'array',
            'warnings' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function downloadName(): string
    {
        $base = pathinfo($this->original_filename, PATHINFO_FILENAME) ?: 'hand-history';

        return $base.'-pokerstars.txt';
    }

    public function outputExists(): bool
    {
        return Storage::disk('local')->exists($this->output_path);
    }
}
