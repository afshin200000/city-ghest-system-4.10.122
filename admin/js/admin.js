(function($){
    /* ===== SINGLE column implementation (consolidated) ===== */
    function cgsApplyStepColumns(cols, step) {
        /* guides flag */
        cols = Math.max(1, Math.min(6, parseInt(cols, 10) || 1));
        var $targets;
        if (step != null && step !== '') {
            $targets = $('#cgs-live-preview .cgs-form-step[data-step="'+step+'"] .cgs-step-fields');
            if (!$targets.length) {
                var idx = (parseInt(step, 10) || 1) - 1;
                $targets = $('#cgs-live-preview .cgs-step-fields').eq(idx);
            }
        } else {
            $targets = $('#cgs-live-preview .cgs-step-fields');
        }
        if (!$targets.length) $targets = $('.cgs-preview-mode .cgs-step-fields');
        $targets.each(function(){
            this.style.setProperty('--cgs-cols', String(cols));
            // پیش‌نمایش: flex (نه grid) تا تغییر اندازه فیلد پایدار بماند
            $(this).attr('data-step-cols', cols).addClass('cgs-has-guides').css({
                display: 'flex',
                flexWrap: 'wrap',
                gap: '12px',
                width: '100%'
            });
        });
        if (step != null && step !== '') {
            $('.cgs-step-columns[data-step="'+step+'"]').val(String(cols));
            $('.cgs-preview-step-cols[data-step="'+step+'"]').val(String(cols));
        }
        return cols;
    }

    'use strict';

    // Beautiful confirm dialog
    window.cgsConfirm = function(message, title) {
        return new Promise(function(resolve) {
            var $m = $('#cgs-confirm-modal');
            if (!$m.length) {
                resolve(window.confirm(message));
                return;
            }
            $m.find('.cgs-confirm-title').text(title || 'تأیید عملیات');
            $m.find('.cgs-confirm-text').text(message);
            $m.addClass('is-open').attr('aria-hidden', 'false');
            function close(val) {
                $m.removeClass('is-open').attr('aria-hidden', 'true');
                $('#cgs-confirm-yes, #cgs-confirm-no').off('click.cgsC');
                resolve(val);
            }
            $('#cgs-confirm-yes').off('click.cgsC').on('click.cgsC', function(){ close(true); });
            $('#cgs-confirm-no').off('click.cgsC').on('click.cgsC', function(){ close(false); });
            $m.off('click.cgsC').on('click.cgsC', function(e){ if (e.target === this) close(false); });
        });
    };


    // Safety check
    if (typeof cgsAdmin !== 'undefined' && !cgsAdmin.ajax_url) {
        cgsAdmin.ajax_url = (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php';
    }
    if (typeof cgsAdmin === 'undefined') {
        console.error('CGS: cgsAdmin object not found. Using fallbacks.');
        window.cgsAdmin = {
            ajax_url: (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php',
            nonce: '',
            locations: {},
            sound_enabled: 1,
            sound_volume: 40
        };
    }

    
    var cgsSortableInstances = [];


    function initPreviewLayoutSortable() {
        if (typeof $.fn.sortable !== 'function') {
            $('#cgs-layout-msg').text('jQuery UI Sortable لود نشده').css('color','red');
            return;
        }
        var $boxes = $('#cgs-live-preview .cgs-step-fields, #cgs-live-preview .cgs-layout-canvas');
        if (!$boxes.length) $boxes = $('#cgs-live-preview');
        $boxes.each(function(){
            var $box = $(this);
            if ($box.hasClass('ui-sortable')) {
                try { $box.sortable('destroy'); } catch(e) {}
            }
            if (this._cgsSortable) { try { this._cgsSortable.destroy(); } catch(e){} this._cgsSortable=null; }
            if (this._cgsStableSort) { try { this._cgsStableSort.destroy(); } catch(e){} this._cgsStableSort=null; }
            $box.find('.cgs-field-group, .cgs-field-card').each(function(){
                var $c = $(this);
                $c.css('position','relative');
                if (!$c.children('.cgs-drag-grip').length) {
                    $c.prepend('<span class="cgs-drag-grip" title="جابجایی" style="position:absolute;top:4px;right:4px;z-index:30;cursor:grab;background:#1a237e;color:#fff;padding:4px 8px;border-radius:6px;font-size:13px;user-select:none;">⋮⋮</span>');
                }
            });
            $box.sortable({
                items: '.cgs-field-group, .cgs-field-card',
                cancel: 'input,textarea,select,button,option,a,.cgs-resize-handle,.cgs-width-badge',
                handle: '.cgs-drag-grip',
                placeholder: 'cgs-sortable-placeholder',
                tolerance: 'pointer',
                opacity: 0.85,
                distance: 3,
                forcePlaceholderSize: true,
                helper: 'clone',
                appendTo: 'body',
                zIndex: 10000,
                start: function(e, ui){
                    ui.placeholder.width(ui.item.outerWidth());
                    ui.placeholder.height(Math.max(40, ui.item.outerHeight()));
                },
                update: function(){
                    $('#cgs-layout-msg').text('ترتیب تغییر کرد — ذخیره چیدمان را بزنید').css('color','#b45309');
                }
            });
        });
        document.body.classList.remove('cgs-is-sorting');
        $('#cgs-layout-msg').text('درگ آماده — از ⋮⋮ بکشید').css('color','green');
    }
    window.initPreviewLayoutSortable = initPreviewLayoutSortable;




    var currentType = (new URLSearchParams(window.location.search)).get('type') || 'representative';
    if (typeof cgsAdmin !== 'undefined' && cgsAdmin.locations) {
        window.cgsLocations = cgsAdmin.locations;
    }

    /**
     * Apply form appearance styles LIVE to preview (and inject style tag)
     */
    
    /** اعمال قطعی ظاهر دکمه پیش‌نمایش — بدون وابستگی به قالب CSS شکننده */
    window.cgsForcePreviewButtons = function() {
        try {
            var bc = ($('#st-btn-color').val() || $('#st-color-button').val() || '#1a237e').toString().trim();
            if (!bc || bc === '#' || /^#fff(fff)?$/i.test(bc) || bc.toLowerCase() === 'white') {
                bc = '#1a237e';
            }
            var tpl = ($('#st-btn-template').val() || 'flat').toString();
            var bfs = parseInt($('#st-btn-font-size').val(), 10) || 14;
            var bfont = $('#st-btn-font').val() || 'Vazirmatn';
            var lightTpls = { outline:1, soft:1, glass:1, glass3d:1, minimal:1, bordered3d:1, ice:1, warning:1 };
            var isLight = !!lightTpls[tpl];
            var bg = isLight ? '#eef2ff' : bc;
            var fg = isLight ? bc : '#ffffff';
            var border = isLight ? ('2px solid ' + bc) : 'none';
            if (tpl === 'outline') { bg = '#ffffff'; fg = bc; border = '2px solid ' + bc; }
            if (tpl === 'success') { bg = '#16a34a'; fg = '#ffffff'; border = 'none'; }
            if (tpl === 'danger') { bg = '#dc2626'; fg = '#ffffff'; border = 'none'; }
            if (tpl === 'dark') { bg = '#0f172a'; fg = '#ffffff'; border = 'none'; }
            if (tpl === 'warning') { bg = '#f59e0b'; fg = '#1c1917'; border = 'none'; }
            var $btns = $('#cgs-live-preview button, #cgs-live-preview .cgs-btn, #cgs-live-preview .cgs-next-step, #cgs-live-preview .cgs-prev-step, #cgs-live-preview #cgs-preview-submit');
            $btns.each(function(){
                var el = this;
                el.style.setProperty('background', bg, 'important');
                el.style.setProperty('background-color', bg, 'important');
                el.style.setProperty('background-image', 'none', 'important');
                el.style.setProperty('color', fg, 'important');
                el.style.setProperty('border', border, 'important');
                el.style.setProperty('opacity', '1', 'important');
                el.style.setProperty('visibility', 'visible', 'important');
                el.style.setProperty('display', 'inline-flex', 'important');
                el.style.setProperty('align-items', 'center', 'important');
                el.style.setProperty('justify-content', 'center', 'important');
                el.style.setProperty('font-weight', '700', 'important');
                el.style.setProperty('font-size', bfs + 'px', 'important');
                el.style.setProperty('font-family', bfont + ',Tahoma,sans-serif', 'important');
                el.style.setProperty('padding', '10px 20px', 'important');
                el.style.setProperty('border-radius', '10px', 'important');
                el.style.setProperty('cursor', 'pointer', 'important');
                el.style.setProperty('box-shadow', isLight ? 'none' : '0 4px 14px rgba(26,35,126,0.28)', 'important');
            });
            $('#cgs-live-preview, #cgs-preview-form, #cgs-live-preview .cgs-form-wrapper').removeClass('cgs-btn-style-glass');
            // نوار دکمه‌ها حتما دیده شود
            $('#cgs-live-preview .cgs-form-actions, #cgs-live-preview .cgs-step-nav').css({
                display: 'flex', visibility: 'visible', opacity: 1, gap: '10px', marginTop: '12px', padding: '8px 0'
            });
        } catch (e) { console.warn('cgsForcePreviewButtons', e); }
    };

window.cgsApplyPreviewStyles = function(styles) {
        if (!styles) styles = {};
        var $wrap = $('#cgs-live-preview .cgs-form-wrapper, #cgs-live-preview .cgs-preview-mode, .cgs-preview-mode');
        if (!$wrap.length) $wrap = $('#cgs-live-preview');
        // also set data attributes for CSS
        $wrap.attr('data-label-pos', styles.label_position || 'beside');
        $wrap.attr('data-cols', styles.form_columns || '2');

        var css = '';
        var labelFont = styles.label_font || 'Vazirmatn';
        var inputFont = styles.input_font || 'Vazirmatn';
        css += '.cgs-preview-mode .cgs-field-group > label, #cgs-live-preview .cgs-field-group > label {';
        css += 'font-family:' + labelFont + ',Tahoma,sans-serif !important;';
        css += 'font-size:' + (styles.label_size || 15) + 'px !important;';
        css += 'font-weight:' + (styles.label_weight || 600) + ' !important;';
        css += 'font-style:' + (styles.label_style || 'normal') + ' !important;';
        css += 'text-decoration:' + (styles.label_decoration || 'none') + ' !important;';
        css += 'text-align:' + (styles.label_align || 'right') + ' !important;';
        css += 'color:' + (styles.color_label || '#1a1a2e') + ' !important;';
        css += '}';
        css += '.cgs-preview-mode .cgs-required, #cgs-live-preview .cgs-required, .cgs-preview-mode .req, #cgs-live-preview .req, .cgs-preview-mode span.req, #cgs-live-preview span.req { color:' + (styles.color_required || '#c62828') + ' !important; }';
        css += '.cgs-preview-mode .cgs-field-group input, .cgs-preview-mode .cgs-field-group select, .cgs-preview-mode .cgs-field-group textarea,';
        css += '#cgs-live-preview .cgs-field-group input, #cgs-live-preview .cgs-field-group select, #cgs-live-preview .cgs-field-group textarea {';
        css += 'font-family:' + inputFont + ',Tahoma,sans-serif !important;';
        css += 'font-size:' + (styles.input_size || 15) + 'px !important;';
        css += 'font-weight:' + (styles.input_weight || 400) + ' !important;';
        css += 'font-style:' + (styles.input_style || 'normal') + ' !important;';
        css += 'text-decoration:' + (styles.input_decoration || 'none') + ' !important;';
        css += 'text-align:' + (styles.input_align || 'right') + ' !important;';
        css += 'color:' + (styles.color_input || '#1a1a2e') + ' !important;';
        css += 'border-color:' + (styles.color_border || '#e0e4ec') + ' !important;';
        css += 'background-color:' + (styles.color_bg || '#ffffff') + ' !important;';
        css += '}';
        var radius = styles.button_radius || 10;
        css += '.cgs-preview-mode .cgs-btn, #cgs-live-preview .cgs-btn { border-radius:' + radius + 'px !important; }';

        var btnColor = styles.color_button || '#1a237e';
        if (styles.button_style === 'glass') {
            css += '.cgs-preview-mode .cgs-btn-primary, .cgs-preview-mode .cgs-btn-success,';
            css += '#cgs-live-preview .cgs-btn-primary, #cgs-live-preview .cgs-btn-success {';
            css += 'background:rgba(255,255,255,0.28) !important;';
            css += 'backdrop-filter:blur(12px) !important; -webkit-backdrop-filter:blur(12px) !important;';
            css += 'border:1.5px solid rgba(255,255,255,0.5) !important;';
            css += 'color:' + btnColor + ' !important;';
            css += 'box-shadow:0 8px 28px rgba(0,0,0,0.12) !important;';
            css += '}';
            $wrap.addClass('cgs-btn-style-glass');
            css += '#cgs-live-preview .cgs-btn, #cgs-live-preview .cgs-btn-primary, #cgs-live-preview .cgs-btn-success, #cgs-live-preview .cgs-btn-secondary {'
                + 'background:rgba(255,255,255,0.35) !important;'
                + 'backdrop-filter:blur(12px) saturate(1.4) !important;-webkit-backdrop-filter:blur(12px) saturate(1.4) !important;'
                + 'border:1.5px solid rgba(255,255,255,0.65) !important;'
                + 'box-shadow:0 8px 24px rgba(26,35,126,0.15) !important;'
                + 'color:' + (styles.color_button || '#1a237e') + ' !important; font-weight:700 !important; }';
        } else {
            css += '.cgs-preview-mode .cgs-btn-primary, .cgs-preview-mode .cgs-btn-success,';
            css += '#cgs-live-preview .cgs-btn-primary, #cgs-live-preview .cgs-btn-success {';
            css += 'background:' + btnColor + ' !important; color:#fff !important;';
            css += 'border:none !important; backdrop-filter:none !important;';
            css += '}';
            $wrap.removeClass('cgs-btn-style-glass');
        }

        // Button texts
        if (styles.btn_next_text) {
            $wrap.find('.cgs-next-step').each(function(){
                var $i = $(this).find('.cgs-icon').clone();
                $(this).empty().append($i).append(' ' + styles.btn_next_text);
            });
        }
        if (styles.btn_prev_text) {
            $wrap.find('.cgs-prev-step').each(function(){
                var $i = $(this).find('.cgs-icon').clone();
                $(this).empty().append($i).append(' ' + styles.btn_prev_text);
            });
        }
        if (styles.btn_submit_text) {
            $wrap.find('#cgs-preview-submit, .cgs-submit-btn').each(function(){
                var $i = $(this).find('.cgs-icon').clone();
                $(this).empty().append($i).append(' ' + styles.btn_submit_text);
            });
        }

        // Background image
        
        // سایه‌ها و افکت دکمه — واقعاً اعمال شود
        var mapShadow = {
            none: 'none',
            soft: '0 4px 14px rgba(15,23,42,0.08)',
            medium: '0 8px 24px rgba(15,23,42,0.14)',
            strong: '0 12px 36px rgba(15,23,42,0.22)',
            glow: '0 8px 32px rgba(26,35,126,0.35)'
        };
        if (styles.shadow_form && mapShadow[styles.shadow_form]) {
            css += '#cgs-live-preview .cgs-form-wrapper, #cgs-live-preview .cgs-form-step { box-shadow:' + mapShadow[styles.shadow_form] + ' !important; }';
        }
        if (styles.shadow_field && mapShadow[styles.shadow_field]) {
            css += '#cgs-live-preview .cgs-field-group, #cgs-live-preview .cgs-field-card { box-shadow:' + mapShadow[styles.shadow_field] + ' !important; }';
        }
        if (styles.shadow_btn && mapShadow[styles.shadow_btn]) {
            css += '#cgs-live-preview .cgs-btn, #cgs-live-preview .cgs-btn-primary, #cgs-live-preview .cgs-btn-success, #cgs-live-preview .cgs-btn-secondary { box-shadow:' + mapShadow[styles.shadow_btn] + ' !important; }';
        }
        if (styles.btn_hover === 'lift') {
            css += '#cgs-live-preview .cgs-btn:hover, #cgs-live-preview .cgs-btn-primary:hover, #cgs-live-preview .cgs-btn-success:hover { transform:translateY(-3px) !important; }';
        } else if (styles.btn_hover === 'scale') {
            css += '#cgs-live-preview .cgs-btn:hover, #cgs-live-preview .cgs-btn-primary:hover { transform:scale(1.04) !important; }';
        } else if (styles.btn_hover === 'glow') {
            css += '#cgs-live-preview .cgs-btn:hover, #cgs-live-preview .cgs-btn-primary:hover { filter:brightness(1.08); box-shadow:0 0 20px rgba(26,35,126,0.45) !important; }';
        }
        if (styles.button_style === 'solid') {
            css += '#cgs-live-preview .cgs-btn-primary, #cgs-live-preview .cgs-btn-success { background:' + (styles.color_button||'#1a237e') + ' !important; color:#fff !important; backdrop-filter:none !important; }';
        }

        if (styles.form_bg_image) {
            var op = (parseInt(styles.form_bg_opacity, 10) || 85) / 100;
            $wrap.addClass('has-bg').css({
                'background-image': 'url(' + styles.form_bg_image + ')',
                'background-size': 'cover',
                'background-position': 'center'
            });
            css += '.cgs-preview-mode.has-bg { position:relative; }';
            css += '.cgs-preview-mode.has-bg::after { content:""; position:absolute; inset:0; background:rgba(255,255,255,' + op + '); border-radius:inherit; pointer-events:none; z-index:0; }';
            css += '.cgs-preview-mode.has-bg > * { position:relative; z-index:1; }';
        } else {
            $wrap.removeClass('has-bg').css({ 'background-image': '', 'background-size': '', 'background-position': '' });
        }

        
        // Layout
        var cols = parseInt(styles.form_columns, 10) || 1;
        var gap = parseInt(styles.field_gap, 10) || 12;
        var labelPos = styles.label_position || 'above';
        var labelW = parseInt(styles.label_width, 10) || 30;
        css += '.cgs-preview-mode .cgs-step-fields { display:grid !important; grid-template-columns:repeat(' + cols + ',minmax(0,1fr)) !important; gap:' + gap + 'px !important; }';
        css += '.cgs-two-fields { display:grid !important; grid-template-columns:100px 1fr !important; gap:10px !important; width:100%; }';
        css += '.cgs-sub-label { display:block; font-size:0.8rem !important; font-weight:600; margin-bottom:4px; }';
        $wrap.toggleClass('cgs-labels-beside', labelPos === 'beside');
        $wrap.toggleClass('cgs-labels-above', labelPos !== 'beside');
        // کلاس روی ریشه پیش‌نمایش و wrapper
        $('#cgs-live-preview, #cgs-live-preview .cgs-form-wrapper, .cgs-preview-mode').toggleClass('cgs-labels-beside', labelPos === 'beside').toggleClass('cgs-labels-above', labelPos !== 'beside');
        if (labelPos === 'beside') {
            css += '#cgs-live-preview.cgs-preview-mode .cgs-field-group, #cgs-live-preview .cgs-field-group, .cgs-preview-mode .cgs-field-group { display:flex !important; flex-direction:row !important; flex-wrap:nowrap !important; align-items:center !important; gap:12px !important; }';
            css += '#cgs-live-preview .cgs-field-group > label, .cgs-preview-mode .cgs-field-group > label { display:block !important; flex:0 0 ' + labelW + '% !important; max-width:' + labelW + '% !important; width:' + labelW + '% !important; margin:0 !important; margin-bottom:0 !important; }';
            css += '#cgs-live-preview .cgs-field-group > .cgs-field-control, #cgs-live-preview .cgs-field-group > .cgs-two-fields, #cgs-live-preview .cgs-field-group > .cgs-file-upload, #cgs-live-preview .cgs-field-group > input, #cgs-live-preview .cgs-field-group > select, #cgs-live-preview .cgs-field-group > textarea { flex:1 1 auto !important; min-width:0 !important; width:auto !important; max-width:100% !important; }';
            css += '#cgs-live-preview.cgs-labels-beside .cgs-field-group { display:flex !important; flex-direction:row !important; }';
        } else {
            css += '#cgs-live-preview .cgs-field-group, .cgs-preview-mode .cgs-field-group { display:block !important; flex-direction:column !important; }';
            css += '#cgs-live-preview .cgs-field-group > label, .cgs-preview-mode .cgs-field-group > label { display:block !important; width:100% !important; max-width:100% !important; flex:none !important; margin-bottom:6px !important; }';
            css += '#cgs-live-preview .cgs-field-group > .cgs-field-control { width:100% !important; display:block !important; }';
        }


        // عنوان فرم — اعمال واقعی
        var $title = $('#cgs-live-preview .cgs-form-title');
        if ($title.length) {
            var tText = styles.form_title_text || $title.text();
            var tIcon = styles.form_title_icon || '';
            var tIconSz = parseInt(styles.form_title_icon_size, 10) || 24;
            if (styles.form_title_text !== undefined) {
                var html = '';
                if (tIcon) html += '<span class="cgs-form-title-icon" style="font-size:'+tIconSz+'px;margin-left:6px;vertical-align:middle;">'+tIcon+'</span>';
                html += $('<span/>').text(tText || '').html();
                $title.html(html || $title.html());
            }
            css += '#cgs-live-preview .cgs-form-title {';
            css += 'font-family:' + (styles.form_title_font || 'Vazirmatn') + ',Tahoma,sans-serif !important;';
            css += 'font-size:' + (styles.form_title_size || 20) + 'px !important;';
            css += 'color:' + (styles.form_title_color || '#1a237e') + ' !important;';
            var tbw = parseInt(styles.form_title_bw, 10) || 0;
            if (tbw > 0) {
                css += 'border:' + tbw + 'px solid ' + (styles.form_title_border || '#c5cae9') + ' !important;';
                css += 'padding:10px 14px !important; border-radius:10px !important; display:inline-block !important;';
            } else {
                css += 'border:none !important;';
            }
            var tsh = styles.form_title_shadow || 'none';
            if (tsh === 'soft') css += 'text-shadow:0 2px 6px rgba(0,0,0,0.12) !important; box-shadow:0 4px 14px rgba(15,23,42,0.08) !important;';
            else if (tsh === 'medium') css += 'text-shadow:0 2px 8px rgba(0,0,0,0.18) !important; box-shadow:0 8px 24px rgba(15,23,42,0.14) !important;';
            else if (tsh === 'strong') css += 'text-shadow:0 3px 12px rgba(0,0,0,0.25) !important; box-shadow:0 12px 36px rgba(15,23,42,0.22) !important;';
            else if (tsh === 'glow') css += 'text-shadow:0 0 12px rgba(26,35,126,0.45) !important; box-shadow:0 0 24px rgba(26,35,126,0.35) !important;';
            else css += 'text-shadow:none !important; box-shadow:none !important;';
            var tan = styles.form_title_anim || 'none';
            var tbgType = styles.form_title_bg_type || 'none';
            var tbgColor = styles.form_title_bg_color || '#eef2ff';
            var tbgMedia = styles.form_title_bg_media || '';
            if (tbgType === 'color') {
              css += '#cgs-live-preview .cgs-form-title, #cgs-preview-form .cgs-form-title { background:' + tbgColor + ' !important; background-image:none !important; padding:10px 16px !important; border-radius:10px !important; display:inline-block !important; max-width:100% !important; box-sizing:border-box !important; }';
              css += '#cgs-live-preview .cgs-form-header { background:transparent !important; background-image:none !important; }';
              css += '#cgs-live-preview .cgs-form-subtitle { background:transparent !important; color:#64748b !important; }';
            } else if (tbgType === 'image' && tbgMedia) {
              css += '#cgs-live-preview .cgs-form-title, #cgs-preview-form .cgs-form-title { background-image:url(' + tbgMedia + ') !important; background-size:cover !important; background-position:center !important; padding:12px 16px !important; border-radius:10px !important; display:inline-block !important; }';
              css += '#cgs-live-preview .cgs-form-header { background:transparent !important; }';
            } else if (tbgType === 'video' && tbgMedia) {
              // ویدئو به‌صورت data-attr؛ JS جدا لایه می‌سازد
              css += '#cgs-live-preview .cgs-form-title, #cgs-preview-form .cgs-form-title { background:transparent !important; position:relative !important; overflow:hidden !important; }';
            } else if (tbgType === 'none') {
              css += '#cgs-live-preview .cgs-form-title, #cgs-preview-form .cgs-form-title { background:transparent !important; background-image:none !important; }';
            }

            if (tan === 'fade') css += 'animation:cgsFadeIn .6s ease !important;';
            else if (tan === 'slide') css += 'animation:cgsSlideIn .5s ease !important;';
            else if (tan === 'pulse') css += 'animation:cgsPulse 1.5s ease infinite !important;';
            css += '}';
        }

        
        // موقعیت عنوان و دکمه‌ها
        var titlePos = styles.title_position || 'top';
        var $header = $('#cgs-live-preview .cgs-form-header, #cgs-live-preview .cgs-form-title').closest('.cgs-form-header');
        if (!$header.length) $header = $('#cgs-live-preview .cgs-form-title').parent();
        if (titlePos === 'hidden') {
            css += '#cgs-live-preview .cgs-form-header, #cgs-live-preview .cgs-form-title { display:none !important; }';
        } else {
            css += '#cgs-live-preview .cgs-form-header, #cgs-live-preview .cgs-form-title { display:block !important; }';
        }
        var btnPos = styles.btn_position || 'bottom';
        var btnScope = styles.btn_position_scope || 'all';
        var align = styles.btn_align || 'space-between';
        var bmt=parseInt(styles.btn_mt,10); if(isNaN(bmt)) bmt=12;
        var bmb=parseInt(styles.btn_mb,10); if(isNaN(bmb)) bmb=0;
        var bml=parseInt(styles.btn_ml,10); if(isNaN(bml)) bml=parseInt(styles.btn_mx,10)||0;
        var bmr=parseInt(styles.btn_mr,10); if(isNaN(bmr)) bmr=parseInt(styles.btn_mx,10)||0;
        var bgap=parseInt(styles.btn_gap,10); if(isNaN(bgap)) bgap=8;
        css += '#cgs-live-preview .cgs-form-actions, #cgs-live-preview .cgs-step-nav, #cgs-live-preview .cgs-form-footer { display:flex !important; justify-content:' + align + ' !important; flex-wrap:wrap !important; gap:'+bgap+'px !important; margin-top:'+bmt+'px !important; margin-bottom:'+bmb+'px !important; margin-left:'+bml+'px !important; margin-right:'+bmr+'px !important; }';
        // اعمال مستقیم روی DOM تا بلافاصله دیده شود
        try {
          $('#cgs-live-preview .cgs-form-actions, #cgs-live-preview .cgs-step-nav').css({
            display:'flex', justifyContent: align, flexWrap:'wrap', gap: bgap+'px',
            marginTop: bmt+'px', marginBottom: bmb+'px', marginLeft: bml+'px', marginRight: bmr+'px'
          });
        } catch(e){}
        if (btnPos === 'sticky-bottom') {
            css += '#cgs-live-preview .cgs-form-actions { position:sticky !important; bottom:0; background:rgba(255,255,255,0.95); padding:10px; z-index:20; border-top:1px solid #e2e8f0; }';
        }
        

        var tpl = styles.btn_template || 'flat';
        if (tpl === 'default') tpl = 'flat';
        var bsz = styles.btn_size || 'md';
        var ban = styles.btn_anim || 'none';
        var bc = styles.btn_color || styles.color_button || '#1a237e';
        if (!bc || bc === '#ffffff' || bc === '#fff' || bc === 'white') bc = '#1a237e';
        var bfont = styles.btn_font || styles.input_font || 'Vazirmatn';
        var bfs = parseInt(styles.btn_font_size,10) || 14;
        var pad = bsz === 'sm' ? '6px 14px' : (bsz === 'lg' ? '12px 24px' : '9px 18px');
        var btnSel = '#cgs-live-preview .cgs-btn, #cgs-live-preview .cgs-btn-primary, #cgs-live-preview .cgs-btn-success, #cgs-live-preview .cgs-btn-secondary, #cgs-live-preview .cgs-next-step, #cgs-live-preview .cgs-prev-step, #cgs-live-preview #cgs-preview-submit, #cgs-live-preview button.cgs-btn';
        css += btnSel + '{ padding:' + pad + ' !important; transition:all .25s ease !important; position:relative !important; overflow:hidden !important; cursor:pointer !important; font-weight:700 !important; font-family:' + bfont + ',Tahoma,sans-serif !important; font-size:' + bfs + 'px !important; opacity:1 !important; }';
        var maps = {
          flat: 'background:'+bc+' !important;color:#ffffff !important;border:none !important;box-shadow:none !important;border-radius:10px !important;',
          solid: 'background:'+bc+' !important;color:#ffffff !important;border:none !important;border-radius:10px !important;',
          outline: 'background:#ffffff !important;color:'+bc+' !important;border:2px solid '+bc+' !important;box-shadow:none !important;border-radius:10px !important;',
          soft: 'background:rgba(26,35,126,0.14) !important;color:#1a237e !important;border:1px solid rgba(26,35,126,0.25) !important;border-radius:10px !important;',
          glass: 'background:rgba(238,242,255,0.92) !important;backdrop-filter:blur(12px) !important;border:1.5px solid '+bc+' !important;color:'+bc+' !important;box-shadow:0 8px 24px rgba(15,23,42,0.12) !important;border-radius:12px !important;',
          glass3d: 'background:linear-gradient(145deg,#eef2ff,#dbeafe) !important;border:1px solid '+bc+' !important;color:'+bc+' !important;box-shadow:0 10px 28px rgba(15,23,42,0.18),inset 0 1px 0 #fff !important;border-radius:12px !important;',
          neon: 'background:'+bc+' !important;color:#ffffff !important;border:none !important;box-shadow:0 0 8px '+bc+',0 0 22px rgba(26,35,126,0.5) !important;border-radius:10px !important;',
          raised3d: 'background:linear-gradient(180deg,#3f51b5,'+bc+') !important;color:#ffffff !important;border:none !important;border-bottom:4px solid rgba(0,0,0,0.28) !important;box-shadow:0 6px 0 rgba(0,0,0,0.12),0 10px 20px rgba(15,23,42,0.2) !important;border-radius:10px !important;',
          pill: 'background:'+bc+' !important;color:#ffffff !important;border:none !important;border-radius:999px !important;',
          gradient: 'background:linear-gradient(135deg,#667eea,#764ba2) !important;color:#ffffff !important;border:none !important;border-radius:10px !important;',
          shadow: 'background:'+bc+' !important;color:#ffffff !important;border:none !important;box-shadow:0 10px 24px rgba(26,35,126,0.4) !important;border-radius:10px !important;',
          minimal: 'background:#f1f5f9 !important;color:#1e293b !important;border:1px solid #94a3b8 !important;border-radius:8px !important;box-shadow:none !important;',
          success: 'background:#16a34a !important;color:#ffffff !important;border:none !important;border-radius:10px !important;',
          danger: 'background:#dc2626 !important;color:#ffffff !important;border:none !important;border-radius:10px !important;',
          warning: 'background:#f59e0b !important;color:#1c1917 !important;border:none !important;border-radius:10px !important;',
          dark: 'background:#0f172a !important;color:#ffffff !important;border:none !important;border-radius:10px !important;',
          bordered3d: 'background:#eef2ff !important;color:'+bc+' !important;border:2px solid '+bc+' !important;box-shadow:4px 4px 0 '+bc+' !important;border-radius:8px !important;',
          glow_pulse: 'background:#3949ab !important;color:#ffffff !important;border:none !important;box-shadow:0 0 18px rgba(57,73,171,0.65) !important;border-radius:10px !important;',
          ice: 'background:linear-gradient(180deg,#e0f2fe,#bae6fd) !important;color:#0c4a6e !important;border:1px solid #0284c7 !important;border-radius:10px !important;',
          premium: 'background:linear-gradient(135deg,#1a237e,#c5a46f) !important;color:#ffffff !important;border:none !important;border-radius:10px !important;box-shadow:0 8px 20px rgba(197,164,111,0.35) !important;'
        };
        if (maps[tpl]) css += btnSel + '{' + maps[tpl] + '}';
        else css += btnSel + '{' + maps.flat + '}';
        // secondary کمی شفاف‌تر اما خوانا
        css += '#cgs-live-preview .cgs-btn-secondary, #cgs-live-preview .cgs-prev-step { filter:none !important; opacity:1 !important; }';
        if (ban === 'pulse') {
          css += '@keyframes cgsBtnPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.04)}}' + btnSel + '{animation:cgsBtnPulse 1.6s ease-in-out infinite !important;}';
        } else if (ban === 'shine') {
          css += btnSel + '::after{content:"";position:absolute;top:0;left:-120%;width:60%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.45),transparent);animation:cgsBtnShine 2.4s ease infinite;}@keyframes cgsBtnShine{0%{left:-120%}100%{left:140%}}';
        } else if (ban === 'bounce') {
          css += '@keyframes cgsBtnBounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-3px)}}' + btnSel + '{animation:cgsBtnBounce 1.8s ease-in-out infinite !important;}';
        }
        if (styles.btn_fullwidth === '1') {
            css += '#cgs-live-preview #cgs-preview-submit, #cgs-live-preview .cgs-submit-btn { width:100% !important; }';
        }
        // اعمال مستقیم رنگ روی دکمه‌ها — غلبه بر public.css glass
        try {
          var $btns = $('#cgs-live-preview .cgs-btn, #cgs-live-preview .cgs-next-step, #cgs-live-preview .cgs-prev-step, #cgs-live-preview #cgs-preview-submit');
          var solidBg = (tpl === 'outline' || tpl === 'soft' || tpl === 'glass' || tpl === 'glass3d' || tpl === 'minimal' || tpl === 'bordered3d' || tpl === 'ice') ? null : bc;
          $btns.each(function(){
            var el = this;
            if (solidBg) {
              el.style.setProperty('background', solidBg, 'important');
              el.style.setProperty('background-image', 'none', 'important');
              el.style.setProperty('color', '#ffffff', 'important');
              el.style.setProperty('border', 'none', 'important');
              el.style.setProperty('opacity', '1', 'important');
            } else if (tpl === 'outline') {
              el.style.setProperty('background', '#ffffff', 'important');
              el.style.setProperty('color', bc, 'important');
              el.style.setProperty('border', '2px solid ' + bc, 'important');
            } else if (tpl === 'soft' || tpl === 'glass' || tpl === 'glass3d') {
              el.style.setProperty('background', '#eef2ff', 'important');
              el.style.setProperty('color', bc, 'important');
              el.style.setProperty('border', '1px solid ' + bc, 'important');
            }
          });
          // حذف کلاس glass از wrapper پیش‌نمایش تا public.css سفید نکند
          $('#cgs-live-preview, #cgs-live-preview .cgs-form-wrapper, #cgs-preview-form').removeClass('cgs-btn-style-glass');
        } catch (eBtn) {}


        var $tag = $('#cgs-live-style-tag');
        if (!$tag.length) {
            $tag = $('<style id="cgs-live-style-tag"></style>');
            $('head').append($tag);
        }
        $tag.html(css);
        if (window.cgsForcePreviewButtons) window.cgsForcePreviewButtons();
        if (window.cgsForceTitleBg) window.cgsForceTitleBg();
    };

    function cgsCollectStylesFromUI() {
        return {
            label_font: $('#st-label-font').val(),
            label_size: $('#st-label-size').val(),
            label_weight: $('#st-label-weight').val(),
            label_style: $('#st-label-style').val(),
            label_decoration: $('#st-label-decoration').val(),
            label_align: $('#st-label-align').val(),
            input_font: $('#st-input-font').val(),
            input_size: $('#st-input-size').val(),
            input_weight: $('#st-input-weight').val(),
            input_style: $('#st-input-style').val(),
            input_decoration: $('#st-input-decoration').val(),
            input_align: $('#st-input-align').val(),
            color_label: $('#st-color-label').val(),
            color_required: $('#st-color-required').val(),
            color_input: $('#st-color-input').val(),
            color_border: $('#st-color-border').val(),
            color_bg: $('#st-color-bg').val(),
            color_button: $('#st-color-button').val(),
            button_radius: $('#st-button-radius').val(),
            button_style: $('#st-button-style').val(),
            form_title_text: $('#st-form-title-text').val()||'',
            form_title_font: $('#st-form-title-font').val()||'',
            form_title_size: $('#st-form-title-size').val()||'20',
            form_title_color: $('#st-form-title-color').val()||'#1a237e',
            form_title_border: $('#st-form-title-border').val()||'#c5cae9',
            form_title_bw: $('#st-form-title-bw').val()||'0',
            form_title_shadow: $('#st-form-title-shadow').val()||'none',
            form_title_anim: $('#st-form-title-anim').val()||'none',
            form_title_icon: $('#st-form-title-icon').val()||'',
            form_title_icon_size: $('#st-form-title-icon-size,#st-form-title-bg-type,#st-form-title-bg-color,#st-form-title-bg-media').val()||'24',
            form_title_bg_type: $('#st-form-title-bg-type').val()||'color',
            form_title_bg_color: $('#st-form-title-bg-color').val()||'#eef2ff',
            form_title_bg_media: $('#st-form-title-bg-media').val()||'',
            btn_next_text: $('#st-btn-next').val() || 'مرحله بعد',
            btn_prev_text: $('#st-btn-prev').val() || 'مرحله قبل',
            btn_submit_text: $('#st-btn-submit').val() || 'ثبت نهایی درخواست',
            form_bg_image: $('#st-form-bg').val() || '',
            form_bg_opacity: $('#st-form-bg-op').val() || '85',
            form_bg_effect: $('#st-form-bg-effect').val() || 'none',
            
            shadow_form: $('#st-shadow-form').val() || 'soft',
            shadow_field: $('#st-shadow-field').val() || 'none',
            shadow_btn: $('#st-shadow-btn').val() || 'medium',
            btn_hover: $('#st-btn-hover').val() || 'lift',
            btn_sound: $('#st-btn-sound').val() || '0',
            sound_type: $('#st-sound-type').val() || 'chime',
            sound_volume: $('#st-sound-volume').val() || '40',
            btn_align: $('#st-btn-align').val() || 'space-between',
            title_position: $('#st-title-position').val() || 'top',
            btn_position: $('#st-btn-position').val() || 'bottom',
            btn_position_scope: $('#st-btn-position-scope').val() || 'all',
            btn_template: $('#st-btn-template').val() || 'flat',
            btn_color: (function(){ var c = ($('#st-btn-color').val() || $('#st-color-button').val() || '#1a237e'); if(!c||/^#fff(fff)?$/i.test(c)||c==='white') c='#1a237e'; return c; })(),
            btn_font: $('#st-btn-font').val() || '',
            btn_font_size: $('#st-btn-font-size').val() || '14',
            btn_size: $('#st-btn-size').val() || 'md',
            btn_fullwidth: $('#st-btn-fullwidth').val() || '0',
            btn_anim: $('#st-btn-anim').val() || 'none',
            btn_mt: $('#st-btn-mt').val() || '12',
            btn_mb: $('#st-btn-mb').val() || '0',
            btn_mx: $('#st-btn-mx').val() || '0',
            btn_gap: $('#st-btn-gap').val() || '8',
            btn_ml: $('#st-btn-ml').val() || '0',
            btn_mr: $('#st-btn-mr').val() || '0',
            button_style: $('#st-button-style').val() || 'glass',
            label_position: $('#st-label-position').val() || 'beside',
            form_columns: $('#st-form-columns').val() || '1',
            field_gap: $('#st-field-gap').val() || '12',
            label_width: $('#st-label-width').val() || '30'
        };
    }



    // ========== SORTABLE (SortableJS) ==========
    function initSortable() {
        var el = document.getElementById('cgs-fields-list');
        if (!el || typeof Sortable === 'undefined') return;
        if (el._cgsSortable) {
            try { el._cgsSortable.destroy(); } catch(e) {}
        }
        el._cgsSortable = Sortable.create(el, {
            animation: 180,
            easing: 'cubic-bezier(0.2, 0, 0, 1)',
            delay: 50,
            delayOnTouchOnly: true,
            handle: '.cgs-handle',
            draggable: '.cgs-field-row',
            filter: 'input,textarea,select,button,a',
            preventOnFilter: true,
            ghostClass: 'cgs-sortable-ghost',
            chosenClass: 'cgs-sortable-chosen',
            dragClass: 'cgs-sortable-drag',
            direction: 'vertical',
            scroll: true,
            bubbleScroll: true,
            swapThreshold: 0.6
        });
    }
    initSortable();

    // Save order
    $(document).on('click', '#cgs-btn-save-order', function(e){
        e.preventDefault();
        var fields = [];
        $('#cgs-fields-list .cgs-field-row').each(function(i){
            var raw = $(this).find('.cgs-raw').val();
            if (!raw) return;
            try {
                var data = JSON.parse(raw);
                data.sort_order = i + 1;
                fields.push(data);
            } catch(err) {}
        });
        $('#cgs-order-msg').text('در حال ذخیره...').css('color','#666');
        $.post(cgsAdmin.ajax_url, {
            action: 'cgs_save_form_fields',
            nonce: cgsAdmin.nonce,
            type_key: currentType,
            fields: JSON.stringify(fields)
        }).done(function(res){
            $('#cgs-order-msg').text(res.success ? 'ترتیب ذخیره شد ✓' : (res.data || 'خطا')).css('color', res.success ? 'green' : 'red');
        }).fail(function(){
            $('#cgs-order-msg').text('خطای ارتباط').css('color','red');
        });
    });

    // ========== MODAL ==========
    function openModal(title) {
        $('#cgs-modal-title').text(title || 'افزودن فیلد');
        $('#cgs-modal').css({'display':'flex', 'align-items':'center', 'justify-content':'center'}).addClass('active');
        $('#mf-msg').empty();
    }
    function closeModal() {
        $('#cgs-modal').hide().removeClass('active');
        $('#mf-id').val('');
        $('#mf-label').val('');
        $('#mf-type').val('text');
        $('#mf-placeholder').val('');
        $('#mf-options').val('');
        $('#mf-step').val('1');
        $('#mf-width').val('100');
        $('#mf-maxlen').val('0');
        $('#mf-file-types').val('jpg,jpeg,png,pdf,webp');
        $('#mf-file-size').val('2048');
        $('#mf-file-wrap').hide();
        $('#mf-role').val('');
        $('#mf-required').prop('checked', false);
        $('#mf-options-wrap').hide();
    }

    // Add button
    $(document).on('click', '#cgs-btn-add', function(e){
        e.preventDefault();
        e.stopPropagation();
        closeModal();
        openModal('افزودن فیلد جدید');
        return false;
    });

    // Cancel / backdrop
    $(document).on('click', '#mf-cancel, .cgs-modal-backdrop', function(e){
        e.preventDefault();
        closeModal();
    });

    // Show options for select/radio/checkbox
    $(document).on('change', '#mf-type', function(){
        var t = $(this).val();
        if (['select','radio','checkbox'].indexOf(t) !== -1) {
            $('#mf-options-wrap').show();
        } else {
            $('#mf-options-wrap').hide();
        }
        if (t === 'file') {
            $('#mf-file-wrap').show();
        } else {
            $('#mf-file-wrap').hide();
        }
        if (t === 'table') {
            $('#mf-table-wrap').show();
        } else {
            $('#mf-table-wrap').hide();
        }
        if (t === 'divider') {
            $('#mf-placeholder').attr('placeholder', 'عنوان بخش (اختیاری) — یا از برچسب استفاده کنید');
        }
    });

    // Edit button
    $(document).on('click', '.cgs-btn-edit', function(e){
        e.preventDefault();
        var b = $(this);
        closeModal();
        $('#mf-id').val(b.attr('data-id'));
        $('#mf-label').val(b.attr('data-label'));
        $('#mf-type').val(b.attr('data-type')).trigger('change');
        $('#mf-placeholder').val(b.attr('data-placeholder') || '');
        $('#mf-step').val(b.attr('data-step') || '1');
        $('#mf-width').val(b.attr('data-width') || '100');
        $('#mf-required').prop('checked', b.attr('data-required') == '1');
        $('#mf-options').val(b.attr('data-options') || '');
        // بارگذاری تنظیمات جدول برای ویرایش
        var valRaw = b.attr('data-validation') || '';
        if (!valRaw) {
            try {
                var raw = b.closest('li').find('.cgs-raw').val();
                if (raw) {
                    var full = JSON.parse(raw);
                    if (full && full.validation) {
                        valRaw = typeof full.validation === 'string' ? full.validation : JSON.stringify(full.validation);
                    }
                }
            } catch(e2) {}
        }
        try {
            var vd = valRaw ? JSON.parse(valRaw) : {};
            if (b.attr('data-type') === 'table' || (vd && (vd.table_cols || vd.table_formula))) {
                $('#mf-table-wrap').show();
                $('#mf-table-cols').val(vd.table_cols || 3);
                $('#mf-table-rows').val(vd.table_rows || 2);
                $('#mf-table-max-rows').val(vd.table_max_rows || 10);
                $('#mf-table-color').val(vd.table_color || '#1a237e');
                $('#mf-table-color-text').val(vd.table_color_text || '#ffffff');
                $('#mf-table-label').val(vd.table_label || '');
                $('#mf-table-headers').val(Array.isArray(vd.table_headers) ? vd.table_headers.join(',') : (vd.table_headers || ''));
                $('#mf-table-striped').prop('checked', vd.table_striped != 0);
                $('#mf-table-bordered').prop('checked', vd.table_bordered != 0);
                $('#mf-table-compact').prop('checked', !!vd.table_compact);
                $('#mf-table-addrow').prop('checked', vd.table_addrow != 0);
                $('#mf-table-formula').val(vd.table_formula || '');
            }
        } catch (err) {}
        try {
            var vd2 = valRaw ? JSON.parse(valRaw) : {};
            if (typeof window.cgsFillConditions === 'function') {
                window.cgsFillConditions(vd2.conditions || null);
            }
        } catch (e3) {
            if (typeof window.cgsFillConditions === 'function') window.cgsFillConditions(null);
        }
        // Detect special role from field_key
        var fkey = b.attr('data-key') || '';
        var roles = ['province','city','mobile','landline','area_code','national_id','email','full_name','postal_code','address','birth_date','id_card_front','id_card_back','website','person_type','business_type','business_detail','company_name','economic_code','national_id_company','bank_account','bank_card','card_name','bank_name','bank_branch','branch_code','sheba','account_holder','guarantee_type','check_bank','check_date','check_subject','check_sheba','check_series','check_serial','check_sayad_image','promissory_count','promissory_amount','promissory_date','promissory_serial','promissory_image','guarantee_owner','guarantor_name','guarantor_national_id','guarantor_mobile','guarantor_relation','guarantor_sign_status'];
        if (roles.indexOf(fkey) !== -1) {
            $('#mf-role').val(fkey);
        } else {
            $('#mf-role').val('');
        }
        openModal('ویرایش فیلد');
    });

    // Save field (Add or Update)
    $(document).on('click', '#mf-save', function(e){
        e.preventDefault();
        var label = $.trim($('#mf-label').val());
        if (!label) {
            $('#mf-msg').html('<span style="color:red">برچسب فیلد الزامی است</span>');
            return;
        }
        var id = $('#mf-id').val();
        var payload = {
            nonce: cgsAdmin.nonce,
            type_key: currentType,
            label: label,
            field_type: $('#mf-type').val(),
            placeholder: $('#mf-placeholder').val(),
            step_number: $('#mf-step').val(),
            field_width: $('#mf-width').val() || '100',
            min_age: $('#mf-min-age').val() || 0,
            max_age: $('#mf-max-age').val() || 0,
            is_required: $('#mf-required').is(':checked') ? 1 : 0,
            options: $('#mf-options').val(),
            max_length: $('#mf-maxlen').val() || '0',
            file_types: $('#mf-file-types').val() || 'jpg,jpeg,png,pdf,webp',
            file_max_kb: $('#mf-file-size').val() || '2048',
            field_role: $('#mf-role').val() || '',
            table_cols: $('#mf-table-cols').val() || '3',
            table_rows: $('#mf-table-rows').val() || '2',
            table_max_rows: $('#mf-table-max-rows').val() || '10',
            table_color: $('#mf-table-color').val() || '#1a237e',
            table_color_text: $('#mf-table-color-text').val() || '#ffffff',
            table_label: $('#mf-table-label').val() || '',
            table_headers: $('#mf-table-headers').val() || '',
            table_striped: $('#mf-table-striped').is(':checked') ? 1 : 0,
            table_bordered: $('#mf-table-bordered').is(':checked') ? 1 : 0,
            table_compact: $('#mf-table-compact').is(':checked') ? 1 : 0,
            table_addrow: $('#mf-table-addrow').is(':checked') ? 1 : 0,
            table_formula: $('#mf-table-formula').val() || '',
            table_excel: $('#mf-table-excel').val() || '',
            conditions: (typeof window.cgsCollectConditions === 'function' && window.cgsCollectConditions())
                ? JSON.stringify(window.cgsCollectConditions()) : ''
        };
        payload.action = id ? 'cgs_update_field' : 'cgs_add_field';
        if (id) payload.id = id;

        $('#mf-msg').html('<span style="color:#666">در حال ذخیره...</span>');
        $.post(cgsAdmin.ajax_url, payload)
            .done(function(res){
                if (res.success) {
                    $('#mf-msg').html('<span style="color:green">' + (res.data.message || 'ذخیره شد') + '</span>');
                    setTimeout(function(){ window.location.reload(); }, 500);
                } else {
                    $('#mf-msg').html('<span style="color:red">' + (res.data || 'خطا در ذخیره') + '</span>');
                }
            })
            .fail(function(xhr){
                $('#mf-msg').html('<span style="color:red">خطای سرور: ' + xhr.status + '</span>');
            });
    });

    // Delete
    $(document).on('click', '.cgs-btn-del', function(e){
        e.preventDefault();
        if (!confirm('این فیلد حذف شود؟')) return;
        var id = $(this).attr('data-id');
        var $row = $(this).closest('.cgs-field-row');
        $.post(cgsAdmin.ajax_url, {
            action: 'cgs_delete_field',
            nonce: cgsAdmin.nonce,
            id: id
        }).done(function(res){
            if (res.success) {
                $row.fadeOut(200, function(){ $(this).remove(); });
            } else {
                alert(res.data || 'خطا در حذف فیلد');
            }
        }).fail(function(){
            alert('خطای ارتباط با سرور');
        });
    });

    // Save styles
    $(document).on('click', '.cgs-save-styles-btn, #cgs-btn-save-styles', function(e){
        e.preventDefault();
        var styles = cgsCollectStylesFromUI();
        window.cgsApplyPreviewStyles(styles);
        $('#cgs-style-msg').text('در حال ذخیره...').css('color','#666');
        $.post(cgsAdmin.ajax_url, {
            action: 'cgs_save_form_styles',
            nonce: cgsAdmin.nonce,
            type_key: (typeof currentType !== 'undefined' ? currentType : 'representative'),
            styles: styles
        }).done(function(res){
            var ok = res && res.success;
            $('#cgs-style-msg').text(ok ? 'ذخیره شد و روی پیش‌نمایش اعمال شد ✓' : (res.data || res.data && res.data.message || 'خطا در ذخیره')).css('color', ok ? 'green' : 'red');
            if (ok) window.cgsApplyPreviewStyles(styles);
        }).fail(function(xhr){
            $('#cgs-style-msg').text('خطای ارتباط: ' + (xhr.status||'')).css('color','red');
        });
    });

    // Color pickers initialized on document.ready with live preview callback

    
    // Special field role auto-config
    var rolePresets = {
        province:   { label: 'استان', type: 'select', placeholder: 'استان خود را انتخاب کنید', maxlen: '0', required: true },
        city:       { label: 'شهر', type: 'select', placeholder: 'ابتدا استان را انتخاب کنید', maxlen: '0', required: true },
        mobile:     { label: 'شماره موبایل', type: 'tel', placeholder: '0912XXXXXXX', maxlen: '11', required: true },
        landline:   { label: 'تلفن ثابت', type: 'tel', placeholder: 'شماره بدون کد شهرستان', maxlen: '8', required: false },
        area_code:  { label: 'کد مخابراتی', type: 'text', placeholder: 'خودکار', maxlen: '4', required: false },
        national_id:{ label: 'کد ملی', type: 'text', placeholder: 'کد ملی ۱۰ رقمی', maxlen: '10', required: true },
        email:      { label: 'ایمیل', type: 'email', placeholder: 'example@email.com', maxlen: '100', required: false },
        full_name:  { label: 'نام و نام خانوادگی', type: 'text', placeholder: 'نام کامل خود را وارد کنید', maxlen: '80', required: true },
        postal_code:{ label: 'کد پستی', type: 'text', placeholder: 'کد پستی ۱۰ رقمی', maxlen: '10', required: false },
        address:    { label: 'آدرس کامل', type: 'textarea', placeholder: 'آدرس دقیق پستی', maxlen: '300', required: true },
        birth_date: { label: 'تاریخ تولد', type: 'date', placeholder: 'انتخاب تاریخ', maxlen: '0', required: false },
        id_card_front: { label: 'تصویر روی کارت ملی', type: 'file', placeholder: '', maxlen: '0', required: true },
        id_card_back:  { label: 'تصویر پشت کارت ملی', type: 'file', placeholder: '', maxlen: '0', required: true },
        website:    { label: 'نشانی اینترنتی', type: 'url', placeholder: 'https://example.com', maxlen: '200', required: false },
        person_type:{ label: 'نوع شخص', type: 'select', placeholder: '', maxlen: '0', required: true },
        business_type:{ label: 'نوع صنف', type: 'select', placeholder: 'صنف خود را انتخاب کنید', maxlen: '0', required: true },
        business_detail:{ label: 'توضیح جزئی صنف', type: 'text', placeholder: 'مثلاً: فروش موبایل و لوازم جانبی', maxlen: '150', required: false },
        company_name:{ label: 'نام شرکت / فروشگاه', type: 'text', placeholder: 'نام رسمی مجموعه', maxlen: '120', required: false },
        economic_code:{ label: 'کد اقتصادی', type: 'text', placeholder: 'کد اقتصادی ۱۲ رقمی', maxlen: '12', required: false },
        national_id_company:{ label: 'شناسه ملی شرکت', type: 'text', placeholder: 'شناسه ملی ۱۱ رقمی', maxlen: '11', required: false },
        bank_account: { label: 'شماره حساب', type: 'text', placeholder: 'شماره حساب بانکی', maxlen: '20', required: false },
        bank_card: { label: 'شماره کارت', type: 'text', placeholder: '۱۶ رقم کارت', maxlen: '16', required: false },
        card_name: { label: 'نام روی کارت', type: 'text', placeholder: 'نام حک‌شده روی کارت', maxlen: '50', required: false },
        bank_name: { label: 'نام بانک', type: 'text', placeholder: 'مثلاً ملت، ملی، صادرات', maxlen: '40', required: false },
        bank_branch: { label: 'نام شعبه', type: 'text', placeholder: 'نام شعبه بانک', maxlen: '60', required: false },
        branch_code: { label: 'کد شعبه', type: 'text', placeholder: 'کد شعبه', maxlen: '10', required: false },
        sheba: { label: 'شماره شبا', type: 'text', placeholder: 'IR بدون فاصله', maxlen: '26', required: false },
        account_holder: { label: 'نام صاحب حساب', type: 'text', placeholder: 'نام صاحب حساب', maxlen: '80', required: false },
        guarantee_type: { label: 'نوع تضمین', type: 'select', placeholder: '', maxlen: '0', required: false },
        check_bank: { label: 'نام بانک چک', type: 'text', placeholder: 'بانک صادرکننده چک', maxlen: '40', required: false },
        check_date: { label: 'تاریخ ثبت چک', type: 'date', placeholder: 'انتخاب تاریخ', maxlen: '0', required: false },
        check_subject: { label: 'موضوع چک', type: 'text', placeholder: 'بابت تضمین همکاری', maxlen: '100', required: false },
        check_sheba: { label: 'شبا چک', type: 'text', placeholder: 'شماره شبا مرتبط با چک', maxlen: '26', required: false },
        check_series: { label: 'شماره سری چک', type: 'text', placeholder: 'سری', maxlen: '20', required: false },
        check_serial: { label: 'شماره سریال چک', type: 'text', placeholder: 'سریال', maxlen: '20', required: false },
        check_sayad_image: { label: 'تصویر ثبت چک در صیاد', type: 'file', placeholder: '', maxlen: '0', required: false },
        promissory_count: { label: 'تعداد برگ سفته', type: 'number', placeholder: 'تعداد', maxlen: '2', required: false },
        promissory_amount: { label: 'مبلغ سفته (ریال)', type: 'number', placeholder: 'مبلغ به ریال', maxlen: '15', required: false },
        promissory_date: { label: 'تاریخ سفته', type: 'date', placeholder: 'انتخاب تاریخ', maxlen: '0', required: false },
        promissory_serial: { label: 'شماره سریال سفته', type: 'text', placeholder: 'سریال سفته', maxlen: '30', required: false },
        promissory_image: { label: 'تصویر سفته', type: 'file', placeholder: '', maxlen: '0', required: false },
        guarantee_owner: { label: 'صاحب سند تضمین', type: 'select', placeholder: '', maxlen: '0', required: true },
        guarantor_name: { label: 'نام صاحب سند', type: 'text', placeholder: 'نام و نام خانوادگی', maxlen: '80', required: false },
        guarantor_national_id: { label: 'کد ملی صاحب سند', type: 'text', placeholder: 'کد ملی ۱۰ رقمی', maxlen: '10', required: false },
        guarantor_mobile: { label: 'موبایل صاحب سند', type: 'tel', placeholder: '09XXXXXXXXX', maxlen: '11', required: false },
        guarantor_relation: { label: 'نسبت با متقاضی', type: 'text', placeholder: 'مثلاً پدر، شریک، ضامن', maxlen: '40', required: false },
        guarantor_sign_status: { label: 'وضعیت امضای دیجیتال', type: 'select', placeholder: '', maxlen: '0', required: false }
    };

    $(document).on('change', '#mf-role', function(){
        var role = $(this).val();
        if (!role || !rolePresets[role]) return;
        var p = rolePresets[role];
        $('#mf-label').val(p.label);
        $('#mf-type').val(p.type).trigger('change');
        $('#mf-placeholder').val(p.placeholder);
        $('#mf-maxlen').val(p.maxlen);
        $('#mf-required').prop('checked', p.required);
    });

    
    // Save step names and educational files (attr نه data — بعد از renumber کش jQuery کهنه است)
    $(document).on('click', '#cgs-save-step-meta', function(e){
        e.preventDefault();
        var meta = {};
        function stepKey($el) {
            return String($el.attr('data-step') || $el.data('step') || '');
        }
        $('#cgs-step-meta-cards .cgs-step-card').each(function(){
            var step = stepKey($(this));
            if (!step) return;
            meta[step] = { name: '', icon: '', icon_url: '', columns: 2, files: [] };
            var $c = $(this);
            meta[step].name = $c.find('.cgs-step-name').val() || '';
            meta[step].icon = $c.find('.cgs-step-icon').val() || '';
            meta[step].icon_url = $c.find('.cgs-step-icon-url').val() || '';
            meta[step].columns = parseInt($c.find('.cgs-step-columns').val(), 10) || 2;
            $c.find('.cgs-step-file-item').each(function(){
                meta[step].files.push({
                    url: $(this).attr('data-url') || $(this).data('url') || '',
                    type: $(this).attr('data-type') || $(this).data('type') || 'image',
                    title: $(this).attr('data-title') || $(this).data('title') || ''
                });
            });
            if (meta[step].files.length) meta[step].image = meta[step].files[0].url;
        });
        if (!Object.keys(meta).length) {
            $('#cgs-step-meta-msg').text('هیچ مرحله‌ای برای ذخیره نیست').css('color','#b45309');
            return;
        }
        $('#cgs-step-meta-msg').text('در حال ذخیره...').css('color','#666');
        $.ajax({
            url: cgsAdmin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cgs_save_step_meta',
                nonce: cgsAdmin.nonce,
                type_key: currentType,
                meta: JSON.stringify(meta)
            }
        }).done(function(res){
            var msg = res.success ? ( (res.data && res.data.message) ? res.data.message : 'ذخیره شد ✓' ) : (res.data || 'خطا');
            $('#cgs-step-meta-msg').text(msg).css('color', res.success ? 'green' : 'red');
            if (res.success) setTimeout(function(){ location.reload(); }, 500);
        }).fail(function(xhr){
            var t = (xhr && xhr.responseText) ? xhr.responseText.substring(0, 120) : '';
            $('#cgs-step-meta-msg').text('خطای ارتباط' + (t ? ': '+t : '')).css('color','red');
        });
    });

    // Media library: add image/PDF to step
    $(document).on('click', '.cgs-add-step-file', function(e){
        e.preventDefault();
        var step = $(this).data('step');
        var $list = $('.cgs-step-files[data-step="'+step+'"]');
        if (typeof wp === 'undefined' || !wp.media) {
            alert('کتابخانه رسانه وردپرس بارگذاری نشده است.');
            return;
        }
        var frame = wp.media({
            title: 'انتخاب تصویر یا PDF آموزشی',
            button: { text: 'افزودن' },
            multiple: true,
            library: { type: ['image', 'application/pdf'] }
        });
        frame.on('select', function(){
            var selection = frame.state().get('selection');
            selection.each(function(att){
                att = att.toJSON();
                var type = (att.mime === 'application/pdf' || (att.url && att.url.indexOf('.pdf') !== -1)) ? 'pdf' : 'image';
                var html = '<div class="cgs-step-file-item" data-url="'+att.url+'" data-type="'+type+'" data-title="'+(att.title||'')+'">';
                if (type === 'pdf') html += '<a href="'+att.url+'" target="_blank">📄 '+(att.filename||'PDF')+'</a> ';
                else html += '<img src="'+att.url+'" alt="" style="height:36px;width:auto;border-radius:4px;vertical-align:middle;"> ';
                html += '<button type="button" class="button-link cgs-remove-step-file" style="color:#c00;">حذف</button></div>';
                $list.append(html);
            });
        });
        frame.open();
    });
    
    // ── مراحل فرم: کارت‌ها (افزودن / حذف / جابجایی / ویرایش) ──
    var CGS_STEP_ICONS = {
        'user':'کاربر','users':'کاربران','phone':'تلفن','mobile':'موبایل','id-card':'کارت ملی',
        'map':'آدرس','calendar':'تاریخ','bank':'بانک','file':'فایل','camera':'تصویر',
        'lock':'امنیت','home':'خانه','mail':'ایمیل','building':'شرکت','money':'مالی',
        'shield':'تضمین','edit':'ویرایش','star':'ستاره','check':'تأیید','success':'موفقیت'
    };

    function cgsStepIconOptions(selected) {
        var html = '<option value="">— بدون —</option>';
        Object.keys(CGS_STEP_ICONS).forEach(function(ic){
            html += '<option value="'+ic+'"'+(selected===ic?' selected':'')+'>'+CGS_STEP_ICONS[ic]+'</option>';
        });
        return html;
    }

    function cgsBuildStepCard(stepNum, data) {
        data = data || {};
        var name = data.name || '';
        var icon = data.icon || '';
        var iconUrl = data.icon_url || '';
        var cols = parseInt(data.columns, 10) || 2;
        if (cols < 1) cols = 1;
        if (cols > 6) cols = 6;
        var colOpts = '';
        for (var i = 1; i <= 6; i++) {
            colOpts += '<option value="'+i+'"'+(cols===i?' selected':'')+'>'+i+' ستون</option>';
        }
        var html = '';
        html += '<div class="cgs-step-card" data-step="'+stepNum+'" style="border:1px solid #e2e8f0;border-radius:12px;padding:12px;background:#fff;font-size:12px;">';
        html += '<div class="cgs-step-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid #eef2ff;">';
        html += '<span class="cgs-step-drag-handle" title="جابجایی" style="cursor:grab;color:#94a3b8;font-size:16px;">☰</span>';
        html += '<div class="cgs-step-card-title" style="font-weight:800;color:#1a237e;flex:1;text-align:center;">مرحله '+stepNum+'</div>';
        html += '<button type="button" class="cgs-delete-step button button-small" data-step="'+stepNum+'" style="background:#dc2626;color:#fff;border:none;border-radius:6px;padding:2px 10px;font-size:11px;">حذف</button>';
        html += '</div>';
        html += '<label style="font-size:12px;display:block;">نام نمایشی</label>';
        html += '<input type="text" class="cgs-step-name" data-step="'+stepNum+'" value="'+String(name).replace(/"/g,'&quot;')+'" placeholder="مثلاً اطلاعات شخصی" style="width:100%;margin-bottom:6px;padding:6px 8px;border-radius:8px;border:1px solid #cbd5e1;">';
        html += '<label style="font-size:12px;display:block;">آیکن</label>';
        html += '<select class="cgs-step-icon" data-step="'+stepNum+'" style="width:100%;margin-bottom:4px;padding:6px;border-radius:8px;">'+cgsStepIconOptions(icon)+'</select>';
        html += '<input type="hidden" class="cgs-step-icon-url" data-step="'+stepNum+'" value="'+(iconUrl||'')+'">';
        html += '<div class="cgs-step-icon-preview" data-step="'+stepNum+'" style="min-height:22px;margin:4px 0;">';
        if (iconUrl) {
            html += '<img src="'+iconUrl+'" style="height:22px;">';
        } else if (icon) {
            html += '<span class="cgs-icon cgs-icon-'+icon+'"></span>';
        }
        html += '</div>';
        html += '<button type="button" class="cgs-btn-admin cgs-upload-step-icon" data-step="'+stepNum+'" style="font-size:11px;margin-bottom:6px;">آیکن سفارشی</button>';
        html += '<label style="font-size:12px;display:block;">تعداد ستون</label>';
        html += '<div style="display:flex;gap:6px;margin-bottom:6px;">';
        html += '<select class="cgs-step-columns" data-step="'+stepNum+'" style="flex:1;padding:6px;border-radius:8px;">'+colOpts+'</select>';
        html += '<button type="button" class="cgs-btn-admin cgs-apply-cols-all" data-step="'+stepNum+'" style="font-size:11px;">به همه</button>';
        html += '</div>';
        html += '<label style="font-size:12px;display:block;">فایل آموزشی</label>';
        html += '<div class="cgs-step-files" data-step="'+stepNum+'"></div>';
        html += '<button type="button" class="cgs-btn-admin cgs-add-step-file" data-step="'+stepNum+'" style="font-size:11px;margin-top:4px;">+ تصویر/PDF</button>';
        html += '</div>';
        return html;
    }

    /** شماره‌گذاری مجدد کارت‌ها بعد از حذف یا جابجایی */
    function cgsRenumberStepCards() {
        var n = 1;
        $('#cgs-step-meta-cards .cgs-step-card').each(function(){
            var $c = $(this);
            $c.attr('data-step', n).removeData('step');
            $c.find('[data-step]').each(function(){
                $(this).attr('data-step', n).removeData('step');
            });
            $c.find('.cgs-step-card-title').text('مرحله ' + n);
            $c.find('.cgs-delete-step').attr('data-step', n).removeData('step');
            n++;
        });
        var $mf = $('#mf-step');
        if ($mf.length) {
            var max = Math.max(1, n - 1);
            var cur = $mf.val();
            $mf.empty();
            for (var i = 1; i <= Math.max(max, 15); i++) {
                $mf.append('<option value="'+i+'">مرحله '+i+'</option>');
            }
            if (cur) $mf.val(cur);
        }
    }

    function cgsInitStepCardsSortable() {
        /* بازیابی از 4.0.3 — jQuery UI */
        var $box = $('#cgs-step-meta-cards');
        if (!$box.length || typeof $.fn.sortable !== 'function') return;
        var el = $box[0];
        if (el._cgsSortable) { try { el._cgsSortable.destroy(); } catch(e){} el._cgsSortable=null; }
        if ($box.hasClass('ui-sortable')) {
            try { $box.sortable('destroy'); } catch (e) {}
        }
        $box.sortable({
            items: '.cgs-step-card',
            handle: '.cgs-step-drag-handle',
            cancel: 'input, textarea, select, button, a, label',
            placeholder: 'cgs-step-sortable-placeholder',
            tolerance: 'pointer',
            forcePlaceholderSize: true,
            distance: 5,
            update: function() {
                if (typeof cgsRenumberStepCards === 'function') cgsRenumberStepCards();
                $('#cgs-step-meta-msg').text('ترتیب عوض شد — ذخیره مراحل را بزنید').css('color', '#b45309');
            }
        });
        document.body.classList.remove('cgs-is-sorting');
        $('#cgs-step-meta-cards input, #cgs-step-meta-cards select, #cgs-step-meta-cards textarea')
            .prop('disabled', false).css({pointerEvents:'auto', opacity:1});
    }



    $(document).on('click', '#cgs-add-step-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        var $box = $('#cgs-step-meta-cards');
        if (!$box.length) {
            alert('بخش مراحل پیدا نشد. صفحه را رفرش کنید (Ctrl+Shift+R).');
            return;
        }
        $box.find('.cgs-step-empty-msg').remove();
        var maxStep = 0;
        $box.find('.cgs-step-card').each(function(){
            maxStep = Math.max(maxStep, parseInt($(this).attr('data-step'), 10) || 0);
        });
        if (maxStep >= 20) {
            alert('حداکثر ۲۰ مرحله مجاز است.');
            return;
        }
        var next = maxStep + 1;
        $box.append(cgsBuildStepCard(next, { columns: 2 }));
        cgsInitStepCardsSortable();
        // افزودن گزینه به select مرحله فیلد
        var $mf = $('#mf-step');
        if ($mf.length && !$mf.find('option[value="'+next+'"]').length) {
            $mf.append('<option value="'+next+'">مرحله '+next+'</option>');
        }
        $('#cgs-step-meta-msg').text('مرحله '+next+' اضافه شد — نام بدهید و ذخیره کنید').css('color', '#1565c0');
        var $new = $box.find('.cgs-step-card[data-step="'+next+'"]');
        if ($new.length) {
            $new[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            $new.find('.cgs-step-name').focus();
        }
    });

    // Step icon live preview + custom upload
    
    // Live sync step name/icon → preview indicators
    $(document).on('input change', '#st-form-title-text,#st-form-title-font,#st-form-title-size,#st-form-title-color,#st-form-title-border,#st-form-title-bw,#st-form-title-shadow,#st-form-title-anim,#st-form-title-icon,#st-form-title-icon-size,#st-form-title-bg-type,#st-form-title-bg-color,#st-form-title-bg-media', function(){
        if (typeof applyFormStyles === 'function') applyFormStyles();
        else if (typeof injectPreviewStyles === 'function') injectPreviewStyles();
        else if (typeof applyStylesToPreview === 'function') applyStylesToPreview();
        // direct apply
        var $h = $('#cgs-live-preview .cgs-form-title');
        if ($h.length && $('#st-form-title-text').length) {
            var icon = $('#st-form-title-icon').val()||'';
            var titleText = $('#st-form-title-text').val();
            if (titleText) {
                var html = (icon ? '<span style="font-size:'+(parseInt($('#st-form-title-icon-size,#st-form-title-bg-type,#st-form-title-bg-color,#st-form-title-bg-media').val(),10)||24)+'px;margin-left:6px;">'+ $('<span>').text(icon).html() +'</span>' : '') + $('<span>').text(titleText).html();
                $h.html(html);
            }
            $h.css({fontFamily:$('#st-form-title-font').val()||'',fontSize:(parseInt($('#st-form-title-size').val(),10)||20)+'px',color:$('#st-form-title-color').val()||'#1a237e'});
        }
    });

    $(document).on('input change', '.cgs-step-name, .cgs-step-icon', function(){
        var step = String($(this).data('step') || $(this).closest('.cgs-step-card').data('step') || '');
        if (!step) return;
        var name = $('.cgs-step-name[data-step="'+step+'"]').val() || ('مرحله ' + step);
        var icon = $('.cgs-step-icon[data-step="'+step+'"]').val() || '';
        var iconUrl = $('.cgs-step-icon-url[data-step="'+step+'"]').val() || '';
        var $ind = $('#cgs-live-preview .cgs-step-indicator[data-step="'+step+'"]');
        if (!$ind.length) {
            // try by index
            var idx = parseInt(step,10) - 1;
            $ind = $('#cgs-live-preview .cgs-step-indicator').eq(idx);
        }
        if ($ind.length) {
            var label = name;
            if (iconUrl) {
                $ind.html('<img src="'+iconUrl+'" style="height:14px;vertical-align:middle;margin-left:4px;"> ' + $('<span>').text(label).html());
            } else if (icon) {
                $ind.html('<span class="cgs-icon cgs-icon-'+icon+'"></span> ' + $('<span>').text(label).html());
            } else {
                $ind.text(label);
            }
        }
        // also step heading inside form step
        var $head = $('#cgs-live-preview .cgs-form-step[data-step="'+step+'"] .cgs-step-heading, #cgs-live-preview .cgs-form-step[data-step="'+step+'"] .cgs-step-title');
        if ($head.length) $head.text(name);
    });

$(document).on('change', '.cgs-step-icon', function(){
        var step = $(this).data('step');
        var val = $(this).val();
        var $prev = $('.cgs-step-icon-preview[data-step="'+step+'"]');
        $('.cgs-step-icon-url[data-step="'+step+'"]').val('');
        if (val) {
            $prev.html('<span class="cgs-icon cgs-icon-lg cgs-icon-'+val+'"></span>');
        } else {
            $prev.empty();
        }
    });
    $(document).on('click', '.cgs-upload-step-icon', function(e){
        e.preventDefault();
        var step = $(this).data('step');
        if (typeof wp === 'undefined' || !wp.media) { alert('رسانه وردپرس بارگذاری نشده'); return; }
        var frame = wp.media({
            title: 'آیکن سفارشی مرحله (SVG یا PNG)',
            button: { text: 'انتخاب آیکن' },
            multiple: false,
            library: { type: ['image'] }
        });
        frame.on('select', function(){
            var att = frame.state().get('selection').first().toJSON();
            $('.cgs-step-icon-url[data-step="'+step+'"]').val(att.url);
            $('.cgs-step-icon[data-step="'+step+'"]').val('');
            $('.cgs-step-icon-preview[data-step="'+step+'"]').html('<img src="'+att.url+'" alt="" style="height:28px;width:auto;vertical-align:middle;">');
        });
        frame.open();
    });
    $(document).on('click', '.cgs-clear-step-icon', function(e){
        e.preventDefault();
        var step = $(this).data('step');
        $('.cgs-step-icon[data-step="'+step+'"]').val('');
        $('.cgs-step-icon-url[data-step="'+step+'"]').val('');
        $('.cgs-step-icon-preview[data-step="'+step+'"]').empty();
    });

    $(document).on('click', '.cgs-apply-cols-all', function(e){
        e.preventDefault();
        var cols = $(this).closest('.cgs-step-card').find('.cgs-step-columns').val();
        window.cgsConfirm('تعداد ستون «' + cols + '» روی همه مراحل اعمال شود؟', 'اعمال ستون').then(function(ok){
            if (!ok) return;
            $('.cgs-step-columns').val(cols);
            $('#cgs-step-meta-msg').text('اعمال شد — ذخیره را بزنید').css('color','#b45309');
        });
    });
    $(document).on('click', '.cgs-delete-step', function(e){
        e.preventDefault();
        var $card = $(this).closest('.cgs-step-card');
        var step = $card.data('step') || $card.attr('data-step');
        var $box = $('#cgs-step-meta-cards');
        window.cgsConfirm('مرحله ' + step + ' حذف شود؟ (فیلدهای این مرحله را جداگانه جابه‌جا یا حذف کنید)', 'حذف مرحله').then(function(ok){
            if (!ok) return;
            $card.remove();
            if (!$box.find('.cgs-step-card').length) {
                $box.append('<p class="cgs-step-empty-msg" style="grid-column:1/-1;color:#666;">هنوز مرحله‌ای نیست. دکمه «+ مرحله» را بزنید.</p>');
            } else {
                cgsRenumberStepCards();
            }
            $('#cgs-step-meta-msg').text('حذف شد — ذخیره مراحل را بزنید').css('color','#b45309');
        });
    });

    // فعال‌سازی sortable کارت‌های مرحله هنگام بارگذاری
    cgsInitStepCardsSortable();

    $(document).on('click', '.cgs-remove-step-file', function(e){
        e.preventDefault();
        var $item = $(this).closest('.cgs-step-file-item');
        window.cgsConfirm('این فایل آموزشی حذف شود؟', 'حذف فایل').then(function(ok){
            if (ok) $item.remove();
        });
    });

    // Copy shortcode
    $(document).on('click', '#cgs-copy-shortcode', function(e){
        e.preventDefault();
        var text = $('#cgs-shortcode-text').text().trim();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function(){
                $('#cgs-copy-msg').fadeIn().delay(1500).fadeOut();
            });
        } else {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            $('#cgs-copy-msg').fadeIn().delay(1500).fadeOut();
        }
    });

    // Status change
    $(document).on('change', '.cgs-status-change', function(){
        var $el = $(this);
        if (!confirm('وضعیت تغییر کند؟')) return;
        $.post(cgsAdmin.ajax_url, {
            action: 'cgs_update_status',
            nonce: cgsAdmin.nonce,
            application_id: $el.data('id'),
            status: $el.val()
        }).done(function(res){
            if (res.success) location.reload();
            else alert(res.data || 'خطا');
        });
    });




    // ========== Interactive Preview (no real save) ==========
    
    
    function cgsGetUiVolume() {
        var v = 40;
        if ($('#st-sound-volume').length) v = parseInt($('#st-sound-volume').val(), 10);
        else if (typeof cgsAdmin !== 'undefined' && cgsAdmin.sound_volume !== undefined) v = parseInt(cgsAdmin.sound_volume, 10);
        if (isNaN(v)) v = 40;
        return Math.max(0, Math.min(100, v)) / 100 * 0.45;
    }
    function cgsSoundEnabled() {
        if ($('#st-btn-sound').length) return $('#st-btn-sound').val() !== '0';
        if (typeof cgsAdmin !== 'undefined' && cgsAdmin.sound_enabled == 0) return false;
        return true;
    }
    function cgsPlayToneSequence(freqs, type, vol) {
        try {
            if (!vol || vol <= 0) return;
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            if (ctx.state === 'suspended') ctx.resume();
            freqs.forEach(function(freq, i) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = type || 'sine';
                osc.frequency.value = freq;
                var start = ctx.currentTime + i * 0.11;
                gain.gain.setValueAtTime(0, start);
                gain.gain.linearRampToValueAtTime(vol, start + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.01, start + 0.28);
                osc.start(start);
                osc.stop(start + 0.32);
            });
        } catch (e) {}
    }
    function cgsPlaySuccessSound() {
        if (!cgsSoundEnabled()) return;
        var vol = cgsGetUiVolume();
        var st = ($('#st-sound-type').val() || 'chime');
        var map = {
            chime: [523.25, 659.25, 783.99],
            bell: [784, 988, 1175],
            success: [392, 523, 659, 784],
            sparkle: [1046, 1318, 1568],
            coin: [987, 1318],
            ding: [880, 1174],
            double: [659, 659, 880],
            rising: [440, 554, 659, 880],
            levelup: [523, 659, 784, 1046],
            fanfare: [392, 523, 659, 784, 1046],
            glass: [1200, 1600],
            harp: [329, 415, 523, 659]
        };
        cgsPlayToneSequence(map[st] || map.chime, st === 'glass' ? 'triangle' : 'sine', vol);
    }
    /** صدای خطا — جدا از صدای موفقیت */
    function cgsPlayErrorSound() {
        if (!cgsSoundEnabled()) return;
        var vol = cgsGetUiVolume() * 0.9;
        // زنگ wrong: دو بوق پایین کوتاه
        cgsPlayToneSequence([220, 180], 'square', vol);
    }
    window.cgsPlaySuccessSound = cgsPlaySuccessSound;
    window.cgsPlayErrorSound = cgsPlayErrorSound;

function initPreviewForm() {
        var $wrap = $('.cgs-preview-mode');
        if (!$wrap.length) return;

        var $steps = $wrap.find('.cgs-form-step');
        var total = $steps.length;
        var currentIdx = 0;

        // Ensure first step visible
        $steps.removeClass('active').hide();
        if (total) {
            $steps.eq(0).addClass('active').show();
        }

        // Init jalali datepickers in preview
        if (typeof cgsBindDatepickers === 'function') { try { cgsBindDatepickers($wrap); } catch(e) {} }
        if (typeof CGSJalaliPicker !== 'undefined') {
            $wrap.find('input.cgs-jalali-date, input.cgs-datepicker').each(function(){
                if (!$(this).data('jdp-init')) {
                    try { new CGSJalaliPicker($(this)); $(this).data('jdp-init', 1); } catch(e) {}
                }
            });
        }

        function goToIndex(idx) {
            if (idx < 0 || idx >= total) return;
            $steps.removeClass('active').hide();
            var $cur = $steps.eq(idx).addClass('active').show();
            currentIdx = idx;
            var pct = total ? Math.round(((idx + 1) / total) * 100) : 0;
            $wrap.find('.cgs-progress-fill').css('width', pct + '%');
            $wrap.find('.cgs-step-indicator').each(function(i){
                $(this).removeClass('active done');
                if (i < idx) $(this).addClass('done');
                if (i === idx) $(this).addClass('active');
            });
            // map resize when step changes
            setTimeout(function(){
                if (window.cgsIranMap && typeof window.cgsIranMap.resize === 'function') {
                    window.cgsIranMap.resize();
                }
                if (typeof L !== 'undefined' && window.cgsIranMap) {
                    try { window.cgsIranMap.resize(); } catch(e) {}
                }
            }, 200);
        }

        function validateCurrent() {
            var errors = [];
            var $step = $steps.eq(currentIdx);
            // Preview: soft validation — only mark empty required, don't block if data-preview skip
            $step.find('[required]').each(function(){
                var $el = $(this);
                var val = $el.val();
                var label = $el.closest('.cgs-field-group, .cgs-field-card').find('label').first().clone().children().remove().end().text().replace('*','').trim();
                if (!val || (typeof val === 'string' && !val.trim())) {
                    $el.css('border-color','#c62828');
                    errors.push({label: label || 'فیلد', message: 'این فیلد الزامی است.'});
                } else {
                    $el.css('border-color','');
                }
            });
            return errors;
        }

        function showErrors(errors) {
            var html = '<div class="cgs-error-box"><div class="cgs-error-header">لطفاً موارد زیر را اصلاح کنید (یا برای تست خالی بگذارید و دوباره «مرحله بعد» را بزنید تا رد شود)</div><table class="cgs-error-table"><thead><tr><th>فیلد</th><th>خطا</th></tr></thead><tbody>';
            errors.forEach(function(e){
                html += '<tr><td>'+e.label+'</td><td>'+e.message+'</td></tr>';
            });
            html += '</tbody></table></div>';
            $wrap.find('.cgs-form-message').removeClass('success').addClass('error').html(html).show();
        }

        // Preview navigation: first click with errors shows them; second click skips (test mode)
        $wrap.off('click.cgsPrevNav').on('click.cgsPrevNav', '.cgs-next-step', function(e){
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            var errors = validateCurrent();
            if (errors.length && !$btn.data('force-next')) {
                showErrors(errors);
                try { cgsPlayErrorSound(); } catch(err) {}
                $btn.data('force-next', 1);
                $btn.text('رد کردن و مرحله بعد (تست)');
                return;
            }
            // موفقیت: فقط وقتی واقعاً می‌رود مرحله بعد
            try { cgsPlaySuccessSound(); } catch(err) {}
            $btn.data('force-next', 0);
            $btn.text($('#st-btn-next').val() || 'مرحله بعد');
            $wrap.find('.cgs-form-message').hide();
            if (currentIdx < total - 1) goToIndex(currentIdx + 1);
        });

        $wrap.off('click.cgsPrevBack').on('click.cgsPrevBack', '.cgs-prev-step', function(e){
            e.preventDefault();
            e.stopPropagation();
            if ($('#st-btn-sound').val() === '1' || (typeof cgsAdmin !== 'undefined' && cgsAdmin.sound_enabled)) {
                try { cgsPlaySuccessSound(); } catch(err) {}
            }
            $wrap.find('.cgs-form-message').hide();
            if (currentIdx > 0) goToIndex(currentIdx - 1);
        });

        $wrap.on('click', '#cgs-preview-submit', function(e){
            e.preventDefault();
            var errors = validateCurrent();
            if (errors.length) { showErrors(errors); return; }
            if ($('#st-btn-sound').val() !== '0') { try { cgsPlaySuccessSound(); } catch(err) {} }
            $wrap.find('.cgs-form-message').removeClass('error').addClass('success').html(
                '<div class="cgs-celebrate">' +
                '<div class="cgs-celebrate-icon">👍</div>' +
                '<div class="cgs-celebrate-title">تبریک!</div>' +
                '<div class="cgs-celebrate-text">تست موفق بود — در حالت واقعی درخواست ثبت می‌شد.</div>' +
                '<div class="cgs-celebrate-sub">هیچ اطلاعاتی ذخیره نشد (حالت آزمایشی)</div>' +
                '</div>'
            ).show();
        });

        // Province cascade in preview (object cities)
        $wrap.off('change.cgsProvPreview').on('change.cgsProvPreview', 'select.cgs-province, select[data-role="province"]', function(){
            var province = $(this).val();
            var locations = window.cgsLocations || (typeof cgsAdmin !== 'undefined' ? cgsAdmin.locations : {}) || {};
            var $city = $wrap.find('select.cgs-city, select[data-role="city"]');
            $city.empty().append('<option value="">انتخاب شهر...</option>');
            var entry = locations[province];
            var pcode = entry && entry.code ? entry.code : '';
            var cities = (entry && entry.cities) ? entry.cities : (Array.isArray(entry) ? entry : []);
            cities.forEach(function(city){
                var name = (typeof city === 'string') ? city : (city.name || '');
                var code = (typeof city === 'object' && city.code) ? city.code : pcode;
                if (!name) return;
                $city.append($('<option></option>').val(name).attr('data-code', code).text(name));
            });
            $wrap.find('input.cgs-area-code, input[data-role="area_code"]').val(pcode).prop('readonly', true);
            if (window.cgsIranMap && typeof window.cgsIranMap.showCity === 'function') {
                /* wait for city */
            }
        });
        $wrap.off('change.cgsCityPreview').on('change.cgsCityPreview', 'select.cgs-city, select[data-role="city"]', function(){
            var name = $(this).val();
            var code = $(this).find('option:selected').attr('data-code') || '';
            $wrap.find('input.cgs-area-code, input[data-role="area_code"]').val(code).prop('readonly', true);
            if (name && window.cgsIranMap && typeof window.cgsIranMap.showCity === 'function') {
                var prov = $wrap.find('select.cgs-province').val() || '';
                window.cgsIranMap.showCity(name, prov);
            }
        });

        // Numeric only + max length enforcement
        $wrap.on('input', 'input, textarea', function(){
            var $el = $(this);
            var maxLen = parseInt($el.attr('maxlength') || $el.data('maxlength') || 0, 10);
            var charset = $el.data('charset') || '';
            var val = this.value;
            if (charset === 'numeric' || $el.hasClass('cgs-numeric') || $el.hasClass('cgs-tel') || $el.attr('inputmode') === 'numeric') {
                val = val.replace(/[^0-9]/g, '');
            } else if (charset === 'alpha') {
                val = val.replace(/[^\u0600-\u06FFa-zA-Z\s]/g, '');
            }
            if (maxLen > 0 && val.length > maxLen) {
                val = val.substring(0, maxLen);
            }
            if (this.value !== val) this.value = val;
        });

        // Province -> city + area code (object cities with .name/.code)
        var locations = window.cgsLocations || (typeof cgsAdmin !== 'undefined' ? cgsAdmin.locations : {}) || {};
        function cgsSetArea($root, code) {
            code = code || '';
            $root.find('input.cgs-area-code, input[data-role="area_code"], input[name="area_code"]').val(code).prop('readonly', true);
            $root.find('.cgs-area-code-display, .cgs-area-code-box').text(code || '—');
        }
        $wrap.off('change.cgsProv').on('change.cgsProv', 'select.cgs-province, select[data-role="province"], select[name="province"]', function(){
            var province = $(this).val();
            var $city = $wrap.find('select.cgs-city, select[data-role="city"], select[name="city"]');
            $city.empty().append('<option value="">انتخاب شهر...</option>');
            var entry = locations[province];
            var cities = [];
            var pcode = '';
            if (entry) {
                pcode = entry.code || '';
                if (Array.isArray(entry)) cities = entry;
                else if (entry.cities) cities = entry.cities;
            }
            cities.forEach(function(city){
                var name = (typeof city === 'string') ? city : (city.name || '');
                var code = (typeof city === 'object' && city.code) ? city.code : pcode;
                if (!name) return;
                $city.append($('<option></option>').val(name).attr('data-code', code).text(name));
            });
            cgsSetArea($wrap, pcode);
        });
        $wrap.off('change.cgsCity').on('change.cgsCity', 'select.cgs-city, select[data-role="city"], select[name="city"]', function(){
            var code = $(this).find('option:selected').attr('data-code') || '';
            cgsSetArea($wrap, code);
        });
    }

    // Live style preview on any change
    function cgsLiveStyleUpdate() {
        if (typeof window.cgsApplyPreviewStyles === 'function') {
            window.cgsApplyPreviewStyles(cgsCollectStylesFromUI()); if (window.cgsForcePreviewButtons) window.cgsForcePreviewButtons();
        if (window.cgsForceTitleBg) window.cgsForceTitleBg();
        }
    }
    if (typeof cgsLiveStyleUpdate === 'function') { try { cgsLiveStyleUpdate(); } catch(e){} }
    $(document).on('input change', '#st-label-font,#st-label-size,#st-label-weight,#st-label-style,#st-label-decoration,#st-label-align,#st-input-font,#st-input-size,#st-input-weight,#st-input-style,#st-input-decoration,#st-input-align,#st-color-label,#st-color-required,#st-color-input,#st-color-border,#st-color-bg,#st-color-button,#st-btn-color,#st-btn-font,#st-btn-font-size,#st-btn-template,#st-button-radius,#st-button-style,#st-btn-next,#st-btn-prev,#st-btn-submit,#st-form-bg,#st-form-bg-op,#st-form-bg-effect,#st-label-position,#st-form-columns,#st-field-gap,#st-label-width,#st-shadow-form,#st-shadow-field,#st-shadow-btn,#st-btn-hover,#st-btn-sound,#st-btn-align,#st-button-style,#st-label-position,#st-form-columns,#st-field-gap,#st-label-width,#st-shadow-form,#st-shadow-field,#st-shadow-btn,#st-btn-hover,#st-btn-sound,#st-btn-align,#st-button-style,#st-sound-type,#st-sound-volume', cgsLiveStyleUpdate);
    // wpColorPicker iris events
    $(document).on('change', '.cgs-color-picker, .wp-color-picker', cgsLiveStyleUpdate);
    $(document).on('click', '.iris-palette, .iris-square-value, .iris-picker', function(){
        setTimeout(cgsLiveStyleUpdate, 50);
    });

$(document).on('click', '#st-form-bg-browse', function(e){
        e.preventDefault();
        if (typeof wp === 'undefined' || !wp.media) { alert('رسانه وردپرس در دسترس نیست'); return; }
        var frame = wp.media({ title: 'تصویر پس‌زمینه فرم', button: { text: 'انتخاب' }, multiple: false });
        frame.on('select', function(){
            var url = frame.state().get('selection').first().toJSON().url;
            $('#st-form-bg').val(url);
            $('#st-form-bg-preview').html('<img src="'+url+'" alt="" style="max-height:80px;border-radius:8px;border:1px solid #e2e8f0;">');
            if (typeof cgsLiveStyleUpdate === 'function') cgsLiveStyleUpdate();
        });
        frame.open();
    });
    $(document).on('click', '#st-form-bg-clear', function(e){
        e.preventDefault();
        $('#st-form-bg').val('');
        $('#st-form-bg-preview').empty();
        if (typeof cgsLiveStyleUpdate === 'function') cgsLiveStyleUpdate();
    });
    $(document).on('change input', '#st-title-position,#st-btn-position,#st-btn-position-scope,#st-btn-template,#st-btn-size,#st-btn-fullwidth,#st-btn-align,#st-btn-anim,#st-btn-mt,#st-btn-mb,#st-btn-mx,#st-btn-ml,#st-btn-mr,#st-btn-gap', function(){
        if (typeof cgsLiveStyleUpdate === 'function') cgsLiveStyleUpdate();
        else if (typeof window.cgsApplyPreviewStyles === 'function' && typeof cgsCollectStylesFromUI === 'function') window.cgsApplyPreviewStyles(cgsCollectStylesFromUI()); if (window.cgsForcePreviewButtons) window.cgsForcePreviewButtons();
        if (window.cgsForceTitleBg) window.cgsForceTitleBg();
    });

$(document).ready(function(){
        initPreviewForm();
        setTimeout(function(){
            if (typeof initPreviewLayoutSortable === 'function') initPreviewLayoutSortable();
        }, 200);
        setTimeout(function(){
            if (typeof window.cgsApplyPreviewStyles === 'function' && typeof cgsCollectStylesFromUI === 'function') {
                window.cgsApplyPreviewStyles(cgsCollectStylesFromUI()); if (window.cgsForcePreviewButtons) window.cgsForcePreviewButtons();
        if (window.cgsForceTitleBg) window.cgsForceTitleBg();
            }
        }, 400);
        if ($.fn.wpColorPicker) {
            $('.cgs-color-picker').each(function(){
                var $el = $(this);
                if ($el.hasClass('wp-color-picker')) return;
                $el.wpColorPicker({
                    change: function() {
                        setTimeout(function(){
                            if (typeof cgsLiveStyleUpdate === 'function') cgsLiveStyleUpdate();
                            else if (typeof window.cgsApplyPreviewStyles === 'function') window.cgsApplyPreviewStyles(cgsCollectStylesFromUI()); if (window.cgsForcePreviewButtons) window.cgsForcePreviewButtons();
        if (window.cgsForceTitleBg) window.cgsForceTitleBg();
                        }, 20);
                    },
                    clear: function() {
                        setTimeout(function(){
                            if (typeof cgsLiveStyleUpdate === 'function') cgsLiveStyleUpdate();
                        }, 20);
                    }
                });
            });
        }
    });


    // ===== Preview DnD — فقط یک بار، بدون Sortable دوم =====
    function cgsInitPreviewLayoutDnD() {
        initPreviewLayoutSortable();
    }



    $(document).on('click', '#cgs-live-preview .cgs-set-width', function(e){
        e.preventDefault();
        e.stopPropagation();
        var w = String($(this).data('w'));
        var $fg = $(this).closest('.cgs-field-group');
        $fg.removeClass('cgs-w-25 cgs-w-33 cgs-w-50 cgs-w-100').addClass('cgs-w-' + w).attr('data-width', w);
        $fg.find('.cgs-set-width').removeClass('is-on');
        $(this).addClass('is-on');
        $('#cgs-layout-msg').text('عرض تغییر کرد — ذخیره چیدمان را بزنید').css('color','#b45309');
    });

    $(document).on('click', '#cgs-btn-save-layout', function(e){
        e.preventDefault();
        var items = [];
        $('#cgs-live-preview .cgs-field-group[data-field-id]').each(function(i){
            items.push({
                id: $(this).data('field-id'),
                width: $(this).attr('data-width') || '100',
                sort_order: i + 1
            });
        });
        if (!items.length) {
            alert('فیلدی برای ذخیره نیست');
            return;
        }
        $('#cgs-layout-msg').text('در حال ذخیره...').css('color','#666');
        $.post(cgsAdmin.ajax_url, {
            action: 'cgs_save_layout',
            nonce: cgsAdmin.nonce,
            type_key: currentType,
            items: items
        }).done(function(res){
            $('#cgs-layout-msg').text(res.success ? 'چیدمان ذخیره شد ✓' : (res.data || 'خطا')).css('color', res.success ? 'green' : 'red');
            if (res.success) {
                // refresh left field list order optionally
                setTimeout(function(){ location.reload(); }, 600);
            }
        }).fail(function(){
            $('#cgs-layout-msg').text('خطای ارتباط').css('color','red');
        });
    });

    // Init DnD after preview ready
    $(function(){
        setTimeout(cgsInitPreviewLayoutDnD, 500);
    });


    // Preview: change columns for a step
    $(document).on('click', '.cgs-preview-apply-cols-LEGACY-DISABLED', function(e){
        e.preventDefault();
        e.stopPropagation();
        var $btn = $(this);
        var step = $btn.data('step');
        var $bar = $btn.closest('.cgs-step-col-bar');
        var cols = parseInt($bar.find('.cgs-preview-step-cols').val(), 10) || 2;
        var $step = $btn.closest('.cgs-form-step');
        var $fields = $step.find('.cgs-step-fields').first();
        if (!$fields.length) $fields = $btn.closest('.cgs-step-fields');
        $fields.attr('data-step-cols', cols);
        $fields[0].style.setProperty('--cgs-cols', String(cols));
        $fields.attr('style',
            'display:grid !important;' +
            'grid-template-columns:repeat(' + cols + ',minmax(0,1fr)) !important;' +
            'gap:12px !important;' +
            '--cgs-cols:' + cols + ';'
        );
        if (typeof initPreviewLayoutSortable === 'function') {
            setTimeout(initPreviewLayoutSortable, 50);
        }
        $('.cgs-step-columns[data-step="'+step+'"]').val(String(cols));
        $('#cgs-layout-msg').text('ستون مرحله ' + step + ' = ' + cols + ' — «ذخیره مراحل» را بزنید').css('color','#b45309');
        // persist columns into step meta via quick ajax if available
        if (typeof cgsAdmin !== 'undefined') {
            var meta = {};
            $('.cgs-step-name').each(function(){
                var s = String($(this).data('step'));
                if (!meta[s]) meta[s] = {};
                meta[s].name = $(this).val();
            });
            $('.cgs-step-columns').each(function(){
                var s = String($(this).data('step'));
                if (!meta[s]) meta[s] = {};
                meta[s].columns = parseInt($(this).val(),10)||2;
            });
            meta[String(step)] = meta[String(step)] || {};
            meta[String(step)].columns = cols;
        }
    });
    // Mouse resize (restored from 4.0.3)
    (function(){
        var resizing = null, startX = 0, startW = 0, $parent = null;
        $(document).on('mousedown', '#cgs-live-preview .cgs-resize-handle', function(e){
            e.preventDefault();
            e.stopPropagation();
            var $fg = $(this).closest('.cgs-field-group, .cgs-field-card');
            resizing = $fg;
            $parent = $fg.parent();
            startX = e.pageX;
            startW = $fg.outerWidth();
            $fg.addClass('is-resizing');
            if ($parent.hasClass('ui-sortable')) {
                try { $parent.sortable('disable'); } catch (err) {}
            }
            $('body').css('cursor', 'ew-resize');
        });
        $(document).on('mousemove.cgsResize', function(e){
            if (!resizing) return;
            e.preventDefault();
            var parentW = $parent.innerWidth() || 1;
            // RTL: حرکت ماوس به چپ = عرض بیشتر
            var dx = startX - e.pageX;
            var pct = Math.round(((startW + dx) / parentW) * 100);
            pct = Math.max(15, Math.min(100, pct));
            resizing
                .removeClass('cgs-w-25 cgs-w-33 cgs-w-50 cgs-w-100')
                .attr('data-width', String(pct))
                .css({
                    width: pct + '%',
                    flex: '0 0 ' + pct + '%',
                    maxWidth: '100%',
                    '--cgs-fw': pct + '%'
                });
            resizing[0].style.setProperty('--cgs-fw', pct + '%');
            resizing.find('.cgs-width-badge').text(pct + '٪');
        });
        $(document).on('mouseup.cgsResize', function(){
            if (!resizing) return;
            var $fg = resizing;
            var pct = parseInt($fg.attr('data-width'), 10) || 100;
            $fg.removeClass('is-resizing');
            $fg[0].style.setProperty('--cgs-fw', pct + '%');
            $fg.css({ width: pct + '%', flex: '0 0 ' + pct + '%' });
            if ($parent && $parent.hasClass('ui-sortable')) {
                try { $parent.sortable('enable'); } catch (err) {}
            }
            $('body').css('cursor', '');
            $('#cgs-layout-msg').text('عرض ' + pct + '٪ — در حال ذخیره...').css('color', '#666');
            // ذخیره فوری عرض در دیتابیس تا با رفرش برنگردد
            var fid = $fg.data('field-id') || $fg.attr('data-field-id');
            if (fid && typeof cgsAdmin !== 'undefined') {
                var items = [{ id: String(fid), width: String(pct), sort_order: String($fg.index() + 1) }];
                $.ajax({
                    url: cgsAdmin.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'cgs_save_layout',
                        nonce: cgsAdmin.nonce,
                        type_key: (typeof currentType !== 'undefined' ? currentType : ''),
                        items: items
                    }
                }).done(function(res){
                    $('#cgs-layout-msg').text(res.success ? ('عرض ' + pct + '٪ ذخیره شد ✓') : 'خطا در ذخیره عرض').css('color', res.success ? 'green' : 'red');
                }).fail(function(){
                    $('#cgs-layout-msg').text('خطای ارتباط — دکمه ذخیره چیدمان را بزنید').css('color', 'red');
                });
            } else {
                $('#cgs-layout-msg').text('عرض تغییر کرد — «ذخیره چیدمان» را بزنید').css('color', '#b45309');
            }
            resizing = null;
            $parent = null;
        });
    })();





    // موقعیت لیبل: اعمال فوری روی پیش‌نمایش + همگام‌سازی همه سلکت‌ها
    $(document).on('change', '#cgs-label-position, #st-label-position, select[name="label_position"], #mf-label-position, [data-style-key="label_position"]', function(){
        var v = $(this).val() || 'beside';
        $('#cgs-label-position, #st-label-position, select[name="label_position"], [data-style-key="label_position"]').not(this).val(v);
        var $prev = $('#cgs-live-preview');
        $prev.removeClass('cgs-labels-beside cgs-labels-above');
        $prev.addClass(v === 'beside' ? 'cgs-labels-beside' : 'cgs-labels-above');
        $prev.attr('data-label-pos', v);
        if (typeof cgsLiveStyleUpdate === 'function') {
            try { cgsLiveStyleUpdate(); } catch(e) {}
        }
        $('#cgs-layout-msg').text('موقعیت برچسب: ' + (v === 'beside' ? 'کنار فیلد' : 'بالای فیلد')).css('color', 'green');
    });
    // مقدار اولیه لیبل از استایل ذخیره‌شده
    $(function(){
        var $sel = $('#cgs-label-position, select[name="label_position"], [data-style-key="label_position"]').first();
        var v = $sel.length ? $sel.val() : 'beside';
        var $prev = $('#cgs-live-preview');
        if ($prev.length) {
            $prev.removeClass('cgs-labels-beside cgs-labels-above');
            $prev.addClass((v === 'above') ? 'cgs-labels-above' : 'cgs-labels-beside');
        }
    });



    $(document).on('click', '#cgs-btn-apply-template', function(e){
        e.preventDefault();
        var id = $('#cgs-template-select').val();
        if (!id) { alert('یک قالب از لیست انتخاب کنید'); return; }
        var replace = $('#cgs-tpl-replace').is(':checked') ? 1 : 0;
        var title = $('#cgs-template-select option:selected').text();
        var msg = replace
            ? ('فیلدهای فعلی این نوع فرم حذف و قالب «' + title + '» جایگزین شود؟')
            : ('فیلدهای قالب «' + title + '» به فرم اضافه شوند؟');
        var doApply = function(){
            $('#cgs-tpl-msg').text('در حال اعمال قالب...').css('color','#666');
            $.post(cgsAdmin.ajax_url, {
                action: 'cgs_apply_template',
                nonce: cgsAdmin.nonce,
                template_id: id,
                type_key: currentType,
                replace: replace
            }).done(function(res){
                if (res && res.success) {
                    $('#cgs-tpl-msg').text((res.data && res.data.message) ? res.data.message : 'اعمال شد').css('color','green');
                    setTimeout(function(){ window.location.reload(); }, 600);
                } else {
                    var err = (res && res.data) ? (typeof res.data === 'string' ? res.data : (res.data.message || JSON.stringify(res.data))) : 'خطای ناشناخته';
                    $('#cgs-tpl-msg').text(err).css('color','red');
                    alert(err);
                }
            }).fail(function(xhr){
                var err = 'خطای ارتباط (' + (xhr.status||'?') + ')';
                $('#cgs-tpl-msg').text(err).css('color','red');
                alert(err);
            });
        };
        if (window.cgsConfirm) {
            window.cgsConfirm(msg, 'اعمال قالب').then(function(ok){ if (ok) doApply(); });
        } else if (window.confirm(msg)) {
            doApply();
        }
    });

    $(document).on('click', '#cgs-btn-delete-template', function(e){
        e.preventDefault();
        var id = $('#cgs-template-select').val();
        if (!id) { alert('قالبی برای حذف انتخاب نشده'); return; }
        var title = $('#cgs-template-select option:selected').text();
        var msg = 'قالب «' + title + '» برای همیشه حذف شود؟ این عمل قابل بازگشت نیست.';
        var doDel = function(){
            $('#cgs-tpl-msg').text('در حال حذف...').css('color','#666');
            $.post(cgsAdmin.ajax_url, {
                action: 'cgs_delete_template',
                nonce: cgsAdmin.nonce,
                id: id
            }).done(function(res){
                if (res && res.success) {
                    $('#cgs-template-select option:selected').remove();
                    $('#cgs-template-select').val('');
                    $('#cgs-tpl-msg').text('حذف شد').css('color','green');
                } else {
                    alert((res && res.data) ? res.data : 'خطا در حذف');
                }
            });
        };
        if (window.cgsConfirm) {
            window.cgsConfirm(msg, 'تأیید حذف قالب').then(function(ok){ if (ok) doDel(); });
        } else if (window.confirm(msg)) {
            doDel();
        }
    });



    // ذخیره قالب: هم‌نام → UPDATE؛ نسخه جدید → INSERT با version++
    $(document).on('click', '#cgs-btn-save-template', function(e){
        e.preventDefault();
        var name = ($('#cgs-template-name').val() || '').trim();
        var selectedId = $('#cgs-template-select').val() || '';
        var asNew = $('#cgs-tpl-new-version').is(':checked');
        if (!name && selectedId) {
            var optText = ($('#cgs-template-select option:selected').text() || '').trim();
            name = optText.replace(/\s*v\d+\s*$/i, '').replace(/^\s*★\s*/, '').trim();
            if (name) $('#cgs-template-name').val(name);
        }
        if (!name) { alert('نام قالب را وارد کنید'); return; }
        var msg = asNew
            ? ('نسخه جدید از «' + name + '» ساخته شود؟')
            : ('قالب «' + name + '» ذخیره/به‌روزرسانی شود؟ (هم‌نام = جایگزینی)');
        window.cgsConfirm(msg, 'ذخیره قالب').then(function(ok){
            if (!ok) return;
            $('#cgs-tpl-msg').text('در حال ذخیره...').css('color','#666');
            $.ajax({
                url: cgsAdmin.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'cgs_save_template',
                    nonce: cgsAdmin.nonce,
                    name: name,
                    type_key: currentType,
                    parent_id: selectedId || 0,
                    template_id: selectedId || 0,
                    as_new_version: asNew ? 1 : 0
                }
            }).done(function(res){
                if (res.success) {
                    $('#cgs-tpl-msg').text((res.data && res.data.message) ? res.data.message : 'ذخیره شد ✓').css('color','green');
                    setTimeout(function(){
                        var url = window.location.href.replace(/#.*$/, '');
                        if (url.indexOf('type=') === -1 && typeof currentType !== 'undefined') {
                            url += (url.indexOf('?')>=0?'&':'?') + 'type=' + encodeURIComponent(currentType);
                        }
                        window.location.href = url;
                    }, 600);
                } else {
                    var err = (typeof res.data === 'string') ? res.data : (res.data && res.data.message) || 'خطا در ذخیره';
                    $('#cgs-tpl-msg').text(err).css('color','red');
                    alert(err);
                }
            }).fail(function(xhr){
                $('#cgs-tpl-msg').text('خطای ارتباط ('+(xhr.status||'?')+')').css('color','red');
            });
        });
    });


    function cgsLoadTemplateVersions(id) {
        if (!id) {
            $('#cgs-tpl-version-panel').hide();
            return;
        }
        $.post(cgsAdmin.ajax_url, {
            action: 'cgs_template_versions',
            nonce: cgsAdmin.nonce,
            id: id
        }).done(function(res){
            if (!res.success) {
                alert(res.data || 'خطا');
                return;
            }
            var rows = res.data.versions || [];
            var html = '<table class="widefat striped" style="border-radius:10px;overflow:hidden;"><thead><tr><th>نسخه</th><th>نام</th><th>منبع</th><th>تاریخ</th><th>عملیات</th></tr></thead><tbody>';
            if (!rows.length) {
                html += '<tr><td colspan="5">نسخه‌ای نیست</td></tr>';
            }
            rows.forEach(function(r){
                html += '<tr>';
                html += '<td><strong>v' + (r.version_num||1) + '</strong></td>';
                html += '<td>' + $('<div>').text(r.name||'').html() + '</td>';
                html += '<td>' + (r.source==='custom'?'سفارشی':'پیش‌فرض') + '</td>';
                html += '<td dir="ltr">' + (r.updated_at||'') + '</td>';
                html += '<td>';
                html += '<button type="button" class="cgs-btn-admin cgs-btn-admin-primary cgs-ver-apply" data-id="'+r.id+'" style="padding:4px 10px;font-size:11px;margin-left:4px;">اعمال</button>';
                html += '<button type="button" class="cgs-btn-admin cgs-btn-admin-danger cgs-ver-del" data-id="'+r.id+'" style="padding:4px 10px;font-size:11px;">حذف</button>';
                html += '</td></tr>';
            });
            html += '</tbody></table>';
            $('#cgs-tpl-versions').html(html);
            $('#cgs-tpl-version-panel').show();
        });
    }
    $(document).on('click', '#cgs-btn-tpl-versions', function(e){
        e.preventDefault();
        var id = $('#cgs-template-select').val();
        if (!id) { alert('ابتدا یک قالب انتخاب کنید'); return; }
        cgsLoadTemplateVersions(id);
    });
    $(document).on('change', '#cgs-template-select', function(){
        var $opt = $(this).find('option:selected');
        var val = $(this).val();
        var txt = ($opt.text() || '').trim();
        var glabel = String($opt.closest('optgroup').attr('label') || '');
        if (val && txt && glabel.indexOf('سفارشی') !== -1) {
            var clean = txt.replace(/\s*v\d+\s*$/i, '').replace(/^\s*★\s*/, '').trim();
            if (clean) $('#cgs-template-name').val(clean);
        }
        if ($('#cgs-tpl-version-panel').is(':visible')) {
            cgsLoadTemplateVersions(val);
        }
    });
    $(document).on('click', '.cgs-ver-apply', function(){
        var id = $(this).data('id');
        $('#cgs-template-select').val(String(id));
        $('#cgs-btn-apply-template').trigger('click');
    });
    $(document).on('click', '.cgs-ver-del', function(){
        var id = $(this).data('id');
        var self = this;
        var go = function(){
            $.post(cgsAdmin.ajax_url, { action:'cgs_delete_template', nonce:cgsAdmin.nonce, id:id }).done(function(res){
                if (res.success) {
                    cgsLoadTemplateVersions($('#cgs-template-select').val());
                    $('#cgs-template-select option[value="'+id+'"]').remove();
                } else alert(res.data||'خطا');
            });
        };
        if (window.cgsConfirm) window.cgsConfirm('این نسخه حذف شود؟','حذف نسخه').then(function(ok){ if(ok) go(); });
        else if (confirm('حذف شود؟')) go();
    });


    // Image upload preview in form builder preview
    $(document).on('change', '#cgs-live-preview input[type=file]', function(){
        var input = this;
        var $card = $(input).closest('.cgs-field-group, .cgs-field-card, .cgs-field-control');
        $card.find('.cgs-file-thumb').remove();
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];
        var $box = $('<div class="cgs-file-thumb" style="margin-top:8px;padding:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;"></div>');
        $box.append($('<div></div>').text(file.name + ' (' + Math.round(file.size/1024) + ' KB)'));
        if (file.type && file.type.indexOf('image/') === 0) {
            var url = URL.createObjectURL(file);
            $box.prepend($('<img>', { src: url, css: { maxWidth: '100%', maxHeight: '120px', borderRadius: '8px', display: 'block', marginBottom: '6px' } }));
        }
        $card.append($box);
    });


    // Jalali datepicker — force bind on preview + admin
    function cgsBindDatepickers(root) {
        var $root = root ? $(root) : $(document);
        if (typeof CGSJalaliPicker === 'undefined') return;
        $root.find('input.cgs-datepicker, input.cgs-jalali-date, input[data-role="birth_date"], input[name="birth_date"]').each(function(){
            var $inp = $(this);
            if ($inp.data('jdp-init')) return;
            try {
                new CGSJalaliPicker($inp);
                $inp.data('jdp-init', 1);
                $inp.attr('readonly', false);
                $inp.css('cursor', 'pointer');
            } catch (e) { console.warn('jdp', e); }
        });
    }
    $(document).on('focus click', 'input.cgs-datepicker, input.cgs-jalali-date', function(){
        if (typeof CGSJalaliPicker === 'undefined') return;
        if (!$(this).data('jdp-init')) {
            try { new CGSJalaliPicker($(this)); $(this).data('jdp-init', 1); } catch(e) {}
        }
    });
    $(function(){ setTimeout(function(){ cgsBindDatepickers(document); }, 400); });


    /* AREA CODE — admin preview (force) */
    function cgsAdminFillArea(code) {
        code = (code || '').toString().replace(/[^0-9]/g, '');
        $('#cgs-live-preview input.cgs-area-code, #cgs-live-preview input[data-role="area_code"], #cgs-live-preview input[name="area_code"], input.cgs-area-code').val(code).attr('value', code).prop('readonly', true);
        $('#cgs-live-preview .cgs-area-code-box, .cgs-area-code-display').text(code || '—');
    }
    $(document).on('change', '#cgs-live-preview select.cgs-city, #cgs-live-preview select[data-role="city"]', function(){
        var city = $(this).val();
        var code = $(this).find('option:selected').attr('data-code') || '';
        if (!code) {
            var province = $('#cgs-live-preview select.cgs-province, #cgs-live-preview select[data-role="province"]').first().val();
            var locations = window.cgsLocations || (typeof cgsAdmin !== 'undefined' ? cgsAdmin.locations : {}) || {};
            if (province && locations[province]) {
                code = locations[province].code || '';
                var cities = locations[province].cities || [];
                for (var i = 0; i < cities.length; i++) {
                    var c = cities[i];
                    if (typeof c === 'object' && c.name === city && c.code) { code = c.code; break; }
                }
            }
        }
        cgsAdminFillArea(code);
    });
    $(document).on('change', '#cgs-live-preview select.cgs-province, #cgs-live-preview select[data-role="province"]', function(){
        var province = $(this).val();
        var locations = window.cgsLocations || (typeof cgsAdmin !== 'undefined' ? cgsAdmin.locations : {}) || {};
        var code = (locations[province] && locations[province].code) ? locations[province].code : '';
        cgsAdminFillArea(code);
    });

    /* IMAGE PREVIEW — always next to file field */
    $(document).on('change', '#cgs-live-preview input[type=file], input.cgs-file-input', function(){
        var input = this;
        var $input = $(input);
        var targetId = $input.data('preview-target');
        var $card = $input.closest('.cgs-field-group, .cgs-field-card, .cgs-upload-row, .cgs-field-control');
        var $prev = targetId ? $('#' + targetId) : $card.find('.cgs-upload-preview').first();
        if (!$prev.length) {
            $prev = $('<div class="cgs-upload-preview" style="width:110px;height:110px;border:2px dashed #c5cae9;border-radius:12px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#f8fafc;margin-top:8px;font-size:11px;color:#94a3b8;">پیش‌نمایش</div>');
            $card.append($prev);
        }
        $prev.empty().text('پیش‌نمایش تصویر');
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];
        if (file.type && file.type.indexOf('image/') === 0) {
            var url = URL.createObjectURL(file);
            $prev.empty().append($('<img>', {src:url, css:{width:'100%',height:'100%',objectFit:'cover'}}));
        } else if (/\.pdf$/i.test(file.name) || file.type === 'application/pdf') {
            $prev.empty().css({color:'#1a237e',fontWeight:700,textAlign:'center',padding:'8px'}).html('📄 PDF<br><small style="font-weight:400">'+file.name+'</small>');
        } else {
            $prev.text(file.name);
        }
    });


    // When admin changes columns in step meta cards → live preview grid
    $(document).on('change', '.cgs-step-columns-LEGACY', function(){
        var cols = parseInt($(this).val(), 10) || 2;
        var step = $(this).data('step') || $(this).closest('.cgs-step-card').data('step');
        var $fields = $('#cgs-live-preview .cgs-form-step[data-step="'+step+'"] .cgs-step-fields');
        if (!$fields.length) {
            $fields = $('#cgs-live-preview .cgs-step-fields').eq((parseInt(step,10)||1)-1);
        }
        if (!$fields.length) $fields = $('#cgs-live-preview .cgs-step-fields');
        $fields.each(function(){
            this.style.setProperty('--cgs-cols', String(cols));
            $(this).attr('data-step-cols', cols).attr('style',
                'display:grid !important;grid-template-columns:repeat('+cols+',minmax(0,1fr)) !important;gap:12px !important;');
        });
        $('.cgs-preview-step-cols').filter(function(){
            return String($(this).closest('[data-step]').data('step')||'') === String(step);
        }).val(cols);
    });


    /* Unified column handlers (override duplicates) */
    $(document).off('click.cgsCols').on('click.cgsCols', '.cgs-preview-apply-cols, .cgs-apply-cols', function(e){
        e.preventDefault(); e.stopPropagation();
        var $btn = $(this);
        var step = $btn.data('step') || $btn.closest('[data-step]').data('step');
        var cols = parseInt($btn.closest('.cgs-step-col-bar, .cgs-step-card').find('.cgs-preview-step-cols, .cgs-step-columns').first().val(), 10) || 2;
        cgsApplyStepColumns(cols, step);
        $('#cgs-layout-msg').text('ستون مرحله ' + (step||'') + ' = ' + cols).css('color','#1a237e');
    });
    $(document).off('click.cgsColsAll').on('click.cgsColsAll', '.cgs-preview-apply-cols-all, .cgs-apply-cols-all', function(e){
        e.preventDefault();
        var cols = parseInt($(this).closest('.cgs-step-col-bar, .cgs-step-card').find('.cgs-preview-step-cols, .cgs-step-columns').first().val(), 10) || 2;
        cgsApplyStepColumns(cols, null);
        $('.cgs-step-columns, .cgs-preview-step-cols').val(String(cols));
        $('#cgs-layout-msg').text('همه مراحل: ' + cols + ' ستون').css('color','#1a237e');
    });
    $(document).off('change.cgsColsMeta').on('change.cgsColsMeta', '.cgs-step-columns, .cgs-preview-step-cols', function(){
        var cols = parseInt($(this).val(), 10) || 2;
        var step = $(this).data('step') || $(this).closest('[data-step]').data('step');
        cgsApplyStepColumns(cols, step);
    });

    /* Landline area code — single force path */
    function cgsForceAreaCode(code) {
        code = String(code || '').replace(/[^0-9]/g, '');
        var $inputs = $('#cgs-live-preview input.cgs-area-code, #cgs-live-preview input[data-role="area_code"], #cgs-live-preview input[name="area_code"], input.cgs-area-code, input[data-role="area_code"]');
        $inputs.val(code).attr('value', code).prop('readonly', true).trigger('input');
        $('#cgs-live-preview .cgs-area-code-box, .cgs-area-code-display').text(code || '—');
    }
    $(document).off('change.cgsForceCity').on('change.cgsForceCity', 'select.cgs-city, select[data-role="city"], select[name="city"]', function(){
        var $opt = $(this).find('option:selected');
        var code = $opt.attr('data-code') || '';
        if (!code) {
            var locations = window.cgsLocations || (typeof cgsAdmin !== 'undefined' ? cgsAdmin.locations : {}) || {};
            var province = $(this).closest('form, .cgs-form-wrapper, .cgs-preview-mode, #cgs-live-preview').find('select.cgs-province, select[data-role="province"]').first().val();
            if (province && locations[province]) {
                code = locations[province].code || '';
                var city = $(this).val();
                var cities = locations[province].cities || [];
                for (var i = 0; i < cities.length; i++) {
                    var c = cities[i];
                    if (typeof c === 'object' && c.name === city && c.code) { code = c.code; break; }
                }
            }
        }
        cgsForceAreaCode(code);
        if (typeof cgsAdminFillArea === 'function') cgsAdminFillArea(code);
        if (typeof cgsFillAreaCode === 'function') cgsFillAreaCode(code);
    });


    $(document).on('change', '#st-form-columns', function(){
        var cols = parseInt($(this).val(), 10) || 1;
        if (typeof cgsApplyStepColumns === 'function') {
            cgsApplyStepColumns(cols, null);
        }
    });


    $(function(){
        if (typeof cgsLiveStyleUpdate === 'function') {
            try { cgsLiveStyleUpdate(); } catch(e) {}
        }
        $(document).on('change', '#st-label-position', function(){
            if (typeof cgsLiveStyleUpdate === 'function') cgsLiveStyleUpdate();
        });
    });

    function cgsPreviewPlaySound() {
        var enabled = ($('#st-btn-sound').val() === '1' || $('#st-btn-sound').is(':checked'));
        if (!enabled && typeof cgsAdmin !== 'undefined' && !parseInt(cgsAdmin.sound_enabled, 10)) return;
        try {
            var ctx = window.cgsAudioCtx || (window.cgsAudioCtx = new (window.AudioContext || window.webkitAudioContext)());
            var o = ctx.createOscillator();
            var g = ctx.createGain();
            o.type = 'sine';
            o.frequency.value = 880;
            g.gain.value = 0.08;
            if (typeof cgsAdmin !== 'undefined' && cgsAdmin.sound_volume) {
                g.gain.value = Math.max(0.02, Math.min(0.3, parseInt(cgsAdmin.sound_volume, 10) / 100 * 0.25));
            }
            o.connect(g); g.connect(ctx.destination);
            o.start();
            setTimeout(function(){ try { o.stop(); } catch(e){} }, 120);
        } catch (e) {}
    }
    $(document).on('click', '#cgs-live-preview .cgs-btn, #cgs-live-preview button.cgs-btn, #cgs-preview-submit, #cgs-live-preview .cgs-next-step, #cgs-live-preview .cgs-prev-step', function(){
        cgsPreviewPlaySound();
    });
    $(document).on('click', '.cgs-table-add-row', function(e){
        e.preventDefault();
        var $wrap = $(this).closest('.cgs-dynamic-table-wrap, .cgs-field-control');
        var $tb = $wrap.find('tbody');
        if (!$tb.length) return;
        var max = parseInt($wrap.attr('data-max-rows') || $wrap.data('max-rows') || 50, 10);
        if ($tb.find('tr').length >= max) {
            alert('حداکثر ' + max + ' ردیف مجاز است');
            return;
        }
        var $last = $tb.find('tr').last();
        if ($last.length) {
            var $clone = $last.clone();
            $clone.find('input').val('');
            $tb.append($clone);
        }
    });


/* ===== ROBUST LANDLINE AREA CODE (final) ===== */
(function($){
  function locs(){
    if (typeof cgsAdmin !== 'undefined' && cgsAdmin.locations) return cgsAdmin.locations;
    if (window.cgsLocations) return window.cgsLocations;
    return {};
  }
  function findCode(province, city){
    var L = locs();
    if (!province || !L[province]) return '';
    var p = L[province];
    var fallback = p.code || '';
    var cities = p.cities || [];
    if (!city) return fallback;
    for (var i=0;i<cities.length;i++){
      var c = cities[i];
      if (typeof c === 'string') {
        if (c === city) return fallback;
      } else if (c && (c.name === city || c.title === city)) {
        return c.code || fallback;
      }
    }
    return fallback;
  }
  function fillCode(code){
    code = String(code||'').replace(/[^0-9]/g,'');
    var $inputs = $('input.cgs-area-code, input[data-role="area_code"], input[name="area_code"], #cgs-area-code-field');
    if (!$inputs.length) {
      // inject next to landline if missing
      var $land = $('input.cgs-landline, input[data-role="landline"], input[name="landline"], input[name="phone_fixed"]').first();
      if ($land.length) {
        var $box = $('<div class="cgs-area-inject" style="display:inline-block;margin-left:8px;"><label style="font-size:11px;display:block;">کد شهرستان</label><input type="text" class="cgs-area-code" name="area_code" data-role="area_code" readonly maxlength="4" style="width:70px;text-align:center;font-weight:700;background:#eef2ff;"></div>');
        $land.before($box);
        $inputs = $box.find('input');
      }
    }
    $inputs.val(code).attr('value', code).prop('readonly', true).trigger('change');
    $('.cgs-area-code-box, .cgs-area-code-display').text(code || '—');
  }
  function fillCities($prov){
    var province = $prov.val();
    var $form = $prov.closest('form, .cgs-form-wrapper, .cgs-preview-mode, body');
    var $city = $form.find('select.cgs-city, select[data-role="city"], select[name="city"]').first();
    if (!$city.length) return;
    var L = locs();
    var cities = (L[province] && L[province].cities) ? L[province].cities : [];
    var pcode = (L[province] && L[province].code) ? L[province].code : '';
    $city.empty().append($('<option></option>').val('').text('انتخاب شهر'));
    for (var i=0;i<cities.length;i++){
      var c = cities[i];
      var name = (typeof c === 'string') ? c : (c.name || c.title || '');
      var code = (typeof c === 'object' && c.code) ? c.code : pcode;
      if (name) $city.append($('<option></option>').val(name).attr('data-code', code).text(name));
    }
    fillCode(pcode);
  }
  $(document).on('change.cgsAreaFinal', 'select.cgs-province, select[data-role="province"], select[name="province"]', function(){
    fillCities($(this));
  });
  $(document).on('change.cgsAreaFinal', 'select.cgs-city, select[data-role="city"], select[name="city"]', function(){
    var code = $(this).find('option:selected').attr('data-code') || '';
    if (!code) {
      var $form = $(this).closest('form, .cgs-form-wrapper, .cgs-preview-mode, body');
      var province = $form.find('select.cgs-province, select[data-role="province"], select[name="province"]').first().val();
      code = findCode(province, $(this).val());
    }
    fillCode(code);
  });

    $(document).on('click', '#st-sound-preview', function(e){
        e.preventDefault();
        var key = $('#st-sound-type').val() || 'chime';
        var vol = parseInt($('#st-sound-volume').val(), 10) || 40;
        var patterns = {
            chime:[[880,0.12],[1320,0.12],[1760,0.18]], bell:[[660,0.2],[990,0.25]],
            success:[[523,0.1],[659,0.1],[784,0.2]], sparkle:[[1200,0.06],[1500,0.06],[1800,0.1]],
            coin:[[987,0.08],[1318,0.15]], ding:[[1000,0.2]], double:[[880,0.1],[880,0.1]],
            rising:[[400,0.08],[500,0.08],[600,0.08],[750,0.12]], levelup:[[440,0.08],[554,0.08],[659,0.08],[880,0.15]],
            fanfare:[[523,0.1],[659,0.1],[784,0.1],[1046,0.2]], glass:[[1500,0.08],[1800,0.12]], harp:[[329,0.1],[415,0.1],[523,0.1],[659,0.15]]
        };
        try {
            var ctx = window.cgsSoundCtx || (window.cgsSoundCtx = new (window.AudioContext||window.webkitAudioContext)());
            var seq = patterns[key] || patterns.chime;
            var t0 = ctx.currentTime, g0 = Math.max(0.01, vol/100) * 0.2;
            seq.forEach(function(n,i){
                var o=ctx.createOscillator(), g=ctx.createGain();
                o.type='sine'; o.frequency.value=n[0]; g.gain.value=g0;
                o.connect(g); g.connect(ctx.destination);
                var s=t0+i*0.12; o.start(s); g.gain.exponentialRampToValueAtTime(0.001,s+n[1]); o.stop(s+n[1]+0.02);
            });
        } catch(err) {}
    });

    // محاسبات جدول — شبیه اکسل (SUM/AVG/COUNT روی ستون)
    $(document).on('input change', '.cgs-dynamic-table tbody input', function(){
        var $table = $(this).closest('table');
        cgsTableRecalc($table);
    });
    $(document).on('blur', '.cgs-dynamic-table tbody input', function(){
        var v = String($(this).val()||'');
        if (v.charAt(0) !== '=') return;
        var $table = $(this).closest('table');
        var res = cgsEvalExcel(v, $table);
        if (res !== null && res !== undefined) {
            $(this).data('formula', v);
            $(this).val(res);
            cgsTableRecalc($table);
        }
    });
    function cgsEvalExcel(expr, $table){
        expr = String(expr||'').trim();
        if (expr.charAt(0) !== '=') return null;
        expr = expr.slice(1).toUpperCase();
        // SUM(A:A) / AVG(A:A)
        var m = expr.match(/^(SUM|AVG|COUNT|MIN|MAX|PRODUCT)\(([A-Z]):\1?\)$/);
        if (!m) m = expr.match(/^(SUM|AVG|COUNT|MIN|MAX|PRODUCT)\(([A-Z]):([A-Z])\)$/);
        if (m) {
            var mode = m[1].toLowerCase();
            var col = m[2].charCodeAt(0) - 65;
            var vals = [];
            $table.find('tbody tr').each(function(){
                var v = parseFloat(String($(this).find('td').eq(col).find('input').val()||'').replace(/,/g,''));
                if (!isNaN(v)) vals.push(v);
            });
            if (!vals.length) return '—';
            if (mode==='sum') return vals.reduce(function(a,b){return a+b;},0);
            if (mode==='avg') return (vals.reduce(function(a,b){return a+b;},0)/vals.length).toFixed(2);
            if (mode==='count') return vals.length;
            if (mode==='min') return Math.min.apply(null,vals);
            if (mode==='max') return Math.max.apply(null,vals);
            if (mode==='product') return vals.reduce(function(a,b){return a*b;},1);
        }
        // A1+B2 style
        expr = expr.replace(/([A-Z])(\d+)/g, function(_, colL, rowN){
            var c = colL.charCodeAt(0)-65, r = parseInt(rowN,10)-1;
            var $inp = $table.find('tbody tr').eq(r).find('td').eq(c).find('input');
            var v = parseFloat(String($inp.val()||'').replace(/,/g,''));
            return isNaN(v) ? 0 : v;
        });
        if (!/^[\d\.\+\-\*\/\(\)\s]+$/.test(expr)) return null;
        try { return Function('"use strict";return (' + expr + ')')(); } catch(e){ return null; }
    }
    function cgsTableRecalc($table){
        var $foot = $table.find('tfoot');
        if (!$foot.length) return;
        $foot.find('[data-cgs-agg]').each(function(){
            var col = parseInt($(this).data('col'), 10);
            var mode = $(this).data('cgs-agg');
            var vals = [];
            $table.find('tbody tr').each(function(){
                var $cell = $(this).find('td').eq(col).find('input');
                var n = parseFloat(String($cell.val()||'').replace(/,/g,'').replace(/[۰-۹]/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d);}));
                if (!isNaN(n)) vals.push(n);
            });
            var out = '—';
            if (vals.length) {
                if (mode === 'sum') out = vals.reduce(function(a,b){return a+b;},0);
                else if (mode === 'avg') out = (vals.reduce(function(a,b){return a+b;},0)/vals.length).toFixed(2);
                else if (mode === 'count') out = vals.length;
                else if (mode === 'min') out = Math.min.apply(null, vals);
                else if (mode === 'max') out = Math.max.apply(null, vals);
                else if (mode === 'product') out = vals.reduce(function(a,b){return a*b;},1);
            }
            $(this).text(out);
        });
    }


    // ── منطق شرطی فیلد ──
    $(document).on('change', '#cgs-cond-enabled', function(){
        $('#cgs-cond-body').toggle(this.checked);
    });
    function cgsCondRuleHtml(rule) {
        rule = rule || {};
        return '<div class="cgs-cond-rule" style="display:grid;grid-template-columns:1fr 110px 1fr auto;gap:6px;margin:4px 0;align-items:center;">'+
            '<input type="text" class="cgs-cond-field" placeholder="کلید فیلد مرجع" value="'+(rule.field||'')+'" style="width:100%;">'+
            '<select class="cgs-cond-op">'+
            ['equals|برابر','not_equals|نابرابر','contains|شامل','empty|خالی','not_empty|غیرخالی'].map(function(x){
                var p=x.split('|'); return '<option value="'+p[0]+'" '+(rule.op===p[0]?'selected':'')+'>'+p[1]+'</option>';
            }).join('')+
            '</select>'+
            '<input type="text" class="cgs-cond-value" placeholder="مقدار" value="'+(rule.value||'')+'" style="width:100%;">'+
            '<button type="button" class="button-link cgs-cond-del" style="color:#c00;">×</button></div>';
    }
    $(document).on('click', '#cgs-cond-add-rule', function(e){
        e.preventDefault();
        $('#cgs-cond-rules').append(cgsCondRuleHtml());
    });
    $(document).on('click', '.cgs-cond-del', function(){ $(this).closest('.cgs-cond-rule').remove(); });

    function cgsCollectConditions() {
        if (!$('#cgs-cond-enabled').is(':checked')) return null;
        var rules = [];
        $('#cgs-cond-rules .cgs-cond-rule').each(function(){
            rules.push({
                field: $(this).find('.cgs-cond-field').val()||'',
                op: $(this).find('.cgs-cond-op').val()||'equals',
                value: $(this).find('.cgs-cond-value').val()||''
            });
        });
        return { enabled: true, action: $('#cgs-cond-action').val()||'show', logic: $('#cgs-cond-logic').val()||'and', rules: rules };
    }
    function cgsFillConditions(cond) {
        $('#cgs-cond-rules').empty();
        if (!cond || !cond.enabled) {
            $('#cgs-cond-enabled').prop('checked', false);
            $('#cgs-cond-body').hide();
            return;
        }
        $('#cgs-cond-enabled').prop('checked', true);
        $('#cgs-cond-body').show();
        $('#cgs-cond-action').val(cond.action||'show');
        $('#cgs-cond-logic').val(cond.logic||'and');
        (cond.rules||[]).forEach(function(r){ $('#cgs-cond-rules').append(cgsCondRuleHtml(r)); });
    }
    // expose for save field
    window.cgsCollectConditions = cgsCollectConditions;
    window.cgsFillConditions = cgsFillConditions;



    // تنظیمات پیشرفته قالب
    $(document).on('click', '#cgs-tpl-save-as', function(){
        if ($('#cgs-save-template, #cgs-btn-save-template, [data-action="save-template"]').length) {
            $('#cgs-save-template, #cgs-btn-save-template').first().trigger('click');
        } else {
            var name = prompt('نام قالب جدید:');
            if (!name || typeof cgsAdmin === 'undefined') return;
            $.post(cgsAdmin.ajax_url, {
                action: 'cgs_save_current_template', nonce: cgsAdmin.nonce,
                type_key: (typeof currentType !== 'undefined' ? currentType : 'representative'),
                name: name
            }).done(function(res){
                $('#cgs-adv-tpl-msg').text(res.success ? (res.data.message||'ذخیره شد') : (res.data||'خطا')).css('color', res.success?'green':'red');
                if (res.success) setTimeout(function(){ location.reload(); }, 600);
            });
        }
    });
    $(document).on('click', '#cgs-tpl-overwrite', function(){
        var name = $('#cgs-template-select option:selected').text() || prompt('نام قالب برای بازنویسی:');
        if (!name || typeof cgsAdmin === 'undefined') return;
        $.post(cgsAdmin.ajax_url, {
            action: 'cgs_save_current_template', nonce: cgsAdmin.nonce,
            type_key: (typeof currentType !== 'undefined' ? currentType : 'representative'),
            name: name.replace(/\s*\(v\d+\).*$/,'').replace(/^\[[^\]]+\]\s*/,''),
            template_id: $('#cgs-template-select').val() || ''
        }).done(function(res){
            $('#cgs-adv-tpl-msg').text(res.success ? (res.data.message||'به‌روز شد') : (res.data||'خطا')).css('color', res.success?'green':'red');
        });
    });
    $(document).on('click', '#cgs-tpl-load-selected', function(){
        var id = $('#cgs-template-select').val();
        if (!id) { alert('قالب را از لیست انتخاب کنید'); return; }
        if ($('#cgs-load-template').length) $('#cgs-load-template').trigger('click');
        else {
            $.post(cgsAdmin.ajax_url, { action:'cgs_load_template', nonce:cgsAdmin.nonce, template_id:id }).done(function(res){
                if (res.success) location.reload();
                else alert(res.data||'خطا');
            });
        }
    });
    $(document).on('click', '#cgs-tpl-blank', function(){
        if (!confirm('فرم فعلی پاک و قالب خالی بارگذاری شود؟')) return;
        $.post(cgsAdmin.ajax_url, { action:'cgs_load_blank_template', nonce:cgsAdmin.nonce, type_key: currentType }).done(function(){ location.reload(); });
    });
    $(document).on('click', '#cgs-tpl-export-json', function(){
        if (typeof cgsAdmin === 'undefined') return;
        $.post(cgsAdmin.ajax_url, { action:'cgs_export_template_json', nonce:cgsAdmin.nonce, type_key: currentType }).done(function(res){
            if (!res.success) { alert(res.data||'خطا'); return; }
            var blob = new Blob([JSON.stringify(res.data, null, 2)], {type:'application/json'});
            var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'cgs-template.json'; a.click();
        });
    });
    $(document).on('click', '#cgs-tpl-import-json', function(){ $('#cgs-tpl-import-file').click(); });
    $(document).on('change', '#cgs-tpl-import-file', function(){
        var f = this.files && this.files[0]; if (!f) return;
        var reader = new FileReader();
        reader.onload = function(){
            $.post(cgsAdmin.ajax_url, { action:'cgs_import_template_json', nonce:cgsAdmin.nonce, type_key: currentType, json: reader.result }).done(function(res){
                $('#cgs-adv-tpl-msg').text(res.success ? 'وارد شد' : (res.data||'خطا')).css('color', res.success?'green':'red');
                if (res.success) setTimeout(function(){ location.reload(); }, 500);
            });
        };
        reader.readAsText(f);
    });


})(jQuery);


    $(document).on('click', '#st-sound-preview', function(e){
        e.preventDefault();
        var key = $('#st-sound-type').val() || 'chime';
        var vol = parseInt($('#st-sound-volume').val(), 10) || 40;
        var patterns = {
            chime:[[880,0.12],[1320,0.12],[1760,0.18]], bell:[[660,0.2],[990,0.25]],
            success:[[523,0.1],[659,0.1],[784,0.2]], sparkle:[[1200,0.06],[1500,0.06],[1800,0.1]],
            coin:[[987,0.08],[1318,0.15]], ding:[[1000,0.2]], double:[[880,0.1],[880,0.1]],
            rising:[[400,0.08],[500,0.08],[600,0.08],[750,0.12]], levelup:[[440,0.08],[554,0.08],[659,0.08],[880,0.15]],
            fanfare:[[523,0.1],[659,0.1],[784,0.1],[1046,0.2]], glass:[[1500,0.08],[1800,0.12]], harp:[[329,0.1],[415,0.1],[523,0.1],[659,0.15]]
        };
        try {
            var ctx = window.cgsSoundCtx || (window.cgsSoundCtx = new (window.AudioContext||window.webkitAudioContext)());
            var seq = patterns[key] || patterns.chime;
            var t0 = ctx.currentTime, g0 = Math.max(0.01, vol/100) * 0.2;
            seq.forEach(function(n,i){
                var o=ctx.createOscillator(), g=ctx.createGain();
                o.type='sine'; o.frequency.value=n[0]; g.gain.value=g0;
                o.connect(g); g.connect(ctx.destination);
                var s=t0+i*0.12; o.start(s); g.gain.exponentialRampToValueAtTime(0.001,s+n[1]); o.stop(s+n[1]+0.02);
            });
        } catch(err) {}
    });

    // محاسبات جدول — شبیه اکسل (SUM/AVG/COUNT روی ستون)
    $(document).on('input change', '.cgs-dynamic-table tbody input', function(){
        var $table = $(this).closest('table');
        cgsTableRecalc($table);
    });
    $(document).on('blur', '.cgs-dynamic-table tbody input', function(){
        var v = String($(this).val()||'');
        if (v.charAt(0) !== '=') return;
        var $table = $(this).closest('table');
        var res = cgsEvalExcel(v, $table);
        if (res !== null && res !== undefined) {
            $(this).data('formula', v);
            $(this).val(res);
            cgsTableRecalc($table);
        }
    });
    function cgsEvalExcel(expr, $table){
        expr = String(expr||'').trim();
        if (expr.charAt(0) !== '=') return null;
        expr = expr.slice(1).toUpperCase();
        // SUM(A:A) / AVG(A:A)
        var m = expr.match(/^(SUM|AVG|COUNT|MIN|MAX|PRODUCT)\(([A-Z]):\1?\)$/);
        if (!m) m = expr.match(/^(SUM|AVG|COUNT|MIN|MAX|PRODUCT)\(([A-Z]):([A-Z])\)$/);
        if (m) {
            var mode = m[1].toLowerCase();
            var col = m[2].charCodeAt(0) - 65;
            var vals = [];
            $table.find('tbody tr').each(function(){
                var v = parseFloat(String($(this).find('td').eq(col).find('input').val()||'').replace(/,/g,''));
                if (!isNaN(v)) vals.push(v);
            });
            if (!vals.length) return '—';
            if (mode==='sum') return vals.reduce(function(a,b){return a+b;},0);
            if (mode==='avg') return (vals.reduce(function(a,b){return a+b;},0)/vals.length).toFixed(2);
            if (mode==='count') return vals.length;
            if (mode==='min') return Math.min.apply(null,vals);
            if (mode==='max') return Math.max.apply(null,vals);
            if (mode==='product') return vals.reduce(function(a,b){return a*b;},1);
        }
        // A1+B2 style
        expr = expr.replace(/([A-Z])(\d+)/g, function(_, colL, rowN){
            var c = colL.charCodeAt(0)-65, r = parseInt(rowN,10)-1;
            var $inp = $table.find('tbody tr').eq(r).find('td').eq(c).find('input');
            var v = parseFloat(String($inp.val()||'').replace(/,/g,''));
            return isNaN(v) ? 0 : v;
        });
        if (!/^[\d\.\+\-\*\/\(\)\s]+$/.test(expr)) return null;
        try { return Function('"use strict";return (' + expr + ')')(); } catch(e){ return null; }
    }
    function cgsTableRecalc($table){
        var $foot = $table.find('tfoot');
        if (!$foot.length) return;
        $foot.find('[data-cgs-agg]').each(function(){
            var col = parseInt($(this).data('col'), 10);
            var mode = $(this).data('cgs-agg');
            var vals = [];
            $table.find('tbody tr').each(function(){
                var $cell = $(this).find('td').eq(col).find('input');
                var n = parseFloat(String($cell.val()||'').replace(/,/g,'').replace(/[۰-۹]/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d);}));
                if (!isNaN(n)) vals.push(n);
            });
            var out = '—';
            if (vals.length) {
                if (mode === 'sum') out = vals.reduce(function(a,b){return a+b;},0);
                else if (mode === 'avg') out = (vals.reduce(function(a,b){return a+b;},0)/vals.length).toFixed(2);
                else if (mode === 'count') out = vals.length;
                else if (mode === 'min') out = Math.min.apply(null, vals);
                else if (mode === 'max') out = Math.max.apply(null, vals);
                else if (mode === 'product') out = vals.reduce(function(a,b){return a*b;},1);
            }
            $(this).text(out);
        });
    }


    // ── منطق شرطی فیلد ──
    $(document).on('change', '#cgs-cond-enabled', function(){
        $('#cgs-cond-body').toggle(this.checked);
    });
    function cgsCondRuleHtml(rule) {
        rule = rule || {};
        return '<div class="cgs-cond-rule" style="display:grid;grid-template-columns:1fr 110px 1fr auto;gap:6px;margin:4px 0;align-items:center;">'+
            '<input type="text" class="cgs-cond-field" placeholder="کلید فیلد مرجع" value="'+(rule.field||'')+'" style="width:100%;">'+
            '<select class="cgs-cond-op">'+
            ['equals|برابر','not_equals|نابرابر','contains|شامل','empty|خالی','not_empty|غیرخالی'].map(function(x){
                var p=x.split('|'); return '<option value="'+p[0]+'" '+(rule.op===p[0]?'selected':'')+'>'+p[1]+'</option>';
            }).join('')+
            '</select>'+
            '<input type="text" class="cgs-cond-value" placeholder="مقدار" value="'+(rule.value||'')+'" style="width:100%;">'+
            '<button type="button" class="button-link cgs-cond-del" style="color:#c00;">×</button></div>';
    }
    $(document).on('click', '#cgs-cond-add-rule', function(e){
        e.preventDefault();
        $('#cgs-cond-rules').append(cgsCondRuleHtml());
    });
    $(document).on('click', '.cgs-cond-del', function(){ $(this).closest('.cgs-cond-rule').remove(); });

    function cgsCollectConditions() {
        if (!$('#cgs-cond-enabled').is(':checked')) return null;
        var rules = [];
        $('#cgs-cond-rules .cgs-cond-rule').each(function(){
            rules.push({
                field: $(this).find('.cgs-cond-field').val()||'',
                op: $(this).find('.cgs-cond-op').val()||'equals',
                value: $(this).find('.cgs-cond-value').val()||''
            });
        });
        return { enabled: true, action: $('#cgs-cond-action').val()||'show', logic: $('#cgs-cond-logic').val()||'and', rules: rules };
    }
    function cgsFillConditions(cond) {
        $('#cgs-cond-rules').empty();
        if (!cond || !cond.enabled) {
            $('#cgs-cond-enabled').prop('checked', false);
            $('#cgs-cond-body').hide();
            return;
        }
        $('#cgs-cond-enabled').prop('checked', true);
        $('#cgs-cond-body').show();
        $('#cgs-cond-action').val(cond.action||'show');
        $('#cgs-cond-logic').val(cond.logic||'and');
        (cond.rules||[]).forEach(function(r){ $('#cgs-cond-rules').append(cgsCondRuleHtml(r)); });
    }
    // expose for save field
    window.cgsCollectConditions = cgsCollectConditions;
    window.cgsFillConditions = cgsFillConditions;



    // تنظیمات پیشرفته قالب
    $(document).on('click', '#cgs-tpl-save-as', function(){
        if ($('#cgs-save-template, #cgs-btn-save-template, [data-action="save-template"]').length) {
            $('#cgs-save-template, #cgs-btn-save-template').first().trigger('click');
        } else {
            var name = prompt('نام قالب جدید:');
            if (!name || typeof cgsAdmin === 'undefined') return;
            $.post(cgsAdmin.ajax_url, {
                action: 'cgs_save_current_template', nonce: cgsAdmin.nonce,
                type_key: (typeof currentType !== 'undefined' ? currentType : 'representative'),
                name: name
            }).done(function(res){
                $('#cgs-adv-tpl-msg').text(res.success ? (res.data.message||'ذخیره شد') : (res.data||'خطا')).css('color', res.success?'green':'red');
                if (res.success) setTimeout(function(){ location.reload(); }, 600);
            });
        }
    });
    $(document).on('click', '#cgs-tpl-overwrite', function(){
        var name = $('#cgs-template-select option:selected').text() || prompt('نام قالب برای بازنویسی:');
        if (!name || typeof cgsAdmin === 'undefined') return;
        $.post(cgsAdmin.ajax_url, {
            action: 'cgs_save_current_template', nonce: cgsAdmin.nonce,
            type_key: (typeof currentType !== 'undefined' ? currentType : 'representative'),
            name: name.replace(/\s*\(v\d+\).*$/,'').replace(/^\[[^\]]+\]\s*/,''),
            template_id: $('#cgs-template-select').val() || ''
        }).done(function(res){
            $('#cgs-adv-tpl-msg').text(res.success ? (res.data.message||'به‌روز شد') : (res.data||'خطا')).css('color', res.success?'green':'red');
        });
    });
    $(document).on('click', '#cgs-tpl-load-selected', function(){
        var id = $('#cgs-template-select').val();
        if (!id) { alert('قالب را از لیست انتخاب کنید'); return; }
        if ($('#cgs-load-template').length) $('#cgs-load-template').trigger('click');
        else {
            $.post(cgsAdmin.ajax_url, { action:'cgs_load_template', nonce:cgsAdmin.nonce, template_id:id }).done(function(res){
                if (res.success) location.reload();
                else alert(res.data||'خطا');
            });
        }
    });
    $(document).on('click', '#cgs-tpl-blank', function(){
        if (!confirm('فرم فعلی پاک و قالب خالی بارگذاری شود؟')) return;
        $.post(cgsAdmin.ajax_url, { action:'cgs_load_blank_template', nonce:cgsAdmin.nonce, type_key: currentType }).done(function(){ location.reload(); });
    });
    $(document).on('click', '#cgs-tpl-export-json', function(){
        if (typeof cgsAdmin === 'undefined') return;
        $.post(cgsAdmin.ajax_url, { action:'cgs_export_template_json', nonce:cgsAdmin.nonce, type_key: currentType }).done(function(res){
            if (!res.success) { alert(res.data||'خطا'); return; }
            var blob = new Blob([JSON.stringify(res.data, null, 2)], {type:'application/json'});
            var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'cgs-template.json'; a.click();
        });
    });
    $(document).on('click', '#cgs-tpl-import-json', function(){ $('#cgs-tpl-import-file').click(); });
    $(document).on('change', '#cgs-tpl-import-file', function(){
        var f = this.files && this.files[0]; if (!f) return;
        var reader = new FileReader();
        reader.onload = function(){
            $.post(cgsAdmin.ajax_url, { action:'cgs_import_template_json', nonce:cgsAdmin.nonce, type_key: currentType, json: reader.result }).done(function(res){
                $('#cgs-adv-tpl-msg').text(res.success ? 'وارد شد' : (res.data||'خطا')).css('color', res.success?'green':'red');
                if (res.success) setTimeout(function(){ location.reload(); }, 500);
            });
        };
        reader.readAsText(f);
    });


})(jQuery);


