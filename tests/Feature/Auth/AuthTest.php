<?php

declare(strict_types=1);

use App\Models\User;

describe('Register', function () {

    it('creates a user and returns token', function () {
        $response = $this->postJson('/api/v1/register', [
            'name'                  => 'Harun',
            'email'                 => 'harun@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'user' => ['id', 'name', 'email'],
                     'token',
                 ]);

        $this->assertDatabaseHas('users', ['email' => 'harun@test.com']);
    });

    it('fails with duplicate email', function () {
        User::factory()->create(['email' => 'harun@test.com']);

        $this->postJson('/api/v1/register', [
            'name'                  => 'Harun',
            'email'                 => 'harun@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422);
    });

    it('fails with missing fields', function () {
        $this->postJson('/api/v1/register', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

});

describe('Login', function () {

    it('returns token with correct credentials', function () {
        User::factory()->create([
            'email'    => 'harun@test.com',
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/v1/login', [
            'email'    => 'harun@test.com',
            'password' => 'password123',
        ])->assertStatus(200)
          ->assertJsonStructure(['user', 'token']);
    });

    it('fails with wrong password', function () {
        User::factory()->create(['email' => 'harun@test.com']);

        $this->postJson('/api/v1/login', [
            'email'    => 'harun@test.com',
            'password' => 'wrong-password',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    });

});

describe('Logout', function () {

    it('deletes token on logout', function () {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
             ->postJson('/api/v1/logout')
             ->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    });

    it('returns 401 without token', function () {
        $this->postJson('/api/v1/logout')
             ->assertStatus(401);
    });

});
