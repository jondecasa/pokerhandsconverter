<?php

namespace App\Console\Commands;

use App\Support\Telegram;
use Illuminate\Console\Command;

class TelegramTest extends Command
{
    protected $signature = 'telegram:test';

    protected $description = 'Send a test message through the Telegram bot, or list the chat ids that have written to it';

    public function handle(Telegram $telegram): int
    {
        if ($telegram->token() === '') {
            $this->error('Set TELEGRAM_BOT_TOKEN in .env first.');

            return self::FAILURE;
        }

        if ($telegram->chatId() === '') {
            $chats = $telegram->recentChats();

            if ($chats === []) {
                $this->warn('TELEGRAM_CHAT_ID is empty and the bot has no messages yet: open the bot in Telegram, press Start (or write to it) and run this again.');

                return self::FAILURE;
            }

            $this->info('Put one of these chat ids in TELEGRAM_CHAT_ID:');
            foreach ($chats as $id => $label) {
                $this->line("  {$id}  ({$label})");
            }

            return self::SUCCESS;
        }

        if (! $telegram->send('✅ Telegram está conectado: avisaré aquí de cada nueva suscripción.')) {
            $this->error('Telegram did not accept the message (see storage/logs/laravel.log).');

            return self::FAILURE;
        }

        $this->info('Test message sent.');

        return self::SUCCESS;
    }
}
