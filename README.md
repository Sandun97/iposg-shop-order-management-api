# Shop Order Management API

This project is a RESTful API built with Laravel 11 for managing shop
orders, product stock, and reporting. It was developed as part of the
Senior Software Engineer (Laravel) Technical Assessment.

Detailed system design explanations for **Part 2 (Concurrency Fix)** and
**Part 3 (Architecture Design)** are available in **Architecture.md**.

------------------------------------------------------------------------

## Technology Stack

-   Laravel 11
-   PHP 8+
-   MySQL
-   PHPUnit

------------------------------------------------------------------------

## Features

-   Create orders with multiple products
-   Reduce product stock atomically during order creation
-   Cancel orders and restore product stock
-   Retrieve paginated order lists with filtering
-   Retrieve individual order details
-   Generate a report of top-selling products
-   Concurrency-safe stock management
-   Unit and feature tests

------------------------------------------------------------------------

## Installation

Clone the repository:

https://github.com/Sandun97/iposg-shop-order-management-api.git

``` bash
cd shop-order-management-api
```

Install dependencies:

``` bash
composer install
```

Create environment file:

``` bash
cp .env.example .env
```

Generate application key:

``` bash
php artisan key:generate
```

Configure your database in `.env`

Example configuration:

    DB_CONNECTION=mysql
    DB_DATABASE=shop_order_api
    DB_USERNAME=root
    DB_PASSWORD=

Run migrations:

``` bash
php artisan migrate
```

Seed sample data:

``` bash
php artisan db:seed
```

Start the server:

``` bash
php artisan serve
```

API URL:

    http://127.0.0.1:8000

------------------------------------------------------------------------

## API Endpoints

### Create Order

POST `/api/orders`

Example JSON:

``` json
{
  "shop_id": 1,
  "items": [
    { "product_id": 1, "qty": 2 },
    { "product_id": 2, "qty": 1 }
  ]
}
```

Behavior:

-   Validates input
-   Checks stock availability
-   Deducts stock atomically
-   Stores product snapshot
-   Calculates order total

------------------------------------------------------------------------

### Get Orders

GET `/api/orders`

Filters:

    /api/orders?shop_id=1
    /api/orders?status=completed
    /api/orders?from=2026-01-01&to=2026-12-31

------------------------------------------------------------------------

### Get Single Order

GET `/api/orders/{id}`

------------------------------------------------------------------------

### Cancel Order

PATCH `/api/orders/{id}/cancel`

Restores stock using database transactions.

------------------------------------------------------------------------

### Top Products Report

GET `/api/reports/top-products`

Returns the top 5 products by quantity sold.

------------------------------------------------------------------------

## Concurrency Handling

Row-level locking is used to prevent negative stock during concurrent
requests.

Example:

``` php
$product = Product::where('id', $item['product_id'])
    ->lockForUpdate()
    ->firstOrFail();
```

This ensures that when one transaction updates the product stock, other
transactions must wait until the first transaction finishes.

------------------------------------------------------------------------

## Error Handling

Missing resources return:

``` json
{
  "message": "Resource not found"
}
```

Status code:

    404

------------------------------------------------------------------------

## Testing

Run all tests:

``` bash
php artisan test
```

Run only the concurrency test:

``` bash
php artisan test --filter=ParallelOrderTest
```

Feature tests cover:

-   Order creation
-   Stock deduction
-   Order cancellation
-   Stock restoration

Unit tests cover:

-   Business logic validation
-   Insufficient stock checks

------------------------------------------------------------------------

## Architecture Overview (Clean Architecture)

This project follows **Clean Architecture principles** to separate
responsibilities and keep the system maintainable, testable, and
scalable.

The architecture separates the application into different layers:

Controller → Service → Model → Database

Controllers handle HTTP requests.\
Services contain business logic.\
Models interact with the database.

------------------------------------------------------------------------

## Architecture & Design Documentation

Detailed explanations for the technical assessment questions are
provided in the following document:

**Architecture.md**

This document contains the design and implementation details for the
following sections of the assessment.

### Part 2 -- Debug & Concurrency Problem

The system originally allowed product stock to become negative when
multiple users placed orders at the same time.

To solve this problem the following strategy was implemented:

-   Database transactions
-   Row-level locking (`lockForUpdate`)
-   Stock validation before deduction

A **parallel feature test** was also created to simulate concurrent
order processing and ensure that stock never becomes negative.

Test file:

    tests/Feature/ParallelOrderTest.php

------------------------------------------------------------------------

### Part 3 -- Architecture & Design Question

The system design for **asynchronous Payments and Refunds** using
**webhooks and background jobs** is documented in **Architecture.md**.

The architecture document explains:

-   Laravel Queue structure
-   Idempotency implementation
-   Duplicate processing prevention
-   Webhook validation strategy
-   Retry strategy
-   Dead-letter / failed job handling
-   Database schema considerations

The design focuses on **scalability, reliability, and fault-tolerant
processing** for payment events.

------------------------------------------------------------------------

## Author

Technical Assessment Submission\
Senior Software Engineer (Laravel)
