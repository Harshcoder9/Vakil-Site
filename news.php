<?php
// news.php — All Legal News & Updates | Vakil Platform

// --- NEWS FETCHER (reused from index.php logic) ---
function getLegalNews() {
    $apiKey = 'c5c61164f1347b427df922506f3a85de';
    $cacheFile = __DIR__ . '/news_cache.json';
    $cacheTime = 3600;

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if (!empty($data)) return $data;
    }

    $url = "https://gnews.io/api/v4/search?q=Supreme%20Court%20India%20OR%20Corporate%20Law%20India&lang=en&country=in&max=9&apikey=$apiKey";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode === 200 && isset($data['articles'])) {
        $formattedNews = [];
        foreach ($data['articles'] as $article) {
            $formattedNews[] = [
                'domain'  => 'Legal Update',
                'title'   => $article['title'],
                'summary' => substr($article['description'], 0, 160) . '...',
                'image'   => $article['image'] ?? 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=600&q=80',
                'date'    => date('M d, H:i', strtotime($article['publishedAt'])),
                'link'    => $article['url']
            ];
        }
        file_put_contents($cacheFile, json_encode($formattedNews));
        return $formattedNews;
    }

    // Fallback — read stale cache if it exists
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if (!empty($data)) return $data;
    }

    return [[
        'domain'  => 'System Message',
        'title'   => 'News Feed Unavailable',
        'summary' => 'Unable to connect to the live news server. Please check back later.',
        'image'   => 'https://images.unsplash.com/photo-1505664194779-8beaceb93744?auto=format&fit=crop&w=600&q=80',
        'date'    => 'Now',
        'link'    => '#'
    ]];
}

