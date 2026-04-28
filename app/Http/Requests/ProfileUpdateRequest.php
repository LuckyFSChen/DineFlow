<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $user = $this->user();

                    if (! $user instanceof User || ! in_array($user->role, User::EMAIL_LOGIN_ROLES, true)) {
                        return;
                    }

                    if (User::emailIsReservedForLogin((string) $value, $user->id)) {
                        $fail(__('validation.unique', ['attribute' => __('validation.attributes.email')]));
                    }
                },
            ],
        ];
    }
}
