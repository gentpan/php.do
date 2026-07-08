(function() {
    var loadingCounts = { bar: 0, page: 0, dots: 0 };
    var loadProgress = { value: 0, timer: null, raf: null };
    var FEED_DOTS_SVG = '<svg fill="hsl(228, 97%, 42%)" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="4" cy="12" r="3" opacity="1"><animate id="spinner_qYjJ" begin="0;spinner_t4KZ.end-0.25s" attributeName="opacity" dur="0.75s" values="1;.2" fill="freeze"/></circle><circle cx="12" cy="12" r="3" opacity=".4"><animate begin="spinner_qYjJ.begin+0.15s" attributeName="opacity" dur="0.75s" values="1;.2" fill="freeze"/></circle><circle cx="20" cy="12" r="3" opacity=".3"><animate id="spinner_t4KZ" begin="spinner_qYjJ.begin+0.3s" attributeName="opacity" dur="0.75s" values="1;.2" fill="freeze"/></circle></svg>';
    var PAGE_SPINNER_SVG = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="hsl(228, 97%, 42%)" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="42 64"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.75s" repeatCount="indefinite"/></circle></svg>';

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
                setLoading(true, 'page');
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
                        setLoading(false, 'page');
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
                setLoading(true, 'page');
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
                        setLoading(false, 'page');
                    });
                return;
            }

            var remove = e.target.closest('[data-passkey-delete]');
            if (remove) {
                e.preventDefault();
                setLoading(true, 'page');
                passkeyRequest('delete', { id: remove.getAttribute('data-passkey-delete') })
                    .then(function(json) {
                        toast(json.message || 'Passkey 已删除。', 'success');
                        window.setTimeout(function() { window.location.reload(); }, 500);
                    })
                    .catch(function(err) {
                        toast(err.message || 'Passkey 删除失败。', 'error');
                    })
                    .finally(function() {
                        setLoading(false, 'page');
                    });
            }
        });
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

    function qfConfirm(message, opts) {
        opts = opts || {};
        var title = opts.title || '确认操作';
        var confirmText = opts.confirmText || '确定';
        var cancelText = opts.cancelText || '取消';
        var danger = opts.danger !== false;

        return new Promise(function(resolve) {
            var existing = document.getElementById('qf-confirm-overlay');
            if (existing && existing.parentNode) existing.parentNode.removeChild(existing);

            var overlay = document.createElement('div');
            overlay.id = 'qf-confirm-overlay';
            overlay.className = 'qf-confirm-overlay';
            overlay.innerHTML =
                '<div class="qf-confirm-box" role="dialog" aria-modal="true" aria-labelledby="qf-confirm-title">' +
                '<div class="qf-confirm-head">' +
                '<span class="qf-confirm-icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>' +
                '<h3 class="qf-confirm-title" id="qf-confirm-title"></h3>' +
                '</div>' +
                '<p class="qf-confirm-message"></p>' +
                '<div class="qf-confirm-actions">' +
                '<button type="button" class="btn btn-light" data-qf-confirm-cancel></button>' +
                '<button type="button" class="btn' + (danger ? ' btn-danger' : '') + '" data-qf-confirm-ok></button>' +
                '</div></div>';

            document.body.appendChild(overlay);
            overlay.querySelector('.qf-confirm-title').textContent = title;
            overlay.querySelector('.qf-confirm-message').textContent = message || '';
            overlay.querySelector('[data-qf-confirm-cancel]').textContent = cancelText;
            overlay.querySelector('[data-qf-confirm-ok]').textContent = confirmText;

            var settled = false;
            function finish(ok) {
                if (settled) return;
                settled = true;
                document.removeEventListener('keydown', onKey);
                overlay.classList.remove('is-open');
                window.setTimeout(function() {
                    if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
                }, 120);
                resolve(!!ok);
            }
            function onKey(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    finish(false);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    finish(true);
                }
            }

            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) finish(false);
            });
            overlay.querySelector('[data-qf-confirm-cancel]').addEventListener('click', function() { finish(false); });
            overlay.querySelector('[data-qf-confirm-ok]').addEventListener('click', function() { finish(true); });
            document.addEventListener('keydown', onKey);

            requestAnimationFrame(function() {
                overlay.classList.add('is-open');
                overlay.querySelector('[data-qf-confirm-ok]').focus();
            });
        });
    }
    window.qfConfirm = qfConfirm;

    function qfConfirmOpts(message) {
        var msg = String(message || '');
        var isDelete = /删除|清除|重置/.test(msg);
        return {
            title: isDelete ? '确认删除' : '确认操作',
            confirmText: isDelete ? '确定删除' : '确定',
            danger: isDelete
        };
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
                qfConfirm('需要登录才能进行此操作', { title: '需要登录', confirmText: '去登录', danger: false }).then(function(ok) {
                    if (ok) window.location.href = loginRequired.getAttribute('data-login-url') || loginRequired.href;
                });
                return;
            }

            var confirmed = e.target.closest('[data-confirm]');
            if (!confirmed || confirmed.hasAttribute('data-ajax') || confirmed.tagName === 'FORM') return;

            // form[data-confirm] 交给 submit 事件统一处理，避免点提交按钮时弹两次
            if (confirmed.closest('form[data-confirm]') && (confirmed.tagName === 'BUTTON' || confirmed.tagName === 'INPUT')) {
                return;
            }

            var msg = confirmed.getAttribute('data-confirm');
            if (!msg) return;
            e.preventDefault();

            qfConfirm(msg, qfConfirmOpts(msg)).then(function(ok) {
                if (!ok) return;
                if (confirmed.tagName === 'A') {
                    window.location.href = confirmed.href;
                    return;
                }
                var form = confirmed.form || confirmed.closest('form');
                if (form && (confirmed.tagName === 'BUTTON' || confirmed.tagName === 'INPUT')) {
                    form.setAttribute('data-qf-confirmed', '1');
                    if (typeof form.requestSubmit === 'function') form.requestSubmit(confirmed);
                    else form.submit();
                }
            });
        });

        document.addEventListener('submit', function(e) {
            var form = e.target.closest('form');
            if (!form || form.getAttribute('data-qf-confirmed') === '1') return;

            var submitter = e.submitter || null;
            var msg = form.getAttribute('data-confirm') || (submitter && submitter.getAttribute('data-confirm'));
            if (!msg) return;

            e.preventDefault();
            qfConfirm(msg, qfConfirmOpts(msg)).then(function(ok) {
                if (!ok) return;
                form.setAttribute('data-qf-confirmed', '1');
                if (typeof form.requestSubmit === 'function') form.requestSubmit(submitter || undefined);
                else form.submit();
            });
        });
    }

    // IP 地理位置异步查询（管理员可见的 IP 徽章）
    function loadIpGeo(root) {
        root = root || document;
        var badges = root.querySelectorAll('[data-ip-geo]:not([data-ip-loaded])');
        if (!badges.length) return;
        var ips = [];
        badges.forEach(function(el) {
            var ip = (el.getAttribute('data-ip-geo') || '').trim();
            if (ip && ips.indexOf(ip) < 0) ips.push(ip);
        });
        if (!ips.length) return;
        var base = window.qfGeoipUrl || 'api/geoip.php';
        var url = base + (base.indexOf('?') >= 0 ? '&' : '?') + 'ips=' + encodeURIComponent(ips.join(','));
        fetch(url, { credentials: 'same-origin' }).then(function(r) {
            return r.json();
        }).then(function(res) {
            if (!res || !res.ok || !res.data) return;
            badges.forEach(function(el) {
                var ip = (el.getAttribute('data-ip-geo') || '').trim();
                var info = res.data[ip];
                el.setAttribute('data-ip-loaded', '1');
                if (!info) return;
                var parts = [];
                if (info.country) parts.push(info.country);
                if (info.region && info.region !== info.country) parts.push(info.region);
                else if (info.city && info.city !== info.country) parts.push(info.city);
                var label = parts.join(' · ');
                var flagWrap = el.querySelector('.phpdo-ip-flag-wrap');
                var detail = el.querySelector('.phpdo-ip-detail');
                var flagUrl = (info.flag || '').trim();
                var code = (info.country_code || '').toLowerCase();
                if (!flagUrl && code) {
                    flagUrl = 'https://flagcdn.io/' + encodeURIComponent(code) + '.svg';
                }
                if (flagUrl && flagWrap) {
                    flagWrap.innerHTML = '<img class="phpdo-ip-flag" src="' + flagUrl.replace(/"/g, '') + '" alt="" width="16" height="16" loading="lazy" decoding="async">';
                    flagWrap.hidden = false;
                    el.classList.add('has-flag');
                }
                if (detail) {
                    detail.textContent = 'IP: ' + ip + (label ? ' · ' + label : '');
                }
                el.setAttribute('title', 'IP: ' + ip + (label ? ' · ' + label : ''));
            });
        }).catch(function() {});
    }

    function initIpGeo() {
        window.qfLoadIpGeo = loadIpGeo;
        // 尽快发起：原先 idle/最多等 2s 会体感更慢
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() { loadIpGeo(document); });
        } else {
            loadIpGeo(document);
        }
    }

    // 帖子管理工具栏（置顶/加精/删除等）走 AJAX，不刷新页面
    function initThreadAdminAjax() {
        document.addEventListener('click', function(e) {
            var badge = e.target.closest('.action-badge[data-ajax]');
            if (!badge) return;
            e.preventDefault();
            if (badge.classList.contains('is-loading')) return;

            var href = badge.getAttribute('href');
            if (!href) return;

            function runAjax() {
                var url = href + (href.indexOf('?') >= 0 ? '&' : '?') + 'ajax=1';
                badge.classList.add('is-loading');
                fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                }).then(function(r) {
                    return r.json();
                }).then(function(data) {
                    badge.classList.remove('is-loading');
                    if (!data || !data.ok) {
                        if (window.qfToast) window.qfToast((data && data.msg) ? data.msg : '操作失败');
                        return;
                    }
                    if (data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }
                    if (data.removed) {
                        var reply = badge.closest('.reply');
                        if (reply && reply.parentNode) reply.parentNode.removeChild(reply);
                        if (window.qfToast && data.msg) window.qfToast(data.msg);
                        return;
                    }
                    if (typeof data.tools === 'string') {
                        var tools = badge.closest('[data-thread-tools]');
                        if (tools) {
                            tools.innerHTML = data.tools;
                            if (window.qfLoadIpGeo) window.qfLoadIpGeo(tools);
                        }
                        if (window.qfToast && data.msg) window.qfToast(data.msg);
                    }
                }).catch(function() {
                    badge.classList.remove('is-loading');
                    if (window.qfToast) window.qfToast('网络错误，请重试');
                });
            }

            var confirmMsg = badge.getAttribute('data-confirm');
            if (confirmMsg) {
                qfConfirm(confirmMsg, { title: '确认删除', confirmText: '删除', danger: true }).then(function(ok) {
                    if (ok) runAjax();
                });
                return;
            }
            runAjax();
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

        enhanceTimes(root);
    }

    function timeAgo(d) {
        var diff = Math.floor((Date.now() - d.getTime()) / 1000);
        if (diff < 0) diff = 0;
        if (diff < 60) return '刚刚';
        if (diff < 3600) return Math.floor(diff / 60) + ' 分钟前';
        if (diff < 86400) return Math.floor(diff / 3600) + ' 小时前';
        if (diff < 2592000) return Math.floor(diff / 86400) + ' 天前';
        if (diff < 31536000) return Math.floor(diff / 2592000) + ' 个月前';
        return Math.floor(diff / 31536000) + ' 年前';
    }

    function formatAbsolute(d) {
        var tz = (window.qfUserTimezone || '').trim();
        var opts = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        };
        try {
            if (tz) {
                return d.toLocaleString('zh-CN', Object.assign({ timeZone: tz }, opts));
            }
            return d.toLocaleString('zh-CN', opts);
        } catch (e) {
            return d.toLocaleString();
        }
    }

    function enhanceTimes(root) {
        (root || document).querySelectorAll('.phpdo-time[datetime]').forEach(function(el) {
            var iso = el.getAttribute('datetime');
            if (!iso) return;
            var d = new Date(iso);
            if (isNaN(d.getTime())) return;
            el.textContent = timeAgo(d);
            el.setAttribute('title', formatAbsolute(d));
        });
    }

    function qfConfettiBurst(anchor, opts) {
        if (!anchor || typeof anchor.getBoundingClientRect !== 'function') return;
        opts = opts || {};
        var themes = {
            like: { emojis: ['👍', '✨', '💛'], count: 18 },
            cheer: { emojis: ['👏', '✨', '🎊'], count: 20 },
            celebrate: { emojis: ['🎉', '🌸', '✨', '🎊'], count: 30 },
            appreciate: { emojis: ['✨', '⭐', '💫'], count: 22 },
            smile: { emojis: ['🙂', '✨'], count: 14 },
            upvote: { emojis: ['👍', '🌸', '✨', '🎉'], count: 24 }
        };
        var theme = themes[opts.theme] || themes.celebrate;
        var rect = anchor.getBoundingClientRect();
        var x = rect.left + rect.width / 2;
        var y = rect.top + rect.height / 2;
        var layer = document.createElement('div');
        layer.className = 'phpdo-confetti-layer';
        layer.setAttribute('aria-hidden', 'true');
        document.body.appendChild(layer);
        var colors = ['#f5a623', '#ff674f', '#505b93', '#ff8ab0', '#79c779', '#ffd54f'];
        var i, p, angle, dist, dx, dy, rot, size;
        for (i = 0; i < theme.count; i++) {
            p = document.createElement('span');
            p.className = 'phpdo-confetti-piece';
            if (theme.emojis && theme.emojis.length) {
                p.className += ' phpdo-confetti-emoji';
                p.textContent = theme.emojis[i % theme.emojis.length];
            } else {
                size = 5 + Math.random() * 7;
                p.style.width = size + 'px';
                p.style.height = (size * 0.55) + 'px';
                p.style.background = colors[i % colors.length];
            }
            angle = Math.random() * Math.PI * 2;
            dist = 36 + Math.random() * 96;
            dx = Math.cos(angle) * dist;
            dy = Math.sin(angle) * dist - (28 + Math.random() * 40);
            rot = (Math.random() * 720 - 360) + 'deg';
            p.style.setProperty('--dx', dx + 'px');
            p.style.setProperty('--dy', dy + 'px');
            p.style.setProperty('--rot', rot);
            p.style.left = x + 'px';
            p.style.top = y + 'px';
            p.style.animationDelay = (Math.random() * 120) + 'ms';
            layer.appendChild(p);
        }
        window.setTimeout(function() {
            if (layer.parentNode) layer.parentNode.removeChild(layer);
        }, 1400);
    }

    function qfReactionPop(btn) {
        if (!btn) return;
        btn.classList.remove('is-popping');
        void btn.offsetWidth;
        btn.classList.add('is-popping');
        window.setTimeout(function() {
            btn.classList.remove('is-popping');
        }, 450);
    }

    function initThreadVotes() {
        document.addEventListener('submit', function(e) {
            var form = e.target.closest('form[data-vote-form]');
            if (!form || e.defaultPrevented) return;
            e.preventDefault();
            var root = form.closest('[data-thread-votes], [data-post-votes]');
            var data = new FormData(form);
            if (!data.get('csrf_token')) data.append('csrf_token', window.qfCsrfToken || '');
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
                if (Number(json.vote) === 1) {
                    qfConfettiBurst(upButton || form, { theme: 'upvote' });
                    qfReactionPop(upButton);
                }
            }).catch(function(err) {
                toast(err.message || '投票失败。', 'error');
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
                setLoading(true, 'dots');
                fetch(feedUrl.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(res) {
                        return res.text();
                    })
                    .then(function(html) {
                        var nextDoc = new DOMParser().parseFromString(html, 'text/html');
                        replaceFrom(nextDoc, ['.phpdo-feed-tabs', '.latest-list', '.phpdo-breadcrumb']);
                        ensureFeedDots();
                        var curList = document.querySelector('[data-feed-list]');
                        var nextList = nextDoc.querySelector('[data-feed-list]');
                        if (curList && nextList) {
                            curList.setAttribute('data-has-more', nextList.getAttribute('data-has-more') || '0');
                            curList.setAttribute('data-latest-ts', nextList.getAttribute('data-latest-ts') || '');
                            curList.setAttribute('data-filter', nextList.getAttribute('data-filter') || feedFilter);
                        }
                        document.title = nextDoc.title || document.title;
                        if (typeof window.qfFeedRememberTitle === 'function') {
                            window.qfFeedRememberTitle(document.title);
                        }
                        if (typeof window.qfFeedSync === 'function') window.qfFeedSync();
                        enhanceMedia(document);
                    })
                    .catch(function() {
                        window.location.href = feedLink.href;
                    })
                    .finally(function() {
                        setLoading(false, 'dots');
                    });
                return;
            }

            var link = e.target.closest('.filter-tabs a, .latest-title-dropdown a');
            if (!link || link.target || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            var url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return;

            e.preventDefault();
            setLoading(true, 'page');
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
                    setLoading(false, 'page');
                });
        });

        window.addEventListener('popstate', function() {
            window.location.reload();
        });
    }

    function initFeedStream() {
        var list = document.querySelector('[data-feed-list]');
        if (!list) return;

        var moreWrap = document.querySelector('[data-feed-more]');
        var moreBtn = moreWrap ? moreWrap.querySelector('[data-load-more]') : null;
        var endEl = moreWrap ? moreWrap.querySelector('[data-feed-end]') : null;
        var newBtn = document.querySelector('[data-new-topics]');
        var newCount = newBtn ? newBtn.querySelector('[data-new-count]') : null;

        var state = { page: 1, loading: false, hasMore: false, autoUsed: false, filter: 'reply', latestTs: '', newCount: 0 };
        var baseTitle = String(document.title || '').replace(/^\(\d+\)\s*/, '');

        function buildUrl(params) {
            var url = new URL(window.location.origin + window.location.pathname);
            Object.keys(params).forEach(function(k) { url.searchParams.set(k, params[k]); });
            return url.href;
        }

        function renderMoreUi() {
            if (moreBtn) moreBtn.hidden = !state.hasMore;
            if (endEl) endEl.hidden = state.hasMore || !list.querySelector('.phpdo-thread-row');
        }

        function syncDocTitle(n) {
            n = parseInt(n, 10) || 0;
            document.title = n > 0 ? ('(' + n + ') ' + baseTitle) : baseTitle;
        }

        function rememberBaseTitle(title) {
            baseTitle = String(title || document.title || '').replace(/^\(\d+\)\s*/, '');
            syncDocTitle(state.newCount);
        }

        function hideNewTopics() {
            state.newCount = 0;
            if (newBtn) newBtn.hidden = true;
            syncDocTitle(0);
        }

        function showNewTopics(n) {
            n = parseInt(n, 10) || 0;
            if (n <= 0) {
                hideNewTopics();
                return;
            }
            state.newCount = n;
            if (!newBtn) {
                syncDocTitle(n);
                return;
            }
            if (newCount) newCount.textContent = n;
            newBtn.hidden = false;
            syncDocTitle(n);
        }

        function syncFromList() {
            state.page = 1;
            state.loading = false;
            state.autoUsed = false;
            state.filter = list.getAttribute('data-filter') || 'reply';
            state.hasMore = list.getAttribute('data-has-more') === '1';
            state.latestTs = list.getAttribute('data-latest-ts') || '';
            renderMoreUi();
            hideNewTopics();
        }

        function loadNext() {
            if (state.loading || !state.hasMore) return;
            state.loading = true;
            if (moreBtn) moreBtn.classList.add('is-loading');
            var next = state.page + 1;
            var params = { ajax: 'rows', page: next };
            if (state.filter !== 'reply') params.filter = state.filter;
            fetch(buildUrl(params), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(res) {
                    state.hasMore = res.headers.get('X-Has-More') === '1';
                    return res.text();
                })
                .then(function(html) {
                    var tmp = document.createElement('div');
                    tmp.innerHTML = html;
                    var added = tmp.querySelectorAll('.phpdo-thread-row').length;
                    if (added) {
                        var frag = document.createDocumentFragment();
                        while (tmp.firstChild) frag.appendChild(tmp.firstChild);
                        list.appendChild(frag);
                        state.page = next;
                        enhanceMedia(list);
                    } else {
                        state.hasMore = false;
                    }
                    list.setAttribute('data-has-more', state.hasMore ? '1' : '0');
                    renderMoreUi();
                })
                .catch(function() {})
                .finally(function() {
                    state.loading = false;
                    if (moreBtn) moreBtn.classList.remove('is-loading');
                });
        }

        if ('IntersectionObserver' in window && moreWrap) {
            var io = new IntersectionObserver(function(entries) {
                entries.forEach(function(en) {
                    if (en.isIntersecting && state.hasMore && !state.loading && !state.autoUsed) {
                        state.autoUsed = true;
                        loadNext();
                    }
                });
            }, { rootMargin: '240px 0px' });
            io.observe(moreWrap);
        }

        if (moreBtn) {
            moreBtn.addEventListener('click', function() { state.autoUsed = true; loadNext(); });
        }

        function poll() {
            if (!state.latestTs || document.hidden) return;
            var params = { ajax: 'check', since: state.latestTs };
            if (state.filter !== 'reply') params.filter = state.filter;
            fetch(buildUrl(params), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data && data.count > 0) showNewTopics(data.count);
                    else if (state.newCount > 0) hideNewTopics();
                })
                .catch(function() {});
        }
        window.setInterval(poll, 20000);
        window.setTimeout(poll, 8000);
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) poll();
        });
        window.qfFeedRememberTitle = rememberBaseTitle;

        if (newBtn) {
            newBtn.addEventListener('click', function() {
                if (state.loading) return;
                state.loading = true;
                newBtn.classList.add('is-loading');
                var params = {};
                if (state.filter !== 'reply') params.filter = state.filter;
                setLoading(true, 'dots');
                fetch(buildUrl(params), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(res) { return res.text(); })
                    .then(function(html) {
                        var nextDoc = new DOMParser().parseFromString(html, 'text/html');
                        var nextList = nextDoc.querySelector('[data-feed-list]');
                        if (nextList) {
                            list.innerHTML = nextList.innerHTML;
                            list.setAttribute('data-has-more', nextList.getAttribute('data-has-more') || '0');
                            list.setAttribute('data-latest-ts', nextList.getAttribute('data-latest-ts') || '');
                            list.setAttribute('data-filter', nextList.getAttribute('data-filter') || state.filter);
                            enhanceMedia(list);
                        }
                        syncFromList();
                        var top = list.getBoundingClientRect().top + window.pageYOffset - 90;
                        window.scrollTo({ top: top < 0 ? 0 : top, behavior: 'smooth' });
                    })
                    .catch(function() {})
                    .finally(function() {
                        state.loading = false;
                        newBtn.classList.remove('is-loading');
                        setLoading(false, 'dots');
                    });
            });
        }

        window.qfFeedSync = syncFromList;
        syncFromList();
    }

    function ensureTopLoadBar() {
        if (document.querySelector('.qf-topload')) return;
        var loader = document.createElement('div');
        loader.className = 'qf-topload';
        loader.setAttribute('aria-hidden', 'true');
        loader.innerHTML = '<div class="progress-container"><div class="progress-bar"></div><div class="particles"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div><div class="progress-text">0%</div></div>';
        document.body.appendChild(loader);
    }

    function ensurePageSpinner() {
        if (document.querySelector('.qf-loading-indicator')) return;
        var loader = document.createElement('div');
        loader.className = 'qf-loading-indicator';
        loader.setAttribute('aria-hidden', 'true');
        loader.innerHTML = PAGE_SPINNER_SVG;
        document.body.appendChild(loader);
    }

    function ensureFeedDots() {
        var tabs = document.querySelector('.phpdo-feed-tabs');
        if (!tabs) return null;
        var dots = tabs.querySelector('.phpdo-feed-loading');
        if (dots) return dots;
        dots = document.createElement('span');
        dots.className = 'phpdo-feed-loading';
        dots.setAttribute('aria-hidden', 'true');
        dots.innerHTML = FEED_DOTS_SVG;
        var rss = tabs.querySelector('.phpdo-rss');
        if (rss) tabs.insertBefore(dots, rss);
        else tabs.appendChild(dots);
        return dots;
    }

    function loadbarSet(pct) {
        var el = document.querySelector('.qf-topload');
        if (!el) return;
        pct = Math.max(0, Math.min(100, pct));
        var bar = el.querySelector('.progress-bar');
        var txt = el.querySelector('.progress-text');
        if (bar) bar.style.width = pct + '%';
        if (txt) txt.textContent = Math.round(pct) + '%';
    }

    var loadbarNow = (window.performance && performance.now)
        ? function() { return performance.now(); }
        : function() { return Date.now(); };

    // 逐帧补间到目标值：保证进度条与数字同步，且 1-100 每个整数都会被显示出来
    function loadbarAnimateTo(target, duration, cb) {
        var el = document.querySelector('.qf-topload');
        var bar = el ? el.querySelector('.progress-bar') : null;
        if (loadProgress.raf) { cancelAnimationFrame(loadProgress.raf); loadProgress.raf = null; }
        var startVal = loadProgress.value;
        var startT = loadbarNow();
        duration = Math.max(1, duration || 400);
        // 补间期间关闭 CSS width 过渡，改由 rAF 逐帧驱动，避免文字跳数、条与数字不同步
        if (bar) bar.style.transition = 'none';
        function step() {
            var t = Math.min(1, (loadbarNow() - startT) / duration);
            var eased = 1 - Math.pow(1 - t, 2); // easeOut：前段快、末段稍缓
            loadProgress.value = startVal + (target - startVal) * eased;
            loadbarSet(loadProgress.value);
            if (t < 1) {
                loadProgress.raf = requestAnimationFrame(step);
            } else {
                loadProgress.raf = null;
                loadProgress.value = target;
                loadbarSet(target);
                if (bar) bar.style.transition = '';
                if (cb) cb();
            }
        }
        loadProgress.raf = requestAnimationFrame(step);
    }

    function loadbarStart() {
        ensureTopLoadBar();
        if (loadProgress.raf) { cancelAnimationFrame(loadProgress.raf); loadProgress.raf = null; }
        // 从 1 起步，数值 1-100 全程可见
        loadProgress.value = 1;
        loadbarSet(1);
        if (loadProgress.timer) clearInterval(loadProgress.timer);
        // 真实加载期间平滑爬升（前快后慢，封顶 92%）：加载越久数字停留越久，直观反映“慢”
        loadProgress.timer = window.setInterval(function() {
            if (loadProgress.raf) return; // 正在补间到 100 时不再 trickle
            if (loadProgress.value < 92) {
                loadProgress.value = Math.min(92, loadProgress.value + (92 - loadProgress.value) * 0.06 + 0.35);
                loadbarSet(loadProgress.value);
            }
        }, 90);
    }

    function loadbarDone() {
        if (loadProgress.timer) { clearInterval(loadProgress.timer); loadProgress.timer = null; }
        // 平滑冲到 100 并逐个显示剩余数字；剩余越多补间越久 => 加载越快，1-100 跑得越完整、越连贯
        var remaining = Math.max(0, 100 - loadProgress.value);
        var dur = Math.max(260, remaining * 9);
        loadbarAnimateTo(100, dur, function() {
            window.setTimeout(function() {
                if (loadProgress.raf) { cancelAnimationFrame(loadProgress.raf); loadProgress.raf = null; }
                loadProgress.value = 0;
                loadbarSet(0);
            }, 300);
        });
    }

    function normalizeLoadingMode(mode) {
        if (mode === 'bar' || mode === 'dots' || mode === 'page') return mode;
        return 'page';
    }

    function setLoading(active, mode) {
        mode = normalizeLoadingMode(mode);
        var wasOn = loadingCounts[mode] > 0;
        loadingCounts[mode] += active ? 1 : -1;
        if (loadingCounts[mode] < 0) loadingCounts[mode] = 0;
        var on = loadingCounts[mode] > 0;

        if (mode === 'bar') {
            if (on && !wasOn) loadbarStart();
            else if (!on && wasOn) loadbarDone();
            document.body.classList.toggle('qf-is-bar-loading', on);
            return;
        }

        if (mode === 'dots') {
            ensureFeedDots();
            document.body.classList.toggle('qf-is-feed-loading', on);
            return;
        }

        if (on) ensurePageSpinner();
        document.body.classList.toggle('qf-is-loading', on);
    }

    function clearModeLoading(mode) {
        mode = normalizeLoadingMode(mode);
        loadingCounts[mode] = 0;
        if (mode === 'bar') {
            if (loadProgress.timer) { clearInterval(loadProgress.timer); loadProgress.timer = null; }
            if (loadProgress.raf) { cancelAnimationFrame(loadProgress.raf); loadProgress.raf = null; }
            loadProgress.value = 0;
            loadbarSet(0);
            document.body.classList.remove('qf-is-bar-loading');
            return;
        }
        if (mode === 'dots') {
            document.body.classList.remove('qf-is-feed-loading');
            return;
        }
        document.body.classList.remove('qf-is-loading', 'qf-ajax-loading');
    }

    function clearAllLoading() {
        clearModeLoading('bar');
        clearModeLoading('page');
        clearModeLoading('dots');
    }

    function isHomePage() {
        return document.body.classList.contains('page-index');
    }

    function getNavigationType() {
        try {
            var entries = performance.getEntriesByType && performance.getEntriesByType('navigation');
            if (entries && entries.length && entries[0].type) return entries[0].type;
        } catch (e) {}
        try {
            if (performance.navigation && typeof performance.navigation.type === 'number') {
                return performance.navigation.type;
            }
        } catch (e2) {}
        return 'navigate';
    }

    function shouldShowHomeBar(navType) {
        if (!isHomePage()) return false;
        // 强制刷新
        if (navType === 1 || navType === 'reload') return true;
        // 前进后退：不使用彩色条
        if (navType === 2 || navType === 'back_forward') return false;
        // 初次进入：无站内 referrer（直接打开 / 外链进入）；站内跳回首页算普通切换
        try {
            if (document.referrer) {
                var ref = new URL(document.referrer);
                if (ref.origin === location.origin) return false;
            }
        } catch (err) {}
        return true;
    }

    function finishHomeBarSoon() {
        function finish() {
            setLoading(false, 'bar');
            window.removeEventListener('load', finish);
        }
        if (document.readyState === 'complete') {
            window.setTimeout(finish, 420);
        } else {
            window.addEventListener('load', finish);
            window.setTimeout(finish, 8000);
        }
    }

    function initHomeBarLoading() {
        if (window.__qfHomeBarEarly) {
            // header 已提前点亮彩色条，这里接管进度动画并在 load 后收起
            loadingCounts.bar = Math.max(1, loadingCounts.bar);
            document.body.classList.add('qf-is-bar-loading');
            loadbarStart();
            finishHomeBarSoon();
            window.__qfHomeBarEarly = false;
            return;
        }
        if (!shouldShowHomeBar(getNavigationType())) return;
        setLoading(true, 'bar');
        finishHomeBarSoon();
    }

    function initFormLoading() {
        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (e.defaultPrevented) return;
            if (!form || form.hasAttribute('data-no-global-loading')) return;
            if (form.closest('.auth-modal-box')) return;
            setLoading(true, 'page');
        });

        window.addEventListener('pageshow', function() {
            // 清掉可能卡住的页面/tab 加载态；首页彩色条由 initHomeBarLoading 单独管理
            clearModeLoading('page');
            clearModeLoading('dots');
        });
    }

    function toast(message, type) {
        message = String(message || '').trim();
        if (!message) return;
        var isError = type === 'error';

        var stack = document.querySelector('.qf-toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'qf-toast-stack';
            stack.setAttribute('aria-live', 'polite');
            stack.setAttribute('aria-atomic', 'true');
            document.body.appendChild(stack);
        }

        var item = document.createElement('div');
        item.className = 'qf-toast qf-toast-' + (isError ? 'error' : 'success');
        item.setAttribute('role', isError ? 'alert' : 'status');
        item.innerHTML = (isError
            ? '<i class="fa-solid fa-triangle-exclamation"></i>'
            : '<i class="fa-solid fa-circle-check" aria-hidden="true"></i>')
            + '<span class="qf-toast-message"></span>';
        item.querySelector('.qf-toast-message').textContent = message;
        stack.appendChild(item);

        var close = function() {
            if (item.classList.contains('is-leaving')) return;
            item.classList.add('is-leaving');
            window.setTimeout(function() {
                if (item.parentNode) item.parentNode.removeChild(item);
            }, 180);
        };
        window.setTimeout(close, 3600);
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

        function setState() {
            var y = window.pageYOffset || document.documentElement.scrollTop || 0;
            var docH = (document.documentElement.scrollHeight || 0) - window.innerHeight;
            // 滚过半页才显示回到顶部：长页面按滚动进度过半，短页面按滚过半屏
            var pastHalf = docH > 120 ? (y / docH) > 0.5 : (y > window.innerHeight * 0.5);
            toolbar.classList.toggle('is-scrolled', pastHalf);
        }

        if (topButton) {
            topButton.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        window.addEventListener('scroll', setState, { passive: true });
        window.addEventListener('resize', setState, { passive: true });
        setState();
    }

    function initPrivateMessages() {
        var root = document.querySelector('[data-pm-root]');
        if (!root) return;
        var stream = root.querySelector('[data-pm-stream]');
        var form = root.querySelector('[data-pm-form]');
        var input = form ? form.querySelector('[data-pm-input]') : null;
        var apiUrl = form ? (form.getAttribute('data-pm-api') || '') : '';
        var uid = parseInt(window.qfCurrentUserId || '0', 10) || 0;

        function scrollStreamToBottom() {
            if (!stream) return;
            stream.scrollTop = stream.scrollHeight;
        }

        function appendMessage(msg) {
            if (!stream || !msg) return;
            var empty = stream.querySelector('.phpdo-messages-empty-chat');
            if (empty) empty.remove();
            var row = document.createElement('article');
            row.className = 'phpdo-msg-row is-mine';
            row.setAttribute('data-message-id', String(msg.id || ''));
            var bubble = document.createElement('div');
            bubble.className = 'phpdo-msg-bubble';
            var body = document.createElement('p');
            body.textContent = msg.body || '';
            var time = document.createElement('time');
            if (msg.time_html) {
                time.innerHTML = msg.time_html;
            } else {
                time.textContent = '刚刚';
            }
            bubble.appendChild(body);
            bubble.appendChild(time);
            row.appendChild(bubble);
            stream.appendChild(row);
            scrollStreamToBottom();
        }

        scrollStreamToBottom();

        if (input) {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (form) form.requestSubmit();
                }
            });
        }

        if (!form || !apiUrl) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var body = input ? String(input.value || '').trim() : '';
            if (!body) return;
            var data = new FormData(form);
            data.set('body', body);
            if (!data.get('csrf_token')) data.append('csrf_token', window.qfCsrfToken || '');
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            fetch(apiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            }).then(function(res) {
                return res.json().then(function(json) {
                    if (!res.ok || !json.ok) throw new Error(json.error || '发送失败。');
                    return json;
                });
            }).then(function(json) {
                if (input) input.value = '';
                if (json.message) appendMessage(json.message);
            }).catch(function(err) {
                toast(err.message || '发送失败。', 'error');
            }).finally(function() {
                if (submitBtn) submitBtn.disabled = false;
                if (input) input.focus();
            });
        });
    }

    function initRssCopy() {
        function fallbackCopy(text) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.top = '-1000px';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
        }
        document.addEventListener('click', function(e) {
            var btn = e.target.closest ? e.target.closest('[data-rss-copy]') : null;
            if (!btn) return;
            e.preventDefault();
            var url = btn.getAttribute('data-rss-url') || '';
            if (!url) return;
            var done = function() {
                if (window.qfToast) window.qfToast('复制 RSS 成功');
                btn.classList.add('is-copied');
                window.clearTimeout(btn._rssCopiedTimer);
                btn._rssCopiedTimer = window.setTimeout(function() {
                    btn.classList.remove('is-copied');
                }, 2000);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(done, function() { fallbackCopy(url); done(); });
            } else {
                fallbackCopy(url);
                done();
            }
        });
    }

    function initReactions() {
        var box = document.querySelector('[data-reactions]');
        if (!box) return;
        var loggedIn = box.getAttribute('data-logged-in') === '1';
        var loginUrl = box.getAttribute('data-login-url') || '';
        var threadId = box.getAttribute('data-thread-id') || '';
        box.addEventListener('click', function(e) {
            var btn = e.target.closest ? e.target.closest('.phpdo-reaction') : null;
            if (!btn || !box.contains(btn)) return;
            if (!loggedIn) {
                if (loginUrl) window.location.href = loginUrl;
                return;
            }
            var reaction = btn.getAttribute('data-reaction') || '';
            if (!reaction || btn.disabled) return;
            var buttons = box.querySelectorAll('.phpdo-reaction');
            var i;
            for (i = 0; i < buttons.length; i++) buttons[i].disabled = true;
            var data = new FormData();
            data.append('thread_id', threadId);
            data.append('reaction', reaction);
            data.append('csrf_token', window.qfCsrfToken || '');
            fetch('api/react.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            }).then(function(res) {
                return res.json().then(function(json) {
                    if (!res.ok || !json.ok) throw new Error(json.error || '操作失败。');
                    return json;
                });
            }).then(function(json) {
                var b, key, active;
                for (b = 0; b < buttons.length; b++) {
                    key = buttons[b].getAttribute('data-reaction');
                    active = json.active === key;
                    buttons[b].classList.toggle('is-active', active);
                    buttons[b].setAttribute('aria-pressed', active ? 'true' : 'false');
                }
                if (json.counts) {
                    Object.keys(json.counts).forEach(function(k) {
                        var el = box.querySelector('[data-reaction-count="' + k + '"]');
                        if (el) {
                            var n = parseInt(json.counts[k], 10) || 0;
                            el.textContent = n > 0 ? n : '';
                        }
                    });
                }
                if (json.active) {
                    var activeBtn = box.querySelector('[data-reaction="' + json.active + '"]') || btn;
                    qfConfettiBurst(activeBtn, { theme: json.active });
                    qfReactionPop(activeBtn);
                }
            }).catch(function(err) {
                toast(err.message || '操作失败。', 'error');
            }).finally(function() {
                var j;
                for (j = 0; j < buttons.length; j++) buttons[j].disabled = false;
            });
        });
    }

    window.qfEnhanceMedia = enhanceMedia;
    window.qfSetLoading = setLoading;
    initNavMore();
    initPasskeys();
    initSearchModal();
    initThreadVotes();
    initSigninModal();
    initInlineActions();
    initThreadAdminAjax();
    initIpGeo();
    initRightToolbar();
    initPrivateMessages();
    initFormLoading();
    initHomeBarLoading();
    initToast();
    enhanceMedia(document);
    ensureFeedDots();
    initAjaxFilters();
    initFeedStream();
    initRssCopy();
    initReactions();

    requestAnimationFrame(function() {
        document.body.classList.add('qf-page-ready');
    });
})();
