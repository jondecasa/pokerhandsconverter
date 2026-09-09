<?php

namespace App\Console\Commands;

use App\Poker\CoinPokerConverter;
use App\Poker\ConverterOptions;
use Illuminate\Console\Command;

class ConvertHandHistory extends Command
{
    protected $signature = 'pokerhh:convert
        {input : Path to the CoinPoker hand-history .txt file}
        {output? : Where to write the PokerStars-formatted file (defaults to <input>-pokerstars.txt)}
        {--timezone-mode= : dual|et|keep (overrides config)}
        {--force : Overwrite the output file if it already exists}';

    protected $description = 'Convert a CoinPoker hand-history file to PokerStars format';

    public function handle(): int
    {
        $input = $this->argument('input');

        if (! is_file($input)) {
            $this->error("Input file not found: {$input}");

            return self::FAILURE;
        }

        $output = $this->argument('output')
            ?? preg_replace('/\.txt$/i', '', $input).'-pokerstars.txt';

        if (is_file($output) && ! $this->option('force')) {
            $this->error("Output file already exists (use --force): {$output}");

            return self::FAILURE;
        }

        $options = ConverterOptions::fromConfig(array_filter([
            'timezone_mode' => $this->option('timezone-mode'),
        ]));

        $result = (new CoinPokerConverter($options))->convert((string) file_get_contents($input));

        if ($result->handCount === 0) {
            $this->error('No hands could be parsed from that file.');

            return self::FAILURE;
        }

        file_put_contents($output, $result->output);

        $this->info("Converted {$result->handCount} hand(s) -> {$output}");
        foreach ($result->formatBreakdown() as $format => $count) {
            $this->line("  {$format}: {$count}");
        }

        if ($result->hasWarnings()) {
            $this->warn(count($result->warnings).' warning(s):');
            foreach ($result->warnings as $warning) {
                $hand = $warning['hand'] !== null ? "#{$warning['hand']} " : '';
                $this->line("  - {$hand}{$warning['message']}");
            }
        }

        return self::SUCCESS;
    }
}
