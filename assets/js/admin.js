(function() {
    function insertText(textarea, text) {
        if (!textarea) return;
        var start = textarea.selectionStart || textarea.value.length;
        var end = textarea.selectionEnd || textarea.value.length;
        textarea.value = textarea.value.substring(0, start) + text + textarea.value.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + text.length;
        textarea.focus();
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function notify(message, type) {
        if (window.pdToast) {
            window.pdToast(message, type || 'info');
        }
    }

    function initForumCategories() {
        var categories = window.pdForumCategories || null;
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

    function ensureUploadProgress() {
        var panel = document.querySelector('.pd-upload-progress');
        if (panel) return panel;
        panel = document.createElement('div');
        panel.className = 'pd-upload-progress';
        panel.innerHTML = '<div class="pd-upload-progress-title"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i><span>上传中</span></div><div class="pd-upload-progress-name"></div><div class="pd-upload-progress-track"><span></span></div><div class="pd-upload-progress-text">0%</div>';
        document.body.appendChild(panel);
        return panel;
    }

    function setUploadProgress(fileName, percent, state) {
        var panel = ensureUploadProgress();
        panel.classList.add('is-visible');
        panel.classList.toggle('is-error', state === 'error');
        panel.classList.toggle('is-done', state === 'done');
        panel.querySelector('.pd-upload-progress-name').textContent = fileName || '';
        panel.querySelector('.pd-upload-progress-track span').style.width = Math.max(0, Math.min(100, percent || 0)) + '%';
        panel.querySelector('.pd-upload-progress-text').textContent = state === 'done' ? '完成' : (state === 'error' ? '失败' : Math.round(percent || 0) + '%');
        if (state === 'done' || state === 'error') {
            window.setTimeout(function() {
                panel.classList.remove('is-visible', 'is-error', 'is-done');
            }, state === 'done' ? 1200 : 2200);
        }
    }

    function requestAttachmentDescription(callback) {
        var modal = document.getElementById('pd-attachment-dialog');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'pd-attachment-dialog';
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

    function initMarkdownEditor(root) {
        var textarea = root.querySelector('[data-editor-textarea]') || root.querySelector('textarea');
        if (!textarea) return;

        var toolbarWords = root.querySelector('[data-editor-words-toolbar]');
        var paragraphs = root.querySelector('[data-editor-paragraphs]');
        var previewBtn = root.querySelector('[data-md-preview]');
        var filePicker = root.querySelector('[data-md-file-picker]');
        var imagePicker = root.querySelector('[data-md-image-picker]');
        var attachPicker = root.querySelector('[data-md-attach-picker]');
        var previewUrl = root.getAttribute('data-preview-url') || '';
        var uploadUrl = root.getAttribute('data-upload-url') || 'api/upload-image';
        var attachUrl = root.getAttribute('data-attach-url') || 'api/upload-attachment';
        var csrf = root.getAttribute('data-csrf') || window.pdCsrfToken || '';

        function insert(before, after, placeholder) {
            var start = textarea.selectionStart || 0;
            var end = textarea.selectionEnd || 0;
            var selected = textarea.value.slice(start, end) || placeholder;
            var text = before + selected + after;
            if (typeof textarea.setRangeText === 'function') {
                textarea.setRangeText(text, start, end, 'end');
            } else {
                textarea.value = textarea.value.slice(0, start) + text + textarea.value.slice(end);
                textarea.selectionStart = textarea.selectionEnd = start + text.length;
            }
            textarea.focus();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function insertLine(prefix, placeholder) {
            var start = textarea.selectionStart || 0;
            var end = textarea.selectionEnd || 0;
            var selected = textarea.value.slice(start, end) || placeholder;
            var lined = selected.split('\n').map(function(line) {
                return prefix + line;
            }).join('\n');
            if (typeof textarea.setRangeText === 'function') {
                textarea.setRangeText(lined, start, end, 'end');
            } else {
                textarea.value = textarea.value.slice(0, start) + lined + textarea.value.slice(end);
            }
            textarea.focus();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function command(type) {
            var table = '| 列一 | 列二 |\n| --- | --- |\n| 内容 | 内容 |';
            switch (type) {
                case 'bold': insert('**', '**', '加粗文字'); break;
                case 'italic': insert('*', '*', '斜体文字'); break;
                case 'quote': insertLine('> ', '引用内容'); break;
                case 'code': insert('```\n', '\n```', 'code'); break;
                case 'link': insert('[', '](https://example.com)', '链接文字'); break;
                case 'image': insert('![', '](https://example.com/image.jpg)', '图片描述'); break;
                case 'image-upload':
                    if (imagePicker) imagePicker.click();
                    break;
                case 'attach-upload':
                    if (attachPicker) attachPicker.click();
                    break;
                case 'ul': insertLine('- ', '列表项'); break;
                case 'ol': insertLine('1. ', '列表项'); break;
                case 'table': insert('\n', '\n', table); break;
            }
        }

        function insertHeading(level) {
            level = Math.max(1, Math.min(5, parseInt(level, 10) || 2));
            insertLine(new Array(level + 1).join('#') + ' ', '小标题');
        }

        function closeHeadingMenus(except) {
            root.querySelectorAll('[data-heading-menu]').forEach(function(menu) {
                if (except && menu === except) return;
                var dropdown = menu.querySelector('[data-heading-dropdown]');
                var toggle = menu.querySelector('[data-heading-toggle]');
                if (dropdown) dropdown.hidden = true;
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            });
        }

        root.querySelectorAll('[data-heading-menu]').forEach(function(menu) {
            var toggle = menu.querySelector('[data-heading-toggle]');
            var dropdown = menu.querySelector('[data-heading-dropdown]');
            if (!toggle || !dropdown) return;

            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                var nextOpen = dropdown.hidden;
                closeHeadingMenus(menu);
                dropdown.hidden = !nextOpen;
                toggle.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
            });

            dropdown.querySelectorAll('[data-md-heading]').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    insertHeading(btn.getAttribute('data-md-heading') || '2');
                    dropdown.hidden = true;
                    toggle.setAttribute('aria-expanded', 'false');
                });
            });
        });

        document.addEventListener('click', function(e) {
            if (!root.contains(e.target)) {
                closeHeadingMenus();
                return;
            }
            if (!e.target.closest || !e.target.closest('[data-heading-menu]')) {
                closeHeadingMenus();
            }
        });

        function updateStats() {
            var value = textarea.value;
            var plain = value.replace(/[#>*_`\-\[\]()!|]/g, '').trim();
            var count = plain ? plain.length : 0;
            var paragraphCount = value.trim() ? value.trim().split(/\n\s*\n/).filter(function(block) {
                return block.trim() !== '';
            }).length : 0;
            if (toolbarWords) toolbarWords.textContent = count + ' 字';
            if (paragraphs) paragraphs.textContent = paragraphCount + ' 段';
        }

        function ensurePreviewModal() {
            var modal = document.getElementById('pd-md-preview-dialog');
            if (modal) return modal;
            modal = document.createElement('div');
            modal.id = 'pd-md-preview-dialog';
            modal.className = 'editor-dialog-overlay md-preview-overlay';
            modal.innerHTML = [
                '<div class="editor-dialog-box md-preview-box" role="dialog" aria-modal="true" aria-labelledby="pd-md-preview-title">',
                '<button class="editor-dialog-close" type="button" data-md-preview-close aria-label="关闭">×</button>',
                '<div class="md-preview-head">',
                '<h2 id="pd-md-preview-title">预览</h2>',
                '<span class="md-preview-badge"><i class="fa-brands fa-markdown" aria-hidden="true"></i> Markdown</span>',
                '</div>',
                '<div class="md-preview-body pd-md-body" data-md-preview-body><div class="empty">加载中…</div></div>',
                '<div class="editor-dialog-actions">',
                '<button class="btn btn-light" type="button" data-md-preview-close>关闭</button>',
                '</div>',
                '</div>'
            ].join('');
            document.body.appendChild(modal);
            return modal;
        }

        function openPreviewModal() {
            if (!previewUrl) {
                notify('预览接口不可用', 'error');
                return;
            }
            var modal = ensurePreviewModal();
            var body = modal.querySelector('[data-md-preview-body]');
            body.innerHTML = '<div class="empty">加载中…</div>';
            modal.classList.add('is-open');

            function close() {
                modal.classList.remove('is-open');
                modal.removeEventListener('click', onClick);
                document.removeEventListener('keydown', onKey);
            }
            function onClick(e) {
                if (e.target === modal || e.target.closest('[data-md-preview-close]')) close();
            }
            function onKey(e) {
                if (e.key === 'Escape') close();
            }
            modal.addEventListener('click', onClick);
            document.addEventListener('keydown', onKey);

            var data = new URLSearchParams();
            data.set('csrf_token', csrf);
            data.set('markdown', textarea.value);
            fetch(previewUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: data.toString()
            }).then(function(res) {
                return res.ok ? res.json() : Promise.reject();
            }).then(function(data) {
                body.innerHTML = (data && data.html) ? data.html : '<div class="empty">暂无内容</div>';
                if (window.pdEnhanceMedia) window.pdEnhanceMedia(body);
            }).catch(function() {
                body.innerHTML = '<div class="empty">预览暂时不可用</div>';
            });
        }

        root.querySelectorAll('[data-md]').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                command(btn.getAttribute('data-md'));
            });
        });

        if (previewBtn) {
            previewBtn.addEventListener('click', function(e) {
                e.preventDefault();
                openPreviewModal();
            });
        }

        if (filePicker) {
            filePicker.addEventListener('change', function() {
                var file = filePicker.files && filePicker.files[0];
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function() {
                    textarea.value = String(reader.result || '');
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                };
                reader.readAsText(file);
                filePicker.value = '';
            });
        }

        if (imagePicker) {
            imagePicker.addEventListener('change', function() {
                var file = imagePicker.files && imagePicker.files[0];
                if (!file) return;
                var data = new FormData();
                data.append('csrf_token', csrf);
                data.append('image', file);
                if (window.pdSetLoading) window.pdSetLoading(true);
                xhrUpload(uploadUrl, data, file, function(json) {
                    insert('![', '](' + json.url + ')', file.name.replace(/\.[^.]+$/, '') || '图片');
                    notify('图片上传成功', 'success');
                    if (window.pdSetLoading) window.pdSetLoading(false);
                }, function(error) {
                    notify(error, 'error');
                    if (window.pdSetLoading) window.pdSetLoading(false);
                });
                imagePicker.value = '';
            });
        }

        if (attachPicker) {
            attachPicker.addEventListener('change', function() {
                var file = attachPicker.files && attachPicker.files[0];
                if (!file) return;
                requestAttachmentDescription(function(description) {
                    var data = new FormData();
                    data.append('csrf_token', csrf);
                    data.append('attachment', file);
                    data.append('attachment_description', description || '');
                    if (window.pdSetLoading) window.pdSetLoading(true);
                    xhrUpload(attachUrl, data, file, function(json) {
                        insertText(textarea, '\n' + (json.tag || ('[' + (json.name || '附件') + '](' + json.url + ')')) + '\n');
                        notify('附件上传成功', 'success');
                        if (window.pdSetLoading) window.pdSetLoading(false);
                    }, function(error) {
                        notify(error, 'error');
                        if (window.pdSetLoading) window.pdSetLoading(false);
                    });
                });
                attachPicker.value = '';
            });
        }

        textarea.addEventListener('input', updateStats);
        textarea.addEventListener('keydown', function(e) {
            var key = (e.key || '').toLowerCase();
            if ((e.ctrlKey || e.metaKey) && key === 'b') {
                e.preventDefault();
                command('bold');
            }
            if ((e.ctrlKey || e.metaKey) && key === 'i') {
                e.preventDefault();
                command('italic');
            }
        });

        updateStats();
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

    window.pdInsertText = insertText;

    initForumCategories();
    document.querySelectorAll('.markdown-editor').forEach(initMarkdownEditor);
    initFloorReply();
})();

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
