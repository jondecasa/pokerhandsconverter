<?php

namespace App\Support;

use App\Models\ConversionFailure;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * What the server will really accept for an upload. The app allows config('pokerhandsconverter.max_upload_kb'),
 * but PHP's own upload_max_filesize / post_max_size can be lower (Plesk ships 8 MB), and PHP throws an oversized
 * request away before Laravel sees it.
 */
class UploadLimits
{
    /** Room kept for the multipart envelope and the form's other fields. */
    private const OVERHEAD_BYTES = 65536;

    public static function iniBytes(string $key): int
    {
        $bytes = ini_parse_quantity((string) ini_get($key));

        // 0 or -1 means "no limit" for post_max_size
        return $bytes > 0 ? $bytes : PHP_INT_MAX;
    }

    /** The biggest file (KB) that can get through: the app limit or the PHP limits, whichever is lowest. */
    public static function effectiveKb(): int
    {
        $bytes = min(
            (int) config('pokerhandsconverter.max_upload_kb') * 1024,
            self::iniBytes('upload_max_filesize'),
            self::iniBytes('post_max_size') - self::OVERHEAD_BYTES,
        );

        return max(1, intdiv($bytes, 1024));
    }

    /**
     * An oversized POST is rejected (413) before the session or the controller run, so nothing else would log it.
     * Never throws: logging must not make the rejection worse.
     */
    public static function recordOversizedPost(Request $request): void
    {
        if (! $request->isMethod('POST') || ! $request->is('convert')) {
            return;
        }

        try {
            $length = (int) $request->server('CONTENT_LENGTH');
            $limit = self::iniBytes('post_max_size');

            ConversionFailure::create([
                'user_id' => self::sessionUserId($request),
                'original_filename' => null,
                'file_size' => $length ?: null,
                'reason' => 'upload',
                'message' => 'That file is larger than this server accepts.',
                'details' => implode("\n", [
                    'Rejected before the app could read it: the request is '.number_format($length).' bytes ('.round($length / 1048576, 1).' MB) and PHP post_max_size is '
                        .ini_get('post_max_size').' ('.number_format($limit).' bytes).',
                    'PHP upload_max_filesize='.ini_get('upload_max_filesize').', memory_limit='.ini_get('memory_limit').', max_execution_time='.ini_get('max_execution_time'),
                    'App limit: '.config('pokerhandsconverter.max_upload_kb').' KB',
                    'Referer: '.($request->headers->get('referer') ?: 'n/a'),
                ]),
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Who was signed in, from the session cookie: the session middleware has not run yet at this point, so the
     * encrypted cookie is read by hand and looked up in the database sessions table.
     */
    public static function sessionUserId(Request $request): ?int
    {
        try {
            $cookie = $request->cookies->get(config('session.cookie'));
            if (! is_string($cookie) || $cookie === '') {
                return null;
            }

            $sessionId = CookieValuePrefix::remove(app('encrypter')->decrypt($cookie, false));
            $userId = DB::table(config('session.table', 'sessions'))->where('id', $sessionId)->value('user_id');

            return $userId ? (int) $userId : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
