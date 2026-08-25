<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CbsResponseCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'response_id'   => ['required', 'string', 'max:100'],
            'status_id'     => ['required', 'integer', 'in:1006,1007'],
            'reference_id'  => ['required_without_all:txn_id', 'nullable', 'string', 'max:100'],
            'txn_id'        => ['required_without_all:reference_id', 'nullable', 'string', 'max:100'],
            'reason'        => ['nullable', 'string', 'max:255'],
            'confirmed_by'  => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'status_id.in' => 'The status_id must be either 1006 (SUCCESS) or 1007 (FAILED).',
            'reference_id.required_without_all' => 'Either reference_id or txn_id must be provided to identify the transaction.',
            'txn_id.required_without_all' => 'Either reference_id or txn_id must be provided to identify the transaction.',
        ];
    }
}
