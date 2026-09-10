@php($featuresText = old('features', implode("\n", $plan->featureList())))

@csrf

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-gray-700">Name</label>
        <input name="name" value="{{ old('name', $plan->name) }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Slug <span class="text-gray-400">(URL id, e.g. <code>mid-stakes</code>)</span></label>
        <input name="slug" value="{{ old('slug', $plan->slug) }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm font-mono">
        <x-input-error :messages="$errors->get('slug')" class="mt-1" />
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Description <span class="text-gray-400">(one line, shown on the card)</span></label>
        <input name="description" value="{{ old('description', $plan->description) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        <x-input-error :messages="$errors->get('description')" class="mt-1" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Price</label>
        <div class="mt-1 flex gap-2">
            <input name="price" type="number" step="0.01" min="0" value="{{ old('price', $plan->price) }}" required
                   class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
            <input name="currency" value="{{ old('currency', $plan->currency ?? 'USD') }}" maxlength="3" required
                   class="block w-20 rounded-md border-gray-300 shadow-sm text-sm uppercase">
        </div>
        <x-input-error :messages="$errors->get('price')" class="mt-1" />
        <x-input-error :messages="$errors->get('currency')" class="mt-1" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Billing interval</label>
        <select name="interval" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            @foreach (['month' => 'Monthly', 'year' => 'Yearly'] as $val => $lbl)
                <option value="{{ $val }}" @selected(old('interval', $plan->interval) === $val)>{{ $lbl }}</option>
            @endforeach
        </select>
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Stripe Price ID <span class="text-gray-400">(<code>price_...</code> — required for paid packages; leave blank for a $0 free package)</span></label>
        <input name="stripe_price_id" value="{{ old('stripe_price_id', $plan->stripe_price_id) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm font-mono">
        <x-input-error :messages="$errors->get('stripe_price_id')" class="mt-1" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Stake covered</label>
        <select name="stakes_cap" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            <option value="">— none / not stake-based —</option>
            @foreach ($stakes as $stake)
                <option value="{{ $stake }}" @selected(old('stakes_cap', $plan->stakes_cap) === $stake)>Up to {{ $stake }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('stakes_cap')" class="mt-1" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Stake label override <span class="text-gray-400">(optional)</span></label>
        <input name="stakes_label" value="{{ old('stakes_label', $plan->stakes_label) }}" placeholder="e.g. NL50 – NL500"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Features <span class="text-gray-400">(one per line — shown as bullets)</span></label>
        <textarea name="features" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ $featuresText }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Trial days <span class="text-gray-400">(blank = default {{ (int) config('pokerhandsconverter.trial_days') }})</span></label>
        <input name="trial_days" type="number" min="0" max="365" value="{{ old('trial_days', $plan->trial_days) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        <x-input-error :messages="$errors->get('trial_days')" class="mt-1" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Sort order <span class="text-gray-400">(lower shows first)</span></label>
        <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $plan->sort_order ?? 0) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
    </div>

    <div class="sm:col-span-2 flex flex-wrap gap-6 pt-2">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="is_visible" value="0">
            <input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $plan->is_visible ?? true))
                   class="rounded border-gray-300 text-indigo-600">
            Visible <span class="text-gray-400">— listed on the public pricing page</span>
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active ?? true))
                   class="rounded border-gray-300 text-indigo-600">
            Active <span class="text-gray-400">— can be subscribed to</span>
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="is_highlighted" value="0">
            <input type="checkbox" name="is_highlighted" value="1" @checked(old('is_highlighted', $plan->is_highlighted ?? false))
                   class="rounded border-gray-300 text-indigo-600">
            Highlighted <span class="text-gray-400">— "Best value" badge</span>
        </label>
    </div>
</div>

<div class="mt-8 flex items-center gap-3">
    <button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('admin.plans.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
</div>
