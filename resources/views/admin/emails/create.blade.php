<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Send email</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6"
             x-data="{
                html: '',
                loading: false,
                error: '',
                async preview() {
                    this.loading = true;
                    this.error = '';
                    try {
                        const form = this.$refs.form;
                        const response = await fetch(@js(route('admin.emails.preview')), {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: new FormData(form),
                        });
                        if (response.status === 422) {
                            const payload = await response.json();
                            this.html = '';
                            this.error = Object.values(payload.errors).flat().join(' ');
                        } else if (! response.ok) {
                            this.error = 'The preview could not be generated.';
                        } else {
                            this.html = await response.text();
                        }
                    } catch (e) {
                        this.error = 'The preview could not be generated.';
                    } finally {
                        this.loading = false;
                    }
                },
             }">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">{{ session('status') }}</div>
            @endif

            <div class="pc-card p-6">
                <p class="text-sm text-gray-500">Write a personal email to a user. It is sent in the PokerHandsConverter template, and replies go to {{ config('pokerhandsconverter.contact_email') }}.</p>

                <form x-ref="form" method="POST" action="{{ route('admin.emails.store') }}" class="mt-5 space-y-5">
                    @csrf

                    <div>
                        <label for="to" class="block text-sm font-medium text-gray-700">To</label>
                        <input id="to" name="to" type="email" value="{{ old('to', $to) }}" required autocomplete="off" placeholder="user@example.com"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <x-input-error :messages="$errors->get('to')" class="mt-1" />
                    </div>

                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-gray-400">(email subject and heading)</span></label>
                        <input id="title" name="title" value="{{ old('title') }}" required maxlength="150"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <x-input-error :messages="$errors->get('title')" class="mt-1" />
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700">Message <span class="text-gray-400">(a blank line starts a new paragraph; links are made clickable)</span></label>
                        <textarea id="message" name="message" rows="12" required
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('message') }}</textarea>
                        <x-input-error :messages="$errors->get('message')" class="mt-1" />
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="button" value="0">
                        <input type="checkbox" name="button" value="1" @checked(old('button', '1') == '1')
                               class="rounded border-gray-300 text-indigo-600">
                        Add a <span class="font-medium">See the plans</span> button
                    </label>

                    <div class="flex flex-wrap items-center gap-3 pt-1">
                        <button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500"
                                onclick="return confirm('Send this email to ' + document.getElementById('to').value + '?')">
                            Send email
                        </button>
                        <button type="button" @click="preview()" :disabled="loading" :class="loading ? 'opacity-50' : ''"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md">
                            <span x-text="loading ? 'Loading…' : 'Preview'"></span>
                        </button>
                    </div>
                </form>
            </div>

            <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600"></p>

            <div x-show="html" x-cloak class="pc-card overflow-hidden">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-2 text-xs font-medium uppercase tracking-wide text-gray-500">Preview</div>
                <iframe sandbox="allow-same-origin" title="Email preview" :srcdoc="html" style="height:720px" class="block w-full bg-white"></iframe>
            </div>
        </div>
    </div>
</x-app-layout>
