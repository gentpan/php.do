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

    function b64urlToBuffer(value) {
        value = String(value || '').replace(/-/g, '+').replace(/_/g, '/');
        while (value.length % 4) value += '=';
        var binary = atob(value);
        var bytes = new Uint8Array(binary.length);
        for (var i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
        return bytes.buffer;
    }

    function bufferToB64url(buffer) {
        var bytes = new Uint8Array(buffer);
        var binary = '';
        for (var i = 0; i < bytes.byteLength; i++) binary += String.fromCharCode(bytes[i]);
        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
    }

    function passkeyOptions(options) {
        options.challenge = b64urlToBuffer(options.challenge);
        if (options.user && options.user.id) options.user.id = b64urlToBuffer(options.user.id);
        ['allowCredentials', 'excludeCredentials'].forEach(function(key) {
            if (!options[key]) return;
            options[key].forEach(function(item) {
                item.id = b64urlToBuffer(item.id);
            });
        });
        return options;
    }

    function passkeyAvailable() {
        return window.PublicKeyCredential && navigator.credentials;
    }

    function passkeyRequest(action, payload) {
        return fetch('api/passkey?action=' + encodeURIComponent(action), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.qfCsrfToken || ''
            },
            body: JSON.stringify(payload || {})
        }).then(function(res) {
            return res.json().then(function(json) {
                if (!res.ok || !json.ok) throw new Error(json.error || 'Passkey 操作失败。');
                return json;
            });
        });
    }

    function credentialCreatePayload(credential) {
        var response = credential.response;
        return {
            id: credential.id,
            rawId: bufferToB64url(credential.rawId),
            type: credential.type,
            clientDataJSON: bufferToB64url(response.clientDataJSON),
            attestationObject: bufferToB64url(response.attestationObject),
            transports: typeof response.getTransports === 'function' ? response.getTransports() : []
        };
    }

    function credentialGetPayload(credential) {
        var response = credential.response;
        return {
            id: credential.id,
            rawId: bufferToB64url(credential.rawId),
            type: credential.type,
            clientDataJSON: bufferToB64url(response.clientDataJSON),
            authenticatorData: bufferToB64url(response.authenticatorData),
            signature: bufferToB64url(response.signature),
            userHandle: response.userHandle ? bufferToB64url(response.userHandle) : ''
        };
    }

    function initPasskeys() {
        document.addEventListener('click', function(e) {
            var register = e.target.closest('[data-passkey-register]');
            if (register) {
                e.preventDefault();
                if (!passkeyAvailable()) {
                    toast('当前浏览器不支持 Passkey。', 'error');
                    return;
                }
                setLoading(true);
                passkeyRequest('register-options')
                    .then(function(json) {
                        return navigator.credentials.create({ publicKey: passkeyOptions(json.publicKey) });
                    })
                    .then(function(credential) {
                        return passkeyRequest('register-verify', credentialCreatePayload(credential));
                    })
                    .then(function(json) {
                        toast(json.message || 'Passkey 已添加。', 'success');
                        window.setTimeout(function() { window.location.reload(); }, 500);
                    })
                    .catch(function(err) {
                        toast(err.message || 'Passkey 添加失败。', 'error');
                    })
                    .finally(function() {
                        setLoading(false);
                    });
                return;
            }

            var login = e.target.closest('[data-passkey-login]');
            if (login) {
                e.preventDefault();
                if (!passkeyAvailable()) {
                    toast('当前浏览器不支持 Passkey。', 'error');
                    return;
                }
                var form = login.closest('form');
                var username = form ? form.querySelector('input[name="username"]') : null;
                var usernameValue = username ? username.value.trim() : '';
                if (!usernameValue) {
                    toast('请先输入用户名。', 'error');
                    if (username) username.focus();
                    return;
                }
                setLoading(true);
                passkeyRequest('login-options', { username: usernameValue })
                    .then(function(json) {
                        return navigator.credentials.get({ publicKey: passkeyOptions(json.publicKey) });
                    })
                    .then(function(credential) {
                        return passkeyRequest('login-verify', credentialGetPayload(credential));
                    })
                    .then(function(json) {
                        window.location.href = json.redirect || '/';
                    })
                    .catch(function(err) {
                        toast(err.message || 'Passkey 登录失败。', 'error');
                    })
                    .finally(function() {
                        setLoading(false);
                    });
                return;
            }

            var remove = e.target.closest('[data-passkey-delete]');
            if (remove) {
                e.preventDefault();
                setLoading(true);
                passkeyRequest('delete', { id: remove.getAttribute('data-passkey-delete') })
                    .then(function(json) {
                        toast(json.message || 'Passkey 已删除。', 'success');
                        window.setTimeout(function() { window.location.reload(); }, 500);
                    })
                    .catch(function(err) {
                        toast(err.message || 'Passkey 删除失败。', 'error');
                    })
                    .finally(function() {
                        setLoading(false);
                    });
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

    function initSearchModal() {
        var modal = document.getElementById('qf-search-modal');
        if (!modal) return;
        var input = modal.querySelector('input[name="q"]');

        function openSearch() {
            modal.classList.add('is-open');
            if (input) {
                setTimeout(function() {
                    input.focus();
                    input.select();
                }, 40);
            }
        }

        function closeSearch() {
            modal.classList.remove('is-open');
        }

        document.addEventListener('click', function(e) {
            var open = e.target.closest('[data-search-open]');
            if (open) {
                e.preventDefault();
                openSearch();
                return;
            }

            if (e.target.matches('[data-search-close]') || e.target === modal) {
                closeSearch();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeSearch();
        });
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
                captcha.src = 'api/captcha?t=' + Date.now();
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

    function initThreadVotes() {
        document.addEventListener('submit', function(e) {
            var form = e.target.closest('form[data-vote-form]');
            if (!form || e.defaultPrevented) return;
            e.preventDefault();
            var root = form.closest('[data-thread-votes]');
            var data = new FormData(form);
            if (!data.get('csrf_token')) data.append('csrf_token', window.qfCsrfToken || '');
            setLoading(true);
            fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            }).then(function(res) {
                return res.json().then(function(json) {
                    if (!res.ok || !json.ok) throw new Error(json.error || '投票失败。');
                    return json;
                });
            }).then(function(json) {
                if (!root) return;
                var up = root.querySelector('[data-vote-count="up"]');
                var down = root.querySelector('[data-vote-count="down"]');
                var upButton = root.querySelector('[data-vote-button="up"]');
                var downButton = root.querySelector('[data-vote-button="down"]');
                if (up) up.textContent = json.upvotes;
                if (down) down.textContent = json.downvotes;
                if (upButton) {
                    upButton.classList.toggle('active', Number(json.vote) === 1);
                    upButton.setAttribute('aria-pressed', Number(json.vote) === 1 ? 'true' : 'false');
                }
                if (downButton) {
                    downButton.classList.toggle('active', Number(json.vote) === -1);
                    downButton.setAttribute('aria-pressed', Number(json.vote) === -1 ? 'true' : 'false');
                }
            }).catch(function(err) {
                toast(err.message || '投票失败。', 'error');
            }).finally(function() {
                setLoading(false);
            });
        });
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
            var feedLink = e.target.closest('.phpdo-feed-tabs a[data-feed-filter]');
            if (feedLink && !feedLink.target && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey) {
                var feedFilter = feedLink.getAttribute('data-feed-filter') || 'reply';
                var feedUrl = new URL(window.location.origin + window.location.pathname);
                if (feedFilter !== 'reply') {
                    feedUrl.searchParams.set('filter', feedFilter);
                }

                e.preventDefault();
                setLoading(true);
                document.body.classList.add('qf-ajax-loading');
                fetch(feedUrl.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(res) {
                        return res.text();
                    })
                    .then(function(html) {
                        var nextDoc = new DOMParser().parseFromString(html, 'text/html');
                        replaceFrom(nextDoc, ['.phpdo-feed-tabs', '.latest-list', '.phpdo-breadcrumb']);
                        document.title = nextDoc.title || document.title;
                        enhanceMedia(document);
                    })
                    .catch(function() {
                        window.location.href = feedLink.href;
                    })
                    .finally(function() {
                        document.body.classList.remove('qf-ajax-loading');
                        setLoading(false);
                    });
                return;
            }

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
        loader.innerHTML = '<svg stroke="#5d29f0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g><circle cx="12" cy="12" r="9.5" fill="none" stroke-width="3" stroke-linecap="round"><animate attributeName="stroke-dasharray" dur="1.5s" calcMode="spline" values="0 150;42 150;42 150;42 150" keyTimes="0;0.475;0.95;1" keySplines="0.42,0,0.58,1;0.42,0,0.58,1;0.42,0,0.58,1" repeatCount="indefinite"/><animate attributeName="stroke-dashoffset" dur="1.5s" calcMode="spline" values="0;-16;-59;-59" keyTimes="0;0.475;0.95;1" keySplines="0.42,0,0.58,1;0.42,0,0.58,1;0.42,0,0.58,1" repeatCount="indefinite"/></circle><animateTransform attributeName="transform" type="rotate" dur="2s" values="0 12 12;360 12 12" repeatCount="indefinite"/></g></svg>';
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

    function initRightToolbar() {
        var toolbar = document.querySelector('.phpdo-right-toolbar');
        if (!toolbar) return;
        var topButton = toolbar.querySelector('[data-scroll-top]');
        var bottomButton = toolbar.querySelector('[data-scroll-bottom]');

        function setState() {
            var y = window.pageYOffset || document.documentElement.scrollTop || 0;
            toolbar.classList.toggle('is-scrolled', y > 160);
        }

        if (topButton) {
            topButton.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        if (bottomButton) {
            bottomButton.addEventListener('click', function() {
                var bottom = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
                window.scrollTo({ top: bottom, behavior: 'smooth' });
            });
        }

        window.addEventListener('scroll', setState, { passive: true });
        setState();
    }

    window.qfEnhanceMedia = enhanceMedia;
    window.qfSetLoading = setLoading;
    initNavMore();
    initSideUserMenu();
    initAuthModal();
    initPasskeys();
    initSearchModal();
    initThreadVotes();
    initSigninModal();
    initInlineActions();
    initRightToolbar();
    initFormLoading();
    initToast();
    enhanceMedia(document);
    initAjaxFilters();

    requestAnimationFrame(function() {
        document.body.classList.add('qf-page-ready');
    });
})();
