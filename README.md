# Laravel CRUD Generator with Repository Pattern

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rahulp/crud-generator.svg)](https://packagist.org/packages/rahulp/crud-generator)
[![License](https://img.shields.io/packagist/l/rahulp/crud-generator.svg)](https://github.com/rahulpathak1706/package-rahulp/blob/main/LICENSE)

A powerful Laravel package that generates complete CRUD operations with Repository Pattern implementation, along with API response helpers and database transaction middleware.

## Features

- 🚀 Project setup with essential middleware and helpers
- 🏗️ Generates Models, Controllers, Services, and Repositories
- 📦 Automatic repository binding setup
- 🔄 Database transaction middleware
- 📬 API response helper functions
- ⚡ Support for Laravel 11
- 🛠️ Customizable validation rules

## Installation

You can install the package via composer:

```bash
composer require rahulp/crud-generator
```

## Commands

### 1. Project Setup (Required First)
```bash
php artisan project:setup
```

This command sets up essential components needed for the CRUD operations:

#### A. Database Transaction Middleware
Automatically adds and registers middleware that:
- Wraps all API requests in database transactions
- Automatically commits on successful responses
- Rolls back on exceptions or error responses (4xx, 5xx)
- Handles exceptions gracefully

#### B. API Response Helpers
Adds global helper functions for consistent API responses:

1. Success Response Helper:
```php
ok($message = null, $data = [], $status = 200);

// Examples:
return ok('User profile fetched', $user);
return ok('Post created', $post, 201);
```

Success Response Format:
```json
{
    "meta": {
        "status": 200,
        "message": "User profile fetched",
        "success": true
    },
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

2. Error Response Helper:
```php
error($message = null, $data = [], $type = null);

// Examples:
return error('Validation failed', $validator->errors(), 'validation');
return error('Post not found', [], 'notfound');
return error('Unauthorized access', [], 'forbidden');
```

Error Types and Status Codes:
- `validation` (422) - Validation errors
- `unauthenticated` (401) - Authentication required
- `notfound` (404) - Resource not found
- `forbidden` (403) - Permission denied
- `processError` (400) - Bad request
- `loginCase` (306) - Login specific errors
- Default (500) - Server errors

Error Response Format:
```json
{
    "meta": {
        "status": 422,
        "message": "Validation failed",
        "success": false
    },
    "data": {
        "email": ["The email field is required"],
        "name": ["The name field is required"]
    }
}
```

### 2. Generate CRUD Operations
After setting up the project, you can generate CRUD operations:

```bash
php artisan make:crud {model} {columns}
```

Parameters:
- `model`: Name of the model (e.g., Post, User, Product)
- `columns`: Column definitions in format: name:type:required|nullable:default

Supported Column Types:
- `string` - String/varchar fields
- `integer` - Whole numbers
- `decimal` - Decimal numbers (prices, measurements)
- `text` - Long text content
- `boolean` - True/false values
- `date` - Date only
- `datetime` - Date and time
- `timestamp` - Timestamps

Examples:

1. Basic Post Model:
```bash
php artisan make:crud Post title:string:required content:text:nullable
```

2. Product with Default Values:
```bash
php artisan make:crud Product \
    name:string:required \
    price:decimal:required:0.00 \
    description:text:nullable \
    stock:integer:required:0 \
    is_active:boolean:required:true
```

3. User Model with Validation:
```bash
php artisan make:crud User \
    name:string:required \
    email:string:required:unique \
    phone:string:nullable \
    status:string:required:active
```

Each `make:crud` command generates:
1. Database migration
2. Model with fillable fields and defaults
3. Repository interface and implementation
4. Service class for business logic
5. API controller with validation
6. Automatic repository binding

## Generated Structure
For a Post model, it creates:
```
app/
├── Http/
│   ├── Controllers/
│   │   └── PostController.php
│   └── Middleware/
│       └── DBTransaction.php
├── Models/
│   └── Post.php
├── Repositories/
│   ├── Interfaces/
│   │   └── PostRepositoryInterface.php
│   └── PostRepository.php
├── Services/
│   └── PostService.php
└── Helpers/
    └── functions.php
```

## API Endpoints
Each CRUD generation creates these endpoints:

```bash
# List all records with pagination
GET /api/posts

# Create new record
POST /api/posts

# Get single record
GET /api/posts/{id}

# Update record
PUT /api/posts/{id}

# Delete record
DELETE /api/posts/{id}
```

## Security

If you discover any security related issues, please email rahulspathak17@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- [Rahul Pathak](https://github.com/rahulpathak1706/package-rahulp)