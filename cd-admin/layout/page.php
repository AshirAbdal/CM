<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClickDigim — Admin</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; color: #333; min-height: 100vh; margin: 0; }
        nav { background: #004499; padding: 0 32px; display: flex; align-items: center; height: 56px; }
        nav span { color: #fff; font-weight: bold; font-size: 1.1rem; }
        nav small { color: #aad4ff; font-size: 0.75rem; margin-left: 8px; }
        main { max-width: 900px; margin: 40px auto; padding: 0 24px; }
    </style>
</head>
<body>

<nav>
    <span>ClickDigim <small>Admin</small></span>
</nav>

<main>
    <?= $pageContent ?? '' ?>
</main>

</body>
</html>
