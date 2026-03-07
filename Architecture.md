# Architecture & Concurrency Handling

## Part 2 -- Debug & Concurrency Problem

### Problem

When two users place an order for the same product at the same time, the
product stock can sometimes become negative.

### Root Cause

This issue occurs due to a race condition where multiple requests read
and update the same stock value simultaneously.

Example: Product stock = 5\
Order A qty = 3\
Order B qty = 3

Execution: 1. Request A reads stock = 5 2. Request B reads stock = 5 3.
Request A updates stock → 5 - 3 = 2 4. Request B updates stock → 5 - 3 =
-1

Result: Stock becomes negative.

### Fix Implemented

To prevent this issue: - Database transactions - Row-level locking using
lockForUpdate()

Example:

``` php
    $product = Product::where('id', $item['product_id'])
    ->lockForUpdate()
    ->firstOrFail();

    if (!$product) {
        throw ValidationException::withMessages(['product_id' => 'Product not found']);
    }

    if ($product->stock < $item['qty']) {
        throw ValidationException::withMessages([
            'stock' => "Insufficient stock for product : {$product->name}"
        ]);
    }

    $subtotal = $product->price * $item['qty']; // Calculate subtotal

    $product->decrement('stock', $item['qty']); // Reduce stock
```

### Production Strategy

1.  Start DB transaction
2.  Lock product row
3.  Validate stock inside transaction
4.  Deduct stock safely

### Test Case

Product stock = 2

Order 1 qty = 2 → success\
Order 2 qty = 2 → fails

Feature tests created: - ConcurrencyStockTest - ParallelOrderTest

These tests simulate concurrent order requests and confirm that stock
cannot become negative.

------------------------------------------------------------------------

# Part 3 -- Architecture & Design

## Queue Structure

  Queue           Purpose
  --------------- --------------------
  payments        Payment processing
  refunds         Refund processing
  webhooks        Webhook handling
  notifications   Email/SMS sending

Example dispatch:

``` php
ProcessPaymentJob::dispatch($payment)->onQueue('payments');
```

Example job:

``` php
    class ProcessPaymentJob implements ShouldQueue
    {
        public function handle()
        {
            DB::transaction(function () {
                // payment logic
            });
        }
    }
```

## Idempotency

Webhook events are stored to ensure they are processed only once.

Table: webhook_events

Columns: - id - event_id (unique) - event_type - payload - processed_at

Check before processing:

``` php
    $exists = WebhookEvent::where('event_id', $eventId)->exists();

    if ($exists) {
        return response()->json(['status' => 'already processed']);
    }
```

If exists → skip processing.

## Prevent Duplicate Processing

### Database Unique Constraints

payments table: - gateway_transaction_id (unique)

refunds table: - gateway_refund_id (unique)

### Transaction Lock

``` php
    DB::transaction(function () {

        $payment = Payment::where('transaction_id', $txId)
            ->lockForUpdate()
            ->first();

        if ($payment->status === 'completed') {
            return;
        }

        $payment->update([
            'status' => 'completed'
        ]);

    });
```

## Webhook Validation

1.  Verify gateway signature
2.  Use HTTPS endpoints
3.  Validate payload structure

## Retry Strategy

``` php
    class ProcessPaymentJob implements ShouldQueue
    {
        public $tries = 5;
        public $backoff = [10, 30, 60];
    }
```

Retries occur after 10s, 30s, and 60s.

## Failed Job Handling

Commands:

``` bash
php artisan queue:failed-table php artisan migrate
```

Retry failed job:

``` bash
php artisan queue:retry {id}
```

Monitoring tools: - Laravel Horizon - Sentry - Slack Alerts

## Database Schema

Payments: - id - order_id - gateway_transaction_id (unique) - amount -
currency - status

Refunds: - id - payment_id - gateway_refund_id (unique) - amount -
status

Webhook Events: - id - event_id (unique) - event_type - payload -
processed_at

Indexes: unique(gateway_transaction_id)\
unique(gateway_refund_id)\
unique(event_id)
