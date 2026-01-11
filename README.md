# Client Hour Management API

A Laravel-based API for managing client hour balances, time tracking, wallets, and billing using a **ledger-based architecture**.

## 📋 Overview

This system treats hours as **financial-like credits**, where:
- Credits add minutes
- Debits subtract minutes
- Balance is always derived from ledger
- Negative balances represent debt
- All transactions are immutable

## 🏗️ Architecture Principles

### Ledger as Source of Truth
- All hour changes recorded as immutable transactions
- Balances never stored, always calculated
- State derived from historical events
- Append-only operations
- Corrections via compensating entries

### Service Layer Pattern
- **Controllers** orchestrate HTTP requests
- **Services** contain business logic
- **Models** handle persistence only
- Clear separation of concerns

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- MySQL or PostgreSQL
- Laravel 11.x

### Installation

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed

# Run tests
php artisan test
```

## 📚 Documentation

- [API Documentation](./API_DOCUMENTATION.md) - Comprehensive API guide with examples
- [Non-Negotiable Rules](./NON_NEGOTIABLE_RULES.md) - Core principles and forbidden practices
- [Coding Guidelines](./CLAUDE.md) - Detailed coding preferences
- [Project Structure](../BACKEND-PROJECT-STRUCTURE.md) - Architecture overview
- [Project Planning](../BACKEND-PROJECT-PLANNING.md) - Implementation checklist

## 🧪 Testing

All features are thoroughly tested:

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## 🔑 Key Features

- ✅ **Wallet Management** - Multiple wallets per client
- ✅ **Ledger System** - Immutable transaction log
- ✅ **Time Tracking** - Start/pause/resume/stop timers
- ✅ **Balance Calculation** - Derived from ledger (supports negative balances)
- ✅ **Wallet Transfers** - Atomic transfers between wallets
- ✅ **Invoicing** - Invoice generation and payment tracking
- ✅ **Package System** - Predefined hour packages for purchase
- ✅ **Role-Based Access** - Admin and Client roles
- ✅ **Internationalization** - English and Portuguese support
- ✅ **API Documentation** - Interactive Swagger UI at `/docs/api`

## 🛡️ Security

- Laravel Sanctum authentication
- Role-based authorization (Spatie Permission)
- Policy-based access control
- Client data isolation
- Admin-only internal notes

## 📦 Tech Stack

- **Framework**: Laravel 11.x
- **Database**: MySQL/PostgreSQL
- **Auth**: Laravel Sanctum
- **Permissions**: Spatie Laravel Permission
- **API Docs**: Scramble (OpenAPI/Swagger)
- **Testing**: PHPUnit

## 🌍 Internationalization

All user-facing messages use Laravel's translation system:
- Base language: English (`en`)
- Supported: Portuguese Brazil (`pt_BR`)

## 📝 License

This project is proprietary and confidential.

---

### Deployment on Vercel

This project can be deployed on [Vercel](https://vercel.com):

<a href="https://vercel.com/new/clone?repository-url=https://github.com/tiagofrancafernandes/App-Laravel-on-Vercel/tree/master"><img src="https://vercel.com/button"></a>

**Thanks to:**
- [Vercel](https://vercel.com)
- [php.vercel.app](https://php.vercel.app/)
- [vercel-community](https://github.com/vercel-community/php)
