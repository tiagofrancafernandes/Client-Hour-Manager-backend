# Client Hour Management API - Documentation

## Overview

The Client Hour Management API is a ledger-based hour tracking system that provides wallet management, timers, invoices, and package purchases.

**Base URL:** `/api/v1`
**API Documentation UI:** `/docs/api` (Available in development)

## Architecture

- **Ledger-First Design:** All balance changes are recorded as immutable transactions
- **Service Layer:** Business logic is separated into service classes
- **Policy-Based Authorization:** Role-based access control using Laravel Policies
- **Dual Authentication:** Supports both User (admin/staff) and Client authentication

## Authentication

The API uses Laravel Sanctum for authentication. Include the API token in the `Authorization` header:

```
Authorization: Bearer {your-token}
```

### Authentication Types

1. **User Authentication** (Admin/Staff)
   - Full access to all resources
   - Can create manual transactions
   - Can see internal notes

2. **Client Authentication**
   - Access limited to own wallets and transactions
   - Cannot see internal notes
   - Cannot manually create transactions

---

## Authentication Endpoints

### Login

Authenticate a user and receive an access token.

**Endpoint:** `POST /api/v1/auth/login`

**Authentication:** None (public endpoint)

**Request Body:**

```json
{
  "email": "user@example.com",
  "password": "your-password"
}
```

**Validation:**
- `email`: required, valid email format
- `password`: required, string

**Success Response (200):**

```json
{
  "token": "1|aB3dEf7gH9iJ0kL2mN4oP6qR8sT0uV2wX4yZ6",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "active": true,
    "email_verified_at": "2024-01-01T00:00:00.000000Z",
    "roles": ["admin"],
    "permissions": ["manage wallets", "view transactions"],
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Error Responses:**

- **401 Unauthorized** - Invalid credentials
  ```json
  {
    "message": "These credentials do not match our records."
  }
  ```

- **403 Forbidden** - Inactive account
  ```json
  {
    "message": "Your account is inactive. Please contact support."
  }
  ```

- **422 Unprocessable Entity** - Validation errors
  ```json
  {
    "message": "The email field is required.",
    "errors": {
      "email": ["The email field is required."]
    }
  }
  ```

---

### Get User Info

Retrieve authenticated user information including roles and permissions.

**Endpoint:** `GET /api/v1/auth/info`

**Authentication:** Required (Bearer token)

**Success Response (200):**

```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "active": true,
    "email_verified_at": "2024-01-01T00:00:00.000000Z",
    "roles": ["admin"],
    "permissions": ["manage wallets", "view transactions"],
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Error Response (401):**

```json
{
  "message": "Unauthenticated."
}
```

---

### Logout

Revoke the current access token.

**Endpoint:** `POST /api/v1/auth/logout`

**Authentication:** Required (Bearer token)

**Success Response (200):**

```json
{
  "message": "Successfully logged out."
}
```

**Error Response (401):**

```json
{
  "message": "Unauthenticated."
}
```

---
  "token": "1|abc123...",
  "user": {...}
}
```

## Core Concepts

### Wallets

Wallets store hour balances for clients. Balance is always calculated from transactions (never stored).

- **Balance Calculation:** Sum of all credit transactions minus all debit transactions
- **Negative Balance:** Allowed (represents debt)
- **Cannot be deleted:** Business rule - wallets can only be archived

### Transactions (Ledger)

Immutable records of all balance changes.

**Types:**
- `credit` - Add hours/minutes to wallet
- `debit` - Subtract hours/minutes from wallet
- `transfer_in` - Receive hours from another wallet
- `transfer_out` - Send hours to another wallet

**Important Rules:**
- Transactions are **append-only**
- **Never** update or delete transactions
- Corrections are done via **compensating transactions**

### Timers

Track time spent on work, automatically creating debit transactions when stopped.

**States:** `running`, `paused`, `stopped`, `cancelled`

**Visibility Rules:**
- Admins see all timers
- Clients see their own timers (including hidden)
- Clients see only non-hidden timers from others

### Invoices

Generate invoices for negative balances or package purchases.

- Payment of invoice creates a credit transaction
- Invoices don't directly modify balance

### Packages

Pre-defined hour packages that clients can purchase.

- Wallet-bound packages
- Can be active/inactive
- Purchase flow integrates with payment gateways

## API Endpoints

### Transactions

#### Add Credit

```bash
POST /api/v1/transactions/credit
Authorization: Bearer {admin-token}
Content-Type: application/json

