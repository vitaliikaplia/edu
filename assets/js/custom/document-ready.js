/**
 * document ready
 */
(function () {

    // --- Reading progress bar ---
    var progressBar = document.getElementById('readingProgress');
    if (progressBar) {
        window.addEventListener('scroll', function () {
            var scrollTop = window.scrollY;
            var docHeight = document.documentElement.scrollHeight - window.innerHeight;
            var progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
            progressBar.style.width = progress + '%';
        });
    }

    // --- Scroll to top ---
    var scrollBtn = document.getElementById('scrollToTop');
    if (scrollBtn) {
        function updateScrollBtn() {
            if (window.scrollY < 50) {
                scrollBtn.disabled = true;
            } else {
                scrollBtn.disabled = false;
            }
        }

        updateScrollBtn();
        window.addEventListener('scroll', updateScrollBtn, { passive: true });

        scrollBtn.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // --- Dark theme toggle ---
    var savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }

    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('.theme-toggle');
        if (!toggle) return;
        e.preventDefault();

        // enable smooth transition
        document.documentElement.classList.add('theme-switching');

        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (isDark) {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        }

        // remove transition class after animation
        setTimeout(function () {
            document.documentElement.classList.remove('theme-switching');
        }, 400);
    });

    // --- Search overlay ---
    var overlay = document.getElementById('searchOverlay');
    var input = document.getElementById('searchInput');
    var results = document.getElementById('searchResults');
    var closeBtn = document.getElementById('searchClose');
    var debounceTimer = null;
    var selectedIndex = -1;

    function openSearch() {
        if (!overlay) return;
        overlay.classList.add('is-active');
        document.body.style.overflow = 'hidden';
        setTimeout(function () { input.focus(); }, 100);
    }

    function closeSearch() {
        if (!overlay) return;
        overlay.classList.remove('is-active');
        document.body.style.overflow = '';
        input.value = '';
        results.innerHTML = '<div class="search-hint">Почніть вводити назву лекції або тему</div>';
        selectedIndex = -1;
    }

    // open on trigger click
    document.addEventListener('click', function (e) {
        if (e.target.closest('.search-trigger')) {
            e.preventDefault();
            openSearch();
        }
    });

    // close
    if (closeBtn) {
        closeBtn.addEventListener('click', closeSearch);
    }

    // close on overlay bg click
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeSearch();
        });
    }

    // close on Escape, open on Ctrl+K / Cmd+K
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay && overlay.classList.contains('is-active')) {
            closeSearch();
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            openSearch();
        }
        // arrow navigation in results
        if (overlay && overlay.classList.contains('is-active')) {
            var items = results.querySelectorAll('.search-result-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateSelected(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, 0);
                updateSelected(items);
            } else if (e.key === 'Enter' && selectedIndex >= 0 && items[selectedIndex]) {
                e.preventDefault();
                items[selectedIndex].click();
            }
        }
    });

    function updateSelected(items) {
        items.forEach(function (el, i) {
            el.classList.toggle('is-selected', i === selectedIndex);
        });
        if (items[selectedIndex]) {
            items[selectedIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    // debounced search
    if (input) {
        input.addEventListener('input', function () {
            var q = input.value.trim();
            selectedIndex = -1;

            if (debounceTimer) clearTimeout(debounceTimer);

            if (q.length < 2) {
                results.innerHTML = '<div class="search-hint">Почніть вводити назву лекції або тему</div>';
                return;
            }

            results.innerHTML = '<div class="search-hint">Шукаємо...</div>';

            debounceTimer = setTimeout(function () {
                fetch('/api/search/?q=' + encodeURIComponent(q))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!data.length) {
                            results.innerHTML = '<div class="search-no-results">Нічого не знайдено</div>';
                            return;
                        }
                        var html = '';
                        data.forEach(function (item) {
                            html += '<a href="' + item.url + '" class="search-result-item">';
                            html += '<span class="search-result-icon">' + item.course_icon + '</span>';
                            html += '<div class="search-result-body">';
                            html += '<span class="search-result-title">' + item.title + '</span>';
                            if (item.snippet) {
                                html += '<span class="search-result-snippet">' + item.snippet + '</span>';
                            }
                            html += '<span class="search-result-meta">' + item.course + ' · лекція ' + item.number + ' · ' + item.reading_time + ' хв</span>';
                            html += '</div>';
                            html += '</a>';
                        });
                        results.innerHTML = html;
                    })
                    .catch(function () {
                        results.innerHTML = '<div class="search-no-results">Помилка пошуку</div>';
                    });
            }, 300);
        });
    }

    // --- Copy code button ---
    var copyIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
    var checkIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';

    var preBlocks = document.querySelectorAll('pre');
    preBlocks.forEach(function (pre) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'code-copy-btn';
        btn.setAttribute('aria-label', 'Копіювати код');
        btn.innerHTML = copyIcon;
        pre.appendChild(btn);

        btn.addEventListener('click', function () {
            var code = pre.querySelector('code');
            var text = (code || pre).textContent;
            navigator.clipboard.writeText(text).then(function () {
                btn.innerHTML = checkIcon;
                btn.classList.add('is-copied');
                setTimeout(function () {
                    btn.innerHTML = copyIcon;
                    btn.classList.remove('is-copied');
                }, 1500);
            });
        });
    });

    // --- TOC sidebar: assign IDs to headings & highlight active ---
    var tocSidebar = document.getElementById('tocSidebar');
    if (tocSidebar) {
        var tocLinks = tocSidebar.querySelectorAll('.toc-link');
        var lessonContent = document.querySelector('.lesson-content');

        if (lessonContent && tocLinks.length) {
            // assign IDs to headings in DOM order
            var headings = lessonContent.querySelectorAll('h2, h3');
            var counter = 0;
            headings.forEach(function (heading) {
                counter++;
                heading.id = 'toc-' + counter;
            });

            // smooth scroll on click
            tocLinks.forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    var targetId = this.getAttribute('href').slice(1);
                    var target = document.getElementById(targetId);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        history.replaceState(null, '', '#' + targetId);
                    }
                });
            });

            // Intersection Observer for active link highlighting
            var activeLink = null;
            var tocNav = tocSidebar.querySelector('.toc-nav');

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        var id = entry.target.id;
                        if (activeLink) {
                            activeLink.classList.remove('is-active');
                        }
                        var link = tocSidebar.querySelector('a[href="#' + id + '"]');
                        if (link) {
                            link.classList.add('is-active');
                            activeLink = link;

                            // auto-scroll TOC nav so active link is visible
                            if (tocNav) {
                                var navRect = tocNav.getBoundingClientRect();
                                var linkRect = link.getBoundingClientRect();
                                if (linkRect.top < navRect.top || linkRect.bottom > navRect.bottom) {
                                    link.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                                }
                            }
                        }
                    }
                });
            }, {
                rootMargin: '0px 0px -70% 0px',
                threshold: 0
            });

            headings.forEach(function (heading) {
                if (heading.id) {
                    observer.observe(heading);
                }
            });
        }
    }

})();
