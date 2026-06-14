# ClickDigim White-Label System — Finalized Database Schema

All columns verified from controllers, migrations, and design decisions in whitelevelcasestudy.md.
No extra columns. No guesses.

---

## Table 1: `leads`

**Purpose:** Global identity store — one row per unique person across the entire system.
Email is the only field. All profile data (name, phone, country, etc.) lives in
`leads_relation_table` so each tenant maintains its own isolated copy of the lead's profile.
This prevents cross-tenant data leakage when the same person submits to multiple tenants.

```sql
CREATE TABLE `leads` (
  `L_id`       INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(255)  NOT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`L_id`),
  UNIQUE KEY `leads_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column notes:**
| Column | Rule |
|---|---|
| `email` | UNIQUE — global deduplication key across all tenants. The only data stored at lead level |

---

## Table 2: `leads_relation_table`

**Purpose:** Links a lead to a tenant AND stores the lead's profile data for that tenant.
One row per lead-per-tenant relationship. The same lead can have multiple rows here —
one per tenant they interact with, each with its own isolated copy of the lead's profile.
This prevents cross-tenant data leakage: if John updates his phone on Tenant B's site,
Tenant A's copy is completely unaffected.
`source` removed (CCP_id already identifies the tenant).
`entry_type` removed — the relationship type is determinable from the `orders` table:
- Lead has orders → they purchased something
- Lead has no orders → they only subscribed or submitted a contact form
A single ENUM cannot track multiple interaction types over time (subscribe then order later).

```sql
CREATE TABLE `leads_relation_table` (
  `LR_id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `L_id`                INT UNSIGNED  NOT NULL,
  `CCP_id`              INT UNSIGNED  NOT NULL,
  `name`                VARCHAR(255)  NULL,
  `phone`               VARCHAR(50)   NULL,
  `country`             VARCHAR(100)  NULL,
  `legal_business_name` VARCHAR(255)  NULL,
  `website_url`         VARCHAR(500)  NULL,
  `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`LR_id`),
  UNIQUE KEY `lrt_lead_tenant_unique` (`L_id`, `CCP_id`),
  CONSTRAINT `fk_lrt_lead`   FOREIGN KEY (`L_id`)
    REFERENCES `leads` (`L_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lrt_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column notes:**
| Column | Rule |
|---|---|
| `L_id` + `CCP_id` | UNIQUE together — one relationship row per lead per tenant |
| `name` | This tenant's copy of the lead's name. Different tenants can have different values |
| `phone` | This tenant's copy of the lead's phone number |
| `country` | This tenant's copy of the lead's country |
| `legal_business_name` | Collected by White-Label form for this tenant — NULL if not provided |
| `website_url` | Collected by Featured Blog / White-Label forms for this tenant — NULL if not provided |
| `created_at` | When this lead first appeared in this tenant's world |
| `updated_at` | When this tenant last updated the lead's profile |

**How to determine relationship type without entry_type:**
```sql
-- Does this lead have any order with this tenant?
SELECT o.o_id FROM orders o WHERE o.LR_id = ? LIMIT 1;

-- Does this lead have a paid order?
SELECT o.o_id FROM orders o WHERE o.LR_id = ? AND o.status = 'paid' LIMIT 1;

-- Is this lead subscription-only (no orders at all)?
SELECT COUNT(*) FROM orders WHERE LR_id = ?;  -- returns 0

-- How to get CCP_id from LR_id:
SELECT CCP_id FROM leads_relation_table WHERE LR_id = ?;

-- How to get lead profile for a tenant:
SELECT l.email, lrt.name, lrt.phone, lrt.country
FROM   leads l
JOIN   leads_relation_table lrt ON lrt.L_id = l.L_id
WHERE  lrt.LR_id = ?;
```

---

## Table 3: `services`

**Purpose:** One row per service offered by a tenant. The `service_payload` JSON defines
what extra fields to collect from the lead beyond the common lead fields.

```sql
CREATE TABLE `services` (
  `s_id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `CCP_id`          INT UNSIGNED  NOT NULL,
  `service_name`    VARCHAR(255)  NOT NULL,
  `service_code`    VARCHAR(100)  NOT NULL,
  `unit_price`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `service_payload` JSON          NULL,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`s_id`),
  UNIQUE KEY `services_ccp_code_unique` (`CCP_id`, `service_code`),
  CONSTRAINT `fk_services_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Seed Data — All 10 services with exact `service_payload`

```sql
INSERT INTO `services`
  (`CCP_id`, `service_name`, `service_code`, `unit_price`, `service_payload`)
VALUES

-- ── Appointment Services (Free) ─────────────────────────────────────────────

(1, 'Free Appointment', 'free-appointment', 0.00,
  '{
    "appointment_type": "free",
    "is_paid": false,
    "deposit_pct": 0,
    "fields": [
      { "key": "appointment_date", "type": "date",     "required": true  },
      { "key": "appointment_time", "type": "time",     "required": true  },
      { "key": "timezone",         "type": "string",   "required": false },
      { "key": "notes",            "type": "textarea", "required": false }
    ]
  }'
),

