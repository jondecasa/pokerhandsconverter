<?php

namespace App\Models;

use Database\Factories\ConversionFailureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionFailure extends Model
{
    /** @use HasFactory<ConversionFailureFactory> */
    use HasFactory;

    /** Why an upload did not become a conversion: code => label shown in the admin log. */
    public const REASONS = [
        'upload' => 'Upload rejected',
        'validation' => 'Invalid options',
        'empty' => 'Empty file',
        'binary' => 'Binary / wrong encoding',
        'not_coinpoker' => 'Not a CoinPoker file',
        'no_hands' => 'No hands parsed',
        'stakes_cap' => 'Stakes cap',
        'exception' => 'Server error',
    ];

    protected $fillable = [
        'user_id',
        'original_filename',
        'file_size',
        'reason',
        'message',
        'details',
        'ip',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    /** "2 KB", "1.4 MB"… (no intl needed); null size means the upload never produced a measurable file. */
    public function sizeLabel(): string
    {
        $bytes = $this->file_size;
        if ($bytes === null) {
            return 'size unknown';
        }
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / 1024, 1), '0'), '.').' KB';
        }

        return rtrim(rtrim(number_format($bytes / 1024 / 1024, 1), '0'), '.').' MB';
    }

    /** Tailwind classes for the reason badge: server errors stand out, user-side rejections are calmer. */
    public function reasonClasses(): string
    {
        return match ($this->reason) {
            'exception' => 'bg-red-100 text-red-700',
            'upload', 'binary' => 'bg-amber-100 text-amber-700',
            'stakes_cap' => 'bg-blue-100 text-blue-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }
}
