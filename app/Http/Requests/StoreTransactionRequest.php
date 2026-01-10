<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'minutes' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:500'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'wallet_id.required' => __('validation.transaction.wallet_required'),
            'wallet_id.exists' => __('validation.transaction.wallet_not_found'),
            'minutes.required' => __('validation.transaction.minutes_required'),
            'minutes.min' => __('validation.transaction.minutes_min'),
            'description.max' => __('validation.transaction.description_max'),
            'internal_note.max' => __('validation.transaction.internal_note_max'),
            'occurred_at.date' => __('validation.transaction.occurred_at_invalid'),
        ];
    }
}
