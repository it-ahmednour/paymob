<?php

namespace App\Http\Requests\Manager;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{

    public function attributes()
    {
        return [
            'plan_id'       => 'الخطة',
            'payment_type'  => 'وسيلة الدفع',
            'wallet_number' => 'رقم المحفظة',
        ];
    }

    public function rules()
    {
        return [
            'plan_id'        => ['bail','required_if:action,RENEW,UPGRADE','integer', Rule::exists('plans','id')],
            'payment_type'   => ['bail','required','string','in:WALLET,CREDIT'],
            'wallet_number'  => ['bail','sometimes','required_if:payment_type,WALLET','digits:11'],
        ];
    }

    public function validated()
    {
        return parent::validated();
    }
}