-- ── Appointment Services (Paid) ──────────────────────────────────────────────

(1, 'Quick Consultation', 'quick-consultation', 74.99,
  '{
    "appointment_type": "paid",
    "is_paid": true,
    "deposit_pct": 50,
    "fields": [
      { "key": "appointment_date", "type": "date",     "required": true  },
      { "key": "appointment_time", "type": "time",     "required": true  },
      { "key": "timezone",         "type": "string",   "required": false },
      { "key": "notes",            "type": "textarea", "required": false }
    ]
  }'
),

(1, 'Standard Meeting', 'standard-meeting', 100.00,
  '{
    "appointment_type": "paid",
    "is_paid": true,
    "deposit_pct": 50,
    "fields": [
      { "key": "appointment_date", "type": "date",     "required": true  },
      { "key": "appointment_time", "type": "time",     "required": true  },
      { "key": "timezone",         "type": "string",   "required": false },
      { "key": "notes",            "type": "textarea", "required": false }
    ]
  }'
),

(1, 'Extended Session', 'extended-session', 150.00,
  '{
    "appointment_type": "paid",
    "is_paid": true,
    "deposit_pct": 50,
    "fields": [
      { "key": "appointment_date", "type": "date",     "required": true  },
      { "key": "appointment_time", "type": "time",     "required": true  },
      { "key": "timezone",         "type": "string",   "required": false },
      { "key": "notes",            "type": "textarea", "required": false }
    ]
  }'
),

(1, 'Full Strategy Session', 'full-strategy', 200.00,
  '{
    "appointment_type": "paid",
    "is_paid": true,
    "deposit_pct": 50,
    "fields": [
      { "key": "appointment_date", "type": "date",     "required": true  },
      { "key": "appointment_time", "type": "time",     "required": true  },
      { "key": "timezone",         "type": "string",   "required": false },
      { "key": "notes",            "type": "textarea", "required": false }
    ]
  }'
),

-- ── Growth Plan ───────────────────────────────────────────────────────────────

(1, 'Growth Plan', 'growth-plan', 1500.00,
  '{
    "fields": [
      { "key": "business_name", "type": "string", "required": true }
    ]
  }'
),

-- ── Ad Booking ────────────────────────────────────────────────────────────────

(1, 'Ad Booking', 'ad-booking', 5.00,
  '{
    "fields": [
      { "key": "title",          "type": "string",  "required": true  },
      { "key": "summary",        "type": "string",  "required": true  },
      { "key": "keyword",        "type": "string",  "required": true  },
      { "key": "target_url",     "type": "url",     "required": true  },
      { "key": "slot_number",    "type": "integer", "min": 1, "max": 4, "required": true },
      { "key": "duration_type",  "type": "enum",    "options": ["hours","days"], "required": true },
      { "key": "duration_value", "type": "integer", "min": 1, "required": true  },
      { "key": "start_date",     "type": "date",    "required": true  },
      { "key": "end_date",       "type": "date",    "required": true  },
      { "key": "image_path",     "type": "file",    "required": true  }
    ]
  }'
),

-- ── Featured Blog ─────────────────────────────────────────────────────────────

(1, 'Featured Blog', 'featured-blog', 250.00,
  '{
    "fields": [
      { "key": "suggested_keywords", "type": "string",  "required": false },
      { "key": "products",           "type": "string",  "required": true  },
      { "key": "quantity",           "type": "integer", "required": true  }
    ]
  }'
),

-- ── Add-ons (no extra fields — product selection is the payload) ──────────────

(1, 'Multi-Site Pack', 'multi-site-pack', 1200.00,
  '{
    "fields": []
  }'
),

(1, 'Press Release', 'press-release', 400.00,
  '{
    "fields": []
  }'
),
-- ── White Label Franchise (ClickDigim's own service for new tenants) ──────────

(1, 'White Label Franchise - Starter', 'wl-franchise-starter', 299.00,
  '{
    "fields": [
      { "key": "business_name",   "type": "string", "required": true },
      { "key": "plan_name",       "type": "string", "required": true },
      { "key": "billing_period",  "type": "string", "required": true }
    ]
  }'
),

(1, 'White Label Franchise - Growth', 'wl-franchise-growth', 599.00,
  '{
    "fields": [
      { "key": "business_name",   "type": "string", "required": true },
      { "key": "plan_name",       "type": "string", "required": true },
      { "key": "billing_period",  "type": "string", "required": true }
    ]
  }'
),

(1, 'White Label Franchise - Enterprise', 'wl-franchise-enterprise', 999.00,
  '{
    "fields": [
      { "key": "business_name",   "type": "string", "required": true },
      { "key": "plan_name",       "type": "string", "required": true },
      { "key": "billing_period",  "type": "string", "required": true }
    ]
  }'
),
-- ── Blog Article Unlock ───────────────────────────────────────────────────────

