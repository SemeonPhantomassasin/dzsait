<div class="topbar">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="text-white fw-bold">Админ-панель</div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-light btn-sm" href="/admin/index.php">Главная</a>
            <a class="btn btn-light btn-sm" href="/admin/users.php">Пользователи</a>
            <a class="btn btn-light btn-sm" href="/admin/categories.php">Категории</a>
            <a class="btn btn-light btn-sm" href="/admin/products.php">Товары</a>
            <a class="btn btn-light btn-sm" href="/admin/reviews.php">Отзывы</a>
            <a class="btn btn-light btn-sm" href="/admin/orders.php">Заказы</a>
            <a class="btn btn-outline-light btn-sm" href="/logout.php">Выход</a>
        </div>
    </div>
</div>

<script>
(function () {
    function ensureTabId() {
        var key = 'dz_tab_id';
        var tabId = sessionStorage.getItem(key);
        if (!tabId) {
            tabId = (Date.now().toString(36) + Math.random().toString(36).slice(2, 10)).replace(/\./g, '');
            sessionStorage.setItem(key, tabId);
        }
        return tabId;
    }

    function withTab(url, tabId) {
        try {
            var u = new URL(url, window.location.origin);
            if (u.origin !== window.location.origin) return url;
            if (!u.searchParams.has('tab')) u.searchParams.set('tab', tabId);
            return u.pathname + (u.search ? u.search : '') + (u.hash ? u.hash : '');
        } catch (e) {
            return url;
        }
    }

    var tabId = ensureTabId();

    try {
        var cur = new URL(window.location.href);
        if (!cur.searchParams.has('tab')) {
            cur.searchParams.set('tab', tabId);
            window.history.replaceState(null, '', cur.toString());
        }
    } catch (e) {}

    document.querySelectorAll('a[href]').forEach(function (a) {
        var href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return;
        a.setAttribute('href', withTab(href, tabId));
    });

    document.querySelectorAll('form').forEach(function (f) {
        if (!f.querySelector('input[name="tab"]')) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'tab';
            input.value = tabId;
            f.appendChild(input);
        } else {
            f.querySelector('input[name="tab"]').value = tabId;
        }
        var action = f.getAttribute('action') || window.location.pathname + window.location.search;
        f.setAttribute('action', withTab(action, tabId));
    });
})();
</script>

