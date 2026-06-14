<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<script type="application/json" id="page-meta">
{
    "title": "Page Not Found | Majestic Marquees Blog",
    "name": {
        "description": "The page you're looking for doesn't exist.",
        "robots": "noindex, nofollow"
    }
}
</script>

<div class="min-h-screen bg-[#f5f1e8] flex flex-col items-center justify-center px-6 text-center">
    <img src="/logo.png" alt="Majestic Marquees" class="h-28 w-auto object-contain mb-10">

    <p class="text-xs uppercase tracking-[0.25em] text-tan-500 mb-4 font-sans">404</p>

    <h1 class="font-display text-5xl sm:text-6xl lg:text-7xl text-forest-800 leading-tight mb-6">
        Page not found
    </h1>

    <p class="max-w-md text-forest-700/75 text-base sm:text-lg leading-relaxed font-sans">
        The page you're looking for doesn't exist.
    </p>

    <div class="mt-12 h-px w-16 bg-tan-500 mx-auto"></div>

    <a href="/" class="mt-8 text-sm uppercase tracking-widest text-forest-700 hover:text-tan-500 transition-colors font-sans">
        Back to Blog
    </a>
</div>
