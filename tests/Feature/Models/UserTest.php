<?php

use App\Models\User;
use Symfony\Component\Uid\Ulid;

it('generates an oidc subject when a user is created', function () {
    $user = User::factory()->create();

    expect($user->oidc_subject)
        ->toBeInstanceOf(Ulid::class)
        ->and($user->oidc_subject->toBase32())
        ->not->toBeEmpty();
});

it('generates a unique oidc subject for each user', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    expect($firstUser->oidc_subject)
        ->not->toBe($secondUser->oidc_subject);
});
