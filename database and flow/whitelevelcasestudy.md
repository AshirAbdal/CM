# White-Label System — Lead Flow Case Studies

## Database Design Decisions

### `leads_relation_table` — why `source` and `entry_type` were removed

`CCP_id` already identifies which tenant the relationship belongs to — `source` was redundant.

`entry_type` ENUM was removed because:
1. The UNIQUE constraint on `L_id + CCP_id` means one row per lead per tenant. A single ENUM value cannot represent multiple interaction types (e.g. the same lead subscribes then later places an order — the ENUM would be stale).
2. The relationship type is already determinable from the `orders` table — no separate column needed.

```
leads_relation_table
─────────────────────────────────────
LR_id               PK
L_id                FK → leads
CCP_id              FK → clickdigim_customers_profile
name                varchar   (per-tenant profile copy)
phone               varchar   nullable
country             varchar   nullable
legal_business_name varchar   nullable
website_url         varchar   nullable
created_at          datetime
updated_at          datetime
```

To determine what kind of relationship a lead has with a tenant, query `orders`:
- Orders exist for this LR_id → they purchased something
- No orders exist → they only subscribed or submitted a contact form

---

## Case 1 — New Lead Buying Featured Blog (email not in leads)

**Scenario:** A user submits the Featured Blog form for the first time.
Their email does not exist in the `leads` table.

**Step-by-step flow:**

```
User submits Featured Blog form
        │
        ▼
1. CHECK leads WHERE email = 'john@example.com'
        │
        ▼  NOT FOUND
2. INSERT into leads
        email = "john@example.com"   ← identity only (global unique key)
        │
        ▼
3. INSERT into leads_relation_table
        L_id                = (new lead id)
        CCP_id              = (tenant id)
        name                = "John"
        phone               = "+1 555 0000"
        country             = "United States"
        website_url         = "https://johnbiz.com"
        legal_business_name = null   ← not collected by Featured Blog form
        │  → LR_id = (new)
        ▼
4. INSERT into cart
        LR_id    = (from step 3)
        CCP_id   = (tenant id)
        status   = 'active'
        → cart_id = (new)
        │
        ▼
5. INSERT into cart_items
        cart_id      = (from step 4)
        service_id   = (Featured Blog service id)
        quantity     = 1
        unit_price   = 250.00
        item_payload = {
          "suggested_keywords": "digital marketing USA",
          "products": "Featured Blog × 1",
          "quantity": 1
        }
        │
        ▼
6. User reviews cart → clicks "Proceed to Checkout"
        │
        ▼
7. INSERT into orders
        LR_id         = (from step 3)
        total_amount  = 250.00
        currency_code = 'USD'
        status        = 'pending'
        → o_id = (new)
        │
        ▼
8. INSERT into order_items   ← copied from cart_items
        o_id         = (from step 7)
        LR_id        = (from step 3)
        service_id   = (Featured Blog service id)
        quantity     = 1
        unit_price   = 250.00
        subtotal     = 250.00
        status       = 'pending'
        item_payload = (copied from cart_items.item_payload)
        │
        ▼
9. UPDATE cart.status = 'converted'
        │
        ▼
10. INSERT into invoice
        LR_id          = (from step 3)
        order_id       = (from step 7)
        invoice_number = 'INV-2026-0001'   ← generated
        total_amount   = 250.00
        issue_date     = today
        status         = 'draft'
        UPDATE invoice.status = 'sent'     ← emailed to client
        │
        ▼
11. User pays via PayPal / Stripe
        │
        ▼
12. Payment captured
        │
        ▼
13. INSERT into payments
        invoice_id      = (from step 10)
        LR_id           = (from step 3)
        payment_date    = now()
        amount          = 250.00
        payment_method  = 'paypal'
        transaction_ref = 'CAP-xxx'
        status          = 'paid'
        │
        ▼
14. UPDATE invoice.status      = 'paid'
    UPDATE orders.status       = 'paid'
    UPDATE order_items.status  = 'paid'
        │
        ▼
    INSERT into notification
        LR_id   = (from step 3)
        date    = today
        time    = now()
        type    = 'order_placed'
        message = "New order: Featured Blog × 1 from John"
        is_read = 0
    INSERT into notification
        LR_id   = (from step 3)
        date    = today
        time    = now()
        type    = 'payment_received'
        message = "Payment of $250.00 received from John (Featured Blog)"
        is_read = 0
        │
        ▼
15. Thank you page shown
    Confirmation + invoice email sent to client
```

**Result:**
- `leads` → 1 new row
- `leads_relation_table` → 1 new row
- `cart` → 1 new row (status = `converted` after checkout)
- `cart_items` → 1 new row
- `orders` → 1 new row
- `order_items` → 1 new row
- `invoice` → 1 new row (status = `paid`)
- `payments` → 1 new row

---

## Case 2 — Email Already Exists in Leads (returning lead)

Same user, same service (Featured Blog), but their email is already in the `leads` table
because they either bought another service before OR submitted from a different tenant site.

### Sub-case A: Same tenant (same `CCP_id`)

The person already bought something from this same franchise/tenant previously.

