## Project Overview

This project is an **API-first Laravel application** designed to manage **client hour balances**, **time tracking**, **wallets**, and **billing**, using a **ledger-based architecture**.

The system treats hours as **financial-like credits**, where:
- Credits add minutes
- Debits subtract minutes
- Balance is always derived
- Negative balances represent debt

The project prioritizes **correctness, auditability, and long-term maintainability** over shortcuts or premature optimizations.

---

## Core Architectural Principles

### Ledger as Source of Truth
- All hour changes are recorded as immutable ledger transactions
- Balances are never stored
- State is always derived from historical events
- Transactions are append-only
- Corrections are done via compensating entries, never updates

### Separation of Responsibilities
- Controllers orchestrate requests
- Services contain business rules
- Models persist data only
- No business logic in Controllers
- No business logic hidden in Models

---

## Personal Coding Preferences (Non-Negotiable)

### Early Return
- Always prefer early return
- Avoid nested `if` blocks
- Fail fast

```php
if (! $condition) {
    return;
}
````

### Elseless Code

* Avoid `else` whenever possible
* Each branch should return or throw
* Code paths must be explicit

```php
if ($invalid) {
    throw new DomainException();
}

return $result;
```

---

## PHP & Laravel Best Practices

### PHP

* Use strict typing whenever possible
* Prefer typed arguments and return types
* Avoid magic numbers (use constants)
* Avoid static state
* Avoid global helpers in domain logic
* Never suppress errors using `@`
* Exceptions must be explicit and meaningful

### Laravel

* Use Service Layer for all business rules
* Use Form Requests for validation
* Use Policies for authorization
* Use Jobs for async work
* Use Transactions for all write operations
* Prefer Eloquent relationships, but avoid fat models
* Avoid facades inside Services when possible
* Do not mix HTTP concerns with domain logic

---

## Internationalization (Mandatory)

* All user-facing messages must use translations:

```php
__('messages.wallet.insufficient_balance')
```

* No hardcoded strings in:

  * Controllers
  * Services
  * Exceptions
* Translation keys must be meaningful and hierarchical
* English is the base language
* Portuguese (`pt_BR`) must be supported

---

## Testing Philosophy

### Mandatory Tests

* Every business rule must have tests
* Every Service public method must be covered
* Ledger behavior must be tested thoroughly
* Negative balance scenarios must be tested
* Transfers and timers must be tested atomically

### Test Types

* Unit tests for Services
* Feature tests for API endpoints
* No testing via Controllers only
* Tests must describe behavior, not implementation

### Forbidden

* Skipping tests “temporarily”
* Writing tests only after bugs
* Relying on manual testing for core logic

---

## Documentation & DocBlocks

* Use DocBlocks when intent is not obvious
* Prefer self-explanatory code over excessive comments
* Document:

  * Services
  * Complex calculations
  * Edge cases
* Do not document trivial getters/setters

---

## Wallet & Hour Rules

* Wallets cannot be deleted
* Wallets may be archived
* Clients may have multiple wallets
* One default wallet is created automatically
* Transfers between wallets must be ledger-based
* Timers generate debits only when stopped
* Cancelled timers never affect ledger

---

## Invoice & Billing Rules

* Invoices do not change balances directly
* Payments generate ledger credits
* Invoice status is not the source of truth
* Ledger always wins
* No automatic balance manipulation

---

## Security & Access Control

* Clients see only their own data
* Internal notes are admin-only
* Hidden timers are visible only to creator
* Authorization is enforced via Policies
* Never trust client input for wallet selection

---

## Git & Workflow Rules

* Small, focused commits
* One concern per commit
* Clear commit messages:

  * `feat:`
  * `fix:`
  * `refactor:`
  * `test:`
  * `docs:`
* No mixed refactor + feature commits
* No force-push on shared branches

---

## Explicit Do & Don’t

### Do

* Use Service Layer
* Write tests first when possible
* Respect ledger immutability
* Use translations everywhere
* Think in terms of domain rules

### Don’t

* Store balance in database
* Edit or delete ledger entries
* Add business logic to Controllers
* Skip validation
* Assume “it won’t happen”

---

## Final Guiding Principle

> Facts are immutable.
> State is derived.
> Code must be boring, explicit, and predictable.

Any deviation from these rules must be discussed and justified explicitly.

```