(1, 'Blog Article Unlock', 'blog-article-unlock', 0.00,
  '{
    "fields": [
      { "key": "post_id", "type": "integer", "required": true }
    ]
  }'
);
```

**`service_payload` field reference:**
| Field in payload | Meaning |
|---|---|
| `key` | The `item_payload` key that will be stored in `order_items` |
| `type` | Input type hint for the frontend form builder |
| `required` | Whether the field must be present before creating an order_item |
| `appointment_type` | Top-level key on appointment services only: `"free"` or `"paid"`. Backend reads this to decide whether to create an order and collect payment |
| `is_paid` | Boolean. `false` → skip order/payment/refund logic entirely. `true` → require deposit before slot is confirmed |
| `deposit_pct` | Integer percentage of `unit_price` charged as deposit at booking time. `0` for free, `50` for paid (change here to adjust deposit % without code changes) |

---

## Table 4: `order_items`

**Purpose:** One row per service purchased inside an order.
`item_payload` stores the actual submitted values matching the service's `service_payload` fields.

```sql
CREATE TABLE `order_items` (
  `OI_id`        INT UNSIGNED                                    NOT NULL AUTO_INCREMENT,
  `o_id`         INT UNSIGNED                                    NOT NULL,
  `LR_id`        INT UNSIGNED                                    NOT NULL,
  `service_id`   INT UNSIGNED                                    NULL,
  `product_id`   INT UNSIGNED                                    NULL,
  `quantity`     INT UNSIGNED                                    NOT NULL DEFAULT 1,
  `unit_price`   DECIMAL(10,2)                                   NOT NULL,
  `subtotal`     DECIMAL(10,2)                                   NOT NULL,
  `item_payload` JSON                                            NULL,
  `status`       ENUM('pending','paid','cancelled','completed')  NOT NULL DEFAULT 'pending',
  `created_at`   DATETIME                                        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME                                        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`OI_id`),
  CONSTRAINT `fk_oi_order`   FOREIGN KEY (`o_id`)
    REFERENCES `orders` (`o_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_lr`      FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_service` FOREIGN KEY (`service_id`)
    REFERENCES `services` (`s_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`)
    REFERENCES `products` (`p_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**`item_payload` examples per service at insert time:**

```json
-- Quick / Standard / Extended / Full Strategy appointment
{
  "appointment_id":   42,
  "appointment_date": "2026-07-15",
  "appointment_time": "10:00",
  "timezone":         "America/New_York",
  "notes":            "First strategy call",
  "duration_minutes": 60
}

-- Growth Plan
{
  "business_name": "John's Digital Agency"
}

-- Ad Booking
{
  "title":          "Boost Your SEO Today",
  "summary":        "Get ranked on page 1 with expert SEO strategy",
  "keyword":        "digital-marketing",
  "target_url":     "https://johnbiz.com",
  "slot_number":    2,
  "duration_type":  "days",
  "duration_value": 7,
  "start_date":     "2026-07-01",
  "end_date":       "2026-07-07",
  "image_path":     "ads/john-ad-banner.jpg"
}

-- Featured Blog
{
  "suggested_keywords": "digital marketing USA",
  "products":           "Featured Blog × 1",
  "quantity":           1
}

-- Multi-Site Pack / Press Release
{}

-- Blog Article Unlock
{
  "post_id": 18
}
```

**Column notes:**
| Column | Rule |
|---|---|
| `service_id` | NULL allowed — for product-only items that have no service row |
| `product_id` | NULL allowed — for service-only items that have no product row |
| `item_payload` | Stores submitted values matching the service's `service_payload.fields` keys |
| `status` | Transitions: pending → paid (on payment) or cancelled (on refund/reject) |

---

## Table 5: `appointments`

**Purpose:** One row per booked slot. Exists separately from order_items because:
- Slot availability requires indexed queries on `appointment_date` + `appointment_time`
- `balance_token` must be a UNIQUE column (IDOR protection) — impossible inside JSON
- Status, meeting_link, google_event_id change after order is already paid

```sql
CREATE TABLE `appointments` (
  `apt_id`                INT UNSIGNED                                    NOT NULL AUTO_INCREMENT,
  `LR_id`                 INT UNSIGNED                                    NOT NULL,
  `service_id`            INT UNSIGNED                                    NOT NULL,
  `o_id`                  INT UNSIGNED                                    NULL,
  `appointment_date`      DATE                                            NOT NULL,
  `appointment_time`      TIME                                            NOT NULL,
  `duration_minutes`      INT                                             NOT NULL DEFAULT 60,
  `timezone`              VARCHAR(100)                                    NULL,
  `notes`                 TEXT                                            NULL,
  `status`                ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `meeting_link`          VARCHAR(500)                                    NULL,
  `google_event_id`       VARCHAR(255)                                    NULL,
  `payment_reference`     VARCHAR(100)                                    NULL,
  `amount_paid`           DECIMAL(10,2)                                   NULL,
  `after_amount`          DECIMAL(10,2)                                   NULL,
  `balance_token`         VARCHAR(64)                                     NULL,
  `second_payment_status`   ENUM('pending','paid','failed')                                                    NULL,
  `overall_payment_status`  ENUM('unpaid','deposit_paid','balance_requested','completed','failed')  NOT NULL DEFAULT 'unpaid',
  `created_at`              DATETIME                                                                 NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`              DATETIME                                                                 NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`apt_id`),
  UNIQUE KEY `appointments_balance_token_unique` (`balance_token`),
  UNIQUE KEY `unique_service_slot` (`service_id`, `appointment_date`, `appointment_time`),
  INDEX `idx_apt_slot` (`service_id`, `appointment_date`, `appointment_time`),
  CONSTRAINT `fk_apt_lr`      FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_apt_service` FOREIGN KEY (`service_id`)
    REFERENCES `services` (`s_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_apt_order`   FOREIGN KEY (`o_id`)
    REFERENCES `orders` (`o_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column notes:**