/* —— پیشنهادات: پیش‌نمایش موبایل + کپی ظاهر —— */
(function($){
  $(document).on('change', '#cgs-preview-device-select', function(){
    var key = $(this).val() || 'desktop';
    var map = {
      desktop: {w: null, h: null, type: 'desktop'},
      desktop_1366: {w: 1366, h: 768, type: 'desktop'},
      laptop_14: {w: 1280, h: 720, type: 'laptop'},
      laptop_15: {w: 1366, h: 768, type: 'laptop'},
      laptop_16: {w: 1536, h: 960, type: 'laptop'},
      laptop_17: {w: 1600, h: 900, type: 'laptop'},
      monitor_19: {w: 1440, h: 900, type: 'desktop'},
      monitor_21: {w: 1600, h: 900, type: 'desktop'},
      monitor_24: {w: 1920, h: 1080, type: 'desktop'},
      monitor_27: {w: 2560, h: 1440, type: 'desktop'},
      iphone_se: {w: 375, h: 667, type: 'phone'},
      iphone_13_mini: {w: 360, h: 780, type: 'phone'},
      iphone_14: {w: 390, h: 844, type: 'phone'},
      iphone_14_pro_max: {w: 430, h: 932, type: 'phone'},
      iphone_15_pro: {w: 393, h: 852, type: 'phone'},
      iphone_16_pro_max: {w: 440, h: 956, type: 'phone'},
      samsung_s21: {w: 360, h: 800, type: 'phone'},
      samsung_s23: {w: 360, h: 780, type: 'phone'},
      samsung_s24_ultra: {w: 384, h: 824, type: 'phone'},
      samsung_a54: {w: 412, h: 915, type: 'phone'},
      xiaomi_13: {w: 393, h: 873, type: 'phone'},
      xiaomi_redmi_note: {w: 393, h: 873, type: 'phone'},
      xiaomi_poco: {w: 393, h: 851, type: 'phone'},
      pixel_7: {w: 412, h: 915, type: 'phone'},
      android_small: {w: 360, h: 640, type: 'phone'},
      android_common: {w: 360, h: 800, type: 'phone'},
      tablet_port: {w: 768, h: 1024, type: 'tablet'},
      tablet_land: {w: 1024, h: 768, type: 'tablet'},
      ipad_port: {w: 820, h: 1180, type: 'tablet'},
      ipad_land: {w: 1180, h: 820, type: 'tablet'}
    };
    var conf = map[key] || map.desktop;
    var $prev = $('#cgs-live-preview');
    if (!$prev.length) return;
    $prev.removeClass('cgs-preview-mobile cgs-preview-tablet cgs-device-frame');
    $prev.attr('data-device', key);
    var isPhone = conf.type === 'phone';
    var isTablet = conf.type === 'tablet';
    if (window.cgsEnsurePhoneShell) {
      window.cgsEnsurePhoneShell(isPhone);
    }
    if (!conf.w) {
      $prev.css({maxWidth:'100%', width:'100%', height:'auto', maxHeight:'none', minHeight:'280px', marginLeft:'', marginRight:'', overflow:'visible'});
      return;
    }
    $prev.addClass('cgs-device-frame');
    if (isPhone) $prev.addClass('cgs-preview-mobile');
    if (isTablet) $prev.addClass('cgs-preview-tablet');
    var maxH = isPhone ? Math.min(conf.h, 720) : (isTablet ? Math.min(conf.h, 800) : Math.min(conf.h || 720, 720));
    var css = {
      maxWidth: conf.w + 'px',
      width: conf.w + 'px',
      marginLeft: 'auto',
      marginRight: 'auto',
      minHeight: Math.min(conf.h, maxH) + 'px',
      height: isPhone ? maxH + 'px' : 'auto',
      maxHeight: maxH + 'px',
      overflowX: 'hidden',
      overflowY: 'auto',
      boxSizing: 'border-box'
    };
    $prev.css(css);
    if (isPhone) {
      var $screen = $('#cgs-phone-shell .cgs-phone-screen');
      if ($screen.length) {
        $screen.css({height: maxH + 'px', maxHeight: maxH + 'px'});
      }
    }
  });

  // سازگاری دکمه‌های قدیمی
  $(document).on('click', '#cgs-preview-device-toggle .cgs-dev-btn', function(e){
    e.preventDefault();
    var dev = $(this).data('dev');
    $('#cgs-preview-device-toggle .cgs-dev-btn').removeClass('is-active');
    $(this).addClass('is-active');
    if ($('#cgs-preview-device-select').length) {
      $('#cgs-preview-device-select').val(dev === 'mobile' ? 'iphone_14' : 'desktop').trigger('change');
    }
  });

  $(document).on('click', '#cgs-btn-copy-styles', function(e){
    e.preventDefault();
    var from = $('#cgs-copy-styles-from').val();
    if (!from) { alert('نوع مبدأ را انتخاب کنید'); return; }
    var to = (typeof currentType !== 'undefined' && currentType) ? currentType : (window.cgsAdmin && cgsAdmin.typeKey) || '';
    if (!to) {
      // try from URL or selected type UI
      var m = location.search.match(/[?&]type=([^&]+)/);
      to = m ? decodeURIComponent(m[1]) : '';
    }
    if (!to) { alert('نوع مقصد مشخص نیست'); return; }
    var $btn = $(this).prop('disabled', true).text('...');
    $.post((window.cgsAdmin && cgsAdmin.ajaxUrl) || ajaxurl, {
      action: 'cgs_copy_form_styles',
      nonce: (window.cgsAdmin && cgsAdmin.nonce) || '',
      from_type: from,
      to_type: to
    }).done(function(res){
      if (res && res.success) {
        $('#cgs-style-msg').css('color','#166534').text('ظاهر از «'+from+'» کپی شد — صفحه را رفرش کنید یا ذخیره ظاهر بزنید.');
        if (res.data && res.data.last_save) {
          var L = res.data.last_save;
          $('#cgs-styles-last-save').text('آخرین ذخیره ظاهر: '+(L.time||'')+' — نوع: '+(L.type_key||'')+(L.copied_from?' (کپی از '+L.copied_from+')':''));
        }
        // apply to preview if styles returned
        if (res.data && res.data.styles && typeof window.cgsApplyPreviewStyles === 'function') {
          window.cgsApplyPreviewStyles(res.data.styles);
        }
        setTimeout(function(){ location.reload(); }, 600);
      } else {
        alert((res && res.data) ? res.data : 'خطا در کپی');
      }
    }).fail(function(){ alert('خطای شبکه'); })
    .always(function(){ $btn.prop('disabled', false).text('کپی به فرم فعلی'); });
  });

  // به‌روزرسانی متن آخرین ذخیره بعد از save styles
  $(document).on('cgs:styles-saved', function(ev, data){
    if (data && data.last_save) {
      var L = data.last_save;
      $('#cgs-styles-last-save').text('آخرین ذخیره ظاهر: '+(L.time||'')+' — نوع: '+(L.type_key||''));
    }
  });
})(jQuery);

