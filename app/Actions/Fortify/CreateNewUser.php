<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Services\OrderNotificationService;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(
        protected OrderNotificationService $notifications,
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $profile = filled($input['first_name'] ?? null)
            ? [
                'first_name' => $input['first_name'],
                'middle_name' => $input['middle_name'] ?? null,
                'last_name' => $input['last_name'] ?? '',
            ]
            : ['name' => $input['name']];

        $user = User::create([
            ...$profile,
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        $this->notifications->queueUserEmail($user, 'welcome_customer');

        return $user;
    }
}
