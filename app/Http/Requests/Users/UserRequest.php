<?php

namespace App\Http\Requests\Users;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');
        $creating = $this->isMethod('post');

        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:120'],
            'email' => [
                $creating ? 'required' : 'sometimes',
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => [$creating ? 'required' : 'sometimes', Password::min(8)],
            'role_id' => [
                $creating ? 'required' : 'sometimes',
                'integer',
                Rule::exists('roles', 'id')->where('tenant_id', app(TenantContext::class)->id()),
            ],
        ];
    }
}
