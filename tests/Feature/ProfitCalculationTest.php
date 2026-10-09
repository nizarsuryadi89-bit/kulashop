<?php

use App\Models\Order;
use App\Models\OrderFinancial;
use App\Models\OrderItem;
use App\Services\ProfitService;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->profitService = app(ProfitService::class);
});

test('profit basic calculation correctly computes gross profit', function () {
    // Income = 181172, HPP = 125000 -> Gross Profit = 56172
    $order = new Order;
    $order->hpp_status = 'Complete';
    $order->income_status = 'Complete';

    $financial = new OrderFinancial;
    $financial->total_income = 181172;
    $order->setRelation('financial', $financial);

    $item = new OrderItem;
    $item->qty = 1;
    $item->returned_qty = 0;
    $item->hpp_snapshot = 125000;
    $order->setRelation('items', new Collection([$item]));

    $grossProfit = $this->profitService->getOrderProfit($order);

    expect($grossProfit)->toBe(56172.0);
});

test('profit margin correctly computes percentage with delta', function () {
    // Margin: 56172 / 181172 * 100 = 31.02%
    $order = new Order;
    $order->hpp_status = 'Complete';
    $order->income_status = 'Complete';

    $financial = new OrderFinancial;
    $financial->total_income = 181172;
    $order->setRelation('financial', $financial);

    $item = new OrderItem;
    $item->qty = 1;
    $item->returned_qty = 0;
    $item->hpp_snapshot = 125000;
    $order->setRelation('items', new Collection([$item]));

    $margin = $this->profitService->getOrderMargin($order);

    // 31.00, tolerance 0.01
    expect(round($margin, 2))->toBe(31.0);
});

test('zero income handles division by zero safely and returns null margin', function () {
    // Income = 0, no division by zero error expected.
    $order = new Order;
    $order->hpp_status = 'Complete';
    $order->income_status = 'Complete';

    $financial = new OrderFinancial;
    $financial->total_income = 0;
    $order->setRelation('financial', $financial);

    $item = new OrderItem;
    $item->qty = 1;
    $item->returned_qty = 0;
    $item->hpp_snapshot = 125000;
    $order->setRelation('items', new Collection([$item]));

    $margin = $this->profitService->getOrderMargin($order);

    expect($margin)->toBeNull();
});

test('incomplete hpp returns null profit and sets correct status', function () {
    // Income = 181172, HPP = NULL -> Profit = NULL, Status = Profit Incomplete
    $order = new Order;
    $order->hpp_status = 'Incomplete';
    $order->income_status = 'Complete';

    $financial = new OrderFinancial;
    $financial->total_income = 181172;
    $order->setRelation('financial', $financial);

    $item = new OrderItem;
    $item->qty = 1;
    $item->returned_qty = 0;
    $item->hpp_snapshot = null;
    $order->setRelation('items', new Collection([$item]));

    $grossProfit = $this->profitService->getOrderProfit($order);
    $status = $this->profitService->getOrderProfitStatus($order);

    expect($grossProfit)->toBeNull();
    expect($status)->toBe('Profit Incomplete');
});
