(function () {
    var sidebar = document.getElementById('admin-sidebar');
    var backdrop = document.querySelector('[data-admin-backdrop]');
    var toggle = document.querySelector('[data-admin-menu]');

    function setOpen(open) {
        if (!sidebar) return;
        sidebar.classList.toggle('is-open', open);
        if (backdrop) backdrop.hidden = !open;
        document.body.classList.toggle('admin-sidebar-open', open);
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            setOpen(!sidebar.classList.contains('is-open'));
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function () { setOpen(false); });
    }

    function adminConfirm(message) {
        return new Promise(function (resolve) {
            var box = document.getElementById('admin-confirm');
            if (!box) {
                resolve(window.confirm(message));
                return;
            }
            var msg = box.querySelector('[data-admin-confirm-msg]');
            var ok = box.querySelector('[data-admin-confirm-ok]');
            var cancel = box.querySelector('[data-admin-confirm-cancel]');
            function finish(result) {
                box.hidden = true;
                ok.removeEventListener('click', onOk);
                cancel.removeEventListener('click', onCancel);
                document.removeEventListener('keydown', onKey);
                resolve(result);
            }
            function onOk() { finish(true); }
            function onCancel() { finish(false); }
            function onKey(e) {
                if (e.key === 'Escape') finish(false);
                if (e.key === 'Enter') finish(true);
            }
            if (msg) msg.textContent = message || '确定继续？';
            box.hidden = false;
            ok.addEventListener('click', onOk);
            cancel.addEventListener('click', onCancel);
            document.addEventListener('keydown', onKey);
            ok.focus();
        });
    }

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-confirm]');
        if (!el) return;
        var message = el.getAttribute('data-confirm') || '确定继续？';
        if (el.tagName === 'A') {
            e.preventDefault();
            adminConfirm(message).then(function (ok) {
                if (ok) window.location.href = el.href;
            });
            return;
        }
        if (el.tagName === 'BUTTON' || (el.tagName === 'INPUT' && el.type === 'submit')) {
            var form = el.form || el.closest('form');
            if (!form) return;
            e.preventDefault();
            adminConfirm(message).then(function (ok) {
                if (!ok) return;
                HTMLFormElement.prototype.submit.call(form);
            });
        }
    });
})();