(function($){
  $(document).ajaxSuccess(function(event, xhr, settings){
    try {
      if (!settings || !settings.data) return;
      var d = settings.data;
      if (typeof d === 'string' && d.indexOf('cgs_save_form_styles') !== -1) {
        var res = xhr.responseJSON;
        if (res && res.success) {
          $(document).trigger('cgs:styles-saved', [res.data || {}]);
          if (res.data && res.data.last_save) {
            var L = res.data.last_save;
            $('#cgs-styles-last-save').text('آخرین ذخیره ظاهر: '+(L.time||'')+' — نوع: '+(L.type_key||''));
          }
        }
      }
    } catch(e) {}
  });
})(jQuery);

/* اطمینان از دیده شدن پیش‌نمایش */
(function($){
  function cgsEnsurePreviewVisible(){
    var $p = $('#cgs-live-preview, .cgs-builder-col-preview');
    if (!$p.length) return;
    $p.css({display:'block',visibility:'visible',opacity:1,height:'auto',maxHeight:'none'});
    $('.cgs-builder-col-preview').css({display:'block',visibility:'visible'});
  }
  $(cgsEnsurePreviewVisible);
  $(window).on('load', cgsEnsurePreviewVisible);
  setTimeout(cgsEnsurePreviewVisible, 400);
  setTimeout(cgsEnsurePreviewVisible, 1200);
})(jQuery);

