# API Routes Reference

Complete listing of all API v1 endpoints created for the Client Hour Management API.

---

## Base URL

```
/api/v1
```

All endpoints below are prefixed with `/api/v1`.

---

## Authentication Endpoints

| Method | Endpoint | Auth Required | Description |
|--------|----------|---------------|-------------|
| POST | `/auth/login` | ❌ | Authenticate user and get token |
| GET | `/auth/info` | ✅ | Get current user info |
| POST | `/auth/logout` | ✅ | Revoke current token |

---

## Client Endpoints

| Method | Endpoint | Auth Required | Permission | Description |
|--------|----------|---------------|------------|-------------|
| GET | `/clients` | ✅ | `client.view_any` | List all clients |
| POST | `/clients` | ✅ | `client.create` | Create new client |
| GET | `/clients/{client}` | ✅ | `client.view` | Get client details |
| PUT/PATCH | `/clients/{client}` | ✅ | `client.update` | Update client |
| DELETE | `/clients/{client}` | ✅ | `client.delete` | Soft delete client |

**Query Parameters (index):**
- `status` - Filter by status (active/inactive)
- `search` - Search by name or email
- `per_page` - Items per page (max 100)

---

## Wallet Endpoints

| Method | Endpoint | Auth Required | Permission | Description |
|--------|----------|---------------|------------|-------------|
| GET | `/wallets` | ✅ | `wallet.view_any` | List all wallets |
| POST | `/wallets` | ✅ | `wallet.create` | Create new wallet |
| GET | `/wallets/{wallet}` | ✅ | `wallet.view` | Get wallet details + balance |
| PUT/PATCH | `/wallets/{wallet}` | ✅ | `wallet.update` | Update wallet |
| POST | `/wallets/{wallet}/archive` | ✅ | `wallet.update` | Archive wallet |
| POST | `/wallets/{wallet}/unarchive` | ✅ | `wallet.update` | Unarchive wallet |

**Query Parameters (index):**
- `client_id` - Filter by client
- `include_archived` - Include archived wallets (boolean)
- `per_page` - Items per page (max 100)

**Note:** Wallets cannot be deleted (no DELETE endpoint)

---

## Transaction Endpoints

| Method | Endpoint | Auth Required | Permission | Description |
|--------|----------|---------------|------------|-------------|
| POST | `/transactions/credit` | ✅ | `transaction.create` | Add manual credit |
| POST | `/transactions/debit` | ✅ | `transaction.create` | Add manual debit |

**Note:** Transactions are immutable (no UPDATE/DELETE endpoints)

---

## Timer Endpoints

| Method | Endpoint | Auth Required | Permission | Description |
|--------|----------|---------------|------------|-------------|
| GET | `/timers` | ✅ | `timer.view_any` or `timer.view_own` | List timers |
| POST | `/timers` | ✅ | `timer.create` | Start new timer |
| GET | `/timers/{timer}` | ✅ | `timer.view` | Get timer details |
| POST | `/timers/{timer}/pause` | ✅ | `timer.update` | Pause running timer |
| POST | `/timers/{timer}/resume` | ✅ | `timer.update` | Resume paused timer |
| POST | `/timers/{timer}/stop` | ✅ | `timer.update` | Stop timer (creates debit) |
| POST | `/timers/{timer}/cancel` | ✅ | `timer.delete` | Cancel timer (no ledger entry) |

**Query Parameters (index):**
- `wallet_id` - Filter by wallet
- `status` - Filter by status (running/paused/stopped/cancelled)
- `per_page` - Items per page (max 100)

**Note:** No UPDATE endpoint (use specific actions: pause/resume/stop/cancel)

---

## Invoice Endpoints

| Method | Endpoint | Auth Required | Permission | Description |
|--------|----------|---------------|------------|-------------|
| GET | `/invoices` | ✅ | `invoice.view_any` or `invoice.view_own` | List invoices |
| POST | `/invoices` | ✅ | `invoice.create` | Create invoice |
| GET | `/invoices/{invoice}` | ✅ | `invoice.view` | Get invoice details |
| POST | `/invoices/{invoice}/mark-as-paid` | ✅ | `invoice.mark_paid` | Mark invoice as paid |
| POST | `/invoices/{invoice}/cancel` | ✅ | `invoice.update` | Cancel open invoice |