{
  "wallet_id": 1,
  "minutes": 300,
  "description": "Monthly hours package",
  "internal_note": "Admin-only note",
  "occurred_at": "2024-01-10T10:00:00Z"
}
```

**Response (201):**
```json
{
  "message": "Credit added successfully.",
  "data": {
    "id": 1,
    "wallet_id": 1,
    "type": "credit",
    "minutes": 300,
    "description": "Monthly hours package",
    "occurred_at": "2024-01-10T10:00:00Z",
    "created_at": "2024-01-10T10:00:00Z"
  }
}
```

#### Add Debit

```bash
POST /api/v1/transactions/debit
Authorization: Bearer {admin-token}
Content-Type: application/json

{
  "wallet_id": 1,
  "minutes": 150,
  "description": "Time tracked for project"
}
```

**Response (201):**
```json
{
  "message": "Debit added successfully.",
  "data": {
    "id": 2,
    "wallet_id": 1,
    "type": "debit",
    "minutes": 150,
    "description": "Time tracked for project",
    "occurred_at": "2024-01-10T10:00:00Z",
    "created_at": "2024-01-10T10:00:00Z"
  }
}
```

## Validation Rules

### Transaction Request

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| wallet_id | integer | Yes | Must exist in wallets table |
| minutes | integer | Yes | Must be at least 1 |
| description | string | No | Maximum 500 characters |
| internal_note | string | No | Maximum 1000 characters (admin-only) |
| occurred_at | datetime | No | Must be valid date format |

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "wallet_id": ["Wallet not found."],
    "minutes": ["Minutes must be at least 1."]
  }
}
```

### 404 Not Found
```json
{
  "message": "Resource not found."
}
```

## Internationalization (i18n)

The API supports multiple languages. Use the `Accept-Language` header:

```
Accept-Language: en
Accept-Language: pt-BR
```

**Supported Languages:**
- English (`en`)
- Portuguese Brazil (`pt-BR`)

All user-facing messages and validation errors are translated.

## Rate Limiting

API requests are rate-limited per authenticated user:
- Standard: 60 requests per minute
- Authenticated: 120 requests per minute

Rate limit information is included in response headers:
```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 119
```

## Best Practices

1. **Always use transactions for writes** - All balance modifications must go through the ledger
2. **Never store balance** - Always calculate from transactions
3. **Use descriptive transaction descriptions** - Makes ledger auditable
4. **Handle negative balances gracefully** - They represent legitimate debt states
5. **Check authorization** - Ensure proper permissions before operations
6. **Use appropriate occurred_at** - Backdated transactions should set this field

## Data Models

### Transaction Resource (Response)

```typescript
{
  id: number;
  wallet_id: number;
  type: 'credit' | 'debit' | 'transfer_in' | 'transfer_out';
  minutes: number;
  description: string | null;
  internal_note: string | null;  // Only visible to admins
  occurred_at: string;  // ISO 8601 datetime
  created_at: string;   // ISO 8601 datetime
  updated_at: string;   // ISO 8601 datetime
}
```

## Development

### Running Tests

```bash
php artisan test
```

### Accessing API Documentation UI

In development, visit `/docs/api` to access the interactive API documentation powered by Scramble.

### Exporting OpenAPI Specification

```bash
php artisan scramble:export > openapi.json
```

## Support

For issues, questions, or contributions, please refer to the project repository.