/* گالری قالب دکمه + مختصات واقعی */
(function($){
  $(document).on('click', '.cgs-tpl-swatch', function(e){
    e.preventDefault();
    var tpl = $(this).data('tpl');
    $('.cgs-tpl-swatch').removeClass('is-selected');
    $(this).addClass('is-selected');
    $('#st-btn-template').val(tpl);
    if (typeof cgsLiveStyleUpdate === 'function') cgsLiveStyleUpdate();
    else if (typeof window.cgsApplyPreviewStyles === 'function' && typeof cgsCollectStylesFromUI === 'function') {
      window.cgsApplyPreviewStyles(cgsCollectStylesFromUI()); if (window.cgsForcePreviewButtons) window.cgsForcePreviewButtons();
        if (window.cgsForceTitleBg) window.cgsForceTitleBg();
    }
  });
  $(document).on('input change', '#st-sound-volume', function(){
    $('#st-sound-vol-label').text($(this).val());
  });
})(jQuery);

(function($){
  function cgsUpdateBtnDemo(){
    var tpl = $('#st-btn-template').val() || 'flat';
    var $d = $('#cgs-btn-demo-label');
    if ($d.length) {
      $d.attr('class', 'cgs-tpl-demo cgs-demo-' + tpl);
    }
  }
  $(document).on('change', '#st-btn-template,#st-btn-color,#st-btn-font,#st-btn-font-size', function(){
    cgsUpdateBtnDemo();
    if (typeof cgsLiveStyleUpdate === 'function') cgsLiveStyleUpdate();
    else if (typeof window.cgsApplyPreviewStyles === 'function' && typeof cgsCollectStylesFromUI === 'function') {
      window.cgsApplyPreviewStyles(cgsCollectStylesFromUI()); if (window.cgsForcePreviewButtons) window.cgsForcePreviewButtons();
        if (window.cgsForceTitleBg) window.cgsForceTitleBg();
    }
  });
  $(cgsUpdateBtnDemo);
})(jQuery);

