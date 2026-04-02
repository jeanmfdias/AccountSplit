# AccountSplit

A bill-splitting web app for groups. Register expenses paid by someone's credit card and automatically calculate the minimal set of money transfers so everyone pays their fair share.

> **Core rule:** if User 1 pays a R$120 bill and User 2 pays a R$60 bill, both split equally among 3 people, each person's fair share is R$60. User 3 sends R$60 to User 1. User 2 owes nothing — they already covered their share.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.5 |
| Framework | Symfony (latest stable) |
| ORM | Doctrine ORM + Migrations |
| API | API Platform 3 (REST) |
| Database | PostgreSQL 17 |
| Frontend | Vue 3 SPA (Vite + Pinia + Vue Router 4) |
| Infrastructure | Docker Compose + Makefile |
| Currency | BRL only (stored as integer cents) |

---

## Getting Started

### Prerequisites
- Docker + Docker Compose
- Node.js (for frontend development)

### Run locally

```bash
# Start all services (API + DB)
make up

# API available at:
http://localhost:8080/api

# API docs (Swagger UI):
http://localhost:8080/api/docs

# Start Vue dev server (separate terminal):
make front-install
make front-dev
# Frontend at: http://localhost:5173
```

### Common commands

```bash
make up              # Start Docker services
make down            # Stop Docker services
make shell           # Open shell inside PHP container
make test            # Run all tests
make test-unit       # Run unit tests only
make test-integration# Run integration tests only
make test-feature    # Run feature/HTTP tests only
make migrate         # Run pending DB migrations
make migrate-diff    # Generate new migration from entity changes
make migrate-fresh   # Drop DB, recreate, and run all migrations
make stan            # Run PHPStan (level 8)
make lint            # Check code style (dry-run)
make cs-fix          # Fix code style
make front-dev       # Start Vue dev server
make front-build     # Build Vue for production
```

---

## Project Structure

```
AccountSplit/
├── backend/                   ← Symfony API (PHP 8.5)
│   ├── src/
│   │   ├── Entity/            ← Doctrine entities
│   │   ├── Enum/              ← SplitType enum
│   │   ├── Repository/        ← Doctrine repositories
│   │   ├── Service/           ← SplitCalculator, BalanceCalculator
│   │   ├── ApiResource/       ← API Platform DTOs
│   │   ├── State/             ← API Platform processors & providers
│   │   └── EventSubscriber/   ← UUID generation
│   └── tests/
│       ├── Unit/              ← Pure unit tests
│       ├── Integration/       ← DB integration tests
│       └── Feature/           ← HTTP/API tests
├── frontend/                  ← Vue 3 SPA
│   └── src/
│       ├── api/               ← Axios API client
│       ├── stores/            ← Pinia stores
│       ├── views/             ← Route views
│       └── components/        ← UI components
├── docker/
│   ├── php/Dockerfile
│   └── nginx/default.conf
├── docker-compose.yml
├── Makefile
└── PROJECT_DEFINITIONS.md     ← Full project spec & architecture decisions
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
  ├── id, name
  └── group → Group

Bill
  ├── id, description, amountCents, date
  ├── splitType (equal | percentage | custom)
  ├── paidBy → Participant   ← the card owner who paid
  ├── group  → Group
  └── shares[] → BillShare

BillShare
  ├── bill → Bill
  ├── participant → Participant
  └── amountCents             ← this participant's portion
```

---

## API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/groups` | List all groups |
| `POST` | `/api/groups` | Create a group |
| `GET` | `/api/groups/{id}` | Get group details |
| `PATCH` | `/api/groups/{id}` | Update a group |
| `DELETE` | `/api/groups/{id}` | Delete a group |
| `GET` | `/api/groups/{id}/participants` | List participants |
| `POST` | `/api/groups/{id}/participants` | Add a participant |
| `PATCH` | `/api/groups/{id}/participants/{pid}` | Update participant |
| `DELETE` | `/api/groups/{id}/participants/{pid}` | Remove participant |
| `GET` | `/api/groups/{id}/bills` | List bills |
| `POST` | `/api/groups/{id}/bills` | Add a bill |
| `PATCH` | `/api/groups/{id}/bills/{bid}` | Update a bill |
| `DELETE` | `/api/groups/{id}/bills/{bid}` | Delete a bill |
| `GET` | `/api/groups/{id}/balance` | Get balances & transfers |

---

## Balance Calculation

The balance endpoint returns:
1. **Net balance per participant** — positive means they are owed money, negative means they owe money
2. **Minimal transfers** — the smallest set of payments to settle all debts

### Example

| Bill | Amount | Paid by | Split |
|---|---|---|---|
| Hotel | R$300 | Alice | Equal (3 people) |
| Dinner | R$100 | Bob | Custom: Alice R$50, Bob R$30, Carlos R$20 |

**Result:**
| Participant | Paid | Owes | Net |
|---|---|---|---|
| Alice | R$300 | R$150 | **+R$150** |
| Bob | R$100 | R$130 | **-R$30** |
| Carlos | R$0 | R$120 | **-R$120** |

**Transfers:** Bob → Alice R$30 · Carlos → Alice R$120

---

## Implementation Progress

- [x] **Phase 0** — Docker, Nginx, Makefile infrastructure
- [x] **Phase 0** — Symfony skeleton + all packages installed
- [ ] **Phase 1** — Domain entities (Group, Participant, Bill, BillShare), repositories, migrations
- [ ] **Phase 2** — SplitCalculator service + unit tests (equal / percentage / custom)
- [ ] **Phase 3** — BalanceCalculator service + unit tests (net balances + minimal transfers)
- [ ] **Phase 4** — API Platform CRUD endpoints + feature tests
- [ ] **Phase 5** — Balance endpoint (`GET /api/groups/{id}/balance`) + feature tests
- [ ] **Phase 6** — Vue 3 SPA (groups, participants, bills, balance view)

---

## Development Notes

- All PHP/Composer commands run **inside Docker** (`make shell`)
- TDD: write the failing test first, then implement
- PHPStan level 8 — no suppressed errors without justification
- `amount_cents` is always an integer — no floats in financial calculations
- UUID v7 primary keys (time-sortable for better DB index locality)
- `"group"` table is quoted — reserved word in PostgreSQL