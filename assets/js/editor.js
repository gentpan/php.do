(function() {
    function insertText(textarea, text) {
        if (!textarea) return;
        var start = textarea.selectionStart || textarea.value.length;
        var end = textarea.selectionEnd || textarea.value.length;
        textarea.value = textarea.value.substring(0, start) + text + textarea.value.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + text.length;
        textarea.focus();
    }

    function initForumCategories() {
        var categories = window.qfForumCategories || null;
        var forumSelect = document.querySelector('select[name="forum_id"]');
        var categoryBox = document.getElementById('topic-category-box');
        var categorySelect = document.getElementById('topic-category-select');
        if (!categories || !forumSelect || !categoryBox || !categorySelect) return;

        function refreshCategories() {
            var cats = categories[forumSelect.value] || [];
            categorySelect.innerHTML = '<option value="">不选择分类</option>';
            for (var i = 0; i < cats.length; i++) {
                var option = document.createElement('option');
                option.value = cats[i];
                option.textContent = cats[i];
                categorySelect.appendChild(option);
            }
            categoryBox.style.display = cats.length ? '' : 'none';
        }

        forumSelect.addEventListener('change', refreshCategories);
        refreshCategories();
    }

    function initEditorToolbar() {
        document.querySelectorAll('.editor-toolbar button').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var textarea = this.parentNode.nextElementSibling;
                if (!textarea) return;

                var start = textarea.selectionStart || 0;
                var end = textarea.selectionEnd || 0;
                var before = textarea.value.substring(0, start);
                var selected = textarea.value.substring(start, end);
                var after = textarea.value.substring(end);
                var open = this.getAttribute('data-wrap') || '';
                var close = this.getAttribute('data-close') || '';

                if (this.getAttribute('data-remote-img') === '1') {
                    var url = prompt('请输入远程图片地址，以 http:// 或 https:// 开头');
                    if (!url) return;
                    open = '[img]' + url + '[/img]';
                    close = '';
                }

                if (this.getAttribute('data-link') === '1') {
                    var linkUrl = prompt('请输入链接地址，以 http:// 或 https:// 开头');
                    if (!linkUrl) return;
                    var linkText = selected || prompt('请输入链接显示文字', linkUrl) || linkUrl;
                    open = '[url=' + linkUrl + ']';
                    close = '[/url]';
                    selected = linkText;
                }

                textarea.value = before + open + selected + close + after;
                textarea.focus();
            });
        });
    }

    function toggleUploadTip(btn) {
        var tip = btn.parentNode.querySelector('.upload-tip');
        if (tip) tip.style.display = tip.style.display === 'block' ? 'none' : 'block';
    }

    function initUploadTips() {
        document.querySelectorAll('.upload-help').forEach(function(btn) {
            btn.addEventListener('click', function() {
                toggleUploadTip(btn);
            });
        });
    }

    function uploadAttachment(file, textarea, status) {
        var attachmentDescription = prompt('请输入附件描述（可留空）：', '');
        if (attachmentDescription === null) attachmentDescription = '';

        var attachmentData = new FormData();
        attachmentData.append('csrf_token', window.qfCsrfToken || '');
        attachmentData.append('attachment', file);
        attachmentData.append('attachment_description', attachmentDescription);
        if (status) status.textContent = '正在上传附件...';
        if (window.qfSetLoading) window.qfSetLoading(true);

        fetch('api/upload-attachment', { method: 'POST', body: attachmentData, credentials: 'same-origin' })
            .then(function(res) {
                return res.json();
            })
            .then(function(json) {
                if (json.ok) {
                    insertText(textarea, "\n" + json.tag + "\n");
                    if (status) status.textContent = '附件上传成功，已插入编辑框。';
                } else {
                    if (status) status.textContent = json.error || '附件上传失败。';
                    alert(json.error || '附件上传失败。');
                }
            })
            .catch(function() {
                if (status) status.textContent = '附件上传失败，请稍后再试。';
                alert('附件上传失败，请稍后再试。');
            })
            .finally(function() {
                if (window.qfSetLoading) window.qfSetLoading(false);
            });
    }

    function uploadImage(file, textarea, status) {
        var data = new FormData();
        data.append('csrf_token', window.qfCsrfToken || '');
        data.append('image', file);
        if (status) status.textContent = '正在上传图片...';
        if (window.qfSetLoading) window.qfSetLoading(true);

        fetch('api/upload-image', { method: 'POST', body: data, credentials: 'same-origin' })
            .then(function(res) {
                return res.json();
            })
            .then(function(json) {
                if (json.ok) {
                    insertText(textarea, "\n[img]" + json.url + "[/img]\n");
                    if (status) status.textContent = '图片上传成功，已插入编辑框。';
                } else {
                    if (status) status.textContent = json.error || '图片上传失败。';
                    alert(json.error || '图片上传失败。');
                }
            })
            .catch(function() {
                if (status) status.textContent = '图片上传失败，请稍后再试。';
                alert('图片上传失败，请稍后再试。');
            })
            .finally(function() {
                if (window.qfSetLoading) window.qfSetLoading(false);
            });
    }

    function initInstantUpload() {
        var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        var attachmentExts = ['zip', 'rar'];

        document.querySelectorAll('.qf-instant-upload').forEach(function(input) {
            input.addEventListener('change', function() {
                var textarea = document.getElementById(input.getAttribute('data-target'));
                var status = document.getElementById(input.getAttribute('data-status'));
                var kept = new DataTransfer();

                Array.prototype.slice.call(input.files || []).forEach(function(file) {
                    var ext = (file.name.split('.').pop() || '').toLowerCase();
                    if (imageExts.indexOf(ext) !== -1) {
                        uploadImage(file, textarea, status);
                        return;
                    }

                    if (attachmentExts.indexOf(ext) !== -1) {
                        uploadAttachment(file, textarea, status);
                        return;
                    }

                    if (status) status.textContent = '附件/图片上传失败，格式不支持。';
                    alert('附件/图片上传失败，格式不支持。');
                });

                input.files = kept.files;
            });
        });
    }

    function initFloorReply() {
        document.querySelectorAll('.floor-reply-toggle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var form = document.getElementById(btn.getAttribute('data-reply-target'));
                if (!form) return;
                form.style.display = form.style.display === 'none' ? 'flex' : 'none';
                var input = form.querySelector('input[name="content"]');
                if (form.style.display !== 'none' && input) input.focus();
            });
        });
    }

    window.qfToggleUploadTip = toggleUploadTip;
    window.qfInsertText = insertText;

    initForumCategories();
    initEditorToolbar();
    initUploadTips();
    initInstantUpload();
    initFloorReply();
})();