**Query Parameters (index):**
- `client_id` - Filter by client
- `status` - Filter by status (open/paid/cancelled)
- `per_page` - Items per page (max 100)

**Note:** No UPDATE/DELETE endpoints (use specific actions)

---

## Package Purchase Endpoints

| Method | Endpoint | Auth Required | Permission | Description |
|--------|----------|---------------|------------|-------------|
| POST | `/packages/purchase` | ✅ | `package.purchase` | Initiate package purchase |

---

## Webhook Endpoints

| Method | Endpoint | Auth Required | Description |
|--------|----------|---------------|-------------|
| POST | `/webhooks/payment/{provider}` | ❌ (signature verified) | Payment gateway webhook |

---

## Standard Response Format

### Success Response (2xx)

```json
{
  "message": "Success message (translated)",
  "data": {
    // Resource data or collection
  }
}
```

### Paginated Response

```json
{
  "data": [...],
  "links": {
    "first": "url",
    "last": "url",
    "prev": "url",
    "next": "url"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

### Error Response (4xx/5xx)

```json
{
  "message": "Error message (translated)",
  "errors": {
    "field_name": ["Validation error messages"]
  }
}
```

---

## Common Query Parameters

Most listing endpoints support:
- `per_page` - Items per page (1-100, default: 15)
- `page` - Page number (default: 1)
- `sort_by` - Field to sort by
- `sort_order` - Sort direction (asc/desc)

---

## HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 200 | OK | Successful GET/PUT/PATCH |
| 201 | Created | Successful POST |
| 204 | No Content | Successful DELETE |
| 400 | Bad Request | Invalid request |
| 401 | Unauthorized | Not authenticated |
| 403 | Forbidden | Authenticated but no permission |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limited |
| 500 | Internal Server Error | Server error |

---

## Resource Relationships

### Available `?include=` Parameters

**Clients:**
- `?include=wallets` - Include all client wallets

**Wallets:**
- `?include=client` - Include wallet owner
- `?include=transactions` - Include wallet transactions
- `?include=packages` - Include wallet packages

**Timers:**
- `?include=wallet` - Include associated wallet
- `?include=startedBy` - Include user who started timer

**Invoices:**
- `?include=client` - Include invoice client

---

## Route Count Summary

| Resource | Endpoints |
|----------|-----------|
| Authentication | 3 |
| Clients | 5 |
| Wallets | 7 |
| Transactions | 2 |
| Timers | 7 |
| Invoices | 5 |
| Packages | 1 |
| Webhooks | 1 |
| **TOTAL** | **31** |

---

## Implementation Notes

1. **All endpoints require `auth:sanctum` middleware** except:
   - POST `/auth/login`
   - POST `/webhooks/payment/{provider}`

2. **All endpoints use Policy-based authorization** via:
   - `$this->authorize('action', Model::class)` in controllers

3. **All endpoints return JSON** with proper HTTP status codes

4. **All user-facing messages use i18n** via `__('messages.key')`

5. **All timestamps use ISO 8601 format** (UTC timezone)

6. **Pagination limited to 100 items per page** to prevent performance issues

7. **Resources use Laravel API Resources** for consistent formatting

8. **Form Requests handle validation** before reaching controllers

9. **Services handle business logic** - controllers only orchestrate

10. **Ledger transactions are immutable** - no UPDATE/DELETE endpoints

---

## Next Steps

To complete the API:

1. Create Policies for:
   - ClientPolicy
   - WalletPolicy (partially exists)
   - TimerPolicy
   - InvoicePolicy

2. Add comprehensive tests for all endpoints

3. Document all endpoints in Swagger/OpenAPI (via Scramble)

4. Add rate limiting to sensitive endpoints

5. Implement soft deletes for Clients

6. Add bulk operations if needed

