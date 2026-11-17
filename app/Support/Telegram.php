<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends the site owner a message through their own Telegram bot (token and chat id in .env).
 * Never throws: an alert that cannot be delivered must not break what triggered it.
 */
class Telegram
{
    public function token(): string
    {
        return (string) config('services.telegram.token');
    }

    public function chatId(): string
    {
        return (string) config('services.telegram.chat_id');
    }

    public function enabled(): bool
    {
        return $this->token() !== '' && $this->chatId() !== '';
    }

    /** @param  string  $html  Telegram HTML: escape user text with e() */
    public function send(string $html): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        try {
            $response = Http::timeout(5)->connectTimeout(3)->post($this->url('sendMessage'), [
                'chat_id' => $this->chatId(),
                'text' => $html,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
        } catch (Throwable $e) {
            Log::warning('Telegram message failed', ['error' => $e->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('Telegram rejected a message', ['status' => $response->status(), 'body' => $response->json('description')]);
        }

        return $response->successful();
    }

    /**
     * Chats that have written to the bot recently, to find the chat id to put in .env.
     *
     * @return array<string, string> chat id => label
     */
    public function recentChats(): array
    {
        if ($this->token() === '') {
            return [];
        }

        try {
            $updates = Http::timeout(10)->get($this->url('getUpdates'))->json('result', []);
        } catch (Throwable) {
            return [];
        }

        $chats = [];
        foreach ($updates as $update) {
            $chat = $update['message']['chat'] ?? $update['channel_post']['chat'] ?? null;
            if ($chat) {
                $chats[(string) $chat['id']] = $chat['title'] ?? (trim(($chat['first_name'] ?? '').' '.($chat['last_name'] ?? '')) ?: ($chat['username'] ?? '?'));
            }
        }

        return $chats;
    }

    private function url(string $method): string
    {
        return 'https://api.telegram.org/bot'.$this->token().'/'.$method;
    }
}
