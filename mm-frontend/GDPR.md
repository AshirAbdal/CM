# GDPR Cookie Consent - Research and Implementation Plan

Status: research and recommendation (no code changes made yet)
Scope: Majestic Marquees web properties - customer website (`mm-frontend`) and blog (`mm-blog`)
Last reviewed: 2026-06-25

> Disclaimer: This document is practical compliance guidance, not legal advice. All sources used (Didomi, CookieYes, iubenda) and this plan recommend a final review by a Data Protection Officer (DPO) or qualified lawyer before go-live, especially the reCAPTCHA classification.

---

## 1. Sources reviewed

- Didomi - 10 examples of GDPR-compliant cookie banners: https://www.didomi.io/blog/examples-gdpr-cookie-banners
- CookieYes - 8 Top GDPR Consent Form Examples: https://www.cookieyes.com/blog/gdpr-consent-form-examples/
- iubenda - GDPR consent form examples (what to do and not to do): https://www.iubenda.com/en/blog/gdpr-consent-form-examples/

Supporting authoritative references:

- EDPB Cookie Banner Taskforce Report (January 2023)
- ePrivacy Directive, Article 5(3)
- GDPR Article 4(11), Article 7, Recital 32 (definition and conditions of consent)
- GDPR Article 3(2) (territorial scope for non-EU businesses)
- Indonesia UU PDP - Law No. 27 of 2022 on Personal Data Protection (enforceable since October 2024)

---

## 2. What actually runs on the site (real footprint)

A scan of `mm-frontend` shows a very light third-party and cookie footprint. There is NO Google Analytics, NO Facebook Pixel, and NO advertising or marketing trackers.

| What loads | Where | Sets cookies / sends data to a third party? | Consent needed? |
|---|---|---|---|
| PHP session (`session_start`) | `public/index.php` | First-party session cookie only | No - strictly necessary |
| Google reCAPTCHA v2 | `layout/page.php` | Yes - loads from google.com, sets `_GRECAPTCHA`, sends device data to Google | Contested (see section 7) |
| Google Maps embed (iframe) | `pages/contact.php` | Yes - sets Google cookies and sends visitor IP | Yes |
| Google Fonts | `layout/page.php` | No cookie, but sends visitor IP to Google | Best practice: self-host |
| Tailwind CDN | `layout/page.php` | No cookie (third-party request only) | No (performance note only) |
| YouTube | `layout/page.php`, `pages/contact.php` | It is only a link (`<a href>`), NOT an embed | No - fine as-is |

Key takeaway: the only consent-relevant items today are reCAPTCHA and the Google Maps embed. The banner can be genuinely simple, but it should be built as a category-based system so it is future-proof when analytics is added later.

### 2.1 mm-blog (the blog subdomain) - footprint and options

A scan of `mm-blog` shows an even lighter footprint than the main website. There is NO reCAPTCHA, NO Maps, NO analytics, NO ad or marketing trackers, and NO third-party cookies at all.

| What loads | Where | Sets cookies / sends data to a third party? | Consent-relevant? |
|---|---|---|---|
| PHP session (`session_start`) | `public/index.php` | First-party session cookie only | No - strictly necessary, exempt |
| Google Fonts | `layout/page.php` | No cookie, but sends visitor IP to Google | Yes - IP transfer only |
| Tailwind CDN | `layout/page.php` | No cookie, but sends visitor IP to the CDN | Low - IP transfer only |

Whether the blog needs a banner depends on one choice:

- If Google Fonts and Tailwind are self-hosted on the blog: the only thing left is the first-party session cookie, which is exempt. No banner is strictly required - only a Cookie/Privacy link in the footer disclosing that session cookie.
- If Fonts and Tailwind keep loading from Google/the CDN (as today): the visitor's IP is sent to a third party before any consent. That is the one consent-relevant item, so either get consent for it or remove it (self-host).

Recommendation: put the SAME banner on the blog anyway, for two reasons:

