(function() {
    var root = document.querySelector('[data-storage-tabs]');
    if (!root) return;

    var buttons = root.querySelectorAll('[data-storage-tab]');
    var panels = root.querySelectorAll('[data-storage-panel]');

    function setActive(name) {
        for (var i = 0; i < buttons.length; i++) {
            var active = buttons[i].getAttribute('data-storage-tab') === name;
            buttons[i].classList.toggle('active', active);
            buttons[i].setAttribute('aria-selected', active ? 'true' : 'false');
        }

        for (var j = 0; j < panels.length; j++) {
            panels[j].classList.toggle('active', panels[j].getAttribute('data-storage-panel') === name);
        }
    }

    root.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-storage-tab]');
        if (btn) setActive(btn.getAttribute('data-storage-tab'));
    });
})();
