<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageMeta['title'] ?? 'ClickDigim', ENT_QUOTES) ?></title>
    <?php foreach ($pageMeta['name'] ?? [] as $k => $v): ?>
    <meta name="<?= htmlspecialchars($k, ENT_QUOTES) ?>" content="<?= htmlspecialchars($v, ENT_QUOTES) ?>">
    <?php endforeach; ?>
    <?php foreach ($pageMeta['property'] ?? [] as $k => $v): ?>
    <meta property="<?= htmlspecialchars($k, ENT_QUOTES) ?>" content="<?= htmlspecialchars($v, ENT_QUOTES) ?>">
    <?php endforeach ?>
    <?php if (!empty($pageMeta['schema'])): ?>
    <script type="application/ld+json"><?= json_encode($pageMeta['schema']) ?></script>
    <?php endif; ?>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; color: #333; min-height: 100vh; margin: 0; }
        nav { background: #0066cc; padding: 0 32px; display: flex; align-items: center; height: 56px; }
        nav a { color: #fff; font-weight: bold; font-size: 1.1rem; text-decoration: none; }
        main { max-width: 900px; margin: 40px auto; padding: 0 24px; }
        footer { text-align: center; font-size: 0.85rem; color: #999; padding: 24px 0; }
    </style>
    <script src="/spa.js" defer></script>
</head>
<body>

<nav id="main-nav" style="background:#0066cc;padding:0 32px;display:flex;align-items:center;height:56px;gap:16px">
    <a href="/" class="spa-link" style="color:#fff;font-weight:bold;font-size:1.1rem;text-decoration:none;margin-right:auto">ClickDigim</a>
    <a href="/"      class="spa-link" style="color:#c8e0ff;font-size:0.9rem;text-decoration:none">Home</a>
    <a href="/about" class="spa-link" style="color:#c8e0ff;font-size:0.9rem;text-decoration:none">About</a>
</nav>

<main id="content">
    <?= $pageContent ?? '' ?>
</main>

<footer>
    &copy; 2026 ClickDigim
</footer>

</body>
</html>
