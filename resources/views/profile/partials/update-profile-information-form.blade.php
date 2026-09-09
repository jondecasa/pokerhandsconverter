<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information.") }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="coinpoker_id" :value="__('CoinPoker ID')" />
            <x-text-input id="coinpoker_id" name="coinpoker_id" type="text" class="mt-1 block w-full" :value="old('coinpoker_id', $user->coinpoker_id)" autocomplete="off" placeholder="Your CoinPoker screen name" />
            <p class="mt-1 text-sm text-gray-500">{{ __('Replaces "Hero" in every converted hand history. Leave blank to keep "Hero".') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('coinpoker_id')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" class="mt-1 block w-full bg-gray-100 text-gray-500 cursor-not-allowed" :value="$user->email" disabled readonly />
            <p class="mt-1 text-sm text-gray-500">{{ __('Your email is set at registration and cannot be changed here.') }}</p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
