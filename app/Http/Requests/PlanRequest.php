<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $planId = $this->route('plan')?->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('plans', 'slug')->ignore($planId)],
            'description' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'currency' => ['required', 'string', 'size:3'],
            'interval' => ['required', Rule::in(['month', 'year'])],
            // Required for paid packages; a $0 package may be saved without it.
            'stripe_price_id' => [
                Rule::requiredIf(fn () => (float) $this->input('price') > 0),
                'nullable', 'string', 'max:255',
            ],
            'stakes_cap' => ['nullable', Rule::in(config('pokerhandsconverter.stakes'))],
            'stakes_label' => ['nullable', 'string', 'max:120'],
            'features' => ['nullable', 'string'], // textarea, one feature per line
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_visible' => ['boolean'],
            'is_active' => ['boolean'],
            'is_highlighted' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_visible' => $this->boolean('is_visible'),
            'is_active' => $this->boolean('is_active'),
            'is_highlighted' => $this->boolean('is_highlighted'),
            'currency' => strtoupper((string) $this->input('currency', 'USD')),
        ]);
    }

    /** Data ready for Plan::create()/update(). */
    public function planData(): array
    {
        $data = $this->safe()->except('features');
        $data['features'] = collect(preg_split('/\r\n|\r|\n/', (string) $this->input('features')))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->values()
            ->all();
        $data['sort_order'] ??= 0;

        return $data;
    }
}
