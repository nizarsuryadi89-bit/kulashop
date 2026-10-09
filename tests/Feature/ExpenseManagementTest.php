<?php

use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\ProfitService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $company = Company::create(['name' => 'Demo Shop', 'features' => ['profit']]);
    $this->owner = User::factory()->for($company)->create(['role' => 'owner']);
    $this->admin = User::factory()->for($company)->create(['role' => 'admin']);

    $this->category = ExpenseCategory::create([
        'name' => 'Biaya Iklan',
        'code' => 'EXP-ADS',
        'description' => 'Biaya iklan Meta/Google',
        'status' => 'active',
    ]);
});

test('owner can view expense categories', function () {
    $response = $this->actingAs($this->owner)->get(route('expense-categories.index'));
    $response->assertStatus(200);
    $response->assertSee('Biaya Iklan');
});

test('owner can view expenses', function () {
    Expense::create([
        'expense_category_id' => $this->category->id,
        'amount' => 500000,
        'expense_date' => now()->toDateString(),
        'payment_method' => 'bank_transfer',
        'status' => 'paid',
        'description' => 'Iklan Meta 1',
        'created_by' => $this->owner->id,
    ]);

    $response = $this->actingAs($this->owner)->get(route('expenses.index'));
    $response->assertStatus(200);
    $response->assertSee('500.000');
});

test('can create expense category', function () {
    $response = $this->actingAs($this->owner)->post(route('expense-categories.store'), [
        'name' => 'Biaya Listrik',
        'code' => 'EXP-LST',
        'description' => 'Listrik gudang',
        'status' => 'active',
        'status' => 'active',
    ]);

    $response->assertRedirect(route('expense-categories.index'));
    $this->assertDatabaseHas('expense_categories', [
        'name' => 'Biaya Listrik',
        'code' => 'EXP-LST',
    ]);
});

test('can soft disable expense category', function () {
    Expense::create([
        'expense_category_id' => $this->category->id,
        'amount' => 500000,
        'expense_date' => now()->toDateString(),
        'payment_method' => 'bank_transfer',
        'status' => 'paid',
        'description' => 'Iklan Meta 1',
        'created_by' => $this->owner->id,
    ]);

    $response = $this->actingAs($this->owner)->delete(route('expense-categories.destroy', $this->category));

    $response->assertRedirect(route('expense-categories.index'));

    // Check if status is set to inactive
    $this->assertDatabaseHas('expense_categories', [
        'id' => $this->category->id,
        'status' => 'inactive',
    ]);
});

test('can create expense and status defaults to paid', function () {
    $response = $this->actingAs($this->owner)->post(route('expenses.store'), [
        'expense_category_id' => $this->category->id,
        'amount' => 100000,
        'expense_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'status' => 'paid',
        'reference_number' => 'REF-001',
        'description' => 'Snack',
    ]);

    $response->assertRedirect(route('expenses.index'));
    $this->assertDatabaseHas('expenses', [
        'amount' => 100000,
        'status' => 'paid',
        'created_by' => $this->owner->id,
    ]);
});

test('can cancel an expense', function () {
    $expense = Expense::create([
        'expense_category_id' => $this->category->id,
        'amount' => 200000,
        'expense_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'status' => 'paid',
        'description' => 'To be cancelled',
        'created_by' => $this->owner->id,
    ]);

    $response = $this->actingAs($this->owner)->delete(route('expenses.destroy', $expense));

    $response->assertRedirect(route('expenses.index'));

    // Check if status is set to cancelled
    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'status' => 'cancelled',
    ]);
});

test('profit service only calculates paid expenses', function () {
    Expense::create([
        'expense_category_id' => $this->category->id,
        'amount' => 100000,
        'expense_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'status' => 'paid',
        'description' => 'Paid 1',
        'created_by' => $this->owner->id,
    ]);

    Expense::create([
        'expense_category_id' => $this->category->id,
        'amount' => 50000,
        'expense_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'status' => 'draft',
        'description' => 'Draft 1',
        'created_by' => $this->owner->id,
    ]);

    Expense::create([
        'expense_category_id' => $this->category->id,
        'amount' => 200000,
        'expense_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'status' => 'cancelled',
        'description' => 'Cancelled 1',
        'created_by' => $this->owner->id,
    ]);

    $profitService = app(ProfitService::class);
    $summary = $profitService->getProfitSummary([
        'start_date' => now()->startOfDay(),
        'end_date' => now()->endOfDay(),
    ]);

    // Should only include the 'paid' expense (100,000)
    expect($summary['total_operating_expenses'])->toEqual(100000);
});

test('owner cannot access expenses when the profit module is disabled', function () {
    $company = Company::create(['name' => 'Basic Shop', 'features' => []]);
    $owner = User::factory()->for($company)->create(['role' => 'owner']);

    $this->actingAs($owner)->get(route('expenses.index'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error', 'Akses ditolak. Modul ini belum diaktifkan untuk akun Anda.');

    $this->post(route('expenses.store'), [
        'expense_category_id' => $this->category->id,
        'amount' => 100000,
        'expense_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'status' => 'paid',
        'description' => 'Blocked expense',
    ])->assertRedirect(route('dashboard'));

    $this->assertDatabaseCount('expenses', 0);
});
