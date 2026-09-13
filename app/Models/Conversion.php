<?php

namespace App\Models;

use Database\Factories\ConversionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Conversion extends Model
{
    /** @use HasFactory<ConversionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_filename',
        'output_path',
        'hand_count',
        'splash_pots',
        'bomb_pots',
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

    protected static function booted(): void
    {
        // The URL uses this, not the auto-increment id, so a conversion can't
        // be found by guessing/incrementing — only by knowing the link.
        static::creating(function (Conversion $conversion): void {
            $conversion->public_id ??= self::generateUniquePublicId();
        });

        // Deleting the record deletes everything tied to it: the stored
        // converted file goes too, whatever triggered the delete.
        static::deleting(function (Conversion $conversion): void {
            if ($conversion->output_path) {
                Storage::disk('local')->delete($conversion->output_path);
            }
        });
    }

    private static function generateUniquePublicId(): string
    {
        do {
            $id = Str::random(26);
        } while (self::where('public_id', $id)->exists());

        return $id;
    }

    /** Route-model binding uses the opaque public_id, never the numeric id. */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function downloadName(): string
    {
        $base = pathinfo($this->original_filename, PATHINFO_FILENAME) ?: 'hand-history';

        return $base.'-pokertracker.txt';
    }

    public function outputExists(): bool
    {
        return Storage::disk('local')->exists($this->output_path);
    }
}