window.cgsHardReload = function(delay){
  delay = delay || 400;
  try {
    var u = new URL(window.location.href);
    if (u.searchParams.get('_cgs_reloaded') === '1') return; // جلوگیری از حلقه
  } catch(e0) {}
  setTimeout(function(){
    try {
      var u = new URL(window.location.href);
      u.searchParams.set('_cgs_r', Date.now());
      u.searchParams.set('_cgs_reloaded', '1');
      window.location.replace(u.toString());
    } catch(e) {
      window.location.href = window.location.href.split('#')[0] + (window.location.href.indexOf('?')>=0?'&':'?') + '_cgs_r=' + Date.now() + '&_cgs_reloaded=1';
    }
  }, delay);
};
/* بعد از ذخیره ظاهر / استایل / نمودار — رفرش سخت */
(function($){
  $(document).ajaxSuccess(function(ev, xhr, settings){
    try {
      if (!settings || !settings.data) return;
      var d = String(settings.data);
      if (d.indexOf('cgs_save_form_styles') !== -1 || d.indexOf('cgs_copy_form_styles') !== -1) {
        var res = xhr.responseJSON;
        if (res && res.success && typeof window.cgsHardReload === 'function') {
          // رفرش با query یکتا — شبیه‌سازی اثر Ctrl+F5 بدون دست‌کاری کش کل مرورگر
          window.cgsHardReload(500);
        }
      }
    } catch(e){}
  });
})(jQuery);

