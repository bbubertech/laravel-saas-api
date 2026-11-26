<?php

namespace App\Http\Requests\Customers;

use App\Models\Customer;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Customer|null $customer */
        $customer = $this->route('customer');
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:160'],
            'email' => [
                'nullable',
                'email',
                'max:190',
                Rule::unique('customers', 'email')
                    ->where('tenant_id', app(TenantContext::class)->id())
                    ->ignore($customer?->id),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'company' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
