# AccountSplit — Project Definitions & Roadmap

## What This Project Does

AccountSplit is a bill-splitting web app. Groups of people register expenses paid by someone's credit card. The app calculates the minimal set of money transfers so everyone ends up paying the same fair share.

**Core rule:** if User 1 pays a R$120 bill and User 2 pays a R$60 bill, both split equally among 3 people, each person's fair share is R$60. User 3 owes R$60 and sends it to User 1. User 2 owes nothing (they already paid their share).

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.5 |
| Framework | Symfony (latest stable) |
| ORM | Doctrine ORM + Migrations |
| API | API Platform 3 (REST) |
| Database | PostgreSQL 17 |
| Async | Symfony Messenger (future) |
| Testing | PHPUnit, WebTestCase, PHPStan level 8 |
| Code Style | PSR-12, PHP CS Fixer |
| Frontend | Vue 3 SPA (Vite + Pinia + Vue Router 4) |
| HTTP Client | Axios |
| Infrastructure | Docker Compose, Makefile |
| Currency | BRL only — stored as integer cents (no floats) |

---

## Product Requirements

- **No authentication** — participants are just named people, no login
- **Groups/Events** — users create groups (e.g. "Road trip July"), add named participants, register bills within that group
- **Bills** — each bill has: description, total amount (BRL), date, which participant's card paid it, split type, and which participants are included
- **Split types:**
  - **Equal** (default) — total divided equally; remainder cents distributed to first participants
  - **Percentage** — each participant gets a declared percentage (must sum to 100%)
  - **Custom** — each participant gets a fixed amount (must sum to total)
- **Balance calculation** — show net balance per participant and the minimal set of transfers to settle all debts
- **No settlement tracking** — the app only shows balances; users handle payments outside the app

---

## Repository Layout

```
AccountSplit/
├── backend/                   ← Symfony API
├── frontend/                  ← Vue 3 SPA
├── docker/
│   ├── php/Dockerfile         (multi-stage: base / dev w/ Xdebug / prod)
│   └── nginx/default.conf
├── docker-compose.yml
├── docker-compose.override.yml  (local dev overrides)
├── Makefile
└── PROJECT_DEFINITIONS.md
```

---

## Domain Model

```
Group
  ├── id (UUID v7)
  ├── name
  ├── createdAt
  ├── participants[] → Participant
  └── bills[]       → Bill

Participant
  ├── id (UUID v7)
  ├── name
  └── group → Group

Bill
  ├── id (UUID v7)
  ├── description
  ├── amountCents (int, always positive)
  ├── date
  ├── splitType (equal | percentage | custom)
  ├── paidBy → Participant
  ├── group  → Group
  └── shares[] → BillShare

BillShare
  ├── id (UUID v7)
  ├── bill        → Bill
  ├── participant → Participant
  └── amountCents (int)
```

> Note: the `Group` table is named `` `group` `` with backtick quoting because `group` is a reserved word in PostgreSQL.

---

## Balance Calculation Algorithm

### Step 1 — Net balance per participant
```
For each BillShare in the group:
    participant.net -= share.amountCents      // they owe this amount

For each Bill in the group:
    bill.paidBy.net += bill.amountCents       // they already paid this amount
```

### Step 2 — Minimize transfers (greedy two-pointer)
```
debtors   = participants with net < 0, sorted ascending (most negative first)
creditors = participants with net > 0, sorted descending (most positive first)

while debtors and creditors are non-empty:
    amount = min(|debtor.net|, creditor.net)
    emit Transfer(from=debtor, to=creditor, amount)
    adjust nets; pop participant when net reaches zero
```

Produces at most `n − 1` transfers. Always correct because sum(debts) = sum(credits).

### Example
- Bill 1: R$120, paid by User 1, equal split (3 people) → each owes R$40
- Bill 2: R$60, paid by User 2, equal split (3 people) → each owes R$20

| User | Paid | Share owed | Net |
|---|---|---|---|
| User 1 | R$120 | R$60 | **+R$60** (is owed) |
| User 2 | R$60 | R$60 | **R$0** (settled) |
| User 3 | R$0 | R$60 | **−R$60** (owes) |

**Result:** User 3 → User 1: R$60

---

## Implementation Phases