(function($){
  $(document).on('click', '#st-sound-preview', function(e){
    e.preventDefault();
    try { if (window.cgsPlaySuccessSound) cgsPlaySuccessSound(); } catch(err) {}
  });
})(jQuery);


(function($){
  $(document).on('input change', '#st-btn-color, #st-color-button, #st-btn-template, #st-btn-font, #st-btn-font-size', function(){
    if (typeof cgsLiveStyleUpdate === 'function') cgsLiveStyleUpdate();
    if (typeof window.cgsForcePreviewButtons === 'function') window.cgsForcePreviewButtons();
  });
  // wp color picker
  $(document).on('irischange change', '#st-color-button, .wp-color-picker', function(){
    setTimeout(function(){
      if (typeof window.cgsForcePreviewButtons === 'function') window.cgsForcePreviewButtons();
    }, 30);
  });
  $(function(){ setTimeout(function(){ if (window.cgsForcePreviewButtons) window.cgsForcePreviewButtons();
        if (window.cgsForceTitleBg) window.cgsForceTitleBg(); }, 400); });
})(jQuery);

(function($){
  $(document).on('click', '.cgs-browse-media', function(e){
    e.preventDefault();
    var target = $(this).data('target');
    var type = $(this).data('type') || 'image';
    if (typeof wp === 'undefined' || !wp.media) { alert('رسانه وردپرس در دسترس نیست'); return; }
    var frame = wp.media({ title: 'انتخاب فایل', multiple: false, library: { type: type === 'video' ? 'video' : 'image' } });
    frame.on('select', function(){
      var url = frame.state().get('selection').first().toJSON().url;
      $(target).val(url).trigger('change');
    });
    frame.open();
  });
  $(document).on('change', '#st-form-title-bg-file', function(){
    var f = this.files && this.files[0];
    if (!f || typeof wp === 'undefined' || !wp.media) return;
    // fallback: object URL for preview only
    var url = URL.createObjectURL(f);
    $('#st-form-title-bg-media').val(url).trigger('change');
  });
})(jQuery);

