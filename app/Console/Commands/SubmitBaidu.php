<?php

namespace App\Console\Commands;

use App\Support\BaiduPush;
use App\Support\Locales;
use App\Support\PublicUrls;
use Illuminate\Console\Command;

class SubmitBaidu extends Command
{
    protected $signature = 'baidu:submit {--all : Also send the default-language (English) URLs}';

    protected $description = 'Push the Chinese-language URLs to Baidu (run on the server, after the site is verified on ziyuan.baidu.com)';

    public function handle(BaiduPush $baidu): int
    {
        if (! $baidu->enabled()) {
            $this->error('Baidu push is off: it needs APP_ENV=production and BAIDU_PUSH_TOKEN set in .env.');

            return self::FAILURE;
        }

        $urls = collect(PublicUrls::all())
            ->filter(fn (array $url) => $this->option('all') || ! Locales::isDefault($url['locale']))
            ->pluck('loc')
            ->all();

        if ($urls === []) {
            $this->warn('There are no non-default-language URLs to push yet.');

            return self::SUCCESS;
        }

        $result = $baidu->submit($urls);

        if ($result === null) {
            $this->error('Baidu did not accept the push (see storage/logs/laravel.log).');

            return self::FAILURE;
        }

        $this->info("{$result['success']} of ".count($urls).' URLs accepted by Baidu'.($result['remain'] !== null ? " ({$result['remain']} left in today's quota)." : '.'));

        return self::SUCCESS;
    }
}
