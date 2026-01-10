<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Wallet;
use App\Models\WalletPackage;
use Illuminate\Foundation\Http\FormRequest;

class InitiatePackagePurchaseRequest extends FormRequest
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
            'package_id' => ['required', 'integer', 'exists:wallet_packages,id'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('wallet_id') && $this->has('package_id')) {
                $this->validateWalletAllowsPurchases();
                $this->validatePackageIsActive();
                $this->validatePackageBelongsToWallet();
            }
        });
    }

    /**
     * Validate that the wallet allows client purchases.
     */
    protected function validateWalletAllowsPurchases(): void
    {
        $wallet = Wallet::find($this->input('wallet_id'));

        if ($wallet && !$wallet->allowsClientPurchases()) {
            $this->validator->errors()->add(
                'wallet_id',
                __('messages.package.purchase_disabled')
            );
        }
    }

    /**
     * Validate that the package is active.
     */
    protected function validatePackageIsActive(): void
    {
        $package = WalletPackage::find($this->input('package_id'));

        if ($package && !$package->is_active) {
            $this->validator->errors()->add(
                'package_id',
                __('messages.package.inactive')
            );
        }
    }

    /**
     * Validate that the package belongs to the selected wallet.
     */
    protected function validatePackageBelongsToWallet(): void
    {
        $package = WalletPackage::find($this->input('package_id'));

        if ($package && $package->wallet_id !== (int) $this->input('wallet_id')) {
            $this->validator->errors()->add(
                'package_id',
                __('validation.package.belongs_to_wallet')
            );
        }
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'wallet_id.required' => __('validation.package.wallet_required'),
            'wallet_id.exists' => __('validation.package.wallet_not_found'),
            'package_id.required' => __('validation.package.package_required'),
            'package_id.exists' => __('validation.package.package_not_found'),
            'message.max' => __('validation.package.message_max'),
        ];
    }
}