(function($){
  window.cgsForceTitleBg = function(){
    var type = $('#st-form-title-bg-type').val() || 'none';
    var color = $('#st-form-title-bg-color').val() || '#eef2ff';
    var media = $('#st-form-title-bg-media').val() || '';
    var $title = $('#cgs-live-preview .cgs-form-title');
    var $header = $('#cgs-live-preview .cgs-form-header');
    var $sub = $('#cgs-live-preview .cgs-form-subtitle');
    $header.each(function(){
      this.style.setProperty('background', 'transparent', 'important');
      this.style.setProperty('background-image', 'none', 'important');
    });
    $sub.each(function(){
      this.style.setProperty('background', 'transparent', 'important');
      this.style.setProperty('color', '#64748b', 'important');
    });
    $title.each(function(){
      var el = this;
      if (type === 'color') {
        el.style.setProperty('background', color, 'important');
        el.style.setProperty('background-image', 'none', 'important');
      } else if (type === 'image' && media) {
        el.style.setProperty('background-image', 'url('+media+')', 'important');
        el.style.setProperty('background-size', 'cover', 'important');
        el.style.setProperty('background-position', 'center', 'important');
      } else if (type === 'video' && media) {
        el.style.setProperty('background', '#0f172a', 'important');
      } else {
        el.style.setProperty('background', 'transparent', 'important');
        el.style.setProperty('background-image', 'none', 'important');
      }
      el.style.setProperty('padding', '10px 16px', 'important');
      el.style.setProperty('border-radius', '10px', 'important');
      el.style.setProperty('display', 'inline-block', 'important');
      el.style.setProperty('max-width', '100%', 'important');
    });
  };
  $(document).on('input change', '#st-form-title-bg-type,#st-form-title-bg-color,#st-form-title-bg-media', function(){
    if (window.cgsForceTitleBg) window.cgsForceTitleBg();
    if (typeof cgsLiveStyleUpdate === 'function') cgsLiveStyleUpdate();
  });
  $(function(){ setTimeout(function(){ if (window.cgsForceTitleBg) window.cgsForceTitleBg(); }, 500); });
})(jQuery);

