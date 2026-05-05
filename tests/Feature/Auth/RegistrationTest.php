<?php

use App\Models\User;

test('registration screen is not available', function () {
    $this->get('/register')->assertNotFound();
});

test('new users cannot self-register', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@mydemo.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
    expect(User::query()->where('email', 'test@mydemo.com')->exists())->toBeFalse();
});