$all_news = getLegalNews();
$news_count = count($all_news);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legal News & Updates | Vakil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --bg-main: #0a0a0c; --text-main: #e2e8f0; --text-muted: #94a3b8;
            --nav-bg: rgba(10, 10, 12, 0.85); --card-bg: rgba(23, 23, 26, 0.6);
            --card-border: rgba(255, 255, 255, 0.05); --pill-bg: rgba(255, 255, 255, 0.03);
        }
        [data-theme="light"] {
            --bg-main: #1a1f2e; --text-main: #c9d1d9; --text-muted: #8b949e;
            --nav-bg: rgba(22, 27, 40, 0.92); --card-bg: rgba(30, 37, 52, 0.85);
            --card-border: rgba(255, 255, 255, 0.06); --pill-bg: rgba(255, 255, 255, 0.04);
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-main); color: var(--text-main); transition: background 0.3s, color 0.3s; }

        /* Navbar */
        .glass-nav { background: var(--nav-bg); backdrop-filter: blur(12px); border-bottom: 1px solid var(--card-border); }

        /* Cards */
        .glass-card { background: var(--card-bg); backdrop-filter: blur(20px); border: 1px solid var(--card-border); transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s; }
        .glass-card:hover { transform: translateY(-5px); border-color: #3b82f6; box-shadow: 0 10px 40px -10px rgba(59, 130, 246, 0.2); }

        .text-theme-main  { color: var(--text-main); }
        .text-theme-muted { color: var(--text-muted); }

        /* Theme toggle */
        #theme-toggle { background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 8px; border-radius: 50%; transition: 0.3s; }
        #theme-toggle:hover { color: #3b82f6; background: var(--pill-bg); }

        /* Light mode overrides */
        [data-theme="light"] .glass-nav { border-bottom-color: rgba(255,255,255,0.04); box-shadow: 0 1px 12px rgba(0,0,0,0.25); }
        [data-theme="light"] .glass-card { background: #1e2538; border-color: rgba(255,255,255,0.05); box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        [data-theme="light"] .glass-card:hover { border-color: #3b82f6; box-shadow: 0 10px 40px -10px rgba(59,130,246,0.2); }
        [data-theme="light"] .text-theme-main  { color: #c9d1d9 !important; }
        [data-theme="light"] .text-theme-muted { color: #8b949e !important; }
        [data-theme="light"] .text-slate-400   { color: #8b949e !important; }
        [data-theme="light"] footer { background: #121620 !important; border-top-color: rgba(255,255,255,0.04) !important; }
        [data-theme="light"] footer .text-white  { color: #e2e8f0 !important; }
        [data-theme="light"] footer .text-slate-400 { color: #8b949e !important; }
        [data-theme="light"] footer a:hover { color: #7dd3fc !important; }
        [data-theme="light"] #theme-toggle { color: #8b949e; }
        [data-theme="light"] #theme-toggle:hover { color: #60a5fa; background: rgba(255,255,255,0.06); }

        /* Hero banner */
        .news-hero {
            background: linear-gradient(135deg, #0a0a0c 0%, #0f172a 40%, #1e1b4b 100%);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        /* Search bar */
        .search-input {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-main);
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .search-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
        .search-input::placeholder { color: var(--text-muted); }
        [data-theme="light"] .search-input { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.1); color: #c9d1d9; }

        /* Pulse badge */
        .live-badge { animation: pulse-blue 2s infinite; }
        @keyframes pulse-blue {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.4; }
        }

        /* "No results" state */
        #no-results { display: none; }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col">

    <!-- ===== NAVBAR ===== -->
    <nav class="fixed w-full z-50 glass-nav h-20 flex items-center">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-2">
                <img src="vakillogo.png" alt="Vakil" class="h-8" id="nav-logo">
            </a>
            <div class="hidden md:flex items-center space-x-8 text-sm font-semibold text-theme-muted">
                <a href="index.php"       class="hover:text-blue-500 transition-colors">Home</a>
                <a href="bns_library.php" class="hover:text-blue-500 transition-colors">BNS Library</a>
                <a href="index.php#experts" class="hover:text-blue-500 transition-colors">Experts</a>
                <a href="news.php"        class="text-blue-400 font-bold">Insights</a>
                <a href="about.php"       class="hover:text-blue-500 transition-colors">About</a>
            </div>
            <div class="flex items-center gap-4">
                <button id="theme-toggle" aria-label="Toggle Theme"><i class="fas fa-sun"></i></button>
                <a href="login.php" class="text-sm font-bold text-theme-muted hover:text-blue-500">Log In</a>
                <a href="register.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold text-sm transition-colors">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- ===== HERO BANNER ===== -->
    <section class="news-hero pt-32 pb-16 px-6">
        <div class="container mx-auto text-center">
            <!-- breadcrumb -->
            <div class="inline-flex items-center gap-2 text-xs text-theme-muted mb-6">
                <a href="index.php" class="hover:text-blue-400 transition-colors">Home</a>
                <i class="fas fa-chevron-right text-[10px]"></i>
                <span class="text-blue-400 font-semibold">Legal Insights</span>
            </div>

            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold uppercase tracking-widest mb-6">
                <span class="w-2 h-2 bg-blue-500 rounded-full live-badge"></span>
                Live Legal Feed
            </div>

            <h1 class="text-4xl md:text-5xl font-black text-theme-main mb-4 tracking-tight">Latest Legal Insights</h1>
            <p class="text-theme-muted text-lg mb-2 max-w-xl mx-auto">Stay ahead with real-time updates from India's highest courts, policy changes, and landmark rulings.</p>
            <p class="text-xs text-theme-muted">
                <i class="fas fa-newspaper mr-1 text-blue-500"></i>
                <?= $news_count ?> article<?= $news_count !== 1 ? 's' : '' ?> available &nbsp;·&nbsp;
                <i class="fas fa-sync-alt mr-1 text-blue-500"></i> Auto-refreshed every hour
            </p>

            <!-- Search Bar -->
            <div class="mt-8 max-w-lg mx-auto relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-theme-muted text-sm pointer-events-none"></i>
                <input
                    type="text"
                    id="news-search"
                    placeholder="Search articles by title or keyword…"
                    class="search-input w-full pl-10 pr-4 py-3 rounded-2xl text-sm font-medium"
                    autocomplete="off"
                >
                <button id="clear-search" class="absolute right-4 top-1/2 -translate-y-1/2 text-theme-muted hover:text-blue-400 transition-colors hidden" aria-label="Clear search">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- ===== NEWS GRID ===== -->
    <main class="flex-grow container mx-auto px-6 py-12">

        <!-- Results count -->
        <p id="results-count" class="text-sm text-theme-muted mb-8">
            Showing <span id="visible-count"><?= $news_count ?></span> of <?= $news_count ?> articles
        </p>

        <div id="news-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($all_news as $news): ?>
            <article class="news-card glass-card rounded-3xl overflow-hidden flex flex-col group h-full"
                     data-title="<?= htmlspecialchars(strtolower($news['title'])) ?>"
                     data-summary="<?= htmlspecialchars(strtolower($news['summary'])) ?>">

                <!-- Thumbnail -->
                <div class="h-52 relative overflow-hidden flex-shrink-0">
                    <img
                        src="<?= htmlspecialchars($news['image']) ?>"
                        alt="<?= htmlspecialchars($news['title']) ?>"
                        class="w-full h-full object-cover transition duration-700 group-hover:scale-110 opacity-80 group-hover:opacity-100"
                        loading="lazy"
                        onerror="this.src='https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=600&q=80'"
                    >
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                    <div class="absolute top-4 left-4">
                        <span class="px-3 py-1 bg-black/60 backdrop-blur text-white text-[10px] font-bold rounded-full uppercase tracking-wider">
                            <?= htmlspecialchars($news['domain']) ?>
                        </span>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-7 flex flex-col flex-grow">
                    <div class="flex items-center gap-2 text-xs text-theme-muted mb-4 font-semibold">
                        <i class="far fa-clock text-blue-500"></i>
                        <?= htmlspecialchars($news['date']) ?>
                    </div>
                    <h2 class="text-lg font-bold text-theme-main mb-3 leading-snug group-hover:text-blue-400 transition-colors">
                        <?= htmlspecialchars($news['title']) ?>
                    </h2>
                    <p class="text-sm text-theme-muted leading-relaxed mb-6 flex-grow">
                        <?= htmlspecialchars($news['summary']) ?>
                    </p>
                    <a href="<?= htmlspecialchars($news['link']) ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 text-blue-500 font-bold text-xs uppercase tracking-widest border-b border-blue-500/20 pb-1 hover:text-blue-400 hover:border-blue-400 transition-colors w-fit">
                        Read Full Story <i class="fas fa-external-link-alt text-[10px]"></i>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- No results message -->
        <div id="no-results" class="text-center py-20">
            <div class="text-6xl mb-4">🔍</div>
            <h3 class="text-xl font-bold text-theme-main mb-2">No articles found</h3>
            <p class="text-theme-muted text-sm">Try a different keyword or <button onclick="clearSearch()" class="text-blue-400 hover:underline">clear the search</button>.</p>
        </div>
    </main>

    <!-- ===== FOOTER ===== -->
    <footer class="bg-black/20 border-t border-white/5 pt-16 pb-8 backdrop-blur-lg mt-auto">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
                <div class="col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <img src="vakillogo.png" alt="Vakil" class="h-8 brightness-200 contrast-0">
                        <span class="text-xl font-bold tracking-tight text-white">Vakil</span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Democratizing legal access with AI-driven intelligence and top-tier expert connections across India.
                    </p>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4 text-sm uppercase tracking-wider">Platform</h4>
                    <ul class="space-y-3 text-sm text-slate-400">
                        <li><a href="about.php"         class="hover:text-blue-400 transition-colors">About Vakil</a></li>
                        <li><a href="index.php#experts" class="hover:text-blue-400 transition-colors">Find Lawyers</a></li>
                        <li><a href="news.php"          class="hover:text-blue-400 transition-colors">Legal News</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4 text-sm uppercase tracking-wider">Resources</h4>
                    <ul class="space-y-3 text-sm text-slate-400">
                        <li><a href="bns_library.php" class="hover:text-blue-400 transition-colors">BNS Library</a></li>
                        <li><a href="login.php"       class="hover:text-blue-400 transition-colors">Client Portal</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4 text-sm uppercase tracking-wider">Contact</h4>
                    <ul class="space-y-3 text-sm text-slate-400">
                        <li class="flex items-start gap-2"><i class="fas fa-map-marker-alt mt-0.5 text-blue-500"></i><span>Mumbai, India</span></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/5 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-slate-500 text-xs">&copy; <?= date('Y') ?> Vakil. All rights reserved.</p>
                <a href="index.php" class="text-xs text-slate-500 hover:text-blue-400 transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Home
                </a>
            </div>
        </div>
    </footer>

    <!-- ===== JAVASCRIPT ===== -->
    <script>
        // ---- Theme Toggle ----
        const themeToggleBtn = document.getElementById('theme-toggle');
        const htmlEl = document.documentElement;

        function applyTheme(theme) {
            htmlEl.setAttribute('data-theme', theme);
            themeToggleBtn.innerHTML = theme === 'light'
                ? '<i class="fas fa-moon"></i>'
                : '<i class="fas fa-sun"></i>';
            localStorage.setItem('vakil-theme', theme);
        }

        const savedTheme = localStorage.getItem('vakil-theme') || 'dark';
        applyTheme(savedTheme);

        themeToggleBtn.addEventListener('click', () => {
            applyTheme(htmlEl.getAttribute('data-theme') === 'light' ? 'dark' : 'light');
        });

        // ---- Live Search ----
        const searchInput   = document.getElementById('news-search');
        const clearBtn      = document.getElementById('clear-search');
        const cards         = document.querySelectorAll('.news-card');
        const noResults     = document.getElementById('no-results');
        const visibleCount  = document.getElementById('visible-count');

        function filterNews(query) {
            const q = query.trim().toLowerCase();
            let count = 0;

            cards.forEach(card => {
                const title   = card.dataset.title   || '';
                const summary = card.dataset.summary || '';
                const match   = !q || title.includes(q) || summary.includes(q);
                card.style.display = match ? '' : 'none';
                if (match) count++;
            });

            visibleCount.textContent = count;
            noResults.style.display  = count === 0 ? 'block' : 'none';
            clearBtn.classList.toggle('hidden', !q);
        }

        searchInput.addEventListener('input', () => filterNews(searchInput.value));

        function clearSearch() {
            searchInput.value = '';
            filterNews('');
        }
        clearBtn.addEventListener('click', clearSearch);
    </script>
</body>
</html>
