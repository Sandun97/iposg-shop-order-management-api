
🐞 Part 2 – Debug & Concurrency Problem

Problem:
When two users place an order for the same product at the same time, stock sometimes becomes negative.

Tasks:

1. Explain the root cause.

When multiple users place orders for the same product at the same time, a race condition can occur.
This happens when two or more requests read the same stock value before any of them updates it.

Example scenario:

Product stock = 5
Order A requests qty = 3
Order B requests qty = 3

Execution order:

Request A reads stock = 5
Request B reads stock = 5
Request A deducts stock → 5 - 3 = 2
Request B deducts stock → 5 - 3 = -1

As a result, the product stock becomes negative, which is an incorrect state.

This issue occurs because the database operations are not synchronized between concurrent requests.

2. Implement a fix.

To solve this issue, the order creation process uses:
    - Database transactions
    - Row-level locking

3. Show how you would prevent this in production.
    - Start a database transaction.
    - Lock the product row using lockForUpdate().
    - Validate stock availability inside the transaction.
    - Deduct stock only after acquiring the lock.

Sample Implementation:

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

4. Add a test case (if possible) to simulate or demonstrate the fix.

A test case was implemented to simulate the scenario where two orders attempt to purchase the same product with limited stock.

test case example :
    1. Product stock = 2
    2. First order requests qty = 2 --> succeeds
    3. Second order requests qty = 2 --> fails due to insufficient stock

This verifies that stock cannot become negative even when multiple requests are processed.

More : 

I implemented an additional feature test called ConcurrencyStockTest and ParallelOrderTest to simulate concurrent order requests. 
The test verifies that only one order succeeds when stock is limited, confirming that the transaction and row-level locking mechanism prevents negative stock under concurrent access.

----------------------------------------------------------------------------------------------------------------------------------------------------------------------------

🏗️ Part 3 – Architecture & Design Question

Scenario:
You need to support asynchronous Payments and Refunds using webhooks and background jobs.

1. How you would structure queues in Laravel

Laravel queues will be used to process payments and refunds asynchronously.

Separate queues should be created for different workloads.

| Queue Name      | Purpose                                 |
| --------------- | --------------------------------------- |
| `payments`      | Process payment confirmation            |
| `refunds`       | Handle refund requests                  |
| `webhooks`      | Process incoming gateway webhook events |
| `notifications` | Send email/SMS notifications            |

Example job dispatch:

ProcessPaymentJob::dispatch($payment)->onQueue('payments');
ProcessRefundJob::dispatch($refund)->onQueue('refunds');

Example Job:

    class ProcessPaymentJob implements ShouldQueue
    {
        public function handle()
        {
            DB::transaction(function () {
                // payment logic
            });
        }
    }

2. How to implement idempotency

Use a unique idempotency key provided by the payment gateway.

Example Table:

webhook_events
--------------------------
id
event_id (unique)
event_type
payload
processed_at
created_at

Before processing a webhook:

    $exists = WebhookEvent::where('event_id', $eventId)->exists();

    if ($exists) {
        return response()->json(['status' => 'already processed']);
    }

After processing:

    WebhookEvent::create([
        'event_id' => $eventId,
        'payload' => json_encode($payload),
        'processed_at' => now()
    ]);

This ensures exactly-once processing.

4. How to prevent duplicate processing

Duplicate processing can happen when:
    - Multiple webhook deliveries
    - Concurrent workers
    - Network retries

Several strategies to prevent:

    1. Database Unique Constraints

Example:

payments table
----------------------
id
gateway_transaction_id (unique)
order_id
status
amount

This prevents duplicate payment records.

    2. Database Transactions

apply DB::transaction to prevent transection curruption and lockForUpdate() prevents race conditions when multiple workers try to update the same record.

Example:

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


5. Webhook validation strategy

    1.Verify Signature - check X-Signature
    2.Validate Source - Use HTTPS
    3.Validate Payload Structure

6. Retry strategy

Queue jobs may fail due to temporary issues such as:

    1.Network errors
    2.Payment gateway downtime
    3.Database locks

Example job configuration:

    class ProcessPaymentJob implements ShouldQueue
    {
        public $tries = 5;
        public $backoff = [10, 30, 60];
    }

Meaning:

Retry 1 -> after 10 seconds
Retry 2 -> after 30 seconds
Retry 3 -> after 60 seconds

If the job continues failing, it will be marked as failed.

7. Dead-letter or failure handling

Failed jobs should not be lost.

Migration example:

php artisan queue:failed-table
php artisan migrate

When a job fails:
    - It is stored in failed_jobs
    - Can be inspected and retried

Example retry command:
    php artisan queue:retry {id}

For production monitoring:

Recommended tools:
    - Laravel Horizon
    - Sentry
    - Slack alerts

Example failed job handler:

    public function failed(Exception $exception)
    {
        Log::error('Payment job failed', [
            'payment_id' => $this->paymentId,
            'error' => $exception->getMessage()
        ]);
    }

8. Database schema considerations

Payments Table:

payments
----------------------
id
order_id
gateway_transaction_id (unique)
amount
currency
status
created_at
updated_at

Refunds Table:

refunds
----------------------
id
payment_id
gateway_refund_id (unique)
amount
status
created_at
updated_at

Webhook Events Table:

webhook_events
----------------------
id
event_id (unique)
event_type
payload
processed_at
created_at

Important Indexes:

unique(gateway_transaction_id)
unique(gateway_refund_id)
unique(event_id)

Indexes improve performance when checking duplicates.