| Column | Rule |
|---|---|
| `o_id` | NULL at booking time — set to order id after deposit payment. Remains NULL permanently for free appointments (`is_paid = false`) |
| `status` | `pending` → admin approves → `confirmed`; admin rejects → `cancelled` |
| `google_event_id` | Set by Google Calendar API on admin approval. NULL if Calendar not connected |
| `meeting_link` | Google Meet link. Set alongside `google_event_id` on approval |
| `payment_reference` | e.g. `APT-42`. Shared key between deposit and balance payments |
| `amount_paid` | Deposit amount paid in Phase 1 |
| `after_amount` | Balance still due = `unit_price − amount_paid` |
| `balance_token` | UNIQUE 64-char token. Admin sets this when requesting balance payment |
| `second_payment_status` | `pending` when balance requested, `paid` when balance collected |
| `overall_payment_status` | Lifecycle: `unpaid` → `deposit_paid` → `balance_requested` → `completed`. Set to `failed` on refund |

---

## Slot Availability Index

The index `idx_apt_slot` is on `(service_id, appointment_date, appointment_time)` —
`service_id` must be first so MySQL filters to one tenant's schedule before scanning dates.
Without `service_id` leading the index, MySQL scans all appointments across all tenants.

The `UNIQUE KEY unique_service_slot (service_id, appointment_date, appointment_time)` is the
final race-condition lock against double booking. If two buyers submit for the same slot
simultaneously, only one INSERT succeeds. The backend catches MySQL error 1062 (duplicate key)
and returns "This slot was just taken. Please choose another time."

```sql
SELECT apt_id
FROM   appointments
WHERE  service_id       = 2
  AND  appointment_date = '2026-07-15'
  AND  appointment_time = '10:00'
  AND  status          != 'cancelled';
```

---

## Table 6: `clickdigim_customers_profile`

**Purpose:** One row per franchise tenant. ClickDigim's direct customers.
`CCP_id` is the FK used across every other table to scope data to a tenant.

