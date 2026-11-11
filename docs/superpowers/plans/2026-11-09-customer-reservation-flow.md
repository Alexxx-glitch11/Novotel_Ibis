# Alur Reservasi Pelanggan Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a responsive customer reservation journey from outlet selection through customer details, schedule-based table selection, a ten-minute hold, and reservation confirmation.

**Architecture:** Keep the current outlet route and render a staged Blade form. Add focused reservation/hold actions that validate input and use database transactions to check schedule conflicts, create holds, convert holds, and persist reservations. Use existing Tailwind styles and translations.

**Tech Stack:** Laravel, PHP, Blade, MySQL, Tailwind CSS, existing Eloquent models.

**Spec:** `docs/superpowers/specs/2026-11-09-customer-reservation-flow-design.md`

## Global Constraints

- The hold lifetime is 10 minutes.
- Guest count is 1–50.
- Booking status at confirmation is `pending_payment`.
- Reservation duration defaults to 120 minutes through configuration until operations confirms a standard.
- Reservation input uses `Asia/Jakarta`; slots that cross midnight are rejected because the current schema stores no end date.
- Use existing Blade/Tailwind patterns and Indonesian/English language files.
- Do not implement payment, QR issuance, or authentication in this scope.
- Do not add or run tests unless requested; verify syntax and frontend production build instead.

## Review Focus

- A table whose capacity is smaller than the guest count must not be selectable or reservable.
- Active holds and pending/confirmed reservations with overlapping times must make a table unavailable.
- Expired holds must stop blocking a table.
- A second concurrent hold request for the same table/time must fail without creating a second active hold.
- Tampered outlet, area, and table IDs must be rejected server-side.

---

### Task 1: Add booking flow configuration and routes

**Files:**
- Modify: `config/app.php` or add focused `config/reservation.php`
- Modify: `.env.example`
- Modify: `routes/web.php`

- [x] Add `reservation.duration_minutes` defaulting to `120` and `reservation.hold_minutes` defaulting to `10`.
- [x] Add named POST routes for creating a hold and confirming a reservation, scoped to an outlet code.
- [x] Confirm `php artisan route:list --path=reservasi` shows the intended customer routes.

### Task 2: Build schedule-aware table availability

**Files:**
- Create: `app/Services/ReservationAvailability.php`
- Modify: `app/Http/Controllers/TableSelectionController.php`

- [x] Implement `availableTables(int $outletId, int $areaId, string $date, string $startTime, string $endTime, int $guestCount)` and exclude inactive, undersized, actively held, and conflicting reserved tables.
- [x] Render available tables when the schedule step is submitted, preserving validated customer and schedule data across steps.
- [x] Check PHP syntax for the service and controller.

### Task 3: Create transactional table holds

**Files:**
- Modify: `app/Http/Controllers/TableSelectionController.php`
- Modify: `resources/views/reservasi/pilih-meja.blade.php`

- [x] Add a hold action that verifies the outlet/area/table relationship and capacity, rechecks conflicts inside a database transaction, and creates a 10-minute active hold plus hold item.
- [x] Use a hashed session token and reject a second active hold for the session.
- [x] Show a review step with hold expiry and the selected schedule/table.
- [x] Check PHP syntax for the controller.

### Task 4: Confirm and persist the reservation

**Files:**
- Modify: `app/Http/Controllers/TableSelectionController.php`
- Modify: `app/Models/Reservation.php` only if required for relationships
- Modify: `app/Models/ReservationHold.php` only if required for scoped relations

- [x] Add a confirmation action that locks and validates the session's active hold, checks expiry, creates a unique reservation code, persists validated customer/schedule data with status `pending_payment`, attaches the held table, and marks the hold `converted` in one transaction.
- [x] Redirect to a confirmation page that shows reservation code and next-step payment status without claiming payment is complete. Record creation in `audit_logs` without customer contact values.
- [x] Check PHP syntax for changed PHP files.

### Task 5: Polish the responsive customer journey

**Files:**
- Modify: `resources/views/reservasi/pilih-meja.blade.php`
- Create or modify: `resources/views/reservasi/konfirmasi.blade.php`
- Modify: `lang/id/ui.php`
- Modify: `lang/en/ui.php`

- [x] Add step indicator, grouped data fields, clear validation feedback, responsive summary, and a map/list table selector using existing Tailwind conventions.
- [x] Add complete Indonesian and English copy for the new journey.
- [x] Run `npm run build` and verify Blade syntax with `php artisan view:cache`; then clear compiled views with `php artisan view:clear`.

### Task 6: Requirement review

- [x] Check the design criteria and Review Focus items against the implementation.
- [x] Inspect the final diff and report remaining limitations: provisional 120-minute duration, no payment integration or payment deadline, and no same-day end-time overflow support.

### Task 7: Match the video order and confirmation flow

**Files:**
- Modify: `app/Http/Controllers/TableSelectionController.php`
- Modify: `routes/web.php`
- Create: `resources/views/reservasi/menu.blade.php`
- Create: `resources/views/reservasi/review.blade.php`
- Modify: `resources/views/reservasi/konfirmasi.blade.php`
- Modify: `lang/id/ui.php`
- Modify: `lang/en/ui.php`

- [x] Keep the mockup's customer details, schedule, area tabs, and floor-plan table selection together on the reservation page.
- [x] After the hold, allow menu quantities to be selected and retain the hold countdown.
- [x] Show a final order review with customer, location/time, menu lines, service charge, tax, and grand total.
- [x] Persist menu item snapshots and totals with the reservation in the existing transaction; reject unavailable items and ignore client-sent prices.
- [ ] Check PHP syntax, Blade compilation, and frontend production build.

## Execution Notes

- Implemented in the existing `feature/manager-meja-denah` checkout because the requested customer flow depends on uncommitted manager/floor-plan work already present in this workspace.
- Automated tests were not added or run, per the workspace instruction. PHP lint, route registration, Blade compilation, and Vite production build were run instead.
- Database migrations and browser-based reservation submissions were not run.
