<?php

namespace App\Console\Commands;

use App\Support\IndexNow;
use App\Support\PublicUrls;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class SubmitIndexNow extends Command
{
    protected $signature = 'indexnow:submit';

    protected $description = 'Notify IndexNow search engines of every public URL (run on the server, once after enabling IndexNow)';

    public function handle(IndexNow $indexNow): int
    {
        if (! $indexNow->enabled()) {
            $this->error('IndexNow is off: it needs APP_ENV=production and INDEXNOW_KEY set in .env.');

            return self::FAILURE;
        }

        try {
            $served = trim(Http::timeout(10)->get($indexNow->keyUrl())->body());
        } catch (Throwable $e) {
            $served = '';
        }

        if ($served !== $indexNow->key()) {
            $this->error("The key file isn't being served at {$indexNow->keyUrl()} — search engines would reject the submission.");

            return self::FAILURE;
        }

        $urls = array_column(PublicUrls::all(), 'loc');

        if (! $indexNow->submit($urls)) {
            $this->error('IndexNow did not accept the submission (see storage/logs/laravel.log).');

            return self::FAILURE;
        }

        $this->info(count($urls).' URLs submitted to IndexNow.');

        return self::SUCCESS;
    }
}