```sql
CREATE TABLE `clickdigim_customers_profile` (
  `CCP_id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`                VARCHAR(255)  NOT NULL,
  `phone`               VARCHAR(50)   NULL,
  `address`             TEXT          NULL,
  `contact`             VARCHAR(255)  NULL,
  `stripe_customer_id`  VARCHAR(100)  NULL,
  `paypal_customer_id`  VARCHAR(100)  NULL,
  `default_currency`    VARCHAR(3)    NOT NULL DEFAULT 'USD',
  `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`CCP_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column notes:**
| Column | Rule |
|---|---|
| `name` | Franchise/business name of the tenant |
| `contact` | Primary contact person name at the tenant |
| `stripe_customer_id` | Stripe Customer ID (`cus_xxxx`). Set when tenant saves a card for automatic billing. NULL until card is added |
| `paypal_customer_id` | PayPal vault/customer ID. Set when tenant adds PayPal as billing method. NULL until added |
| `default_currency` | ISO 4217 currency code for this tenant's catalogue pricing (e.g. `USD`, `EUR`, `GBP`). Default `USD` |

---

## Table 7: `tenants_service_info`

**Purpose:** Technical config per tenant. Stores the public key used by the tenant's frontend
to identify which tenant is making requests. Also stores SMTP settings for white-label emails
and a unique seller code for branded invoice number generation. One row per tenant.
Table renamed from `tenents_service_info` (typo fixed).

```sql
CREATE TABLE `tenants_service_info` (
  `t_id`           INT UNSIGNED                          NOT NULL AUTO_INCREMENT,
  `CCP_id`         INT UNSIGNED                          NOT NULL,
  `seller_code`    VARCHAR(10)                           NULL,
  `public_key`     VARCHAR(255)                          NOT NULL,
  `smtp_settings`  JSON                                  NULL,
  `primary_domain` VARCHAR(255)                          NULL,
  `status`                  ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `subscription_start_at`   DATE                                  NOT NULL,
  `subscription_expires_at` DATE                                  NOT NULL,
  `created_at`              DATETIME                              NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`              DATETIME                              NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`t_id`),
  UNIQUE KEY `tsi_public_key_unique` (`public_key`),
  UNIQUE KEY `tsi_seller_code_unique` (`seller_code`),
  CONSTRAINT `fk_tsi_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column notes:**
| Column | Rule |
|---|---|
| `seller_code` | Short uppercase slug (e.g. `MAJMQ`). Used to generate branded invoice numbers: `INV-MAJMQ-20260715-A3F9K2`. UNIQUE |
| `public_key` | UNIQUE — sent in every frontend request header (`X-API-Key`) to identify the tenant. Non-secret public identifier. Renamed from `API_key` |
| `smtp_settings` | JSON. Stores tenant's custom SMTP config for white-label transactional emails. SMTP password must be encrypted at application level before storing. Example: `{"host":"smtp.example.com","port":587,"username":"noreply@example.com","password":"ENCRYPTED","from_name":"Tenant Name","from_email":"noreply@example.com","encryption":"tls"}` |
| `primary_domain` | e.g. `tenant1.com` — used for CORS and domain routing |
| `status` | `suspended` blocks all API access for this tenant |
| `subscription_start_at` | The date the current paid subscription period began. Set when first payment is confirmed |
| `subscription_expires_at` | The date the current subscription period ends. Extended by 30 days on each successful renewal. Middleware checks this on every API request to detect upcoming expiry |

---

## Table 8: `frontend`

**Purpose:** Links a tenant's frontend deployment (subdomain) to their service config.

```sql
CREATE TABLE `frontend` (
  `f_id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `t_id`       INT UNSIGNED NOT NULL,
  `subdomain`  VARCHAR(255) NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`f_id`),
  UNIQUE KEY `frontend_subdomain_unique` (`subdomain`),
  CONSTRAINT `fk_frontend_tsi` FOREIGN KEY (`t_id`)
    REFERENCES `tenants_service_info` (`t_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Table 9: `admin_user`

**Purpose:** All admin panel users across all tenants and ClickDigim itself.
`CCP_id = NULL` means ClickDigim super admin who can see all tenants.
`CCP_id = set` means the user is scoped to that tenant only.

```sql
CREATE TABLE `admin_user` (
  `admin_id`      INT UNSIGNED                                                        NOT NULL AUTO_INCREMENT,
  `CCP_id`        INT UNSIGNED                                                        NULL,
  `name`          VARCHAR(255)                                                        NOT NULL,
  `email`         VARCHAR(255)                                                        NOT NULL,
  `password`      VARCHAR(255)                                                        NOT NULL,
  `role`          ENUM('super_admin','admin','media_manager','community_manager','support') NOT NULL DEFAULT 'admin',
  `is_active`     TINYINT(1)                                                          NOT NULL DEFAULT 1,
  `created_by`    INT UNSIGNED                                                        NULL,
  `last_login_at` DATETIME                                                            NULL,
  `created_at`    DATETIME                                                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME                                                            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `admin_email_unique` (`email`),
  CONSTRAINT `fk_admin_tenant`     FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_admin_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `admin_user` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column notes:**
| Column | Rule |
|---|---|
| `CCP_id` | NULL = ClickDigim super admin (sees all tenants). SET = scoped to that tenant only |
| `role` | Roles match current permissions.php: super_admin has wildcard, others are scoped |
| `created_by` | Self-referencing FK — which admin created this user |

---

## Table 10: `notification`

**Purpose:** Activity feed for admin dashboard. Populated by backend events only — never by users.
One row per event per lead-tenant relationship.

```sql
CREATE TABLE `notification` (
  `n_id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `LR_id`      INT UNSIGNED NOT NULL,
  `date`       DATE         NOT NULL,
  `time`       TIME         NOT NULL,
  `type`       VARCHAR(60)  NOT NULL,
  `message`    TEXT         NOT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`n_id`),
  INDEX `idx_notification_lr` (`LR_id`),
  CONSTRAINT `fk_notification_lr` FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column notes:**
| Column | Rule |
|---|---|
| `type` | Event code: `appointment_booked`, `appointment_confirmed`, `balance_requested`, `order_placed`, `payment_received`, `contact_only` |
| `message` | Human-readable text shown in admin dashboard feed |
| `is_read` | `0` = unread (shows badge count in admin). `1` = read |

---

## Table 11: `blog`

**Purpose:** Blog posts per tenant. Verified from 6 actual migration files.
Renamed from `posts` table in existing system. `CCP_id` added for multi-tenant scope.

```sql
CREATE TABLE `blog` (
  `b_id`               INT UNSIGNED                          NOT NULL AUTO_INCREMENT,
  `CCP_id`             INT UNSIGNED                          NOT NULL,
  `title`              VARCHAR(500)                          NOT NULL,
  `slug`               VARCHAR(500)                          NOT NULL,
  `subtitle`           VARCHAR(500)                          NULL,
  `excerpt`            TEXT                                  NULL,
  `content`            LONGTEXT                              NOT NULL,
  `featured_image`     VARCHAR(500)                          NULL,
  `featured_image_alt` VARCHAR(500)                          NULL,
  `author_id`          INT UNSIGNED                          NULL,
  `read_time`          INT                                   NOT NULL DEFAULT 5,
  `status`             ENUM('draft','published','archived')  NOT NULL DEFAULT 'draft',
  `published_at`       DATETIME                              NULL,
  `meta_title`         VARCHAR(500)                          NULL,
  `meta_description`   TEXT                                  NULL,
  `views`              INT                                   NOT NULL DEFAULT 0,
  `is_paid`            TINYINT(1)                            NOT NULL DEFAULT 0,
  `price`              DECIMAL(8,2)                          NULL,
  `preview_percentage` TINYINT                               NOT NULL DEFAULT 30,
  `created_at`         DATETIME                              NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME                              NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`b_id`),
  UNIQUE KEY `blog_ccp_slug_unique` (`CCP_id`, `slug`),
  INDEX `idx_blog_status` (`status`, `published_at`),
  CONSTRAINT `fk_blog_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_blog_author` FOREIGN KEY (`author_id`)
    REFERENCES `admin_user` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Supporting blog tables (verified from migrations):**

```sql
CREATE TABLE `blog_categories` (
  `cat_id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `CCP_id`      INT UNSIGNED NOT NULL,
  `name`        VARCHAR(255) NOT NULL,
  `slug`        VARCHAR(255) NOT NULL,
  `description` TEXT         NULL,
  `color`       VARCHAR(7)   NOT NULL DEFAULT '#3B82F6',
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cat_id`),
  UNIQUE KEY `blog_cat_ccp_slug_unique` (`CCP_id`, `slug`),
  CONSTRAINT `fk_blogcat_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blog_tags` (
  `tag_id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `CCP_id`     INT UNSIGNED NOT NULL,
  `name`       VARCHAR(255) NOT NULL,
  `slug`       VARCHAR(255) NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `blog_tag_ccp_slug_unique` (`CCP_id`, `slug`),
  CONSTRAINT `fk_blogtag_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blog_post_categories` (
  `b_id`   INT UNSIGNED NOT NULL,
  `cat_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`b_id`, `cat_id`),
  CONSTRAINT `fk_bpc_blog` FOREIGN KEY (`b_id`)
    REFERENCES `blog` (`b_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bpc_cat` FOREIGN KEY (`cat_id`)
    REFERENCES `blog_categories` (`cat_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blog_post_tags` (
  `b_id`   INT UNSIGNED NOT NULL,
  `tag_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`b_id`, `tag_id`),
  CONSTRAINT `fk_bpt_blog` FOREIGN KEY (`b_id`)
    REFERENCES `blog` (`b_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bpt_tag` FOREIGN KEY (`tag_id`)
    REFERENCES `blog_tags` (`tag_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column notes:**
| Column | Rule |
|---|---|
| `is_paid` | `1` = requires payment to read full content |
| `price` | NULL when `is_paid = 0` |
| `preview_percentage` | % of content shown free before paywall. Default 30 (from migration) |

---

## Table 12: `products`

**Purpose:** Tenant's own products (physical or digital). Each tenant manages their own catalogue.
`product_properties` JSON stores variations, colours, sizes, or any product-specific attributes.

```sql
CREATE TABLE `products` (
  `p_id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `CCP_id`              INT UNSIGNED  NOT NULL,
  `product_name`        VARCHAR(255)  NOT NULL,
  `unit_price`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `product_properties`  JSON          NULL,
  `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`p_id`),
  CONSTRAINT `fk_products_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Table 13: `cart` and `cart_items`

**Purpose:** Holds items a lead has added before placing an order.
When checkout completes, `cart.status = 'converted'` and items copy to `order_items`.

```sql
CREATE TABLE `cart` (
  `cart_id`    INT UNSIGNED                           NOT NULL AUTO_INCREMENT,
  `LR_id`      INT UNSIGNED                           NOT NULL,
  `CCP_id`     INT UNSIGNED                           NOT NULL,
  `status`     ENUM('active','converted','abandoned') NOT NULL DEFAULT 'active',
  `created_at` DATETIME                               NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME                               NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  CONSTRAINT `fk_cart_lr`     FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cart_items` (
  `ci_id`        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `cart_id`      INT UNSIGNED  NOT NULL,
  `service_id`   INT UNSIGNED  NULL,
  `product_id`   INT UNSIGNED  NULL,
  `quantity`     INT UNSIGNED  NOT NULL DEFAULT 1,
  `unit_price`   DECIMAL(10,2) NOT NULL,
  `item_payload` JSON          NULL,
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ci_id`),
  CONSTRAINT `fk_ci_cart`    FOREIGN KEY (`cart_id`)
    REFERENCES `cart` (`cart_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ci_service` FOREIGN KEY (`service_id`)
    REFERENCES `services` (`s_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ci_product` FOREIGN KEY (`product_id`)
    REFERENCES `products` (`p_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**`cart` column notes:**
| Column | Rule |
|---|---|
| `LR_id` | Links the cart to a specific lead-tenant relationship |
| `CCP_id` | Stored directly (not derived) to enable fast tenant-scoped queries: `SELECT * FROM cart WHERE CCP_id = ? AND status = 'active'`. Derivable via `LR_id → leads_relation_table.CCP_id` but kept here as a deliberate performance trade-off |
| `status` | `active` while shopping, `converted` after checkout, `abandoned` if the lead never paid |

---

## Table 14: `orders`

**Purpose:** One order per checkout. Created when cart converts.
Links to a lead-tenant relationship via `LR_id`.

> Note: named `orders` (not `order`) — `ORDER` is a reserved keyword in MySQL.

```sql
CREATE TABLE `orders` (
  `o_id`          INT UNSIGNED                                    NOT NULL AUTO_INCREMENT,
  `LR_id`         INT UNSIGNED                                    NOT NULL,
  `total_amount`  DECIMAL(10,2)                                   NOT NULL,
  `currency_code` VARCHAR(3)                                      NOT NULL DEFAULT 'USD',
  `status`        ENUM('pending','paid','cancelled','refunded')   NOT NULL DEFAULT 'pending',
  `order_date`    DATETIME                                        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `order_notes`   TEXT                                            NULL,
  `created_at`    DATETIME                                        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME                                        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`o_id`),
  CONSTRAINT `fk_order_lr` FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**How to get CCP_id from an order:**
```sql
SELECT lrt.CCP_id
FROM   orders o
JOIN   leads_relation_table lrt ON lrt.LR_id = o.LR_id
WHERE  o.o_id = ?;
```

---

## Table 15: `invoice`

**Purpose:** One invoice per order. Created when order is placed, updated when payment arrives.

```sql
CREATE TABLE `invoice` (
  `i_id`           INT UNSIGNED                                        NOT NULL AUTO_INCREMENT,
  `LR_id`          INT UNSIGNED                                        NOT NULL,
  `order_id`       INT UNSIGNED                                        NOT NULL,
  `invoice_number` VARCHAR(100)                                        NOT NULL,
  `total_amount`   DECIMAL(10,2)                                       NOT NULL,
  `issue_date`     DATE                                                NOT NULL,
  `due_date`       DATE                                                NULL,
  `status`         ENUM('draft','sent','paid','overdue','cancelled')   NOT NULL DEFAULT 'draft',
  `created_at`     DATETIME                                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME                                            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`i_id`),
  UNIQUE KEY `invoice_number_unique` (`invoice_number`),
  CONSTRAINT `fk_invoice_lr`    FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invoice_order` FOREIGN KEY (`order_id`)
    REFERENCES `orders` (`o_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column notes:**
| Column | Rule |
|---|---|
| `invoice_number` | UNIQUE globally. Generated by backend using format `INV-{SELLER_CODE}-{YYYYMMDD}-{RANDOM6}` (e.g. `INV-MAJMQ-20260715-A3F9K2`). Seller code comes from `tenants_service_info.seller_code`. Ensures global uniqueness while maintaining white-label branding per tenant |
| `due_date` | NULL = no due date (common for instant-pay services) |
| `status` | `draft` on create → `sent` when emailed → `paid` on payment capture |

---

## Table 16: `payments`

**Purpose:** One row per payment capture. Stores every payment in the system:
- End user paying a tenant for a service (Featured Blog, appointment, ad, etc.)
- A future tenant paying ClickDigim for a White Label Franchise subscription (`CCP_id = 1`)

Both cases use the same table because in both cases a lead is paying for a service.
`CCP_id` is NOT stored here — it is derivable from `LR_id → leads_relation_table.CCP_id`.

```sql
CREATE TABLE `payments` (
  `P_id`            INT UNSIGNED                            NOT NULL AUTO_INCREMENT,
  `invoice_id`      INT UNSIGNED                            NOT NULL,
  `LR_id`           INT UNSIGNED                            NOT NULL,
  `payment_date`    DATETIME                                NOT NULL,
  `amount`          DECIMAL(10,2)                           NOT NULL,
  `payment_method`  VARCHAR(50)                             NOT NULL,
  `transaction_ref` VARCHAR(255)                            NULL,
  `status`          ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `created_at`      DATETIME                                NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME                                NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`P_id`),
  CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`)
    REFERENCES `invoice` (`i_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_lr`      FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**How to get CCP_id (which tenant received the money) from a payment:**
```sql
SELECT lrt.CCP_id
FROM   payments p
JOIN   leads_relation_table lrt ON lrt.LR_id = p.LR_id
WHERE  p.P_id = ?;
```

**Column notes:**
| Column | Rule |
|---|---|
| `payment_method` | `'paypal'` or `'stripe'` — from actual payment controllers |
| `transaction_ref` | PayPal `capture_id` or Stripe `charge_id` — used for refund and idempotency check |
| `status = 'refunded'` | Set when admin rejects a paid appointment and gateway refund is issued |

---

## Table 17: `testimonials`

**Purpose:** Ratings and reviews submitted by leads for a specific tenant's services.
Columns verified from the existing `ratings` migration and `RatingController`.
Scoped to a tenant via `LR_id` — `CCP_id` is NOT stored directly because it is
derivable via `LR_id → leads_relation_table.CCP_id`.
`LR_id` is required (`NOT NULL`) because every testimonial must belong to a tracked lead.
Requires admin approval before becoming visible (`is_active = 0` by default).

```sql
CREATE TABLE `testimonials` (
  `testimonial_id` INT UNSIGNED       NOT NULL AUTO_INCREMENT,
  `LR_id`          INT UNSIGNED       NOT NULL,
  `author_name`    VARCHAR(255)       NOT NULL,
  `author_title`   VARCHAR(255)       NULL,
  `company_name`   VARCHAR(255)       NULL,
  `country`        VARCHAR(100)       NULL,
  `rating`         TINYINT UNSIGNED   NOT NULL DEFAULT 5,
  `quote`          TEXT               NOT NULL,
  `image_path`     VARCHAR(500)       NULL,
  `avatar_url`     VARCHAR(500)       NULL,
  `is_active`      TINYINT(1)         NOT NULL DEFAULT 0,
  `display_order`  INT UNSIGNED       NOT NULL DEFAULT 999,
  `created_at`     DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`testimonial_id`),
  INDEX `idx_testimonial_lr` (`LR_id`, `is_active`, `display_order`),
  CONSTRAINT `fk_testimonial_lr` FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**How to get CCP_id (which tenant owns the testimonial):**
```sql
SELECT lrt.CCP_id
FROM   testimonials t
JOIN   leads_relation_table lrt ON lrt.LR_id = t.LR_id
WHERE  t.testimonial_id = ?;
```

**Column notes:**
| Column | Rule |
|---|---|
| `LR_id` | Required. Links to the lead-tenant relationship. `CCP_id` is derived from this via `leads_relation_table` |
| `author_name` | Reviewer\'s display name |
| `author_title` | Job title or role (e.g. `CEO`, `Marketing Manager`) |
| `company_name` | Reviewer\'s company |
| `rating` | Star rating 1–5. Validated in backend controller |
| `quote` | The review text (min 10, max 500 chars from existing controller) |
| `image_path` | Optional reviewer photo — stored path after upload |
| `avatar_url` | Auto-generated fallback avatar URL (e.g. `ui-avatars.com`) |
| `is_active` | `0` = pending admin approval. `1` = visible on tenant site |
| `display_order` | Admin-controlled sort order for the tenant\'s testimonials section |

---

## FK Dependency Order (run CREATE in this sequence)

```
 1. clickdigim_customers_profile   ← no dependencies
 2. tenants_service_info           ← depends on clickdigim_customers_profile
 3. frontend                       ← depends on tenants_service_info
 4. admin_user                     ← depends on clickdigim_customers_profile (self-ref for created_by)
 5. leads                          ← no dependencies
 6. leads_relation_table           ← depends on leads + clickdigim_customers_profile
 7. services                       ← depends on clickdigim_customers_profile
 8. products                       ← depends on clickdigim_customers_profile
 9. blog                           ← depends on clickdigim_customers_profile + admin_user
10. blog_categories                ← depends on clickdigim_customers_profile
11. blog_tags                      ← depends on clickdigim_customers_profile
12. blog_post_categories           ← depends on blog + blog_categories
13. blog_post_tags                 ← depends on blog + blog_tags
14. orders                         ← depends on leads_relation_table
15. cart                           ← depends on leads_relation_table + clickdigim_customers_profile
16. cart_items                     ← depends on cart + services + products
17. order_items                    ← depends on orders + leads_relation_table + services + products
18. invoice                        ← depends on orders + leads_relation_table
19. payments                       ← depends on invoice + leads_relation_table
20. appointments                   ← depends on leads_relation_table + services + orders
21. notification                   ← depends on leads_relation_table
22. testimonials                   ← depends on leads_relation_table
```
