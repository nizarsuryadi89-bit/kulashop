<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can view manual book page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('manual-book.index'));

    $response->assertStatus(200);
    $response->assertSee('Buku Panduan Penggunaan');
    $response->assertSee('PRINSIP DASAR &amp; ARSITEKTUR SISTEM', false);
});

test('unauthenticated user is redirected to login', function () {
    $response = $this->get(route('manual-book.index'));

    $response->assertRedirect('/login');
});
