# Vira — یکپارچه با جهش

## Product and scope
- Read `VIRA-PROJECT-CONTEXT.fa.md` for the full Persian product context; editable source documents are indexed in `docs/product/README.md`.
- Keep implemented behavior distinct from planned requirements. After changing product scope or completing a milestone, update the relevant source documents and regenerate the consolidated context file.
- One Laravel modular monolith: retail ERP, optional CRM, POS and storefront.
- This repository currently contains the foundation, not a completed retail system.
- One business per installation for now. Tenant isolation and subscription billing are not implemented.
- Persian RTL UI, Vazir font, Blade + Livewire + Alpine; retain the approved navy sidebar and violet/blue/rose themes.

## Module ownership
- `Core`: identity, authorization and workspace.
- `Customers`: shared customer identity; it must not depend on CRM.
- `Crm`: optional relationship follow-ups, notes and calls.
- `Catalog`: product identity, variants and pricing.
- `Inventory`: stock movement and reservations.
- `Sales`: orders, invoices, returns and payment state.
- `Pos` and `Storefront`: sales channels; both call Sales actions.
- Keep code in its owning module. Use named actions/contracts across module boundaries, not other modules' controllers or ad hoc stock updates.
- Nwidart manages installation-level module loading. `ModuleAccess` checks configured availability and dependencies. Entitlements per customer organization are future scope.
- Disabling a module must not delete its data. Never introduce public management routes.

## Security and correctness
- All panel routes require authentication, `panel.access`, module availability and the relevant permission.
- Livewire mutation actions must also authorize; hiding a button does not enforce permission.
- No default passwords, public staff registration, production secrets or customer data in commits.
- Monetary amounts must use explicit units and exact arithmetic. No float storage for money.
- Slow optional side effects belong in queued, retry-safe jobs dispatched after database commit.
- Dates are stored in UTC; display in Asia/Tehran with Jalali formatting.
- Do not suppress critical stock or payment errors to make a page appear successful.

## Verification
- `composer check` runs Pint and the PHPUnit suite.
- `npm run build` must pass after asset changes.
- Feature tests use an isolated in-memory SQLite database, never the local working database.
- Test a new behavior before implementing it; include authorization and disabled-module coverage.
- Browser-check responsive layouts and interactions; honor reduced-motion preferences.

## Running
- `composer dev` starts the local server on 127.0.0.1:8017, a queue worker, and Vite.
- For XAMPP/Laragon Apache, document root must be this repository's `public/` folder. See `docs/local-development.md`.
