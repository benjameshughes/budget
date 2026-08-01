# Money Tracker API

Base URL: `https://money.benjh.com/api`

## Authentication

All endpoints (except webhooks) require a Sanctum token via Bearer auth.

```
Authorization: Bearer {token}
Accept: application/json
```

Tokens are managed at `/settings/api` in the web UI.

## Response Format

All responses are JSON. DTOs use camelCase keys. List endpoints return flat arrays. Transactions index is paginated.

Child records (payments, installments, transfers, pots, days, logs) are included when the parent relationship is loaded. Null means not included, empty array means included but none exist.

## Endpoints

### Dashboard

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/dashboard` | Full financial summary with all accounts, debts, bills, and monthly totals |

Returns: `summary` (totals), `pay` (cadence/dates), `connected_accounts`, `savings_accounts`, `credit_cards`, `debts`, `bnpl_purchases`, `active_bills`, `penny_challenge`

### Transactions

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/transactions` | Paginated list (50 per page). Returns `data` + `meta` |
| GET | `/transactions/{id}` | Single transaction with category and feedback |
| POST | `/transactions` | Create a transaction |
| POST | `/transactions/parse` | Parse natural language and create a transaction |
| DELETE | `/transactions/{id}` | Delete a transaction |

**POST /transactions** body:
```json
{
    "name": "Tesco",
    "amount": 45.50,
    "type": "expense",
    "date": "2026-08-01",
    "category_id": 1,
    "description": "Weekly shop"
}
```

**POST /transactions/parse** body:
```json
{
    "text": "£25 at Costa Coffee"
}
```

### Bills

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/bills` | All bills ordered by next due date |
| POST | `/bills` | Create a bill |
| GET | `/bills/{id}` | Single bill with category |
| PUT | `/bills/{id}` | Update a bill |
| DELETE | `/bills/{id}` | Delete a bill |
| POST | `/bills/{id}/paid` | Mark bill as paid, creates expense transaction, advances next due date |
| POST | `/bills/{id}/toggle` | Toggle bill active/inactive |

**POST /bills** body:
```json
{
    "name": "Council Tax",
    "amount": 150.00,
    "cadence": "monthly",
    "next_due_date": "2026-09-01",
    "category_id": 1,
    "interval_every": 1,
    "autopay": true
}
```

**POST /bills/{id}/paid** body:
```json
{
    "paid_date": "2026-08-01",
    "notes": "August payment"
}
```

### Categories

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/categories` | All categories ordered by name |

### Credit Cards

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/credit-cards` | All credit cards with payments and spending |
| POST | `/credit-cards` | Create a credit card |
| GET | `/credit-cards/{id}` | Single card with payments and spending |
| PUT | `/credit-cards/{id}` | Update card details |
| DELETE | `/credit-cards/{id}` | Delete a credit card |
| POST | `/credit-cards/{id}/payments` | Record a payment against the card |
| POST | `/credit-cards/{id}/spending` | Record spending on the card |

Includes computed fields: `currentBalance`, `availableCredit`, `monthlyInterest`, `utilizationPercent`, `isCleared`.

**POST /credit-cards** body:
```json
{
    "name": "Barclaycard",
    "starting_balance": 500.00,
    "credit_limit": 2000,
    "minimum_payment": 25,
    "interest_rate": 21.9
}
```

**POST /credit-cards/{id}/spending** body:
```json
{
    "name": "Amazon",
    "amount": 49.99,
    "date": "2026-08-01",
    "category_id": 3
}
```

### Debts

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/debts` | All debts with payments |
| POST | `/debts` | Create a debt |
| GET | `/debts/{id}` | Single debt with payments |
| PUT | `/debts/{id}` | Update debt details |
| DELETE | `/debts/{id}` | Delete a debt and its payments |
| POST | `/debts/{id}/payments` | Record a payment against the debt |

Includes computed fields: `currentBalance`, `monthlyInterest`, `isCleared`.

**POST /debts** body:
```json
{
    "name": "Car Finance",
    "starting_balance": 5000.00,
    "minimum_payment": 150,
    "interest_rate": 6.9,
    "due_day": 15
}
```

### BNPL

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/bnpl` | All purchases with installments, newest first |
| POST | `/bnpl` | Create a purchase (auto-splits into 4 installments) |
| GET | `/bnpl/{id}` | Single purchase with installments |
| DELETE | `/bnpl/{id}` | Delete a purchase |
| POST | `/bnpl/installments/{id}/paid` | Mark an installment as paid |

Includes computed fields: `remainingBalance`, `isFullyPaid`, `paidInstallmentsCount`.

**POST /bnpl** body:
```json
{
    "merchant": "Currys",
    "total_amount": 399.99,
    "provider": "zilch",
    "fee": 2.50
}
```

### Savings

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/savings` | All savings accounts with transfers |
| POST | `/savings` | Create a savings account |
| GET | `/savings/{id}` | Single account with transfers |
| PUT | `/savings/{id}` | Update account details |
| DELETE | `/savings/{id}` | Delete account and all transfers |
| POST | `/savings/{id}/deposit` | Deposit into the account |
| POST | `/savings/{id}/withdraw` | Withdraw from the account |

Includes computed fields: `currentBalance`, `progressPercentage`.

**POST /savings/{id}/deposit** body:
```json
{
    "amount": 100.00,
    "date": "2026-08-01",
    "notes": "Monthly savings"
}
```

### Penny Challenges

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/penny-challenges` | All challenges with days, newest first |
| POST | `/penny-challenges` | Create a new challenge (auto-generates days) |
| GET | `/penny-challenges/{id}` | Single challenge with all days |
| POST | `/penny-challenges/{id}/deposit` | Mark days as deposited |

Includes computed fields: `totalDays`, `totalPossible`, `totalDeposited`, `totalRemaining`, `depositedCount`, `progressPercentage`, `isComplete`.

**POST /penny-challenges/{id}/deposit** body:
```json
{
    "day_ids": [1, 2, 3, 4, 5]
}
```

### Connected Accounts (Bank)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/accounts` | All connected bank accounts with pots |
| GET | `/accounts/{id}` | Single account with pots |

Balance returned in both pounds (`balance`) and pence (`balancePence`). Read-only - managed via Monzo/Starling OAuth.

### Automation Rules

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/automation-rules` | All rules |
| GET | `/automation-rules/{id}` | Single rule with execution logs |

### Rule Triggers

| Method | URI | Description |
|--------|-----|-------------|
| POST | `/rules/trigger` | Fire all manual trigger rules |
| POST | `/rules/{id}/trigger` | Fire a specific rule |

### Voice

| Method | URI | Description |
|--------|-----|-------------|
| POST | `/voice/transcribe` | Transcribe audio and create a transaction |

### Webhooks (no auth)

| Method | URI | Description |
|--------|-----|-------------|
| POST | `/webhooks/monzo/{account}/{token}` | Monzo webhook receiver |
| POST | `/webhooks/starling/{account}` | Starling webhook receiver |

Verified via HMAC/token, not Sanctum.

## Architecture

- **DTOs** (`app/DataTransferObjects/`) with `HasJsonOutput` trait handle all JSON serialisation. Same DTOs serve web and API.
- **Query classes** (`app/Queries/`) handle all Eloquent. Controllers stay thin.
- **Actions** (`app/Actions/`) handle all mutations. Gate authorisation lives in Actions, ownership checks in controllers.
- **Show endpoints** verify ownership via `throw_unless($model->user_id === $request->user()->id)`.
- **Relationships** are eager loaded in query classes. Model computed methods use collection access (no N+1).