### Phase 0 — Infrastructure & Tooling
- Docker Compose: `app` (PHP-FPM), `nginx` (port 8080), `db` (PostgreSQL)
- Nginx serves Vue SPA on `/`, proxies `/api` to PHP-FPM
- Symfony skeleton + API Platform + Doctrine + UID + dev tools
- Vue 3 bootstrap with Vite, dev proxy `/api → localhost:8080`
- Makefile: `up`, `down`, `shell`, `test`, `migrate`, `migrate-diff`, `stan`, `lint`, `cs-fix`, `front-install`, `front-dev`, `front-build`
- Verify: `make up` → `GET /api` returns API Platform JSON

### Phase 1 — Domain Entities & Database
- All four entities: Group, Participant, Bill, BillShare
- `SplitType` enum
- UUID v7 generation via `prePersist` event subscriber
- Repositories with JOIN FETCH queries (avoid N+1)
- Doctrine migrations

### Phase 2 — SplitCalculator Service (TDD)
- Pure service, no I/O, fully unit-tested before any API code
- `SplitDefinition` and `ShareResult` value objects
- Equal / Percentage / Custom algorithms with cent-accurate rounding
- `InvalidSplitDefinitionException` for all validation failures
- `BillSharePersister` bridges calculator to Doctrine (does not flush)

### Phase 3 — BalanceCalculator Service (TDD)
- Pure service taking a hydrated `Group` entity
- `ParticipantBalance`, `Transfer`, `GroupBalance` value objects
- Net balance computation + greedy minimization algorithm
- Test builders in `tests/Builder/` for in-memory entity construction

### Phase 4 — API Platform CRUD Endpoints
- Nested REST resources: `/api/groups/{id}/participants`, `/api/groups/{id}/bills`
- `BillInput` DTO + `BillStateProcessor` for write operations (runs SplitCalculator)
- Symfony Validator constraints including cross-field (participants must belong to group)
- CORS via `nelmio/cors-bundle`

### Phase 5 — Balance Endpoint
- `GET /api/groups/{id}/balance`
- `GroupBalanceStateProvider` loads bills via JOIN FETCH, calls BalanceCalculator, maps to output DTOs
- Response includes `amountCents` (int) and `formattedAmount` (BRL string via PHP `NumberFormatter`)

### Phase 6 — Vue 3 Frontend
- Routes: `/` (groups list), `/groups/:id` (tabs: Participants | Bills | Balance)
- Pinia store — all business logic here; components are presentation only
- `BillForm` — handles all split types with live sum validation
- Currency formatting via `Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })`

### Phase 7 — Quality Gates
- PHPStan level 8 + `phpstan-symfony` + `phpstan-doctrine`
- PHP CS Fixer with `@Symfony` + `declare_strict_types`
- GitHub Actions CI: install → migrate → test → stan → lint

### Phase 8 — E2E Smoke & Polish
- Full manual walkthrough of the example scenario (see Balance Calculation Example above)
- README with `make up`, API docs URL, Swagger UI URL

---

## Phase Dependency Order

```
Phase 0 (Infrastructure)
  └── Phase 1 (Entities)
        ├── Phase 2 (SplitCalculator)
        │     └── Phase 3 (BalanceCalculator)
        │           └── Phase 5 (Balance API endpoint)
        └── Phase 4 (CRUD API endpoints)
              └── Phase 6 (Vue SPA)
Phase 7 (Quality gates) — runs alongside from Phase 1 onward
Phase 8 (Polish) — last
```

---

## Key Architectural Decisions

| Decision | Reason |
|---|---|
| `BillShare` materialized at write time | Cheap reads; preserves historical accuracy if participants/percentages change later |
| UUID v7 (time-sortable) | Better B-tree index locality in PostgreSQL vs. random UUIDs |
| `amountCents` as integer | Eliminates floating-point rounding errors in financial calculations |
| Participants not authenticated | Deliberate product choice; UUID is 128-bit unguessable but not secret |
| DTO + Processor for Bill writes | Bill creation has side effects (SplitCalculator) and cross-field validation — not a plain entity persist |
| BalanceCalculator takes a `Group` entity | Keeps it a pure domain service, easy to unit test without database |
| BillRepository uses JOIN FETCH | Single query loads all bills + shares + payers — avoids N+1 on balance endpoint |