# Vira — یکپارچه با جهش

## Product and scope
- Read `VIRA-PROJECT-CONTEXT.fa.md` for the full Persian product context; editable source documents are indexed in `docs/product/README.md`.
- Keep implemented behavior distinct from planned requirements. After changing product scope or completing a milestone, update the relevant source documents and regenerate the consolidated context file.
- One Laravel modular monolith: retail ERP, optional CRM, POS and storefront.
- This repository has a retail base plus catalog/staff, stock-document and reporting upgrades: product images/variants, owner-managed staff access, stock ledger, single-SKU purchase receipts/suppliers, atomic warehouse transfers, manual paid sales/POS and partial returns with manually confirmed refunds, filtered gross/refund/net reports with event CSV and permission-filtered low-stock alerts, CRM, widgets and a public catalog. Online checkout, supplier payments, profit/cost accounting and full accounting are not implemented. See docs/retail-reports.md and previous milestone reports.
- One business per installation for now. Tenant isolation and subscription billing are not implemented.
- Persian RTL UI, local Vazir font, Blade + Livewire + Alpine. The approved V4 panel uses a fixed navy/teal identity, one light theme, and a right-hand sidebar. Do not restore palette swatches or dark-mode toggles.
- Keep a calm, accessible, low-density V4 dashboard with three primary metrics, a true sales chart, due follow-ups, and recent invoices. More information belongs behind progressive disclosure, never fabricated demo data. See docs/panel-v4.md.

## Module ownership
- `Core`: identity, authorization and workspace.
- `Customers`: shared customer identity plus profile UI composed through optional Sales/Crm actions; identity and installation availability must not depend on CRM. See docs/customer-profiles.md.
- `Crm`: optional relationship follow-ups, notes and calls.
- `Catalog`: product identity, variants and pricing.
- `Inventory`: stock movement, physical purchase receipts/supplier identity, warehouse transfers and future reservations; purchase orders/accounting are not implemented.
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
- `composer check` runs Pint and the PHPUnit suite. `npm run test:ui` tests chart presentation logic.
- `npm run build` must pass after asset changes.
- Feature tests use an isolated in-memory SQLite database, never the local working database.
- Test a new behavior before implementing it; include authorization and disabled-module coverage.
- Browser-check responsive layouts and interactions; honor reduced-motion preferences.

## Running
- `composer dev` starts the local server on 127.0.0.1:8017, a queue worker, and Vite.
- For XAMPP/Laragon Apache, document root must be this repository's `public/` folder. See `docs/local-development.md`.


## Current customer UI & five-accent design (2026-11-10)

Use `docs/customers-360.md` for the reviewed customer directory, profile, demo-only seeding guard and semantic five-accent palette. Preserve customer IDs, historical Sales/Crm relationships, and module/permission gates. Never run `DemoRetailSeeder` against the main database. The V4 panel remains light-only; Storefront is independently styled.

## Catalog V2 (2026-11-11)

The approved Catalog V2 visual contract and module behavior are documented in `docs/catalog-v2.md`. Numeric-only *new* SKUs, backward-compatible historic SKU edits, grouped catalog rows, server-filtered incremental no-pagination list and a single document scroll are deliberate. Catalog never directly changes Inventory stock. Keep related tests green.

## Persian validation and error feedback (2026-11-11)

All public-facing validation messages use local `lang/fa/validation.php`, field labels, and Persian HTTP fallback views. For new forms map array keys to Persian names; no technical or English exception text in UI. See `docs/persian-feedback.md`. Production stays `APP_DEBUG=false` and must keep original exceptions logged.

## Catalog auto SKU convention (2026-11-11)

All NEW product/group codes are allocated server-side on SAVE by category: 3-digit stable category prefix + 6-digit sequence; variant SKUs append 2 digits. No random, user-entered, or client-controlled new codes. Allocation and insert must share a DB transaction and lock the category counter. Historical codes stay unchanged on edit. See docs/catalog-v2.md and tests/Feature/CatalogAutomaticSkuTest.php.

## Legacy demo SKU & barcode repair (2026-11-11)

Historical product IDs and invoice SKU snapshots are immutable. Only identifiable, unpublished fixture demo products can be renumbered through the guarded local-only `vira:catalog:repair-codes --apply`; keep their former code in `catalog_code_aliases` and update demo import alias resolution. Existing real/unknown SKUs and non-empty barcodes remain unchanged. New/missing barcodes use a private-use EAN-13 prefix `20`, not a global GS1 GTIN. Preview by default; require consistent SQLite backup before apply. See docs/catalog-v2.md.
