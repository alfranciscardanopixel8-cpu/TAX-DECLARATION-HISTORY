<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $kind = $this->input('property_kind', 'Land');
        $isLand = $kind === 'Land';

        return [
            'pin' => ['required', 'string', 'max:80', 'unique:properties,pin'],
            'property_kind' => ['nullable', 'string', 'in:Land,Building,Machinery'],
            'property_index_number' => ['nullable', 'string', 'max:80'],
            'lot_number' => [$isLand ? 'required' : 'nullable', 'string', 'max:80'],
            'survey_number' => ['nullable', 'string', 'max:80'],
            'title_number' => ['nullable', 'string', 'max:120'],
            'land_pin_reference' => ['nullable', 'string', 'max:80'],
            'barangay' => ['required', 'string', 'max:120'],
            'municipality' => ['required', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'classification' => ['required', 'string', 'max:80'],
            'actual_use' => ['nullable', 'string', 'max:80'],
            'land_area' => ['nullable', 'numeric', 'min:0'],
            'unit_of_measure' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:40'],
            'remarks' => ['nullable', 'string'],
            'extra' => ['nullable', 'array'],
            'owner.name' => ['required', 'string', 'max:180'],
            'owner.address' => ['nullable', 'string'],
            'tax_declaration.td_number' => ['required', 'string', 'max:100'],
            'tax_declaration.arp_number' => ['nullable', 'string', 'max:100'],
            'tax_declaration.effectivity_year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'tax_declaration.market_value' => ['nullable', 'numeric', 'min:0'],
            'tax_declaration.assessed_value' => ['nullable', 'numeric', 'min:0'],
            'tax_declaration.assessment_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_declaration.status' => ['nullable', 'string', 'max:40'],
            'tax_declaration.transaction_type' => ['nullable', 'string', 'max:80'],
            'tax_declaration.actual_use' => ['nullable', 'string', 'max:80'],
            'tax_declaration.memoranda' => ['nullable', 'string'],
            'assessment.assessment_type' => ['nullable', 'string', 'max:80'],
            'assessment.area' => ['nullable', 'numeric', 'min:0'],
            'assessment.unit_of_measure' => ['nullable', 'string', 'max:40'],
            'assessment.unit_value' => ['nullable', 'numeric', 'min:0'],
            'assessment.base_market_value' => ['nullable', 'numeric', 'min:0'],
            'assessment.adjustment' => ['nullable', 'numeric'],
            'assessment.depreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'assessment.taxable' => ['nullable', 'boolean'],
            'assessment.notes' => ['nullable', 'string'],
        ];
    }
}
