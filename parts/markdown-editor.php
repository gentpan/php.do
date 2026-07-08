<?php
$editorId = isset($editorId) ? $editorId : 'editor-md';
$editorName = isset($editorName) ? $editorName : 'content';
$editorValue = isset($editorValue) ? $editorValue : '';
$editorRows = isset($editorRows) ? intval($editorRows) : 16;
$editorPlaceholder = isset($editorPlaceholder) ? $editorPlaceholder : '支持 Markdown：标题、列表、代码块、链接、图片…';
$editorRequired = !isset($editorRequired) || $editorRequired;
$editorCompact = !empty($editorCompact);
$editorMaxlength = isset($editorMaxlength) ? intval($editorMaxlength) : 0;
$editorPreviewUrl = isset($editorPreviewUrl) ? $editorPreviewUrl : qf_url_page('api/markdown-preview.php');
$editorUploadUrl = isset($editorUploadUrl) ? $editorUploadUrl : qf_url_page('api/upload-image.php');
$editorAttachUrl = isset($editorAttachUrl) ? $editorAttachUrl : qf_url_page('api/upload-attachment.php');
$editorClass = 'markdown-editor' . ($editorCompact ? ' is-compact' : '');
?>
<div class="<?php echo h($editorClass); ?>"
     data-preview-url="<?php echo h($editorPreviewUrl); ?>"
     data-upload-url="<?php echo h($editorUploadUrl); ?>"
     data-attach-url="<?php echo h($editorAttachUrl); ?>"
     data-csrf="<?php echo h(qf_csrf_token()); ?>">
    <div class="editor-toolbar" aria-label="Markdown 工具栏">
        <span class="editor-toolbar-label"><i class="fa-brands fa-markdown" aria-hidden="true"></i> Markdown</span>
        <div class="editor-heading-menu" data-heading-menu>
            <button type="button" data-heading-toggle title="标题" aria-haspopup="true" aria-expanded="false">
                <i class="fa-solid fa-heading"></i>
            </button>
            <div class="editor-heading-dropdown" data-heading-dropdown hidden>
                <?php for ($level = 1; $level <= 5; $level++) { ?>
                    <button type="button" data-md-heading="<?php echo $level; ?>">H<?php echo $level; ?></button>
                <?php } ?>
            </div>
        </div>
        <button type="button" data-md="bold" title="加粗"><i class="fa-solid fa-bold"></i></button>
        <button type="button" data-md="italic" title="斜体"><i class="fa-solid fa-italic"></i></button>
        <button type="button" data-md="quote" title="引用"><i class="fa-solid fa-quote-left"></i></button>
        <button type="button" data-md="code" title="代码块"><i class="fa-solid fa-code"></i></button>
        <button type="button" data-md="link" title="链接"><i class="fa-solid fa-link"></i></button>
        <button type="button" data-md="image" title="插入图片 URL"><i class="fa-regular fa-image"></i></button>
        <button type="button" data-md="image-upload" title="上传图片"><i class="fa-solid fa-arrow-up-from-bracket"></i></button>
        <button type="button" data-md="attach-upload" title="上传附件"><i class="fa-solid fa-paperclip"></i></button>
        <button type="button" data-md="ul" title="无序列表"><i class="fa-solid fa-list-ul"></i></button>
        <button type="button" data-md="ol" title="有序列表"><i class="fa-solid fa-list-ol"></i></button>
        <button type="button" data-md="table" title="表格"><i class="fa-solid fa-table"></i></button>
        <label class="editor-file editor-md-import" title="导入本地 .md">
            <i class="fa-solid fa-file-import"></i>
            <span>导入</span>
            <input type="file" data-md-file-picker accept=".md,text/markdown,text/plain">
        </label>
        <input type="file" data-md-image-picker accept="image/*" hidden>
        <input type="file" data-md-attach-picker accept=".zip,.rar,application/zip,application/x-rar-compressed" hidden>
        <span class="editor-toolbar-spacer" aria-hidden="true"></span>
        <span class="editor-toolbar-meta">
            <span data-editor-words-toolbar>0 字</span>
            <span data-editor-paragraphs>0 段</span>
            <span class="editor-toolbar-separator" aria-hidden="true"></span>
            <button type="button" class="editor-preview-btn" data-md-preview title="预览 Markdown 效果">
                <i class="fa-regular fa-eye" aria-hidden="true"></i>
                <span>预览</span>
            </button>
        </span>
    </div>
    <p class="muted upload-tip editor-upload-tip">支持 <?php echo h(qf_upload_allowed_exts_label()); ?>，单个文件最大 <?php echo intval(qf_upload_max_mb()); ?>MB。内容使用 Markdown。</p>
    <div class="editor-pane">
        <textarea name="<?php echo h($editorName); ?>"
                  rows="<?php echo $editorRows; ?>"
                  id="<?php echo h($editorId); ?>"
                  class="post-content-textarea"
                  data-editor-textarea
                  placeholder="<?php echo h($editorPlaceholder); ?>"
                  <?php if ($editorMaxlength > 0) { ?>maxlength="<?php echo $editorMaxlength; ?>"<?php } ?>
                  <?php if ($editorRequired) { ?>required<?php } ?>><?php echo h($editorValue); ?></textarea>
    </div>
</div>
