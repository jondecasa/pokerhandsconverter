<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RangeStudyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $studyId = $this->route('rangeStudy')?->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('range_studies', 'slug')->ignore($studyId)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'row_colors' => ['nullable', 'array'],
            'row_colors.*' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public function studyData(): array
    {
        $data = $this->safe()->except('row_colors');
        $data['sort_order'] ??= 0;
        $data['row_colors'] = array_filter((array) $this->input('row_colors', []), fn ($v) => filled($v));

        return $data;
    }
}
