    </div> <!-- /container -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h5>О проекте</h5>
                    <ul>
                        <li><a href="/about.php">О нас</a></li>
                        <li><a href="/masters.php">Наши мастера</a></li>
                        <li><a href="/catalog.php">Каталог товаров</a></li>
                        <li><a href="/register.php">Стать мастером</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h5>Покупателям</h5>
                    <ul>
                        <li><a href="/catalog.php">Каталог</a></li>
                        <li><a href="/register.php">Регистрация</a></li>
                        <li><a href="/login.php">Вход в аккаунт</a></li>
                        <li><a href="/about.php">Как это работает</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h5>Мастерам</h5>
                    <ul>
                        <li><a href="/register.php">Регистрация</a></li>
                        <li><a href="/dashboard/index.php">Панель управления</a></li>
                        <li><a href="/about.php">Условия сотрудничества</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h5>Контакты</h5>
                    <ul>
                        <li>Email: info@ugolok-mastera.ru</li>
                        <li>Телефон: +7 (800) 123-45-67</li>
                        <li>Время работы: Пн-Вс 9:00-21:00</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <div>© <?=date('Y');?> Уголок Мастера. Все права защищены.</div>
                <div class="text-muted">Ручная работа, честные мастера, прозрачные заказы.</div>
            </div>
        </div>
    </footer>

    <script>
    (function () {
        function ensureTabId() {
            var key = 'dz_tab_id';
            var tabId = sessionStorage.getItem(key);
            if (!tabId) {
                // короткий уникальный id на вкладку
                tabId = (Date.now().toString(36) + Math.random().toString(36).slice(2, 10)).replace(/\./g, '');
                sessionStorage.setItem(key, tabId);
            }
            return tabId;
        }

        function withTab(url, tabId) {
            try {
                var u = new URL(url, window.location.origin);
                // только наши внутренние ссылки
                if (u.origin !== window.location.origin) return url;
                if (!u.searchParams.has('tab')) {
                    u.searchParams.set('tab', tabId);
                }
                return u.pathname + (u.search ? u.search : '') + (u.hash ? u.hash : '');
            } catch (e) {
                return url;
            }
        }

        var tabId = ensureTabId();

        // Если tab отсутствует в URL — добавим (иначе сервер не поймёт, какая это вкладка)
        try {
            var cur = new URL(window.location.href);
            if (!cur.searchParams.has('tab')) {
                cur.searchParams.set('tab', tabId);
                window.history.replaceState(null, '', cur.toString());
            }
        } catch (e) {}

        // Проставляем tab во все внутренние ссылки
        document.querySelectorAll('a[href]').forEach(function (a) {
            var href = a.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return;
            if (href.startsWith('http://') || href.startsWith('https://')) {
                // если это наш домен — тоже добавим tab
                a.setAttribute('href', withTab(href, tabId));
                return;
            }
            // относительные/абсолютные пути
            a.setAttribute('href', withTab(href, tabId));
        });

        // Проставляем hidden tab во все формы (POST/GET)
        document.querySelectorAll('form').forEach(function (f) {
            // Добавим скрытое поле tab, если его нет
            if (!f.querySelector('input[name="tab"]')) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tab';
                input.value = tabId;
                f.appendChild(input);
            } else {
                f.querySelector('input[name="tab"]').value = tabId;
            }

            // Подстрахуемся: tab в action для GET-форм и форм без action
            var action = f.getAttribute('action') || window.location.pathname + window.location.search;
            f.setAttribute('action', withTab(action, tabId));
        });
    })();
    </script>

    <script>
    (function () {
        function ensureOverlay() {
            var existing = document.getElementById('dzLightboxOverlay');
            if (existing) return existing;

            var overlay = document.createElement('div');
            overlay.id = 'dzLightboxOverlay';
            overlay.className = 'dz-lightbox-overlay';
            overlay.innerHTML = '<img alt="">';
            document.body.appendChild(overlay);

            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) close();
            });

            function close() {
                overlay.classList.remove('is-open');
                overlay.querySelector('img').src = '';
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') close();
            });

            overlay._dzClose = close;
            return overlay;
        }

        document.addEventListener('click', function (e) {
            var a = e.target && e.target.closest ? e.target.closest('[data-dz-lightbox]') : null;
            if (!a) return;
            var href = a.getAttribute('href');
            if (!href) return;
            e.preventDefault();
            var overlay = ensureOverlay();
            overlay.querySelector('img').src = href;
            overlay.classList.add('is-open');
        });
    })();
    </script>
</body>
</html>