```
1. CHECK leads WHERE email = 'john@example.com'
        │
        ▼  FOUND → L_id = 5 (existing)

2. UPDATE leads_relation_table SET
        name        = "John"           ← this tenant's copy only — other tenants unaffected
        phone       = "+1 555 0000"
        website_url = "https://johnbiz.com"
   WHERE LR_id = 12
   (profile is per-tenant — leads row is email-only and never updated here)
        │
        ▼
3. CHECK leads_relation_table WHERE L_id = 5 AND CCP_id = (this tenant)
        │
        ▼  FOUND → LR_id = 12 (reuse existing row)

4. CHECK cart WHERE LR_id = 12 AND status = 'active'
        │
        ├── FOUND → add new item to existing cart
        │
        └── NOT FOUND → INSERT into cart
                LR_id  = 12
                CCP_id = (this tenant)
                status = 'active'
                → cart_id = (new)
        │
        ▼
5. INSERT into cart_items
        cart_id      = (from step 4)
        service_id   = (Featured Blog service id)
        quantity     = 1
        unit_price   = 250.00
        item_payload = { ...service-specific data for Featured Blog... }
        │
        ▼
→ Follows Steps 6–15 from Case 1
  (checkout → order → invoice → payment → thank you)
```

**Result:**
- `leads` → 1 row (new email-only row, or already existed — never updated)
- `leads_relation_table` → 1 row (new or reused)
- `cart` → 1 new or reused row (status = `converted` after checkout)
- `cart_items` → 1 new row
- `orders` + `order_items` + `invoice` + `payments` → new chain under the existing LR_id

---

### Sub-case B: Different tenant (different `CCP_id`)

The person already exists in the system (submitted from another franchise site),
now buying from a **different** tenant's site for the first time.

```
1. CHECK leads WHERE email = 'john@example.com'
        │
        ▼  FOUND → L_id = 5 (existing)

2. leads row already exists — email matches, no profile update needed
   (leads stores email only — profile fields live in leads_relation_table per tenant)

3. CHECK leads_relation_table WHERE L_id = 5 AND CCP_id = (NEW tenant)
        │
        ▼  NOT FOUND

4. INSERT into leads_relation_table
        L_id                = 5               ← same existing lead
        CCP_id              = (new tenant)    ← different tenant
        name                = "John"          ← this tenant's own copy of the profile
        phone               = "+1 555 0000"
        country             = "United States"
        website_url         = "https://johnbiz.com"
        legal_business_name = null
        → LR_id = 27 (new row)
        │
        ▼
5. INSERT into cart
        LR_id    = 27
        CCP_id   = (new tenant)
        status   = 'active'
        → cart_id = (new)
        │
        ▼
6. INSERT into cart_items
        cart_id      = (from step 5)
        service_id   = (Featured Blog service id)
        quantity     = 1
        unit_price   = 250.00
        item_payload = { ...service-specific data for Featured Blog... }
        │
        ▼
→ Follows Steps 6–15 from Case 1
  (checkout → order → invoice → payment → thank you)
```