(function($){
  window.cgsEnsurePhoneShell = function(isMobile){
    var $prev = $('#cgs-live-preview');
    if (!$prev.length) return;
    var $shell = $('#cgs-phone-shell');
    if (isMobile) {
      $('body').addClass('cgs-preview-is-mobile');
      if (!$shell.length) {
        $shell = $('<div id="cgs-phone-shell" class="cgs-phone-shell is-on">'+
          '<div class="cgs-phone-notch"></div>'+
          '<div class="cgs-phone-status"><span>9:41</span><span class="cgs-phone-brand">شهر قسط</span><span>100%</span></div>'+
          '<div class="cgs-phone-brand">City Ghest · Mobile Preview</div>'+
          '<div class="cgs-phone-screen"></div>'+
          '<div class="cgs-phone-home"></div></div>');
        $prev.after($shell);
      }
      if (!$prev.parent().hasClass('cgs-phone-screen')) {
        $shell.find('.cgs-phone-screen').append($prev.addClass('cgs-in-phone cgs-preview-mobile'));
      }
      $shell.addClass('is-on').show();
    } else {
      $('body').removeClass('cgs-preview-is-mobile');
      if ($prev.parent().hasClass('cgs-phone-screen')) {
        $('#cgs-phone-shell').after($prev.removeClass('cgs-in-phone'));
      }
      $('#cgs-phone-shell').removeClass('is-on').hide();
      $prev.removeClass('cgs-preview-mobile');
    }
  };
  /* device phone-shell: handled in main device map handler */
  $(function(){
    setTimeout(function(){
      var v = $('#cgs-preview-device-select').val() || 'desktop';
      if (v && v !== 'desktop' && window.cgsEnsurePhoneShell) {
        $('#cgs-preview-device-select').trigger('change');
      }
    }, 600);
  });
})(jQuery);

(function($){
  function cgsSyncTrialLabel(){
    var on = $('#cgs-lp-opt-trial-label').is(':checked');
    var $sub = $('#cgs-live-preview .cgs-form-subtitle');
    if (!$sub.length) return;
    if (on) {
      $sub.removeClass('cgs-sub-off is-hidden').show().css('display','');
      $sub.each(function(){ this.style.removeProperty('display'); });
    } else {
      $sub.addClass('cgs-sub-off is-hidden');
      $sub.each(function(){ this.style.setProperty('display','none','important'); });
    }
  }
  $(document).on('change', '#cgs-lp-opt-trial-label, #cgs-lp-opt-inputs', function(){
    // فقط تیک متن آزمایشی روی subtitle اثر دارد
    if (this.id === 'cgs-lp-opt-trial-label' || this.id === 'cgs-lp-opt-inputs') {
      if (this.id === 'cgs-lp-opt-inputs' && !$('#cgs-lp-opt-trial-label').length) {
        // سازگاری: اگر تیک ورود آزمایشی برداشته شد، متن هم مخفی شود
        var show = $(this).is(':checked');
        $('#cgs-live-preview .cgs-form-subtitle').each(function(){
          this.style.setProperty('display', show ? '' : 'none', 'important');
        });
      }
      cgsSyncTrialLabel();
    }
  });
  $(function(){ setTimeout(cgsSyncTrialLabel, 400); });
})(jQuery);