1. Consistency - visitors move between the `website.` and `blog.` subdomains; one site asking for consent and the other not looks broken and undermines trust.
2. Future-proofing - blogs almost always get analytics added later (to track readership). Building the category-based banner in now means no redesign when that happens.

Cleanest combination: self-host Fonts and Tailwind on both sites AND ship the shared banner. The banner is then mostly a formality today but ready the moment Maps, analytics, or anything else is added. The same consent component, categories, and consent-logging endpoint should be reused across `mm-frontend` and `mm-blog`.

---

## 3. Does GDPR apply? (Yes - plus an Indonesian law)

Majestic Marquees (PT Majestic Marquees and Tents) operates in Bali, Indonesia. Two laws apply:

- GDPR (EU), Article 3(2): applies to non-EU businesses that offer goods or services to people in the EU. A Bali destination-wedding and event marquee business with an English-language site attracts EU customers, so GDPR reaches the EU-facing activity. The site's legal pages already reference GDPR and CCPA.
- Indonesia UU PDP (Law No. 27 of 2022): Indonesia's GDPR-style law, fully enforceable since October 2024. It is modeled on GDPR and requires consent as a lawful basis. So even for Indonesian visitors a proper consent mechanism is required.

Conclusion: build ONE GDPR-grade banner and show it to everyone. That satisfies both laws.

---

## 4. The 7 rules the banner MUST follow

These requirements are consistent across all three sources plus the EDPB Cookie Banner Taskforce Report and the ePrivacy Directive / GDPR articles cited above.

1. "Reject All" must be as easy and visible as "Accept All" - same size, same layer, same prominence. No hidden reject, no greyed-out reject next to a colorful accept (that is a banned dark pattern).
2. No pre-ticked boxes. Every non-essential category defaults to OFF. Consent equals an active click.
3. Block first, load after. Non-essential scripts (Maps, and reCAPTCHA if treated as non-essential) must NOT run until the user agrees.
4. Granular categories. Let users accept Functional but reject Marketing, and so on.
5. Name names. Identify the controller (PT Majestic Marquees and Tents) and third parties (Google), and link to the Cookie and Privacy policies.
6. Easy withdrawal, anytime. Provide a persistent "Cookie settings" control (footer link) that reopens the panel. Withdrawing must be as easy as giving consent.
7. Keep proof. Log each choice: timestamp, the categories chosen, and the banner/policy version. The project already has a database and Logger, so store a consent record.

Two things that are explicitly NOT allowed:

- A cookie wall that forces consent in order to use the site.
- Treating "continued browsing" as consent.

---

## 5. Recommended banner design

A two-layer pattern, matching the CookieYes and Didomi style, in the brand palette (forest-green `forest-800` and tan `tan-500`).

### Layer 1 - bottom bar (first thing a visitor sees)

```
We value your privacy
We use cookies to run this site, keep our forms secure, and show
you our location map. You can accept all, reject non-essential,
or choose. See our Cookie Policy.

   [ Reject All ]      [ Customize ]      [ Accept All ]
   (outline, equal)    (outline, equal)   (forest-800 solid)
```

All three buttons must be the same size. "Accept All" can be the filled brand button; "Reject All" must be equally obvious (an outline button of the same size is fine - what is banned is making reject smaller or hidden).

### Layer 2 - "Customize" opens a preferences panel

```
Manage cookie preferences

 Strictly necessary           [ Always on ]  (locked)
   Session + security. Required for the site to work.

 Functional                   [ off ]
   Fonts + form anti-spam (reCAPTCHA).

 Location map (Google Maps)   [ off ]
   Loads the interactive map on the Contact page.

 Analytics                    [ off ]  (future)
   Anonymous usage stats. Not active yet.

        [ Reject All ]   [ Save preferences ]
```

### Persistent re-open

A small "Cookie settings" link in the footer (next to Privacy and Cookie Policy) that reopens Layer 2. This is the withdrawal mechanism required by rule 6.

---

## 6. Recommended cookie categories (matched to the real footprint)

Because there are no analytics or ads today, keep it to 3 active categories plus 1 placeholder:

1. Strictly necessary (always on, locked): PHP session cookie.
2. Functional (default off): Google Fonts and Google reCAPTCHA.
3. Location map (default off): the Google Maps iframe on the Contact page.
4. Analytics (default off, shown as "not active yet"): reserved for GA4 or Matomo later, so the banner does not need a redesign when analytics is added.

---

## 7. Three Google-specific fixes that make compliance painless

1. Google Maps - "click to load". Replace the iframe on `pages/contact.php` with a static placeholder image plus a "Load map" button. The map (and its cookies) only load if the visitor clicks or has already consented to the Location category.
2. reCAPTCHA - decide and document. Two valid options:
   - Treat it as security-necessary (it protects a service the user requested) and disclose it. This is defensible and simplest.
   - Or, the cleaner privacy route: swap to Cloudflare Turnstile or Friendly Captcha - no Google cookies, no consent debate, drop-in replacement for the loader in `layout/page.php` and the verification in `lib/helpers.php`. Recommended if zero ambiguity is preferred.
3. Self-host Google Fonts. Download the Playfair Display and Open Sans files and serve them from the site's own domain instead of loading from `fonts.googleapis.com` in `layout/page.php`. This removes the IP-to-Google transfer entirely (the issue behind the 2022 German Google Fonts ruling) and loads faster. After this, Fonts no longer needs to be a consent item.

Minor note: `cdn.tailwindcss.com` is Tailwind's development/prototype CDN; Tailwind advises a compiled build for production. No cookie issue, just performance and best practice.

If fixes 1 and 3 are done, the banner becomes trivial: essentially just the Maps toggle plus a future Analytics toggle.

---

## 8. Build vs buy

- Custom (recommended for now): The footprint is tiny and the stack is already in place (PHP, same-origin proxy, Logger, bilingual legal pages). A small vanilla JavaScript banner plus a `consent` cookie plus a server-side consent log gives full GDPR and UU PDP compliance with zero recurring fees.
- CMP (CookieYes, iubenda, Cookiebot, Didomi): worth it only when many trackers are added or when automatic cookie scanning, IAB TCF support, and audit-ready consent records are needed out of the box. Overkill today.

---

## 9. Ready-to-use banner copy (bilingual: English and Bahasa Indonesia)

### Layer 1 - English

> We value your privacy. We use cookies to run this website, keep our contact and quote forms secure, and to show our location map. Click "Accept All" to allow all cookies, "Reject All" to keep only essential ones, or "Customize" to choose. Read our Cookie Policy.
>
> [Reject All] [Customize] [Accept All]

### Layer 1 - Bahasa Indonesia

> Kami menghargai privasi Anda. Kami menggunakan cookie untuk menjalankan situs ini, menjaga keamanan formulir kontak dan penawaran, serta menampilkan peta lokasi kami. Klik "Terima Semua" untuk mengizinkan semua cookie, "Tolak Semua" untuk hanya yang penting, atau "Sesuaikan" untuk memilih. Baca Kebijakan Cookie kami.
>
> [Tolak Semua] [Sesuaikan] [Terima Semua]

### Category descriptions (English / Bahasa Indonesia)

- Strictly necessary / Diperlukan: "Required for the site to work and to secure forms. Always active." / "Diperlukan agar situs berfungsi dan formulir aman. Selalu aktif."
- Functional / Fungsional: "Loads fonts and spam protection on our forms." / "Memuat font dan perlindungan spam pada formulir kami."
- Location map / Peta lokasi: "Loads the Google map on our Contact page." / "Memuat peta Google di halaman Kontak kami."
- Analytics / Analitik: "Anonymous usage statistics (not active yet)." / "Statistik penggunaan anonim (belum aktif)."

---

## 10. Suggested next step

If this plan is approved, the custom banner can be implemented as:

- Layer 1 (bottom bar) and Layer 2 (preferences panel), in the brand colors, bilingual.
- Prior-blocking for Maps and reCAPTCHA (scripts load only after consent).
- A consent-logging endpoint that reuses the existing Logger and database, recording timestamp, chosen categories, and policy version.

No files will be changed until this approach is confirmed.