**Result:**
- `leads` → still 1 row (same person — email-only, never updated)
- `leads_relation_table` → 1 NEW row (new tenant relationship)
- `cart` + `cart_items` → 1 new row each (scoped to new tenant's LR_id)
- `orders` + `order_items` + `invoice` + `payments` → new chain belonging to the new tenant only

Both tenants operate independently through their own `LR_id`. Neither can see the other tenant's data.

---

## Improvement to Implement — Duplicate Pending Order Guard

**Problem:** In Case 2 Sub-case A, if the same person submits the Featured Blog form
twice in a row (page refresh, back button, slow connection retry) before completing payment,
you will get two pending orders for the same lead + same service + same tenant.

Your existing backend already handles this via `updateOrCreate` in `FeaturedBlogIntentController`.
You must mirror this logic at the **order layer** as well:

**Rule before creating a new order:**

```
CHECK orders
  WHERE LR_id     = (existing LR_id for this lead + tenant)
  AND   status    = 'pending'
  JOIN  order_items ON orders.o_id = order_items.o_id
  WHERE order_items.service_id = (Featured Blog service id)

→ IF EXISTS:
    UPDATE order_items SET
        item_payload = (new submitted payload)
        unit_price   = (latest price)
        subtotal     = (latest total)
    UPDATE orders SET
        total_amount = (latest total)
        updated_at   = now()

→ IF NOT EXISTS:
    INSERT new order + order_item (normal flow)
```

**This prevents:**
- Duplicate pending records in `orders` and `order_items`
- Admin seeing ghost unpaid orders
- Duplicate payment captures if the user completes payment on one of the duplicates

**This does NOT affect paid orders** — once `status = 'paid'` the guard skips it,
so the person can legitimately buy the same service again and get a fresh order.

---

## Case 3 — Paid Appointment Booking Flow

Covers the 4 paid appointment service types only. Case 4 covers the free appointment.
The only difference between the 4 paid services is `service_id`, `unit_price`, and `duration_minutes`.
All 5 appointment services (including free) share the same slot availability logic.

| Service               | service_id | unit_price | duration_minutes | appointment_type |
|-----------------------|------------|------------|------------------|------------------|
| Free Appointment      | 5          | $0.00      | 30               | free             |
| Quick Consultation    | 1          | $74.99     | 30               | paid             |
| Standard Meeting      | 2          | $100.00    | 60               | paid             |
| Extended Session      | 3          | $150.00    | 90               | paid             |
| Full Strategy Session | 4          | $200.00    | 120              | paid             |

---

### Critical rule — slot check happens FIRST, before touching leads or orders

The system calls `availabilityService->getAvailableSlots(date, service_id)` and
hard-stops if the slot is taken. No lead row, no order row, nothing is written
until the slot is confirmed free.

---

### Phase 1 — Booking + Deposit Payment

```
User picks service + date + time
        │
        ▼
STEP 1: CHECK slot availability
        appointments WHERE appointment_date = '2026-07-15'
                       AND appointment_time = '10:00'
                       AND service_id = 2
                       AND status != 'cancelled'
        │
        ├── SLOT TAKEN → stop, return error, show alternative slots
        │
        ▼  SLOT FREE → continue
        │
STEP 2: CHECK leads WHERE email = 'john@example.com'
        │
        ├── NOT FOUND → INSERT into leads
        │               email = 'john@example.com'   ← identity only
        │               → L_id = (new)
        │
        └── FOUND → leads row exists (email matches) — no update needed
                    → L_id = (existing)
        │
        ▼
STEP 3: CHECK leads_relation_table WHERE L_id = ? AND CCP_id = (tenant)
        │
        ├── NOT FOUND → INSERT leads_relation_table
        │                 L_id, CCP_id,
        │                 name, phone, country, website_url, legal_business_name
        │                 → LR_id = (new)
        │
        └── FOUND → UPDATE leads_relation_table SET
                      name, phone, country, website_url
                  WHERE LR_id = (existing)
                  → LR_id = (existing)
        │
        ▼
STEP 4: INSERT into appointments   ← reserves the slot immediately
        LR_id             = (from step 3)
        service_id        = 2                    ← Standard Meeting
        o_id              = null                 ← filled after payment
        appointment_date  = '2026-07-15'
        appointment_time  = '10:00'
        duration_minutes  = 60                   ← from services table
        timezone          = 'America/New_York'
        notes             = 'First strategy call'
        status            = 'pending'            ← not confirmed yet
        payment_reference = null                 ← set on deposit payment
        amount_paid       = null
        after_amount      = null
        balance_token     = null
        → apt_id = 42
        │
        ▼
STEP 5: INSERT into cart
        LR_id    = (from step 3)
        CCP_id   = (tenant id)
        status   = 'active'
        → cart_id = (new)
        │
        ▼
STEP 6: INSERT into cart_items
        cart_id      = (from step 5)
        service_id   = 2              ← Standard Meeting
        quantity     = 1
        unit_price   = 100.00         ← full service price
        item_payload = {
          "appointment_id":   42,
          "appointment_date": "2026-07-15",
          "appointment_time": "10:00",
          "timezone":         "America/New_York",
          "notes":            "First strategy call",
          "duration_minutes": 60,
          "deposit_pct":      50
        }
        │
        ▼
STEP 7: User reviews cart → clicks "Proceed to Checkout"
        │
        ▼
STEP 8: INSERT into orders
        LR_id         = (from step 3)
        total_amount  = 100.00
        currency_code = 'USD'
        status        = 'pending'
        → o_id = 88
        │
        ▼
STEP 9: INSERT into order_items   ← copied from cart_items
        o_id         = 88
        LR_id        = (from step 3)
        service_id   = 2
        quantity     = 1
        unit_price   = 100.00
        subtotal     = 100.00
        status       = 'pending'
        item_payload = (copied from cart_items)
        │
        ▼
STEP 10: UPDATE cart.status = 'converted'
         UPDATE appointments SET o_id = 88   ← link order back to appointment
        │
        ▼
STEP 11: User pays deposit via PayPal / Stripe
         Deposit = unit_price × deposit_pct = 100.00 × 50% = $50.00
        │
        ▼
STEP 12: Payment confirmed → fulfillAppointment() runs
         UPDATE appointments SET
           paypal_payment_status  = 'paid'
           paypal_order_id        = 'PAY-xxx'
           paypal_capture_id      = 'CAP-xxx'
           amount_paid            = 50.00          ← deposit amount
           after_amount           = 50.00          ← full_price(100) - deposit(50)
           paid_at                = now()
           payment_reference      = 'APT-42'
           overall_payment_status = 'deposit_paid'
        │
        ▼
STEP 13: INSERT into invoice
          LR_id          = (from step 3)
          order_id       = 88
          invoice_number = 'INV-APT-42-DEP'   ← generated (deposit invoice)
          total_amount   = 50.00
          issue_date     = today
          status         = 'draft'
          UPDATE invoice.status = 'sent'       ← emailed to client
        │
        ▼
STEP 14: INSERT into payments
          invoice_id      = (from step 13)
          LR_id           = (from step 3)
          payment_date    = now()
          amount          = 50.00
          payment_method  = 'paypal'
          transaction_ref = 'CAP-xxx'
          status          = 'paid'
        │
        ▼
STEP 15: UPDATE invoice.status      = 'paid'
         UPDATE orders.status       = 'paid'
         UPDATE order_items.status  = 'paid'
        │
        ▼
         INSERT into notification
             LR_id   = (from step 3)
             date    = today
             time    = now()
             type    = 'order_placed'
             message = "New appointment booking: Standard Meeting on 2026-07-15"
             is_read = 0
         INSERT into notification
             LR_id   = (from step 3)
             date    = today
             time    = now()
             type    = 'payment_received'
             message = "Deposit of $50.00 received (Standard Meeting)"
             is_read = 0
        │
        ▼
STEP 16: Thank you page shown
         Deposit invoice email sent to client
```

**Result after Phase 1:**
- `leads` → 1 row (new email-only row, or already existed)
- `leads_relation_table` → 1 row (new with profile fields, or updated profile)
- `appointments` → 1 new row (slot reserved, status = pending)
- `cart` → 1 new row (status = `converted`)
- `cart_items` → 1 new row
- `orders` → 1 new row
- `order_items` → 1 new row with appointment payload
- `invoice` → 1 new row (deposit invoice, status = `paid`)
- `payments` → 1 new row (deposit)

---

### Phase 2 — Admin Workflow (approve / reject)

```
Admin reviews pending appointment
        │
        ├── REJECT →
        │     UPDATE appointments SET status = 'cancelled'
        │     DELETE Google Calendar event (if google_event_id exists)
        │     Slot is now FREE again for others to book
        │     ── REFUND (paid appointments only, is_paid = true) ──
        │     Issue refund via PayPal / Stripe API for amount_paid
        │     UPDATE payments SET status       = 'refunded'
        │     UPDATE orders.status             = 'refunded'
        │     UPDATE order_items.status        = 'cancelled'
        │     UPDATE invoice.status            = 'cancelled'
        │     Rejection + refund confirmation email sent to client
        │
        └── APPROVE →
              UPDATE appointments SET status = 'confirmed'
              │
              ▼
              If Google Calendar connected:
                CREATE Google Calendar event
                UPDATE appointments SET
                  google_event_id = 'gcal_event_id'
                  meeting_link    = 'https://meet.google.com/xxx'
              │
              ▼
              Send approval email to client
              (includes meeting_link if Google Meet was created)
```

---

### Phase 3 — Balance Payment (when after_amount > 0)

```
Admin requests balance payment
        │
        ▼
UPDATE appointments SET
  balance_token         = UUID (unique, secure one-time token)
  second_payment_status = 'pending'
  overall_payment_status = 'balance_requested'
        │
        ▼
Send balance request email to client
with link: /pay-balance/{balance_token}
        │
        ▼
Client opens link → system reads:
  appointments WHERE balance_token = ?
  Returns: service name, date, time, after_amount
        │
        ▼
Client pays balance via PayPal / Stripe
        │
        ▼
fulfillAppointmentBalance() runs
  UPDATE appointments SET
    second_payment_status     = 'paid'
    second_payment_order_id   = 'PAY-yyy'
    second_payment_capture_id = 'CAP-yyy'
    second_paid_at            = now()
    overall_payment_status    = 'completed'
        │
        ▼
INSERT into invoice (balance invoice)
  LR_id          = (from original booking)
  order_id       = (original o_id from Phase 1)
  invoice_number = 'INV-APT-42-BAL'   ← generated (balance invoice)
  total_amount   = after_amount (50.00)
  issue_date     = today
  status         = 'draft'
  UPDATE invoice.status = 'sent'       ← balance invoice emailed to client
        │
        ▼
INSERT into payments (second payment row)
  invoice_id      = (from above)
  LR_id           = (from original booking)
  payment_date    = now()
  amount          = after_amount (50.00)
  payment_method  = 'paypal'
  transaction_ref = 'CAP-yyy'
  status          = 'paid'
        │
        ▼
UPDATE invoice.status = 'paid'
        │
        ▼
INSERT into notification
  LR_id   = (from original booking)
  date    = today
  time    = now()
  type    = 'payment_received'
  message = "Balance of $50.00 received (Standard Meeting)"
  is_read = 0
Send balance invoice email to client
```

**Result after Phase 3:**
- `appointments.overall_payment_status` = `completed`
- `invoice` → 2 rows total (deposit invoice + balance invoice, both linked to the same order)
- `payments` → 2 rows total (deposit + balance), each linked to their respective invoice via `invoice_id`

---

### Why appointments table must stay separate from order_items

Three things make collapsing it into `order_items.item_payload` impossible:

1. **Slot availability query** — `WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'`
   runs on every calendar page load. This must be indexed. Querying inside JSON is not indexable.

2. **Two-phase payment with two independent transaction rows** — `payment_reference = 'APT-42'`
   ties both payments together. `balance_token` must be a `UNIQUE` column for IDOR protection —
   not possible inside JSON.

3. **Post-payment state machine** — `status`, `google_event_id`, `meeting_link`,
   `second_payment_status` all change independently after the order is already paid.
   The `orders` / `order_items` tables are billing records and must not change after payment.

---

### Improvement — Slot Conflict Guard (duplicate pending booking)

**Problem:** If the same person submits for the same slot twice (page refresh, back button,
slow connection retry) before completing payment, you get two pending `appointments` rows
for the same slot — which blocks that slot for everyone else even though neither is paid.

**Rule before STEP 4 (INSERT into appointments):**

```
CHECK appointments
  WHERE LR_id          = (this lead's LR_id for this tenant)
  AND   service_id     = (selected service)
  AND   appointment_date = '2026-07-15'
  AND   appointment_time = '10:00'
  AND   status != 'cancelled'

→ IF EXISTS (same person, same slot, same tenant, still pending):
    UPDATE order_items SET
        item_payload = (latest submitted payload)
    UPDATE orders SET
        updated_at = now()
    Do NOT create a duplicate appointment row

→ IF NOT EXISTS:
    Proceed with INSERT (normal Phase 1 flow)
```

**This prevents:**
- Duplicate `appointments` rows holding the same slot hostage
- Admin seeing ghost unpaid bookings on the calendar
- Duplicate payment captures if the user completes payment on both attempts

**This does NOT affect paid appointments** — once `paypal_payment_status = 'paid'`
the guard skips it, so the person can legitimately book the same service again on a
different date and get a fresh appointment row.

---

## Case 4 — Free Appointment Booking Flow

**Key difference from Case 3:** `service_payload.is_paid = false`.
No order, no order_items, no payment, no deposit, no refund on rejection.
Admin still approves or rejects — that is the same.

```
User picks Free Appointment + date + time
        │
        ▼
STEP 1: CHECK slot availability
        appointments WHERE appointment_date = '2026-07-15'
                       AND appointment_time = '14:00'
                       AND status != 'cancelled'
        │
        ├── SLOT TAKEN → stop, show error
        │
        ▼  SLOT FREE
STEP 2: CHECK leads WHERE email = 'john@example.com'
        │
        ├── NOT FOUND → INSERT into leads
        │               email = 'john@example.com'   ← identity only
        │               → L_id = (new)
        │
        └── FOUND → leads row exists — no update needed
                    → L_id = (existing)
STEP 3: Upsert leads_relation_table
        │
        ├── NOT FOUND → INSERT (L_id, CCP_id, name, phone, country, ...)
        │               → LR_id = (new)
        │
        └── FOUND → UPDATE profile fields WHERE LR_id = (existing)
        │
        ▼
STEP 4: INSERT into appointments
        LR_id             = (from step 3)
        service_id        = (Free Appointment service id)
        o_id              = NULL         ← no order, never set
        appointment_date  = '2026-07-15'
        appointment_time  = '14:00'
        duration_minutes  = 30
        timezone          = 'America/New_York'
        notes             = 'Quick chat'
        status            = 'pending'
        amount_paid       = NULL         ← free, no payment
        after_amount      = NULL         ← free, no balance
        balance_token     = NULL         ← free, never used
        → apt_id = 43
        │
        ▼
STEP 5: INSERT into notification
        LR_id   = (from step 3)
        date    = today
        time    = now()
        type    = 'appointment_booked'
        message = "Free appointment booked for 2026-07-15 at 14:00"
        is_read = 0
        │
        ▼
        Admin notification email sent
        (No orders, order_items, cart, or payments rows created)
        │
ADMIN DECISION:
        │
        ├── REJECT →
        │     UPDATE appointments SET status = 'cancelled'
        │     Slot freed
        │     No refund needed (was free)
        │     Rejection email sent to client
        │
        └── APPROVE →
              UPDATE appointments SET status = 'confirmed'
              If Google Calendar connected:
                CREATE Google Calendar event
                UPDATE appointments SET
                  google_event_id = 'gcal_xxx'
                  meeting_link    = 'https://meet.google.com/xxx'
              Approval email with meeting link sent to client
```

**Result:**
- `leads` → 1 row (new or updated)
- `leads_relation_table` → 1 row (new or reused)
- `appointments` → 1 new row (slot reserved, status = pending)
- `orders` → **NOT created**
- `order_items` → **NOT created**
- `payments` → **NOT created**

**How backend distinguishes free vs paid at runtime:**
```sql
SELECT JSON_EXTRACT(s.service_payload, '$.is_paid') AS is_paid
FROM   appointments a
JOIN   services s ON a.service_id = s.s_id
WHERE  a.apt_id = 43;
-- returns: false  →  skip all payment/refund logic
-- returns: true   →  enforce deposit + refund on rejection
```

---

## Case 5 — Tenant Site with Name + Email Form Only

**Scenario:** A tenant's website has a simple contact form with only `name` and `email` fields.
No service selection, no payment. The tenant admin sees only a leads list.

**How the backend identifies the tenant:**
Every request from the tenant frontend includes `X-API-Key: "mq-xxxx"` in the header.
Backend looks up `tenants_service_info WHERE public_key = "mq-xxxx"` → gets `CCP_id = 7`.

```
Visitor submits form: { name: "Sarah", email: "sarah@example.com" }
on https://majesticmarquees.com
        │
        ▼
Backend reads X-API-Key from request header
  → tenants_service_info WHERE public_key = "mq-xxxx"
  → CCP_id = 7 (Majestic Marquees)
        │
        ▼
STEP 1: CHECK leads WHERE email = 'sarah@example.com'
        │
        ├── NOT FOUND → INSERT into leads
        │     email  = "sarah@example.com"   ← identity only
        │     → L_id = 91
        │
        └── FOUND → leads row exists (email matches) — no update needed
                    → L_id = (existing)
        │
        ▼
STEP 2: CHECK leads_relation_table WHERE L_id = 91 AND CCP_id = 7
        │
        ├── NOT FOUND → INSERT leads_relation_table
        │     L_id   = 91
        │     CCP_id = 7
        │     name   = "Sarah"
        │     → LR_id = 44
        │
        └── FOUND → UPDATE leads_relation_table SET name = "Sarah" WHERE LR_id = (existing)
                    reuse LR_id = (existing)
        │
        ▼
No order. No order_items. No cart. No payments.
        │
        ▼
STEP 3: INSERT into notification
        LR_id   = 44
        date    = today
        time    = now()
        type    = 'contact_only'
        message = "New lead: Sarah (sarah@example.com)"
        is_read = 0
```

**What the tenant admin sees:**
```sql
-- Tenant admin (CCP_id = 7) queries their leads dashboard:
-- Profile fields (name, phone) are in leads_relation_table — each tenant sees their own copy
SELECT lrt.name, l.email, lrt.phone, lrt.created_at
FROM   leads l
JOIN   leads_relation_table lrt ON lrt.L_id = l.L_id
WHERE  lrt.CCP_id = 7
ORDER  BY lrt.created_at DESC;
```

- Leads list only — name + email (all the form collected)
- No orders tab, no payments tab — nothing was purchased
- Notification badge shows unread count:
  `SELECT COUNT(*) FROM notification n JOIN leads_relation_table lrt ON lrt.LR_id = n.LR_id WHERE lrt.CCP_id = 7 AND n.is_read = 0`

**Result:**
- `leads` → 1 row (new or updated)
- `leads_relation_table` → 1 row (new or reused)
- `notification` → 1 row (unread)
- `orders`, `order_items`, `payments` → **NOT created**

---

## Case 6 — How a Future Tenant Pays ClickDigim (Franchise Subscription)

**Core concept:** ClickDigim is itself a tenant in its own system (`CCP_id = 1`).
A person who wants to become a franchise tenant submits a form on ClickDigim's own site,
becomes a lead against `CCP_id = 1`, and pays for a "White Label Franchise" service.
This uses the exact same `leads → LRT → cart → orders → invoice → payments` chain
as any other lead buying any other service. No separate billing table is needed.

### Part 1 — New Tenant Signs Up (first payment)

```
Person submits franchise signup form on clickdigim.com
        │
        ▼
STEP 1: INSERT into leads
        email = "newowner@example.com"   ← identity only
        → L_id = (new)
        │
        ▼
STEP 2: INSERT into leads_relation_table
        L_id                = (new)
        CCP_id              = 1           ← ClickDigim itself is CCP_id = 1
        name                = "Jane Smith"
        phone               = "+1 555 9999"
        legal_business_name = "Jane's Marketing LLC"
        → LR_id = (new)
        │
        ▼
STEP 3: INSERT into cart
        LR_id  = (from step 2)
        CCP_id = 1
        status = 'active'
        → cart_id = (new)
        │
        ▼
STEP 4: INSERT into cart_items
        cart_id      = (from step 3)
        service_id   = (White Label Franchise - Starter service id)
        quantity     = 1
        unit_price   = 299.00
        item_payload = {
          "business_name":  "Jane's Marketing LLC",
          "plan_name":      "Starter",
          "billing_period": "2026-06"
        }
        │
        ▼
STEP 5: User reviews cart → clicks "Proceed to Checkout"
        │
        ▼
STEP 6: INSERT into orders
        LR_id        = (from step 2)
        total_amount = 299.00
        status       = 'pending'
        order_notes  = 'White Label Franchise - Starter - 2026-06'
        → o_id = (new)
        │
        ▼
STEP 7: INSERT into order_items (copied from cart_items)
STEP 8: UPDATE cart.status = 'converted'
        │
        ▼
STEP 9: INSERT into invoice
        invoice_number = 'INV-CLICKDIGIM-20260601-XXXXXX'
        total_amount   = 299.00
        status         = 'draft' → 'sent'
        │
        ▼
STEP 10: Jane pays via Stripe
         INSERT into payments
           invoice_id      = (from step 9)
           LR_id           = (from step 2)
           amount          = 299.00
           payment_method  = 'stripe'
           transaction_ref = 'pi_xxxx'
           status          = 'paid'
        │
        ▼
STEP 11: Fulfillment runs
         1. INSERT into clickdigim_customers_profile
              name    = "Jane's Marketing LLC"
              contact = "Jane Smith"
              → CCP_id = 8   (Jane's new tenant ID)
         2. INSERT into tenants_service_info
              CCP_id       = 8
              seller_code  = 'JANEML'
              public_key   = (generated)
              status       = 'active'
         3. Admin provisions Jane's subdomains in frontend table
        │
        ▼
STEP 12: Jane gets her login credentials and domain access
```

**Result:**
- `leads` → 1 new row (Jane's global identity)
- `leads_relation_table` → 1 new row (Jane as lead of ClickDigim CCP_id=1)
- `orders` + `order_items` + `invoice` + `payments` → normal chain under CCP_id = 1
- `clickdigim_customers_profile` → 1 new row (Jane's new CCP_id = 8)
- `tenants_service_info` → 1 new row (Jane's technical config)

---

### Part 2 — Monthly Recurring Billing

Each month a cron job creates a new order under Jane's existing `LR_id`.
No new leads row. No new LRT row. Just a new order chain.

```
Cron runs on 1st of each month
        │
        ▼
INSERT into orders
  LR_id        = (Jane's LR_id against CCP_id = 1)
  total_amount = 299.00
  order_notes  = 'White Label Franchise - Starter - 2026-07'
  status       = 'pending'
        │
        ▼
INSERT into order_items
  service_id   = (White Label Franchise - Starter)
  unit_price   = 299.00
  item_payload = { "plan_name": "Starter", "billing_period": "2026-07" }
        │
        ▼
INSERT into invoice
  invoice_number = 'INV-CLICKDIGIM-20260701-XXXXXX'
        │
        ▼
Charge Jane's saved card:
  SELECT stripe_customer_id FROM clickdigim_customers_profile WHERE CCP_id = 1
  (ClickDigim's own Stripe customer — or charge Jane's CCP_id=8 stripe_customer_id)
        │
        ├── SUCCESS →
        │     INSERT into payments (status = 'paid')
        │     UPDATE invoice.status = 'paid'
        │     UPDATE orders.status  = 'paid'
        │     tenants_service_info.status stays 'active'
        │
        └── FAILURE → 7-day grace period (same as described below)
```

---

### Part 3 — What happens when subscription expires (7-day grace period)

```
Payment attempt FAILS
        │
        ▼
UPDATE orders.status = 'pending' (stays unpaid)
        │
        ▼
Day 0:  Retry attempt #1
        Send email → "Payment failed. Please update your card."
        tenants_service_info.status remains 'active'
        ← Jane's site still fully working
        │
Day 3:  Retry attempt #2
        Send reminder email → "3 days left before suspension"
        │
Day 7:  Final retry attempt #3
        │
        ├── PAYMENT SUCCEEDS at any point during 7 days →
        │     INSERT payments (status = 'paid')
        │     UPDATE orders.status  = 'paid'
        │     tenants_service_info.status stays 'active'
        │
        └── STILL UNPAID after 7 days →
              UPDATE tenants_service_info SET status = 'suspended'
              WHERE CCP_id = 8
              │
              ▼
              Every API request from Jane's site:
                Backend reads public_key header → CCP_id = 8
                tenants_service_info.status = 'suspended'
                → HTTP 402 Payment Required
                → Jane's public site shows "Account suspended"
                → Jane's admin panel shows "Billing overdue" modal
```

**Backend enforcement (same middleware as always):**
```php
$tenant = TenantsServiceInfo::where('public_key', $request->header('X-API-Key'))->first();
if ($tenant->status === 'suspended') {
    return response()->json(['message' => 'Account suspended. Please update billing.'], 402);
}
```

---

## Case 7 — Admin Login into Any Tenant's Admin Panel

**Single backend, identified by `CCP_id` — not by subdomain.**

```
User visits https://admin.majesticmarquees.com
        │
        ▼
Admin panel frontend loads
(Same app served under tenant domain via reverse proxy)
        │
        ▼
User enters: email + password
Frontend sends:
  POST /api/admin/login
  Headers: X-API-Key: "mq-xxxx"   ← identifies the tenant
  Body:    { email, password }
        │
        ▼
Backend:
  STEP 1: Lookup tenants_service_info WHERE public_key = "mq-xxxx"
           → CCP_id = 7

  STEP 2: Lookup admin_user
           WHERE email     = submitted_email
           AND   CCP_id    = 7          ← MUST match this tenant
           AND   is_active = 1

  STEP 3a: NOT FOUND or CCP_id mismatch →
             return 401 "Invalid credentials"
             (A ClickDigim super admin [CCP_id = NULL] cannot login
              through a tenant URL — different scope)

  STEP 3b: FOUND, password matches →
             Delete existing tokens (single session)
             Create Sanctum token (expires 24h)
             Return: { token, role, permissions, CCP_id }
        │
        ▼
Token stored in admin frontend localStorage
Every subsequent protected request:
  Authorization: Bearer {token}
  X-API-Key: "mq-xxxx"

Backend middleware on every protected route:
  1. Validate Sanctum token → get admin_id
  2. Confirm admin_user.CCP_id = 7 (or NULL for super admin)
  3. Confirm admin_user.is_active = 1
  4. Check permission gate for specific route
     (from permissions.php based on admin_user.role)
```

**What each admin type can see:**

| Admin | CCP_id | Scope |
|---|---|---|
| ClickDigim super admin | NULL | All tenants, all data, franchise payments (via `payments` table under `CCP_id = 1`) |
| Tenant admin (e.g. Majestic Marquees) | 7 | Only data WHERE CCP_id = 7 |
| Tenant support | 7 | Subset based on role permissions |

**Key isolation rule — every controller query must scope to CCP_id:**
```sql
-- Tenant admin (CCP_id IS NOT NULL):
WHERE lrt.CCP_id = auth()->user()->CCP_id

-- ClickDigim super admin (CCP_id IS NULL):
-- No CCP_id filter — sees all tenants
```

**`frontend` table stores all subdomains including admin:**
```
frontend
  f_id = 10, t_id = 7, subdomain = "https://majesticmarquees.com"
  f_id = 11, t_id = 7, subdomain = "https://blog.majesticmarquees.com"
  f_id = 12, t_id = 7, subdomain = "https://admin.majesticmarquees.com"
```
The backend does not use the subdomain to determine admin vs public — it uses the **route** being called (`/api/admin/*` = admin routes, `/api/*` = public routes) and the **Sanctum token** to scope the session.

---

## Case 8 — Request-Time Subscription Expiry Check (Every API Request)

**How it works:** The tenant middleware already reads `tenants_service_info` on every request
to check `status = 'suspended'`. This same middleware is extended to also check
`subscription_expires_at` against today. No cron needed — the check is lazy (fires only
when the tenant's site is actually used).

**Required column (added to `tenants_service_info`):**
- `subscription_start_at DATE NOT NULL` — set when payment is confirmed
- `subscription_expires_at DATE NOT NULL` — extended by 30 days on each renewal

```
Incoming API request
  Header: X-API-Key: "mq-xxxx"
        │
        ▼
Middleware:
  1. Lookup tenants_service_info WHERE public_key = "mq-xxxx"
     → tenant row found, CCP_id = 8 (Jane)
        │
        ▼
  2. CHECK: tenant.status = 'suspended'?
     → YES: return 402 immediately (existing logic, unchanged)
        │
        ▼
  3. CHECK: today > tenant.subscription_expires_at?
     → YES (fully expired) →
            UPDATE tenants_service_info SET status = 'suspended' WHERE CCP_id = 8
            return 402 "Account suspended. Please renew your subscription."
        │
        ▼
  4. CHECK: tenant.subscription_expires_at <= today + 5 days?
     → NO (more than 5 days left): pass request through normally — done
     → YES (5 days or less): continue to step 5
        │
        ▼
  5. DUPLICATE GUARD — check if renewal invoice already exists this cycle:
     SELECT COUNT(*) FROM invoice
       WHERE LR_id    = (Jane's LR_id against CCP_id = 1)
         AND status   IN ('draft','sent')
         AND issue_date >= DATE_FORMAT(tenant.subscription_expires_at, '%Y-%m-01')
     → EXISTS: already sent this cycle, skip — pass request through
     → NOT EXISTS: continue to step 6
        │
        ▼
  6. INSERT into orders
       LR_id        = (Jane's LR_id against CCP_id = 1)
       total_amount = (unit_price of Jane's franchise plan)
       order_notes  = 'Renewal: Starter - 2026-07'
       status       = 'pending'
        │
        ▼
  7. INSERT into order_items
       service_id   = (White Label Franchise - Starter)
       unit_price   = 299.00
       item_payload = { "plan_name": "Starter", "billing_period": "2026-07" }
        │
        ▼
  8. INSERT into invoice
       invoice_number = 'INV-CLICKDIGIM-20260625-XXXXXX'
       total_amount   = 299.00
       status         = 'sent'
        │
        ▼
  9. Send renewal warning email to Jane
     Subject: "Your subscription expires in 5 days — invoice attached"
     Includes: invoice link + payment link
        │
        ▼
  10. Pass the original API request through — Jane's site keeps working
```

**What happens when Jane pays the renewal invoice:**

```
Payment confirmed (PayPal / Stripe webhook)
        │
        ▼
INSERT into payments (status = 'paid')
UPDATE invoice.status = 'paid'
UPDATE orders.status  = 'paid'
        │
        ▼
UPDATE tenants_service_info SET
  status                 = 'active'
  subscription_start_at  = today
  subscription_expires_at = today + 30 days
WHERE CCP_id = 8
```

**Super admin visibility guard (catches dormant sites):**
```sql
-- Run when super admin loads tenant list in ClickDigim admin panel:
SELECT ccp.CCP_id, ccp.name, tsi.subscription_expires_at
FROM   clickdigim_customers_profile ccp
JOIN   tenants_service_info tsi ON tsi.CCP_id = ccp.CCP_id
WHERE  tsi.subscription_expires_at < CURDATE()
  AND  tsi.status = 'active';
-- Any row returned = expired but not suspended (site had no traffic to trigger middleware)
```

**Risk:** If Jane's site has zero traffic after expiry, middleware never fires, `status` stays
`'active'` indefinitely. The super admin query above is the safety net — no cron needed.

---

## Exception — Dormant Site After Subscription Expiry

**Problem:** The request-time check in Case 8 only fires when someone visits Jane's site.
If Jane's site has zero traffic after her subscription expires, the middleware never runs,
`status` stays `'active'`, and ClickDigim has no record of the overdue account.

```
Jane's subscription expired on June 1st
        │
        ▼
June 1  → no visitors → middleware never fires
June 5  → no visitors → middleware never fires
June 15 → no visitors → middleware never fires
        │
        ▼
Database still shows:
  tenants_service_info.status                 = 'active'      ← wrong, should be suspended
  tenants_service_info.subscription_expires_at = '2026-06-01' ← expired 15 days ago
```

**Solution — Super Admin visibility query (no cron, no worker):**

When the ClickDigim super admin loads the tenant list in the admin panel, the backend runs
this query automatically and flags any tenant whose subscription has expired but is still
marked `active`:

```sql
SELECT ccp.CCP_id, ccp.name, tsi.subscription_expires_at
FROM   clickdigim_customers_profile ccp
JOIN   tenants_service_info tsi ON tsi.CCP_id = ccp.CCP_id
WHERE  tsi.subscription_expires_at < CURDATE()
  AND  tsi.status = 'active';
-- Any row returned = expired but not yet suspended (dormant site, no traffic triggered middleware)
```

**What the super admin does with these results:**

```
Super admin sees "Jane's Marketing LLC" in the overdue list
        │
        ▼
Super admin clicks "Suspend" → backend runs:
  UPDATE tenants_service_info SET status = 'suspended'
  WHERE CCP_id = 8
        │
        ▼
Super admin sends manual reminder email to Jane
```

**Why this is sufficient without a cron:**
- Dormant sites generate no revenue — Jane is not actively using the platform
- The super admin check catches them at their next login to the ClickDigim admin panel
- No infrastructure cost (no cron process, no worker, no queue)
- The Case 8 middleware handles the active sites automatically
- Together both cover 100% of cases: active sites via middleware, dormant sites via super admin query
