(function() {
    function insertText(textarea, text) {
        if (!textarea) return;
        var start = textarea.selectionStart || textarea.value.length;
        var end = textarea.selectionEnd || textarea.value.length;
        textarea.value = textarea.value.substring(0, start) + text + textarea.value.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + text.length;
        textarea.focus();
    }

    function insertAroundSelection(textarea, open, close, selectedOverride) {
        var start = textarea.selectionStart || 0;
        var end = textarea.selectionEnd || 0;
        var before = textarea.value.substring(0, start);
        var selected = selectedOverride !== undefined ? selectedOverride : textarea.value.substring(start, end);
        var after = textarea.value.substring(end);
        textarea.value = before + open + selected + close + after;
        textarea.selectionStart = start + open.length;
        textarea.selectionEnd = start + open.length + selected.length;
        textarea.focus();
    }

    function notify(message, type) {
        if (window.qfToast) {
            window.qfToast(message, type || 'info');
        }
    }

    function findEditorTextarea(toolbar) {
        var node = toolbar ? toolbar.nextElementSibling : null;
        while (node && node.tagName !== 'TEXTAREA') {
            node = node.nextElementSibling;
        }
        return node;
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

    function ensureEditorDialog() {
        var existing = document.getElementById('qf-editor-dialog');
        if (existing) return existing;

        var modal = document.createElement('div');
        modal.id = 'qf-editor-dialog';
        modal.className = 'editor-dialog-overlay';
        modal.innerHTML = [
            '<div class="editor-dialog-box" role="dialog" aria-modal="true" aria-labelledby="qf-editor-dialog-title">',
            '<button class="editor-dialog-close" type="button" data-editor-dialog-close aria-label="关闭">×</button>',
            '<h2 id="qf-editor-dialog-title"></h2>',
            '<form data-editor-dialog-form>',
            '<label data-url-label></label>',
            '<input type="url" name="url" placeholder="https://example.com" required>',
            '<div data-text-field>',
            '<label>显示文字</label>',
            '<input type="text" name="text" placeholder="链接显示文字">',
            '</div>',
            '<div class="editor-dialog-actions">',
            '<button class="btn btn-light" type="button" data-editor-dialog-close>取消</button>',
            '<button class="btn" type="submit">插入</button>',
            '</div>',
            '</form>',
            '</div>'
        ].join('');
        document.body.appendChild(modal);
        return modal;
    }

    function openEditorDialog(type, textarea, selected) {
        var modal = ensureEditorDialog();
        var title = modal.querySelector('#qf-editor-dialog-title');
        var form = modal.querySelector('[data-editor-dialog-form]');
        var urlInput = form.elements.url;
        var textInput = form.elements.text;
        var textField = modal.querySelector('[data-text-field]');
        var urlLabel = modal.querySelector('[data-url-label]');

        title.textContent = type === 'image' ? '插入远程图片' : '插入超链接';
        urlLabel.textContent = type === 'image' ? '图片地址' : '链接地址';
        textField.style.display = type === 'image' ? 'none' : '';
        urlInput.value = '';
        textInput.value = selected || '';
        modal.classList.add('is-open');
        setTimeout(function() { urlInput.focus(); }, 40);

        function close() {
            modal.classList.remove('is-open');
            form.removeEventListener('submit', submit);
            modal.removeEventListener('click', clickClose);
            document.removeEventListener('keydown', keyClose);
        }

        function clickClose(e) {
            if (e.target === modal || e.target.closest('[data-editor-dialog-close]')) close();
        }

        function keyClose(e) {
            if (e.key === 'Escape') close();
        }

        function submit(e) {
            e.preventDefault();
            var url = urlInput.value.trim();
            if (!/^https?:\/\//i.test(url)) {
                urlInput.focus();
                notify('请输入 http:// 或 https:// 开头的地址', 'error');
                return;
            }
            if (type === 'image') {
                insertText(textarea, "\n[img]" + url + "[/img]\n");
            } else {
                var text = textInput.value.trim() || selected || url;
                insertAroundSelection(textarea, '[url=' + url + ']', '[/url]', text);
            }
            close();
        }

        form.addEventListener('submit', submit);
        modal.addEventListener('click', clickClose);
        document.addEventListener('keydown', keyClose);
    }

    function initEditorToolbar() {
        document.querySelectorAll('.editor-toolbar button').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!btn.hasAttribute('data-wrap') && !btn.hasAttribute('data-link') && !btn.hasAttribute('data-remote-img')) return;
                var textarea = findEditorTextarea(btn.parentNode);
                if (!textarea) return;
                var start = textarea.selectionStart || 0;
                var end = textarea.selectionEnd || 0;
                var selected = textarea.value.substring(start, end);
                if (btn.getAttribute('data-remote-img') === '1') {
                    openEditorDialog('image', textarea, selected);
                    return;
                }
                if (btn.getAttribute('data-link') === '1') {
                    openEditorDialog('link', textarea, selected);
                    return;
                }
                insertAroundSelection(textarea, btn.getAttribute('data-wrap') || '', btn.getAttribute('data-close') || '');
            });
        });
    }

    function toggleUploadTip(btn) {
        var root = btn.closest('form') || btn.parentNode;
        var tip = root.querySelector('.upload-tip');
        if (tip) tip.style.display = tip.style.display === 'block' ? 'none' : 'block';
    }

    function initUploadTips() {
        document.querySelectorAll('.upload-help').forEach(function(btn) {
            btn.addEventListener('click', function() {
                toggleUploadTip(btn);
            });
        });
    }

    function ensureUploadProgress() {
        var panel = document.querySelector('.qf-upload-progress');
        if (panel) return panel;
        panel = document.createElement('div');
        panel.className = 'qf-upload-progress';
        panel.innerHTML = '<div class="qf-upload-progress-title"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i><span>上传中</span></div><div class="qf-upload-progress-name"></div><div class="qf-upload-progress-track"><span></span></div><div class="qf-upload-progress-text">0%</div>';
        document.body.appendChild(panel);
        return panel;
    }

    function setUploadProgress(fileName, percent, state) {
        var panel = ensureUploadProgress();
        panel.classList.add('is-visible');
        panel.classList.toggle('is-error', state === 'error');
        panel.classList.toggle('is-done', state === 'done');
        panel.querySelector('.qf-upload-progress-name').textContent = fileName || '';
        panel.querySelector('.qf-upload-progress-track span').style.width = Math.max(0, Math.min(100, percent || 0)) + '%';
        panel.querySelector('.qf-upload-progress-text').textContent = state === 'done' ? '完成' : (state === 'error' ? '失败' : Math.round(percent || 0) + '%');
        if (state === 'done' || state === 'error') {
            window.setTimeout(function() {
                panel.classList.remove('is-visible', 'is-error', 'is-done');
            }, state === 'done' ? 1200 : 2200);
        }
    }

    function requestAttachmentDescription(callback) {
        var modal = document.getElementById('qf-attachment-dialog');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'qf-attachment-dialog';
            modal.className = 'editor-dialog-overlay';
            modal.innerHTML = '<div class="editor-dialog-box" role="dialog" aria-modal="true"><button class="editor-dialog-close" type="button" data-attachment-close aria-label="关闭">×</button><h2>附件描述</h2><form data-attachment-form><label>描述</label><input type="text" name="description" maxlength="120" placeholder="可留空"><div class="editor-dialog-actions"><button class="btn btn-light" type="button" data-attachment-close>取消</button><button class="btn" type="submit">继续上传</button></div></form></div>';
            document.body.appendChild(modal);
        }
        var form = modal.querySelector('[data-attachment-form]');
        var input = form.elements.description;
        input.value = '';
        modal.classList.add('is-open');
        setTimeout(function() { input.focus(); }, 40);

        function close(value, hasValue) {
            modal.classList.remove('is-open');
            form.removeEventListener('submit', submit);
            modal.removeEventListener('click', clickClose);
            document.removeEventListener('keydown', keyClose);
            if (hasValue) callback(value);
        }

        function submit(e) {
            e.preventDefault();
            close(input.value.trim(), true);
        }

        function clickClose(e) {
            if (e.target === modal || e.target.closest('[data-attachment-close]')) close('', false);
        }

        function keyClose(e) {
            if (e.key === 'Escape') close('', false);
        }

        form.addEventListener('submit', submit);
        modal.addEventListener('click', clickClose);
        document.addEventListener('keydown', keyClose);
    }

    function xhrUpload(url, data, file, onSuccess, onError) {
        var xhr = new XMLHttpRequest();
        setUploadProgress(file.name, 2, 'uploading');
        xhr.open('POST', url, true);
        xhr.withCredentials = true;
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) setUploadProgress(file.name, (e.loaded / e.total) * 100, 'uploading');
        };
        xhr.onload = function() {
            var json = null;
            try {
                json = JSON.parse(xhr.responseText || '{}');
            } catch (e) {}
            if (xhr.status >= 200 && xhr.status < 300 && json && json.ok) {
                setUploadProgress(file.name, 100, 'done');
                onSuccess(json);
            } else {
                setUploadProgress(file.name, 100, 'error');
                onError((json && json.error) || '上传失败。');
            }
        };
        xhr.onerror = function() {
            setUploadProgress(file.name, 100, 'error');
            onError('上传失败，请稍后再试。');
        };
        xhr.send(data);
    }

    function uploadAttachment(file, textarea, status) {
        requestAttachmentDescription(function(attachmentDescription) {
            var attachmentData = new FormData();
            attachmentData.append('csrf_token', window.qfCsrfToken || '');
            attachmentData.append('attachment', file);
            attachmentData.append('attachment_description', attachmentDescription);
            if (status) status.textContent = '正在上传附件...';
            if (window.qfSetLoading) window.qfSetLoading(true);
            xhrUpload('api/upload-attachment', attachmentData, file, function(json) {
                insertText(textarea, "\n" + json.tag + "\n");
                if (status) status.textContent = '附件上传成功，已插入编辑框。';
                notify('附件上传成功，已插入编辑框。', 'success');
                if (window.qfSetLoading) window.qfSetLoading(false);
            }, function(error) {
                if (status) status.textContent = error;
                notify(error, 'error');
                if (window.qfSetLoading) window.qfSetLoading(false);
            });
        });
    }

    function uploadImage(file, textarea, status) {
        var data = new FormData();
        data.append('csrf_token', window.qfCsrfToken || '');
        data.append('image', file);
        if (status) status.textContent = '正在上传图片...';
        if (window.qfSetLoading) window.qfSetLoading(true);
        xhrUpload('api/upload-image', data, file, function(json) {
            insertText(textarea, "\n[img]" + json.url + "[/img]\n");
            if (status) status.textContent = '图片上传成功，已插入编辑框。';
            notify('图片上传成功，已插入编辑框。', 'success');
            if (window.qfSetLoading) window.qfSetLoading(false);
        }, function(error) {
            if (status) status.textContent = error;
            notify(error, 'error');
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
                Array.prototype.slice.call(input.files || []).forEach(function(file) {
                    var ext = (file.name.split('.').pop() || '').toLowerCase();
                    if (imageExts.indexOf(ext) !== -1) {
                        uploadImage(file, textarea, status);
                    } else if (attachmentExts.indexOf(ext) !== -1) {
                        uploadAttachment(file, textarea, status);
                    } else {
                        if (status) status.textContent = '附件/图片上传失败，格式不支持。';
                        setUploadProgress(file.name, 100, 'error');
                        notify('附件/图片上传失败，格式不支持。', 'error');
                    }
                });
                input.value = '';
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
