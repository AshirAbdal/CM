# Blog System Setup Guide — mm-admin + mm-blog

> **Goal.** Recreate the ClickDigim blog system inside the Majestic Marquees white-label
> stack: a full **blog manager in `mm-admin`** and a public **blog site in `mm-blog`**
> that looks exactly like the live blog at <https://blog.majesticmarquees.com/>.
>
> **Scope.** Posts, categories, tags, featured image, rich-text content, draft/publish,
> SEO, public listing + single post + related posts.
>
> **Explicitly excluded.** All payment / paywall / purchase / magic-link logic
> (`is_paid`, `price`, `preview_percentage`, post-purchases, access tokens). Every
> post is **free**. See [What we deliberately leave out](#what-we-deliberately-leave-out).

---

## Table of contents

1. [How the real system works (source of truth)](#1-how-the-real-system-works-source-of-truth)
2. [Target architecture in the MM stack](#2-target-architecture-in-the-mm-stack)
3. [Design reference (match the live site)](#3-design-reference-match-the-live-site)
4. [Prerequisites](#4-prerequisites)
5. [PART A — Backend API (`backend_whitelevel`)](#part-a--backend-api-backend_whitelevel)
6. [PART B — Admin manager (`mm-admin`)](#part-b--admin-manager-mm-admin)
7. [PART C — Public blog (`mm-blog`)](#part-c--public-blog-mm-blog)
8. [PART D — End-to-end setup checklist](#part-d--end-to-end-setup-checklist)
9. [PART E — Testing & verification](#part-e--testing--verification)
10. [PART F — Deployment](#part-f--deployment)
11. [Appendix — API reference, fields, exclusions](#appendix)

---

## 1. How the real system works (source of truth)

The blog you are cloning is the **ClickDigim blog** in the `debian/` monorepo. It has three layers:

| Layer | Tech (source) | What it does |
|---|---|---|
| Backend API | Laravel (`backend/`) | `BlogController` (public reads) + `Admin/BlogController` (CRUD posts, categories, tags, image upload) |
| Admin UI | React + Vite (`admin/`) | `Posts.tsx` (list) + `PostEditor.tsx` (CKEditor 5 editor, image upload, inline categories/tags, draft/publish) |
| Public site | Next.js (`frontend-blog-nextjs/`) | Home grid + `[slug]` post page, SSG/ISR, `generateMetadata`, JSON-LD `BlogPosting` |

**Core behaviours to preserve (these are the spec):**

- A **post** has: `title`, `slug` (unique), `subtitle`, `excerpt`, `content` (HTML), `featured_image` + `featured_image_alt`, `author`, `read_time` (auto), `status` (`draft`/`published`/`archived`), `published_at`, `meta_title`, `meta_description`, `views`, plus many-to-many **categories** and **tags**.
- **Slug** is auto-generated from the title and made unique (`-2`, `-3` on collision).
- **read_time** is auto-calculated: `max(1, round(word_count / 200))` minutes.
- **Publishing** sets `published_at = now()` the first time status becomes `published`.
- Public list endpoint supports **pagination**, **category** and **tag** filters; single post returns the post **+ related posts**; categories/tags expose a published **post_count**.
- Rich text is authored with **CKEditor 5** (ClassicEditor) and inline images upload to the backend, which returns a URL embedded into the HTML.
- Public content is rendered as pre-sanitised HTML; SEO uses per-post `<title>`/meta + **JSON-LD `BlogPosting`**.

---

## 2. Target architecture in the MM stack

Unlike the source (Laravel + React + Next.js), the Majestic Marquees stack is **raw PHP 8.4**
everywhere, multi-tenant by `CCP_id`, and already wired together. You will **not** introduce a
framework — you mirror the blog feature into the existing patterns.

```mermaid
graph TB
    subgraph Browser
        A1[mm-admin pages<br/>pages/posts.php · post_editor.php]
        B1[mm-blog pages<br/>home.php · post.php]
    end
    subgraph Edge["mm-admin / mm-blog (raw PHP)"]
        A2[public/index.php router<br/>session JWT + can()/can_any()]
        B2[public/index.php router<br/>server-side render]
    end
    subgraph API["backend_whitelevel (raw PHP 8.4)"]
        R[src/Router.php<br/>'METHOD /wl/path' => Controller]
        C[src/Controllers/BlogController.php<br/>NEW]
        MW[validate_tenant_key X-API-Key → CCP_id<br/>require_auth Bearer JWT<br/>require_permission posts.*]
    end
    DB[(whitelevel_db<br/>blog · blog_categories · blog_tags<br/>blog_post_categories · blog_post_tags)]

    A1 -->|fetch API_BASE + /wl/admin/blog/*<br/>X-API-Key + Bearer JWT| R
    B2 -->|server-side cURL /wl/public/blog/*<br/>X-API-Key only| R
    R --> MW --> C --> DB
```

### The three things you build

| # | Where | Path | What |
|---|---|---|---|
| **A** | `backend_whitelevel/backend/` | `src/Controllers/BlogController.php`, `src/Router.php`, RBAC catalog | The data + API under `/wl/...`, scoped to the tenant `CCP_id` |
| **B** | `CM/mm-admin/` | `pages/posts.php`, `pages/post_editor.php`, `public/index.php`, `layout/page.php` | The blog manager UI (list + editor) |
| **C** | `CM/mm-blog/` | `pages/home.php`, `pages/post.php`, `public/index.php`, `lib/helpers.php` | The public, SEO-friendly blog styled like the live site |

### Key facts about the existing MM stack (verified)

- **Tenant:** Majestic Marquees = `CCP_id 2`, API key `mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462`.
- **Backend base URL:** `http://localhost:8000` (dev) / `https://apiv1.clickdigim.com` (prod).
- **Backend auth:** `X-API-Key` header → resolves tenant `CCP_id`; `Authorization: Bearer <JWT>` (RS256) for `/wl/admin/*`. RBAC via `require_permission()`.
- **Blog tables already exist (empty)** in `whitelevel_db`: `blog`, `blog_categories`, `blog_tags`, `blog_post_categories`, `blog_post_tags` — already tenant-scoped with `CCP_id` and `UNIQUE(CCP_id, slug)`.
- **mm-admin** pages call the backend **directly** from JS with `X-API-Key` + the session JWT (same pattern as `pages/reviews.php`).
- **mm-blog** renders **server-side** (PHP) for SEO and already ships the full brand design system in `layout/page.php` (cream `#f5f1e8`, forest, tan `#a57b5b`, Cormorant Garamond + Inter, `.prose-blog`, `.hero-blog`, `.btn-primary`, `.container-x`).

---

## 3. Design reference (match the live site)

Captured directly from <https://blog.majesticmarquees.com/>:

| Token | Value | Notes |
|---|---|---|
| Page background | `#f5f1e8` (cream) | Header, body and footer all share it |
| Accent / buttons / links | `#a57b5b` (tan/brown) | "Read More" buttons, post titles in lists |
| Body text | `#333333` | |
| Heading font | **Playfair Display** (serif) | mm-blog ships **Cormorant Garamond** with Playfair fallback — same elegant serif feel; keep mm-blog's `font-display` |
| Body font | **Open Sans** | mm-blog ships **Inter** — keep mm-blog's `font-sans` |
| Footer | cream `#f0ead9`, uppercase nav, social icons, `© <year> Majestic Marquees` | Already present in mm-blog layout |

**Layout to reproduce:**

- **Hero**: full-width featured image of the latest post with a dark gradient overlay; white serif title; a meta row with **author**, **date**, **time** (small icons); a tan **Read More** button. Use the existing `.hero-blog` class (it forces white text).
- **Listing**: a search box + a vertical list/grid of cards — each card has a serif **tan title**, a meta line (`Author • 12 March 2026 • 19:44`), an excerpt, and a `Read More »` link.
- **Single post**: large serif title, meta row, featured image, then `.prose-blog` body, tags footer, and a "related posts" grid.

> You do **not** need to redefine colours or fonts — `mm-blog/layout/page.php` already
> defines them. Build the markup with the existing utility/component classes.

---

## 4. Prerequisites

- Local stack running via `bash start-backend.sh` from `/Users/ashirabdalravee/backend_whitelevel/`
  (backend `:8000`, admin `:8002`, site/blog `:8001`).
- XAMPP MariaDB with `whitelevel_db` imported (it already contains the empty `blog*` tables).
- An admin login for mm-admin that can be granted the new `posts.*` permissions.
- A code editor on all three folders: `backend_whitelevel/backend`, `CM/mm-admin`, `CM/mm-blog`.

---

# PART A — Backend API (`backend_whitelevel`)

Everything here lives in `/Users/ashirabdalravee/backend_whitelevel/backend/`.

## A1. Confirm the database tables

The schema already exists in `whitelevel_db` (defined in `debian/whitelevel_db.sql`). Verify:

```sql
SHOW TABLES LIKE 'blog%';
-- Expect: blog, blog_categories, blog_tags, blog_post_categories, blog_post_tags
```

Field summary (the columns the API uses):

| Table | Columns you use |
|---|---|
| `blog` | `b_id, CCP_id, title, slug, subtitle, excerpt, content, featured_image, featured_image_alt, author_id, read_time, status, published_at, meta_title, meta_description, views, created_at, updated_at` |
| `blog_categories` | `cat_id, CCP_id, name, slug, description, color` |
| `blog_tags` | `tag_id, CCP_id, name, slug` |
| `blog_post_categories` | `b_id, cat_id` |
| `blog_post_tags` | `b_id, tag_id` |

> **Payment columns are present but unused.** `blog.is_paid`, `blog.price`,
> `blog.preview_percentage` exist in the table. **Do not read or write them.** Leave the DB
> defaults (`is_paid=0`, `preview_percentage=30`) and never surface them in the API or UI.

Uniqueness is **per tenant**: `UNIQUE(CCP_id, slug)` — so the same slug can exist for different tenants. Every query MUST filter by the acting tenant's `CCP_id`.

## A2. Register RBAC permissions

Add two permission keys to the RBAC catalog (the same place `reviews.view` / `reviews.manage`
are defined — `src/Rbac.php` and the `RoleController::catalog` listing), then grant them to the
**Admin** role:

| Permission | Grants |
|---|---|
| `posts.view` | Read the post/category/tag lists in the admin |
| `posts.manage` | Create / edit / delete posts, categories, tags, and upload images |

These flow into the admin session as `$_SESSION['permissions']`, where `can('posts.view')`
gates the sidebar and pages in mm-admin (Part B).

## A3. Create `src/Controllers/BlogController.php`

Mirror the conventions used by `TestimonialController.php`:
`validate_tenant_key()` → `$tenant['CCP_id']`, `require_auth()` → `$payload`,
`require_permission($payload, 'posts.manage')`, `db()` (PDO), `respond($code, $array)`.

```php
<?php
// -------------------------------------------------------------------
// BlogController — tenant blog (posts, categories, tags)
//
// Public (X-API-Key only):
//   GET /wl/public/blog/posts                 list published (page, per_page, category, tag)
//   GET /wl/public/blog/posts/{slug}          single published post + related
//   GET /wl/public/blog/categories            categories with published post_count
//   GET /wl/public/blog/tags                  tags with published post_count
//
// Admin (X-API-Key + Bearer JWT, posts.view / posts.manage):
//   GET    /wl/admin/blog/posts               list all (status filter, paged)   posts.view
//   GET    /wl/admin/blog/posts/{id}          one post by id or slug             posts.view
//   POST   /wl/admin/blog/posts               create                             posts.manage
//   PUT    /wl/admin/blog/posts/{id}          update                             posts.manage
//   DELETE /wl/admin/blog/posts/{id}          delete                             posts.manage
//   POST   /wl/admin/blog/upload-image        inline editor image upload         posts.manage
//   GET    /wl/admin/blog/categories          list                               posts.view
//   POST   /wl/admin/blog/categories          create                             posts.manage
//   PUT    /wl/admin/blog/categories/{id}     update                             posts.manage
//   DELETE /wl/admin/blog/categories/{id}     delete                             posts.manage
//   GET    /wl/admin/blog/tags                list                               posts.view
//   POST   /wl/admin/blog/tags                create                             posts.manage
//   DELETE /wl/admin/blog/tags/{id}           delete                             posts.manage
//
// Every query is filtered by the acting tenant CCP_id (also the IDOR guard).
// -------------------------------------------------------------------
class BlogController
{
    private const PER_PAGE_MAX = 50;

    // ---------- helpers ------------------------------------------------
    private function slugify(string $text): string
    {
        $s = strtolower(trim($text));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim($s, '-') ?: 'post';
    }

    private function uniqueSlug(int $ccp, string $base, ?int $excludeId = null): string
    {
        $slug = $this->slugify($base);
        $i = 1;
        while (true) {
            $candidate = $i === 1 ? $slug : "$slug-$i";
            $sql = 'SELECT b_id FROM blog WHERE CCP_id = ? AND slug = ?';
            $args = [$ccp, $candidate];
            if ($excludeId) { $sql .= ' AND b_id <> ?'; $args[] = $excludeId; }
            $row = db()->prepare($sql); $row->execute($args);
            if (!$row->fetch()) return $candidate;
            $i++;
        }
    }

    private function readTime(string $html): int
    {
        $words = str_word_count(strip_tags($html));
        return max(1, (int) round($words / 200));
    }

    private function postShape(array $p, array $cats, array $tags): array
    {
        return [
            'id'                 => (int) $p['b_id'],
            'title'              => $p['title'],
            'slug'               => $p['slug'],
            'subtitle'           => $p['subtitle'],
            'excerpt'            => $p['excerpt'],
            'content'            => $p['content'],
            'featured_image_url' => $p['featured_image'] ? media_url($p['featured_image']) : null,
            'featured_image_alt' => $p['featured_image_alt'],
            'author'             => $p['author_id'] ? ['id' => (int)$p['author_id'], 'name' => $p['author_name'] ?? ''] : null,
            'read_time'          => (int) $p['read_time'],
            'status'             => $p['status'],
            'published_at'       => $p['published_at'],
            'meta_title'         => $p['meta_title'],
            'meta_description'   => $p['meta_description'],
            'views'              => (int) $p['views'],
            'categories'         => $cats,
            'tags'               => $tags,
            'created_at'         => $p['created_at'],
            'updated_at'         => $p['updated_at'],
        ];
    }

    // ---------- PUBLIC -------------------------------------------------
    public function publicIndex(): void
    {
        $t = validate_tenant_key(); $ccp = (int) $t['CCP_id'];
        $perPage = min(self::PER_PAGE_MAX, max(1, (int) ($_GET['per_page'] ?? 12)));
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $where = "b.CCP_id = ? AND b.status = 'published'";
        $args  = [$ccp];

        if (!empty($_GET['category'])) {
            $where .= " AND b.b_id IN (SELECT bpc.b_id FROM blog_post_categories bpc
                        JOIN blog_categories c ON c.cat_id = bpc.cat_id
                        WHERE c.CCP_id = ? AND c.slug = ?)";
            $args[] = $ccp; $args[] = $_GET['category'];
        }
        if (!empty($_GET['tag'])) {
            $where .= " AND b.b_id IN (SELECT bpt.b_id FROM blog_post_tags bpt
                        JOIN blog_tags g ON g.tag_id = bpt.tag_id
                        WHERE g.CCP_id = ? AND g.slug = ?)";
            $args[] = $ccp; $args[] = $_GET['tag'];
        }

        $total = (int) db_scalar("SELECT COUNT(*) FROM blog b WHERE $where", $args);

        $stmt = db()->prepare(
            "SELECT b.*, a.name AS author_name
               FROM blog b
          LEFT JOIN admin_user a ON a.admin_id = b.author_id
              WHERE $where
           ORDER BY b.published_at DESC
              LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($args);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = array_map(fn($p) => $this->postShape(
            $p, $this->catsFor((int)$p['b_id']), $this->tagsFor((int)$p['b_id'])
        ), $rows);

        respond(200, [
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ]);
    }

    public function publicShow(string $slug): void
    {
        $t = validate_tenant_key(); $ccp = (int) $t['CCP_id'];
        $stmt = db()->prepare(
            "SELECT b.*, a.name AS author_name FROM blog b
          LEFT JOIN admin_user a ON a.admin_id = b.author_id
              WHERE b.CCP_id = ? AND b.slug = ? AND b.status = 'published' LIMIT 1"
        );
        $stmt->execute([$ccp, $slug]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) respond(404, ['success' => false, 'error' => 'Post not found']);

        // increment views (best effort)
        db()->prepare('UPDATE blog SET views = views + 1 WHERE b_id = ?')->execute([$p['b_id']]);

        $related = db()->prepare(
            "SELECT b.*, a.name AS author_name FROM blog b
          LEFT JOIN admin_user a ON a.admin_id = b.author_id
              WHERE b.CCP_id = ? AND b.status='published' AND b.b_id <> ?
           ORDER BY b.published_at DESC LIMIT 3"
        );
        $related->execute([$ccp, $p['b_id']]);

        respond(200, [
            'success' => true,
            'data'    => $this->postShape($p, $this->catsFor((int)$p['b_id']), $this->tagsFor((int)$p['b_id'])),
            'related' => array_map(fn($r) => $this->postShape(
                $r, $this->catsFor((int)$r['b_id']), $this->tagsFor((int)$r['b_id'])
            ), $related->fetchAll(PDO::FETCH_ASSOC)),
        ]);
    }

    public function publicCategories(): void
    {
        $t = validate_tenant_key(); $ccp = (int) $t['CCP_id'];
        $stmt = db()->prepare(
            "SELECT c.cat_id, c.name, c.slug, c.description, c.color,
                    (SELECT COUNT(*) FROM blog_post_categories bpc
                       JOIN blog b ON b.b_id = bpc.b_id
                      WHERE bpc.cat_id = c.cat_id AND b.status='published') AS posts_count
               FROM blog_categories c WHERE c.CCP_id = ? ORDER BY c.name"
        );
        $stmt->execute([$ccp]);
        respond(200, ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function publicTags(): void
    {
        $t = validate_tenant_key(); $ccp = (int) $t['CCP_id'];
        $stmt = db()->prepare(
            "SELECT g.tag_id, g.name, g.slug,
                    (SELECT COUNT(*) FROM blog_post_tags bpt
                       JOIN blog b ON b.b_id = bpt.b_id
                      WHERE bpt.tag_id = g.tag_id AND b.status='published') AS posts_count
               FROM blog_tags g WHERE g.CCP_id = ? ORDER BY g.name"
        );
        $stmt->execute([$ccp]);
        respond(200, ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // ---------- ADMIN: posts ------------------------------------------
    public function adminIndex(): void
    {
        $payload = require_auth(); require_permission($payload, 'posts.view');
        $ccp = (int) $payload['CCP_id'];
        $perPage = min(self::PER_PAGE_MAX, max(1, (int) ($_GET['per_page'] ?? 15)));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $where = 'b.CCP_id = ?'; $args = [$ccp];
        if (!empty($_GET['status']) && in_array($_GET['status'], ['draft','published','archived'], true)) {
            $where .= ' AND b.status = ?'; $args[] = $_GET['status'];
        }
        $total = (int) db_scalar("SELECT COUNT(*) FROM blog b WHERE $where", $args);
        $stmt = db()->prepare(
            "SELECT b.*, a.name AS author_name FROM blog b
          LEFT JOIN admin_user a ON a.admin_id = b.author_id
              WHERE $where ORDER BY b.created_at DESC LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($args);
        $data = array_map(fn($p) => $this->postShape(
            $p, $this->catsFor((int)$p['b_id']), $this->tagsFor((int)$p['b_id'])
        ), $stmt->fetchAll(PDO::FETCH_ASSOC));

        respond(200, ['success' => true, 'data' => $data, 'meta' => [
            'current_page' => $page, 'per_page' => $perPage, 'total' => $total,
            'last_page' => (int) ceil($total / $perPage),
        ]]);
    }

    public function adminShow(string $idOrSlug): void
    {
        $payload = require_auth(); require_permission($payload, 'posts.view');
        $ccp = (int) $payload['CCP_id'];
        $col = ctype_digit($idOrSlug) ? 'b.b_id' : 'b.slug';
        $stmt = db()->prepare(
            "SELECT b.*, a.name AS author_name FROM blog b
          LEFT JOIN admin_user a ON a.admin_id = b.author_id
              WHERE b.CCP_id = ? AND $col = ? LIMIT 1"
        );
        $stmt->execute([$ccp, $idOrSlug]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) respond(404, ['success' => false, 'error' => 'Post not found']);
        respond(200, ['success' => true, 'data' =>
            $this->postShape($p, $this->catsFor((int)$p['b_id']), $this->tagsFor((int)$p['b_id']))]);
    }

    public function adminStore(): void
    {
        $payload = require_auth(); require_permission($payload, 'posts.manage');
        $ccp = (int) $payload['CCP_id'];
        $in  = json_decode(file_get_contents('php://input'), true) ?? [];

        $title = trim((string)($in['title'] ?? ''));
        if ($title === '') respond(422, ['success' => false, 'error' => 'Title is required']);
        $content = (string)($in['content'] ?? '');
        $status  = in_array($in['status'] ?? 'draft', ['draft','published','archived'], true) ? $in['status'] : 'draft';
        $slug    = $this->uniqueSlug($ccp, $in['slug'] ?? $title);

        $stmt = db()->prepare(
            "INSERT INTO blog
               (CCP_id, title, slug, subtitle, excerpt, content, featured_image, featured_image_alt,
                author_id, read_time, status, published_at, meta_title, meta_description)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $ccp, $title, $slug, $in['subtitle'] ?? null, $in['excerpt'] ?? null, $content,
            $in['featured_image'] ?? null, $in['featured_image_alt'] ?? null,
            (int) $payload['sub'], $this->readTime($content), $status,
            $status === 'published' ? date('Y-m-d H:i:s') : null,
            $in['meta_title'] ?? null, $in['meta_description'] ?? null,
        ]);
        $id = (int) db()->lastInsertId();
        $this->syncCategories($ccp, $id, $in['category_ids'] ?? []);
        $this->syncTags($ccp, $id, $in['tag_ids'] ?? []);

        respond(201, ['success' => true, 'data' => ['id' => $id, 'slug' => $slug]]);
    }

    public function adminUpdate(string $id): void
    {
        $payload = require_auth(); require_permission($payload, 'posts.manage');
        $ccp = (int) $payload['CCP_id']; $id = (int) $id;
        $cur = db()->prepare('SELECT * FROM blog WHERE CCP_id = ? AND b_id = ?');
        $cur->execute([$ccp, $id]);
        $post = $cur->fetch(PDO::FETCH_ASSOC);
        if (!$post) respond(404, ['success' => false, 'error' => 'Post not found']);

        $in = json_decode(file_get_contents('php://input'), true) ?? [];
        $title   = trim((string)($in['title'] ?? $post['title']));
        $content = array_key_exists('content', $in) ? (string)$in['content'] : $post['content'];
        $status  = in_array($in['status'] ?? $post['status'], ['draft','published','archived'], true) ? ($in['status'] ?? $post['status']) : $post['status'];
        $slug    = !empty($in['slug']) && $in['slug'] !== $post['slug']
            ? $this->uniqueSlug($ccp, $in['slug'], $id) : $post['slug'];
        $publishedAt = $post['published_at'];
        if ($status === 'published' && !$publishedAt) $publishedAt = date('Y-m-d H:i:s');

        db()->prepare(
            "UPDATE blog SET title=?, slug=?, subtitle=?, excerpt=?, content=?,
                 featured_image=?, featured_image_alt=?, read_time=?, status=?, published_at=?,
                 meta_title=?, meta_description=? WHERE CCP_id=? AND b_id=?"
        )->execute([
            $title, $slug, $in['subtitle'] ?? $post['subtitle'], $in['excerpt'] ?? $post['excerpt'],
            $content, $in['featured_image'] ?? $post['featured_image'],
            $in['featured_image_alt'] ?? $post['featured_image_alt'], $this->readTime($content),
            $status, $publishedAt, $in['meta_title'] ?? $post['meta_title'],
            $in['meta_description'] ?? $post['meta_description'], $ccp, $id,
        ]);
        if (array_key_exists('category_ids', $in)) $this->syncCategories($ccp, $id, $in['category_ids']);
        if (array_key_exists('tag_ids', $in))      $this->syncTags($ccp, $id, $in['tag_ids']);

        respond(200, ['success' => true, 'data' => ['id' => $id, 'slug' => $slug]]);
    }

    public function adminDestroy(string $id): void
    {
        $payload = require_auth(); require_permission($payload, 'posts.manage');
        db()->prepare('DELETE FROM blog WHERE CCP_id = ? AND b_id = ?')
            ->execute([(int) $payload['CCP_id'], (int) $id]); // pivots cascade
        respond(200, ['success' => true]);
    }

    // ---------- ADMIN: categories & tags ------------------------------
    public function categoryIndex(): void  { $this->taxIndex('blog_categories', 'cat_id', 'posts.view'); }
    public function tagIndex(): void        { $this->taxIndex('blog_tags', 'tag_id', 'posts.view'); }

    public function categoryStore(): void
    {
        $payload = require_auth(); require_permission($payload, 'posts.manage');
        $ccp = (int) $payload['CCP_id'];
        $in  = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim((string)($in['name'] ?? ''));
        if ($name === '') respond(422, ['success' => false, 'error' => 'Name required']);
        $slug = $this->uniqueTaxSlug('blog_categories', 'cat_id', $ccp, $in['slug'] ?? $name);
        db()->prepare('INSERT INTO blog_categories (CCP_id, name, slug, description, color) VALUES (?,?,?,?,?)')
            ->execute([$ccp, $name, $slug, $in['description'] ?? null, $in['color'] ?? '#a57b5b']);
        respond(201, ['success' => true, 'data' => ['id' => (int) db()->lastInsertId(), 'name' => $name, 'slug' => $slug]]);
    }

    public function tagStore(): void
    {
        $payload = require_auth(); require_permission($payload, 'posts.manage');
        $ccp = (int) $payload['CCP_id'];
        $in  = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim((string)($in['name'] ?? ''));
        if ($name === '') respond(422, ['success' => false, 'error' => 'Name required']);
        $slug = $this->uniqueTaxSlug('blog_tags', 'tag_id', $ccp, $in['slug'] ?? $name);
        db()->prepare('INSERT INTO blog_tags (CCP_id, name, slug) VALUES (?,?,?)')
            ->execute([$ccp, $name, $slug]);
        respond(201, ['success' => true, 'data' => ['id' => (int) db()->lastInsertId(), 'name' => $name, 'slug' => $slug]]);
    }

    public function categoryDestroy(string $id): void { $this->taxDestroy('blog_categories', 'cat_id', $id); }
    public function tagDestroy(string $id): void       { $this->taxDestroy('blog_tags', 'tag_id', $id); }

    // ---------- ADMIN: inline image upload ----------------------------
    public function uploadImage(): void
    {
        $payload = require_auth(); require_permission($payload, 'posts.manage');
        if (empty($_FILES['image']['tmp_name'])) respond(422, ['success' => false, 'error' => 'No file']);
        // Validate real MIME with finfo, re-encode via GD, write to public/uploads/blog/.
        // (Reuse the exact avatar pipeline in TestimonialController::saveAvatarOrFail.)
        $path = save_upload_or_fail($_FILES['image'], 'blog');   // returns 'uploads/blog/<rand>.webp'
        respond(201, ['success' => true, 'url' => media_url($path), 'path' => $path]);
    }

    // ---------- private taxonomy + pivot helpers ----------------------
    private function catsFor(int $bId): array
    {
        $s = db()->prepare('SELECT c.cat_id AS id, c.name, c.slug, c.color
                              FROM blog_post_categories p JOIN blog_categories c ON c.cat_id = p.cat_id
                             WHERE p.b_id = ?');
        $s->execute([$bId]); return $s->fetchAll(PDO::FETCH_ASSOC);
    }
    private function tagsFor(int $bId): array
    {
        $s = db()->prepare('SELECT g.tag_id AS id, g.name, g.slug
                              FROM blog_post_tags p JOIN blog_tags g ON g.tag_id = p.tag_id
                             WHERE p.b_id = ?');
        $s->execute([$bId]); return $s->fetchAll(PDO::FETCH_ASSOC);
    }
    private function syncCategories(int $ccp, int $bId, array $ids): void
    {
        db()->prepare('DELETE FROM blog_post_categories WHERE b_id = ?')->execute([$bId]);
        foreach (array_unique(array_map('intval', $ids)) as $cid) {
            $ok = db()->prepare('SELECT 1 FROM blog_categories WHERE cat_id = ? AND CCP_id = ?');
            $ok->execute([$cid, $ccp]);
            if ($ok->fetch()) db()->prepare('INSERT INTO blog_post_categories (b_id, cat_id) VALUES (?,?)')->execute([$bId, $cid]);
        }
    }
    private function syncTags(int $ccp, int $bId, array $ids): void
    {
        db()->prepare('DELETE FROM blog_post_tags WHERE b_id = ?')->execute([$bId]);
        foreach (array_unique(array_map('intval', $ids)) as $tid) {
            $ok = db()->prepare('SELECT 1 FROM blog_tags WHERE tag_id = ? AND CCP_id = ?');
            $ok->execute([$tid, $ccp]);
            if ($ok->fetch()) db()->prepare('INSERT INTO blog_post_tags (b_id, tag_id) VALUES (?,?)')->execute([$bId, $tid]);
        }
    }
    private function taxIndex(string $table, string $pk, string $perm): void
    {
        $payload = require_auth(); require_permission($payload, $perm);
        $pivot = $table === 'blog_categories' ? 'blog_post_categories' : 'blog_post_tags';
        $fk    = $table === 'blog_categories' ? 'cat_id' : 'tag_id';
        $s = db()->prepare("SELECT t.*, (SELECT COUNT(*) FROM $pivot p WHERE p.$fk = t.$pk) AS posts_count
                              FROM $table t WHERE t.CCP_id = ? ORDER BY t.name");
        $s->execute([(int) $payload['CCP_id']]);
        respond(200, ['success' => true, 'data' => $s->fetchAll(PDO::FETCH_ASSOC)]);
    }
    private function taxDestroy(string $table, string $pk, string $id): void
    {
        $payload = require_auth(); require_permission($payload, 'posts.manage');
        db()->prepare("DELETE FROM $table WHERE $pk = ? AND CCP_id = ?")
            ->execute([(int) $id, (int) $payload['CCP_id']]);
        respond(200, ['success' => true]);
    }
    private function uniqueTaxSlug(string $table, string $pk, int $ccp, string $base): string
    {
        $slug = $this->slugify($base); $i = 1;
        while (true) {
            $c = $i === 1 ? $slug : "$slug-$i";
            $s = db()->prepare("SELECT 1 FROM $table WHERE CCP_id = ? AND slug = ?");
            $s->execute([$ccp, $c]);
            if (!$s->fetch()) return $c; $i++;
        }
    }
}
```

> **Helper notes.** `db_scalar()`, `media_url()` and `save_upload_or_fail()` above are thin
> conveniences — if they do not already exist in the backend, add them next to the other
> helpers (or inline the logic). `media_url($path)` should prefix a stored relative path
> (`uploads/blog/x.webp`) with the backend public origin so the browser can load it. The
> upload pipeline must copy `TestimonialController::saveAvatarOrFail()` exactly (finfo MIME
> allow-list + GD re-encode) — never trust the client MIME type.

## A4. Register routes in `src/Router.php`

Add to the `$routes` array (admin routes auto-require JWT + the controller checks the
permission; public routes need only the API key). For the `{slug}` / `{id}` routes follow the
existing dynamic-route mechanism the router already uses for `/wl/public/estimates/{token}`.

```php
// ── Blog: public (X-API-Key only) ─────────────────────────────
'GET /wl/public/blog/posts'            => [BlogController::class, 'publicIndex'],
'GET /wl/public/blog/categories'       => [BlogController::class, 'publicCategories'],
'GET /wl/public/blog/tags'             => [BlogController::class, 'publicTags'],
// dynamic: 'GET /wl/public/blog/posts/{slug}' => [BlogController::class, 'publicShow'],

// ── Blog: admin (X-API-Key + Bearer JWT) ──────────────────────
'GET /wl/admin/blog/posts'             => [BlogController::class, 'adminIndex'],
'POST /wl/admin/blog/posts'            => [BlogController::class, 'adminStore'],
'POST /wl/admin/blog/upload-image'     => [BlogController::class, 'uploadImage'],
'GET /wl/admin/blog/categories'        => [BlogController::class, 'categoryIndex'],
'POST /wl/admin/blog/categories'       => [BlogController::class, 'categoryStore'],
'GET /wl/admin/blog/tags'              => [BlogController::class, 'tagIndex'],
'POST /wl/admin/blog/tags'             => [BlogController::class, 'tagStore'],
// dynamic: GET/PUT/DELETE /wl/admin/blog/posts/{id}
//          PUT/DELETE     /wl/admin/blog/categories/{id}
//          DELETE         /wl/admin/blog/tags/{id}
```

Then `require_once` the new controller in `public/index.php` next to the other controllers:

```php
require_once __DIR__ . '/../src/Controllers/BlogController.php';
```

> **Dynamic segments.** The router already strips the query string and matches `"METHOD /path"`.
> Extend its dispatch with regex patterns for the `{slug}`/`{id}` blog routes exactly like it
> already resolves `/wl/public/estimates/{token}`, passing the captured segment to the
> controller method (`publicShow($slug)`, `adminShow($id)`, `adminUpdate($id)`, `adminDestroy($id)`,
> `categoryUpdate($id)`, `categoryDestroy($id)`, `tagDestroy($id)`).

---

# PART B — Admin manager (`mm-admin`)

All paths under `/Users/ashirabdalravee/CM/mm-admin/`. Follow the page pattern in
`pages/reviews.php`: define `API_BASE` / `API_KEY` / read `$_SESSION['jwt']`, set `$activeNav`,
emit a `page-meta` JSON block, render Tailwind markup using the **tan/cream** theme, and drive
it with an inline `<script>` IIFE that calls the backend with `X-API-Key` + `Bearer` headers.

## B1. Add the routes in `public/index.php`

Insert alongside the other authenticated routes (gate with the new permission):

```php
} elseif ($path === '/posts') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('posts.view')) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/posts.php';
} elseif ($path === '/posts/new') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('posts.manage')) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/post_editor.php';
} elseif (preg_match('#^/posts/(\d+)/edit$#', $path, $m)) {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('posts.manage')) { header('Location: ' . landing_path()); exit; }
    $_GET['id'] = $m[1];
    $pageFile = __DIR__ . '/../pages/post_editor.php';
```

Also add `posts.view` to `landing_path()` in `lib/helpers.php` so a blog-only admin lands on `/posts`.

## B2. Add the sidebar entry in `layout/page.php`

Add a nav item (e.g. just under "Reviews"), gated by the permission:

```php
<?php if (can('posts.view')): ?>
<li>
    <a href="/posts" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'posts' ? 'bg-tan-700 text-white font-medium' : 'text-cream-100 hover:bg-tan-600 hover:text-white' ?>">
        <span class="text-base leading-none">&#9998;</span>
        Blog
    </a>
</li>
<?php endif; ?>
```

## B3. `pages/posts.php` — the list

Structure (mirror `reviews.php`):

- Header constants:
  ```php
  $_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost','127.0.0.1']);
  if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000' : 'https://apiv1.clickdigim.com');
  if (!defined('API_KEY'))  define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
  $layout = 'app'; $activeNav = 'posts';
  $canManage = can('posts.manage');
  ```
- `page-meta` JSON (title "Blog — Majestic Marquees Admin").
- A status filter bar (`All / Draft / Published / Archived`), a **"+ New post"** button linking to `/posts/new`, and a table with columns: **Title** (with thumbnail + read time), **Categories**, **Status** badge, **Date** (`published_at` or `created_at`), **Views**, **Actions** (View-on-site for published, Edit → `/posts/{id}/edit`, Delete with confirm).
- Status badge colours: draft `bg-yellow-100 text-yellow-800`, published `bg-green-100 text-green-800`, archived `bg-gray-100 text-gray-800`.
- Inline `<script>` IIFE:
  ```js
  const API_BASE = '<?= API_BASE ?>', API_KEY = '<?= API_KEY ?>', JWT = '<?= e($_SESSION['jwt'] ?? '') ?>';
  const headers = () => ({ 'Content-Type':'application/json', 'X-API-Key':API_KEY, 'Authorization':'Bearer '+JWT });
  async function load(page = 1, status = 'all') {
      const q = new URLSearchParams({ page, per_page: 15 });
      if (status !== 'all') q.set('status', status);
      const r = await fetch(`${API_BASE}/wl/admin/blog/posts?${q}`, { headers: headers() });
      const json = await r.json();   // { data, meta }
      // render rows + pagination from json.data / json.meta
  }
  async function del(id) {
      await fetch(`${API_BASE}/wl/admin/blog/posts/${id}`, { method:'DELETE', headers: headers() });
      load();
  }
  ```
- "View on site" link target: `https://blog.majesticmarquees.com/<slug>` (or `http://localhost:8001/<slug>` locally).

## B4. `pages/post_editor.php` — create / edit

This is the equivalent of the React `PostEditor.tsx`. Same header constants + `$activeNav='posts'`,
plus `$postId = $_GET['id'] ?? null;`.

**Form fields (exclude all paywall fields):**

| Field | Control | Notes |
|---|---|---|
| Title | text, max 100 | auto-suggest slug on blur |
| Slug | text, max 150 | "generate from title" button |
| Excerpt | textarea | listing/summary text |
| Content | **CKEditor 5** | rich text; inline image upload |
| Featured image | file upload | recommend 1200×630; preview + remove |
| Featured image alt | text | a11y / SEO |
| Categories | multi-select pills + "Add new" inline | `GET/POST/DELETE /wl/admin/blog/categories` |
| Tags | multi-select pills + "New tag" inline | `GET/POST/DELETE /wl/admin/blog/tags` |
| Meta title / Meta description | text / textarea | SEO (optional) |
| Status | buttons: **Save draft** / **Publish** | sends `status:'draft'|'published'` |

**CKEditor 5** via CDN (ClassicEditor) with a custom upload adapter that posts to
`/wl/admin/blog/upload-image`:

```html
<script src="https://cdn.ckeditor.com/ckeditor5/43.0.0/classic/ckeditor.js"></script>
<script>
class MmUpload {
  constructor(loader){ this.loader = loader; }
  async upload(){
    const file = await this.loader.file;
    const fd = new FormData(); fd.append('image', file);
    const r = await fetch(API_BASE + '/wl/admin/blog/upload-image', {
      method:'POST', headers:{ 'X-API-Key':API_KEY, 'Authorization':'Bearer '+JWT }, body: fd
    });
    const j = await r.json();
    return { default: j.url };
  }
  abort(){}
}
ClassicEditor.create(document.querySelector('#content'), {
  toolbar: ['undo','redo','|','heading','|','bold','italic','link','|',
            'bulletedList','numberedList','|','uploadImage','insertTable','blockQuote','|','sourceEditing']
}).then(ed => {
  ed.plugins.get('FileRepository').createUploadAdapter = (loader) => new MmUpload(loader);
  window._editor = ed;
});
</script>
```

**Save handler** builds the JSON body and POSTs (create) or PUTs (update):

```js
async function save(status) {
  const body = {
    title: titleEl.value, slug: slugEl.value, excerpt: excerptEl.value,
    content: window._editor.getData(),
    featured_image: uploadedFeaturedPath,        // relative path returned by upload-image
    featured_image_alt: altEl.value,
    category_ids: selectedCategoryIds, tag_ids: selectedTagIds,
    meta_title: metaTitleEl.value, meta_description: metaDescEl.value,
    status
  };
  const id = <?= $postId ? (int)$postId : 'null' ?>;
  const url = id ? `${API_BASE}/wl/admin/blog/posts/${id}` : `${API_BASE}/wl/admin/blog/posts`;
  const r = await fetch(url, { method: id ? 'PUT' : 'POST', headers: headers(), body: JSON.stringify(body) });
  if (r.ok) location.href = '/posts';
}
```

The featured image uploads through the **same** `/wl/admin/blog/upload-image` endpoint; store the
returned **relative `path`** in `featured_image` (so it stays portable across domains) and show
`url` in the preview.

---

# PART C — Public blog (`mm-blog`)

All paths under `/Users/ashirabdalravee/CM/mm-blog/`. Render **server-side in PHP** (for SEO),
reading from the public endpoints with the API key. The design system is already in
`layout/page.php` — reuse `.container-x`, `.hero-blog`, `.heading-m`, `.prose-blog`, `.btn-primary`,
`.eyebrow`, `forest-*`, `tan-*`, `cream-*`.

## C1. Add API config + a fetch helper to `lib/helpers.php`

```php
$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
define('API_BASE', $_is_local ? 'http://localhost:8000' : 'https://apiv1.clickdigim.com');
define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
unset($_is_local);

/** Server-side GET against the public blog API. Returns decoded array or null. */
function blog_api(string $path): ?array
{
    $ch = curl_init(API_BASE . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['X-API-Key: ' . API_KEY, 'Accept: application/json'],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($res === false || $code >= 400) return null;
    return json_decode($res, true) ?: null;
}
```

## C2. Routing in `public/index.php`

Replace the static `$pages` map with list + dynamic single-post routing:

```php
// Home / listing
if ($path === '/' ) {
    $pageFile = __DIR__ . '/../pages/home.php';
} elseif (preg_match('#^/([a-z0-9-]+)$#', $path, $m)) {
    $_GET['slug'] = $m[1];
    $pageFile = __DIR__ . '/../pages/post.php';
} else {
    http_response_code(404);
    $pageFile = __DIR__ . '/../pages/not-found.php';
}
```

> Keep the existing `/home → /` redirect and `/api/consent-log` handler above this block.
> `post.php` must emit a 404 (render `not-found.php`) when `blog_api()` returns null.

## C3. `pages/home.php` — hero + listing (replace the "Coming Soon" placeholder)

```php
<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; }
$resp  = blog_api('/wl/public/blog/posts?page=1&per_page=12');
$posts = $resp['data'] ?? [];
$hero  = $posts[0] ?? null;
$rest  = array_slice($posts, 1);
$fmt   = fn($iso) => $iso ? date('j F Y', strtotime($iso)) : '';
$fmtT  = fn($iso) => $iso ? date('H:i', strtotime($iso)) : '';
?>
<script type="application/json" id="page-meta">
{ "title": "Blog | Majestic Marquees",
  "name": { "description": "Stories about luxury events, outdoor elegance, and extraordinary gatherings", "robots": "index, follow" },
  "property": { "og:title": "Blog | Majestic Marquees", "og:type": "website" } }
</script>

<?php if ($hero): ?>
<section class="hero-blog relative">
    <?php if (!empty($hero['featured_image_url'])): ?>
    <img src="<?= e($hero['featured_image_url']) ?>" alt="<?= e($hero['featured_image_alt'] ?? $hero['title']) ?>"
         class="absolute inset-0 h-full w-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/40 to-black/20"></div>
    <?php endif; ?>
    <div class="container-x relative py-24 sm:py-32 lg:py-40">
        <a href="/<?= e($hero['slug']) ?>"><h1 class="heading-m max-w-3xl"><?= e($hero['title']) ?></h1></a>
        <?php if (!empty($hero['subtitle'])): ?><p class="mt-4 max-w-2xl text-lg"><?= e($hero['subtitle']) ?></p><?php endif; ?>
        <ul class="mt-6 flex flex-wrap items-center gap-5 text-sm">
            <li><?= e($hero['author']['name'] ?? 'Majestic Marquees') ?></li>
            <li><?= e($fmt($hero['published_at'])) ?></li>
            <li><?= e($fmtT($hero['published_at'])) ?></li>
        </ul>
        <a href="/<?= e($hero['slug']) ?>" class="btn-primary mt-8">Read More</a>
    </div>
</section>
<?php endif; ?>

<section class="section"><div class="container-x">
    <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($rest as $p): ?>
        <article class="group flex flex-col">
            <?php if (!empty($p['featured_image_url'])): ?>
            <a href="/<?= e($p['slug']) ?>" class="block overflow-hidden rounded-sm">
                <img src="<?= e($p['featured_image_url']) ?>" alt="<?= e($p['featured_image_alt'] ?? $p['title']) ?>"
                     class="aspect-[16/10] w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
            </a>
            <?php endif; ?>
            <h3 class="text-primary-ttl mt-5"><a href="/<?= e($p['slug']) ?>" class="text-tan-500 hover:text-tan-600"><?= e($p['title']) ?></a></h3>
            <p class="text-body-s mt-2 text-forest-700/70">
                <?= e($p['author']['name'] ?? 'Majestic Marquees') ?> &middot; <?= e($fmt($p['published_at'])) ?> &middot; <?= e($fmtT($p['published_at'])) ?>
            </p>
            <p class="prose-blog mt-3 line-clamp-3"><?= e($p['excerpt'] ?? '') ?></p>
            <a href="/<?= e($p['slug']) ?>" class="mt-4 text-sm font-medium text-tan-500 hover:text-tan-600">Read More &raquo;</a>
        </article>
        <?php endforeach; ?>
    </div>
</div></section>
```

## C4. `pages/post.php` — single post + related

```php
<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; }
$slug = $_GET['slug'] ?? '';
$resp = blog_api('/wl/public/blog/posts/' . rawurlencode($slug));
if (!$resp || empty($resp['data'])) { http_response_code(404); require __DIR__ . '/not-found.php'; return; }
$p = $resp['data']; $related = $resp['related'] ?? [];
$desc = $p['meta_description'] ?? $p['excerpt'] ?? $p['subtitle'] ?? '';
$canonical = 'https://blog.majesticmarquees.com/' . $p['slug'];

$schema = [
  '@context' => 'https://schema.org', '@type' => 'BlogPosting',
  'headline' => $p['title'], 'description' => $desc,
  'image' => $p['featured_image_url'] ?? null,
  'datePublished' => $p['published_at'], 'dateModified' => $p['updated_at'],
  'author' => ['@type' => 'Person', 'name' => $p['author']['name'] ?? 'Majestic Marquees'],
  'mainEntityOfPage' => $canonical,
];
$fmt = fn($iso) => $iso ? date('j F Y', strtotime($iso)) : '';
?>
<script type="application/json" id="page-meta">
<?= json_encode([
  'title' => ($p['meta_title'] ?? $p['title']) . ' | Majestic Marquees',
  'name' => ['description' => $desc, 'robots' => 'index, follow'],
  'property' => ['og:title' => $p['title'], 'og:description' => $desc, 'og:type' => 'article',
                 'og:image' => $p['featured_image_url'] ?? ''],
  'schema' => $schema,
], JSON_UNESCAPED_SLASHES) ?>
</script>

<article class="section"><div class="container-x max-w-prose">
    <a href="/" class="eyebrow">&larr; Back to blog</a>
    <h1 class="heading-m mt-4"><?= e($p['title']) ?></h1>
    <p class="text-body-s mt-4 text-forest-700/70">
        <?= e($p['author']['name'] ?? 'Majestic Marquees') ?> &middot; <?= e($fmt($p['published_at'])) ?>
        &middot; <?= (int) $p['read_time'] ?> min read
    </p>
    <?php if (!empty($p['featured_image_url'])): ?>
    <img src="<?= e($p['featured_image_url']) ?>" alt="<?= e($p['featured_image_alt'] ?? $p['title']) ?>"
         class="mt-8 w-full rounded-sm object-cover">
    <?php endif; ?>

    <!-- Backend already sanitises content; render as HTML -->
    <div class="prose-blog mt-10"><?= $p['content'] ?></div>

    <?php if (!empty($p['tags'])): ?>
    <div class="mt-10 flex flex-wrap gap-2">
        <?php foreach ($p['tags'] as $t): ?>
        <span class="rounded-sm bg-cream-100 px-3 py-1 text-xs text-forest-700"><?= e($t['name']) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div></article>

<?php if ($related): ?>
<section class="section pt-0"><div class="container-x">
    <h2 class="heading-m mb-10">Related stories</h2>
    <div class="grid gap-10 sm:grid-cols-3">
        <?php foreach ($related as $r): ?>
        <a href="/<?= e($r['slug']) ?>" class="group block">
            <?php if (!empty($r['featured_image_url'])): ?>
            <img src="<?= e($r['featured_image_url']) ?>" alt="<?= e($r['title']) ?>"
                 class="aspect-[16/10] w-full rounded-sm object-cover" loading="lazy">
            <?php endif; ?>
            <h3 class="text-primary-ttl mt-4 text-tan-500 group-hover:text-tan-600"><?= e($r['title']) ?></h3>
        </a>
        <?php endforeach; ?>
    </div>
</div></section>
<?php endif; ?>
```

> **Security note.** Rendering `content` as raw HTML is safe **only because the backend
> sanitises it** (use the backend `HtmlSanitizer` / HTMLPurifier on save in `adminStore`/`adminUpdate`,
> exactly as the source system pre-sanitises). If you skip backend sanitisation, sanitise on
> output instead — never render untrusted HTML.

## C5. SEO parity with the source

- The `layout/page.php` already turns the `page-meta` JSON into `<title>`, `<meta name>`,
  `<meta property>` and a `<script type="application/ld+json">` block — you only populate the JSON.
- Set a per-post canonical to `https://blog.majesticmarquees.com/<slug>`.
- Emit `BlogPosting` JSON-LD (done above), matching the Next.js source.
- Optional but recommended: add `pages/sitemap` + `robots.txt` later (the source generates these).

---

# PART D — End-to-end setup checklist

Work top-to-bottom; each layer depends on the one above it.

**Backend (`backend_whitelevel/backend`)**
- [ ] Confirm `blog*` tables exist in `whitelevel_db` (`SHOW TABLES LIKE 'blog%'`).
- [ ] Add `posts.view` + `posts.manage` to the RBAC catalog; grant to the Admin role.
- [ ] Create `src/Controllers/BlogController.php` (Part A3).
- [ ] Add `media_url()`, `db_scalar()`, `save_upload_or_fail()` helpers if missing.
- [ ] `require_once` the controller in `public/index.php`.
- [ ] Add the static + dynamic blog routes in `src/Router.php` (Part A4).
- [ ] Smoke-test with `curl` (Part E).

**Admin (`mm-admin`)**
- [ ] Add `/posts`, `/posts/new`, `/posts/{id}/edit` routes in `public/index.php`.
- [ ] Add `posts.view` to `landing_path()`.
- [ ] Add the **Blog** sidebar item in `layout/page.php`.
- [ ] Create `pages/posts.php` (list).
- [ ] Create `pages/post_editor.php` (CKEditor + categories/tags + image upload + save).
- [ ] Re-login so the session picks up the new permissions.

**Blog (`mm-blog`)**
- [ ] Add `API_BASE`, `API_KEY`, `blog_api()` to `lib/helpers.php`.
- [ ] Update `public/index.php` routing (list + `/{slug}`).
- [ ] Replace `pages/home.php` with hero + grid.
- [ ] Create `pages/post.php` (single + related + JSON-LD).
- [ ] Verify it visually matches <https://blog.majesticmarquees.com/>.

---

# PART E — Testing & verification

**1. Backend API (no UI needed)**

```bash
KEY="mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462"

# Public list (should be empty initially)
curl -s -H "X-API-Key: $KEY" "http://localhost:8000/wl/public/blog/posts" | jq

# Admin login → capture JWT
JWT=$(curl -s -X POST "http://localhost:8000/wl/admin/login" \
  -H "X-API-Key: $KEY" -H 'Content-Type: application/json' \
  -d '{"email":"vincent@majesticmarquees.com","password":"vincent123"}' | jq -r .token)

# Create a draft
curl -s -X POST "http://localhost:8000/wl/admin/blog/posts" \
  -H "X-API-Key: $KEY" -H "Authorization: Bearer $JWT" -H 'Content-Type: application/json' \
  -d '{"title":"Hello Marquees","content":"<p>First post body…</p>","status":"published"}' | jq

# Public list again (should now show 1)
curl -s -H "X-API-Key: $KEY" "http://localhost:8000/wl/public/blog/posts" | jq '.data[].title'
```

Confirm: slug auto-generated, `read_time` computed, `published_at` set, response shape
matches `{ success, data, meta }`.

**2. Admin UI** — log into `http://localhost:8002`, open **Blog**, create a post with a featured
image + inline image + a category and a tag, save as draft, then publish. Confirm it appears in
the list with the right status badge.

**3. Blog UI** — open `http://localhost:8001/`, confirm the hero shows the latest post and the
grid shows the rest; click through to `/your-slug`; view source and confirm `<title>`, meta tags,
and the `BlogPosting` JSON-LD are present.

**4. Tenant isolation** — every row written carries `CCP_id = 2`; the API never returns another
tenant's posts. (If a second tenant key exists, verify its list stays empty.)

---

# PART F — Deployment

- **Backend**: deploy `BlogController.php` + `Router.php` + helper changes to the host serving
  `apiv1.clickdigim.com`. Ensure `public/uploads/blog/` is writable by the web user
  (`chmod -R 775`, correct `chown`), mirroring the existing uploads directory.
- **mm-admin**: deploy the two new pages + `index.php`/`layout` edits to `admin.majesticmarquees.com`.
  No build step (raw PHP + Tailwind CDN). The CKEditor CDN must be reachable (or self-host it).
- **mm-blog**: deploy the new pages + `helpers.php`/`index.php` edits to `blog.majesticmarquees.com`.
  Follow `CM/server.md` for the Nginx/PHP-FPM blocks and `$allowedHosts`.
- **CORS**: the backend already allows the admin origin. Because mm-blog reads happen **server-side**
  (PHP cURL), no browser CORS entry is needed for the public blog.
- **Cache (optional)**: the source caches public reads for ~5 min. You can add the same later with
  a small file/APCu cache around `blog_api()` / the public controller — not required for launch.

---

# Appendix

## API endpoint reference

**Public (header: `X-API-Key`)**

| Method | Path | Returns |
|---|---|---|
| GET | `/wl/public/blog/posts?page=&per_page=&category=&tag=` | `{ success, data[], meta }` published, newest first |
| GET | `/wl/public/blog/posts/{slug}` | `{ success, data, related[] }` (also increments views) |
| GET | `/wl/public/blog/categories` | `{ success, data[] }` with `posts_count` |
| GET | `/wl/public/blog/tags` | `{ success, data[] }` with `posts_count` |

**Admin (headers: `X-API-Key` + `Authorization: Bearer <JWT>`)**

| Method | Path | Permission |
|---|---|---|
| GET | `/wl/admin/blog/posts?status=&page=` | `posts.view` |
| GET | `/wl/admin/blog/posts/{id}` | `posts.view` |
| POST | `/wl/admin/blog/posts` | `posts.manage` |
| PUT | `/wl/admin/blog/posts/{id}` | `posts.manage` |
| DELETE | `/wl/admin/blog/posts/{id}` | `posts.manage` |
| POST | `/wl/admin/blog/upload-image` (multipart) | `posts.manage` |
| GET / POST | `/wl/admin/blog/categories` | `posts.view` / `posts.manage` |
| PUT / DELETE | `/wl/admin/blog/categories/{id}` | `posts.manage` |
| GET / POST | `/wl/admin/blog/tags` | `posts.view` / `posts.manage` |
| DELETE | `/wl/admin/blog/tags/{id}` | `posts.manage` |

## Post JSON shape (API → UI contract)

```json
{
  "id": 1, "title": "…", "slug": "…", "subtitle": "…", "excerpt": "…",
  "content": "<p>…</p>",
  "featured_image_url": "https://apiv1.clickdigim.com/uploads/blog/x.webp",
  "featured_image_alt": "…",
  "author": { "id": 3, "name": "Ravee" },
  "read_time": 5, "status": "published",
  "published_at": "2026-03-12 19:44:00",
  "meta_title": "…", "meta_description": "…", "views": 12,
  "categories": [ { "id": 1, "name": "Events", "slug": "events", "color": "#a57b5b" } ],
  "tags": [ { "id": 4, "name": "Weddings", "slug": "weddings" } ],
  "created_at": "…", "updated_at": "…"
}
```

## What we deliberately leave out

| Source feature | Action in MM |
|---|---|
| `is_paid`, `price`, `preview_percentage` | Columns exist in `blog`; **never read/write**. Every post is free. |
| `post_purchases`, `featured_blog_purchases`, `blog_access_tokens` | Not created, not used. |
| Paywall UI, "paid article" toggle, price/preview slider | Omit from `post_editor.php`. |
| Magic-link / email access flow, purchase-status endpoints | Not implemented. |
| Stripe / PayPal blog payment intents | Not implemented. |

## File-change map

| Project | New files | Edited files |
|---|---|---|
| `backend_whitelevel/backend` | `src/Controllers/BlogController.php` | `src/Router.php`, `public/index.php`, RBAC catalog, helpers |
| `CM/mm-admin` | `pages/posts.php`, `pages/post_editor.php` | `public/index.php`, `layout/page.php`, `lib/helpers.php` |
| `CM/mm-blog` | `pages/post.php` | `pages/home.php`, `public/index.php`, `lib/helpers.php` |

---

*Reference source (read-only): `debian/backend` (Laravel `BlogController` + `Admin/BlogController`),
`debian/admin` (`Posts.tsx`, `PostEditor.tsx`), `debian/frontend-blog-nextjs` (home + `[slug]`).
Use them when you need the exact behaviour of a feature while implementing the MM equivalent above.*
