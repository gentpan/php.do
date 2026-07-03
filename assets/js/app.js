(function() {
    var loadingCount = 0;

    function initNavMore() {
        var toggle = document.querySelector('[data-nav-more]');
        var menu = document.querySelector('[data-nav-more-menu]');
        if (!toggle || !menu) return;

        function setOpen(open) {
            toggle.classList.toggle('is-open', open);
            menu.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            setOpen(!menu.classList.contains('is-open'));
        });

        document.addEventListener('click', function(e) {
            if (!menu.contains(e.target) && e.target !== toggle && !toggle.contains(e.target)) {
                setOpen(false);
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') setOpen(false);
        });
    }

    function initSideUserMenu() {
        var root = document.querySelector('[data-side-user-menu]');
        if (!root) return;
        var toggle = root.querySelector('[data-side-user-toggle]');
        var panel = root.querySelector('[data-side-user-panel]');
        if (!toggle || !panel) return;
        var pinned = false;

        function setOpen(open) {
            root.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            pinned = !root.classList.contains('is-open') || !pinned;
            setOpen(pinned);
        });

        root.addEventListener('mouseenter', function() {
            setOpen(true);
        });

        root.addEventListener('mouseleave', function() {
            if (!pinned) setOpen(false);
        });

        document.addEventListener('click', function(e) {
            if (!root.contains(e.target)) {
                pinned = false;
                setOpen(false);
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                pinned = false;
                setOpen(false);
            }
        });
    }

    function initAuthModal() {
        var modal = document.getElementById('qf-auth-modal');
        if (!modal) return;

        function setMode(mode) {
            var tabs = modal.querySelectorAll('[data-auth-tab]');
            var panels = modal.querySelectorAll('[data-auth-panel]');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.toggle('active', tabs[i].getAttribute('data-auth-tab') === mode);
            }
            for (var j = 0; j < panels.length; j++) {
                panels[j].classList.toggle('active', panels[j].getAttribute('data-auth-panel') === mode);
            }
        }

        function openAuth(mode) {
            setMode(mode || 'login');
            modal.classList.add('is-open');
            var first = modal.querySelector('.auth-panel.active input');
            if (first) {
                setTimeout(function() {
                    first.focus();
                }, 40);
            }
        }

        function closeAuth() {
            modal.classList.remove('is-open');
        }

        document.addEventListener('click', function(e) {
            var open = e.target.closest('[data-auth-open]');
            if (open) {
                e.preventDefault();
                openAuth(open.getAttribute('data-auth-open'));
                return;
            }

            var tab = e.target.closest('[data-auth-tab]');
            if (tab) {
                setMode(tab.getAttribute('data-auth-tab'));
                return;
            }

            if (e.target.matches('[data-auth-close]') || e.target === modal) {
                closeAuth();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeAuth();
        });

        if (modal.getAttribute('data-initial-auth')) {
            openAuth(modal.getAttribute('data-initial-auth'));
        }
    }

    function initSigninModal() {
        function closeSigninModal() {
            var modal = document.getElementById('qf-signin-modal');
            if (modal) modal.style.display = 'none';
        }

        window.qfCloseSigninModal = closeSigninModal;
        document.addEventListener('click', function(e) {
            if (e.target.closest('[data-signin-close]')) closeSigninModal();
        });
    }

    function initInlineActions() {
        document.addEventListener('click', function(e) {
            var captcha = e.target.closest('[data-captcha-refresh]');
            if (captcha) {
                captcha.src = 'captcha.php?t=' + Date.now();
                return;
            }

            var loginRequired = e.target.closest('[data-login-required]');
            if (loginRequired) {
                e.preventDefault();
                if (confirm('需要登录才能进行此操作')) {
                    window.location.href = loginRequired.getAttribute('data-login-url') || loginRequired.href;
                }
                return;
            }

            var confirmed = e.target.closest('[data-confirm]');
            if (confirmed && !confirm(confirmed.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });

        document.addEventListener('submit', function(e) {
            var form = e.target.closest('form[data-confirm]');
            if (form && !confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    }

    function enhanceMedia(root) {
        root = root || document;
        var imageSelector = '.post-content-box img, .reply .content img, .attachment-list img.attachment-img';
        root.querySelectorAll(imageSelector).forEach(function(img) {
            if (!img.hasAttribute('loading')) img.setAttribute('loading', 'lazy');
            img.setAttribute('decoding', 'async');
            img.classList.add('qf-zoomable-image');
        });

        if (window.LiteZoom && !window.qfLiteZoomBound) {
            window.qfLiteZoomBound = true;
            window.LiteZoom.bind(imageSelector, {
                mode: 'full',
                group: function(img) {
                    var block = img.closest('.reply, .post-content-card, .attachment-list');
                    if (!block) return 'lume-images';
                    if (!block.dataset.lzGroup) {
                        block.dataset.lzGroup = 'lume-' + Math.random().toString(36).slice(2);
                    }
                    return block.dataset.lzGroup;
                },
                caption: function(img) {
                    return (img.getAttribute('alt') || '').trim();
                }
            });
        }

        if (window.LiteZoom && typeof window.LiteZoom.refresh === 'function') {
            window.LiteZoom.refresh(root);
        }
    }

    function replaceFrom(nextDoc, selectors) {
        selectors.forEach(function(selector) {
            var current = document.querySelector(selector);
            var next = nextDoc.querySelector(selector);
            if (current && next) current.innerHTML = next.innerHTML;
        });
    }

    function initAjaxFilters() {
        document.addEventListener('click', function(e) {
            var link = e.target.closest('.filter-tabs a, .latest-title-dropdown a');
            if (!link || link.target || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            var url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return;

            e.preventDefault();
            setLoading(true);
            document.body.classList.add('qf-ajax-loading');
            fetch(url.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(res) {
                    return res.text();
                })
                .then(function(html) {
                    var nextDoc = new DOMParser().parseFromString(html, 'text/html');
                    replaceFrom(nextDoc, ['.latest-title-trigger', '.latest-list', '.thread-list']);

                    var currentTabs = document.querySelectorAll('.filter-tabs');
                    var nextTabs = nextDoc.querySelectorAll('.filter-tabs');
                    currentTabs.forEach(function(tab, index) {
                        if (nextTabs[index]) tab.innerHTML = nextTabs[index].innerHTML;
                    });

                    document.title = nextDoc.title || document.title;
                    history.pushState({ qfAjax: true }, '', url.href);
                    enhanceMedia(document);
                })
                .catch(function() {
                    window.location.href = url.href;
                })
                .finally(function() {
                    document.body.classList.remove('qf-ajax-loading');
                    setLoading(false);
                });
        });

        window.addEventListener('popstate', function() {
            window.location.reload();
        });
    }

    function ensureLoadingIndicator() {
        if (document.querySelector('.qf-loading-indicator')) return;

        var loader = document.createElement('div');
        loader.className = 'qf-loading-indicator';
        loader.setAttribute('aria-hidden', 'true');
        loader.innerHTML = '<svg stroke="hsl(228, 97%, 42%)" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g><circle cx="12" cy="12" r="9.5" fill="none" stroke-width="3" stroke-linecap="round"><animate attributeName="stroke-dasharray" dur="1.5s" calcMode="spline" values="0 150;42 150;42 150;42 150" keyTimes="0;0.475;0.95;1" keySplines="0.42,0,0.58,1;0.42,0,0.58,1;0.42,0,0.58,1" repeatCount="indefinite"/><animate attributeName="stroke-dashoffset" dur="1.5s" calcMode="spline" values="0;-16;-59;-59" keyTimes="0;0.475;0.95;1" keySplines="0.42,0,0.58,1;0.42,0,0.58,1;0.42,0,0.58,1" repeatCount="indefinite"/></circle><animateTransform attributeName="transform" type="rotate" dur="2s" values="0 12 12;360 12 12" repeatCount="indefinite"/></g></svg>';
        document.body.appendChild(loader);
    }

    function setLoading(active) {
        ensureLoadingIndicator();
        loadingCount += active ? 1 : -1;
        if (loadingCount < 0) loadingCount = 0;
        document.body.classList.toggle('qf-is-loading', loadingCount > 0);
    }

    function initFormLoading() {
        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (e.defaultPrevented) return;
            if (!form || form.hasAttribute('data-no-global-loading')) return;
            if (form.closest('.auth-modal-box')) return;
            setLoading(true);
        });

        window.addEventListener('pageshow', function() {
            loadingCount = 0;
            setLoading(false);
        });
    }

    function toast(message, type) {
        message = String(message || '').trim();
        if (!message) return;
        type = type === 'error' ? 'error' : 'success';

        var old = document.querySelector('.qf-toast');
        if (old) old.remove();

        var item = document.createElement('div');
        item.className = 'qf-toast qf-toast-' + type;
        item.setAttribute('role', type === 'error' ? 'alert' : 'status');
        item.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
        item.innerHTML = '<i class="fa-solid ' + (type === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check') + '" aria-hidden="true"></i><span></span>';
        item.querySelector('span').textContent = message;
        document.body.appendChild(item);

        requestAnimationFrame(function() {
            item.classList.add('is-visible');
        });

        window.setTimeout(function() {
            item.classList.remove('is-visible');
            window.setTimeout(function() {
                if (item.parentNode) item.parentNode.removeChild(item);
            }, 180);
        }, 2200);
    }

    function initToast() {
        window.qfToast = toast;
        window.alert = function(message) {
            toast(message, 'error');
        };

        document.querySelectorAll('.alert:not(.auth-alert)').forEach(function(node) {
            var message = node.textContent || '';
            var type = node.classList.contains('success') ? 'success' : 'error';
            node.remove();
            toast(message, type);
        });
    }

    window.qfEnhanceMedia = enhanceMedia;
    window.qfSetLoading = setLoading;
    initNavMore();
    initSideUserMenu();
    initAuthModal();
    initSigninModal();
    initInlineActions();
    initFormLoading();
    initToast();
    enhanceMedia(document);
    initAjaxFilters();

    requestAnimationFrame(function() {
        document.body.classList.add('qf-page-ready');
    });
})();
