<x-guest-layout>
    <x-slot name="heading">Create your PokerHandsConverter account</x-slot>

    @include('auth.partials.google-button')

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="coinpoker_id" :value="__('CoinPoker ID')" />
            <x-text-input id="coinpoker_id" class="block mt-1 w-full" type="text" name="coinpoker_id" :value="old('coinpoker_id')" autocomplete="off" placeholder="Optional — your CoinPoker screen name" />
            <p class="mt-1 text-xs text-slate-400">Used to replace "Hero" in your converted files. You can add or change it later in your profile.</p>
            <x-input-error :messages="$errors->get('coinpoker_id')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center">
                {{ __('Create account') }}
            </x-primary-button>
        </div>

        <p class="mt-4 text-center text-sm text-slate-500">
            Already registered?
            <a class="font-medium text-indigo-600 hover:text-indigo-500" href="{{ route('login') }}">Log in</a>
        </p>
    </form>
</x-guest-layout>
