(function($){
    'use strict';

    if (typeof cgsPlans === 'undefined') {
        console.error('CGS Plans: cgsPlans localize missing');
        window.cgsPlans = { ajax_url: (typeof ajaxurl!=='undefined'?ajaxurl:''), nonce:'', categories:[], styles:{}, detail_types:{} };
    }

    var detailTypes = cgsPlans.detail_types || {};
    var sortables = [];

    function dtypeOptions(selected) {
        var h = '';
        $.each(detailTypes, function(k, lab){
            h += '<option value="'+k+'"'+(k===selected?' selected':'')+'>'+lab+'</option>';
        });
        return h || '<option value="none">بدون جزئیات</option>';
    }

    function detailFieldsHtml(dtype, detail) {
        detail = detail || '';
        if (dtype === 'age_range' || dtype === 'number_range') {
            var parts = String(detail).split('-');
            return '<input type="number" class="cgs-opt-min" value="'+(parts[0]||'')+'" placeholder="حداقل" style="width:70px;"> <span>تا</span> ' +
                   '<input type="number" class="cgs-opt-max" value="'+(parts[1]||'')+'" placeholder="حداکثر" style="width:70px;">';
        }
        if (dtype === 'list') {
            return '<textarea class="cgs-opt-detail" rows="2" placeholder="هر خط یک مقدار" style="width:160px;">'+detail+'</textarea>';
        }
        if (dtype !== 'none') {
            return '<input type="text" class="cgs-opt-detail" value="'+String(detail).replace(/"/g,'&quot;')+'" placeholder="جزئیات" style="width:120px;">';
        }
        return '';
    }

    function collectDetail($item) {
        var dtype = $item.find('.cgs-opt-dtype').val() || 'none';
        if (dtype === 'age_range' || dtype === 'number_range') {
            return ($item.find('.cgs-opt-min').val()||'') + '-' + ($item.find('.cgs-opt-max').val()||'');
        }
        return $item.find('.cgs-opt-detail').val() || '';
    }

    function stepOptionsHtml(selected) {
        selected = String(selected || '1');
        var opts = [
            {v:'1', t:'هر ۱ ماه یک‌بار'},
            {v:'2', t:'هر ۲ ماه یک‌بار'},
            {v:'3', t:'هر ۳ ماه یک‌بار'},
            {v:'4', t:'هر ۴ ماه یک‌بار'},
            {v:'6', t:'هر ۶ ماه یک‌بار'}
        ];
        return opts.map(function(o){
            return '<option value="'+o.v+'"'+(selected===o.v?' selected':'')+'>'+o.t+'</option>';
        }).join('');
    }

    function addStepRow($container, step) {
        step = step || { interval: '1', rate: '' };
        var html = '<div class="cgs-step-row" style="display:grid;grid-template-columns:1.4fr 1fr auto;gap:8px;align-items:end;margin:6px 0;padding:8px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;">' +
            '<div><label style="font-size:11px;color:#64748b;">گام پرداخت</label><select class="dur-step">'+stepOptionsHtml(step.interval)+'</select></div>' +
            '<div><label style="font-size:11px;color:#64748b;">ضریب سود ماهانه %</label><input type="text" class="dur-rate" value="'+(step.rate||'')+'" placeholder="مثلاً 6.2" style="width:100%;"></div>' +
            '<div><button type="button" class="button cgs-remove-step" title="حذف گام">×</button></div></div>';
        $container.append(html);
    }

    function addDurationRow(data) {
        data = data || { months: 12, steps: [{ interval: '1', rate: '' }] };
        if (!data.steps || !data.steps.length) {
            if (data.rate || data.step_interval) {
                data.steps = [{ interval: String(data.step_interval||'1'), rate: data.rate||'' }];
            } else {
                data.steps = [{ interval: '1', rate: '' }];
            }
        }
        var html = '<div class="cgs-duration-row cgs-period-card" style="border:1px solid #c5cae9;border-radius:12px;padding:12px;margin-bottom:12px;background:#fff;box-shadow:0 2px 10px rgba(26,35,126,.06);">' +
            '<div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap;">' +
            '<div style="display:flex;align-items:center;gap:8px;"><strong style="color:#1a237e;">دوره بازپرداخت</strong>' +
            '<input type="number" class="dur-months" value="'+(data.months||12)+'" min="1" max="120" style="width:80px;"> <span>ماه</span></div>' +
            '<div style="display:flex;gap:6px;"><button type="button" class="button cgs-add-step">+ گام</button>' +
            '<button type="button" class="button cgs-remove-dur">حذف دوره</button></div></div>' +
            '<div class="cgs-steps-list"></div>' +
            '<p class="description" style="margin:6px 0 0;font-size:11px;">هر گام = فاصله پرداخت + ضریب سود همان گام برای این دوره</p></div>';
        var $row = $(html);
        var $steps = $row.find('.cgs-steps-list');
        data.steps.forEach(function(s){ addStepRow($steps, s); });
        $('#cgs-durations-list').append($row);
        updatePlanPreview();
    }

    function collectDurations() {
        var list = [];
        $('#cgs-durations-list .cgs-duration-row').each(function(){
            var months = parseInt($(this).find('.dur-months').val(),10)||0;
            var steps = [];
            $(this).find('.cgs-step-row').each(function(){
                var $s = $(this).find('.dur-step');
                steps.push({
                    interval: $s.val()||'1',
                    step_label: $s.find('option:selected').text()||'',
                    rate: $(this).find('.dur-rate').val()||''
                });
            });
            list.push({
                months: months,
                steps: steps,
                rate: steps[0] ? steps[0].rate : '',
                step_interval: steps[0] ? steps[0].interval : '1',
                step_label: steps[0] ? steps[0].step_label : ''
            });
        });
        return list;
    }

    function collectSelected() {
        var selected = {};
        $('.plan-sel-opt').each(function(){
            var cat = $(this).attr('data-cat') || $(this).data('cat');
            var oid = $(this).val();
            if (!selected[cat]) selected[cat] = {};
            var $lab = $(this).closest('label');
            var detail = '';
            if ($lab.find('.plan-det-min').length) {
                detail = ($lab.find('.plan-det-min').val()||'')+'-'+($lab.find('.plan-det-max').val()||'');
            } else if ($lab.find('.plan-det-list').length) {
                detail = $lab.find('.plan-det-list').val()||'';
            }
            selected[cat][oid] = { on: $(this).is(':checked') ? 1 : 0, detail: detail };
        });
        return selected;
    }

    function playBtnSound() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var o = ctx.createOscillator();
            var g = ctx.createGain();
            o.type = 'sine';
            o.frequency.value = 880;
            g.gain.value = 0.08;
            o.connect(g); g.connect(ctx.destination);
            o.start();
            setTimeout(function(){ o.stop(); ctx.close(); }, 120);
        } catch(e) {}
    }

    function renderStarsHtml(st) {
        var n = Math.max(1, Math.min(10, parseInt(st.star_count, 10) || 5));
        var colors = String(st.star_colors || '').split(',');
        var html = '<div class="cgs-pc-stars" style="margin:6px 0;letter-spacing:2px;">';
        for (var i = 0; i < n; i++) {
            var c = (colors[i] || colors[0] || '#f59e0b').trim();
            html += '<span style="color:'+c+';font-size:16px;">★</span>';
        }
        html += '</div>';
        return html;
    }

    function applyCardChrome($card, st, opts) {
        opts = opts || {};
        var shadow = st.card_shadow || '0 6px 24px rgba(15,23,42,0.08)';
        if (shadow === 'none') shadow = 'none';
        var css = {
            background: st.card_bg || '#fff',
            borderColor: st.card_border || '#c5cae9',
            borderRadius: (st.radius || 14) + 'px',
            color: st.text_color || '#333',
            boxShadow: shadow,
            borderWidth: '1px',
            borderStyle: 'solid',
            position: 'relative',
            overflow: 'hidden'
        };
        if (opts.featured && (st.featured_glow === '1' || st.featured_glow === 1)) {
            var fc = st.featured_color || '#4338ca';
            css.boxShadow = (shadow === 'none' ? '' : shadow + ',') + '0 0 0 3px ' + fc + '55, 0 0 28px ' + fc + '66';
            css.borderColor = fc;
        }
        $card.css(css);

        // VIP badge color
        var vipC = st.vip_badge_color || '#fbbf24';
        $card.find('.cgs-badge-vip').css({
            background: vipC,
            color: '#78350f',
            padding: '2px 8px',
            borderRadius: '6px',
            fontSize: '11px',
            fontWeight: '700'
        });

        // stars
        var $starsHost = $card.find('.cgs-pc-stars-host');
        if (!$starsHost.length) {
            $card.find('.cgs-pc-title').before('<div class="cgs-pc-stars-host"></div>');
            $starsHost = $card.find('.cgs-pc-stars-host');
        }
        if (opts.featured || opts.showStars) {
            $starsHost.html(renderStarsHtml(st)).show();
        } else {
            $starsHost.empty().hide();
        }

        // button glass / solid
        var glass = (st.glass_btn === '1' || st.glass_btn === 1) || st.btn_style === 'glass';
        var btnCss = {
            background: glass ? 'rgba(255,255,255,0.35)' : (st.btn_bg || '#1a237e'),
            color: glass ? (st.accent || '#1a237e') : '#fff',
            border: glass ? '1.5px solid rgba(255,255,255,0.55)' : 'none',
            backdropFilter: glass ? 'blur(8px)' : 'none',
            WebkitBackdropFilter: glass ? 'blur(8px)' : 'none',
            borderRadius: '10px',
            padding: '10px 16px',
            fontWeight: '700',
            cursor: 'pointer'
        };
        $card.find('.cgs-pc-btn').text(st.btn_text || 'انتخاب این طرح').css(btnCss);
    }

    function updatePlanPreview() {
        var title = $('#plan_title').val() || 'نام طرح';
        var desc = $('#plan_desc').val() || 'توضیحات طرح';
        var emoji = $('#plan_icon_emoji').val() || '📋';
        var icon = $('#plan_icon').val();
        var $card = $('#cgs-plan-preview-card');
        // اتصال قالب ظاهر اختصاصی طرح
        var st = $.extend({}, cgsPlans.styles || {});
        var did = $('#plan_design_id').val() || '';
        if (did && cgsPlans.designs && cgsPlans.designs[did] && cgsPlans.designs[did].styles) {
            st = $.extend({}, st, cgsPlans.designs[did].styles);
        }
        var isVip = $('#plan_vip').is(':checked');
        var isFeat = $('#plan_featured').is(':checked');

        applyCardChrome($card, st, { featured: isFeat, showStars: isFeat });

        var fac = parseInt($('#plan_facility_use').val(), 10) || 0;
        if (fac > 0) {
            $card.find('.cgs-pc-facility').show().text('مهلت استفاده از اعتبار: ' + fac + ' ماه');
        } else {
            $card.find('.cgs-pc-facility').hide().text('');
        }

        var badges = '';
        if (isVip) badges += '<span class="cgs-badge-vip">VIP</span> ';
        if (isFeat) badges += '<span class="cgs-badge-star" style="color:#f59e0b;">★ ویژه</span> ';
        if (!$('#plan_active').is(':checked')) badges += '<span class="cgs-badge-off">غیرفعال</span>';
        $card.find('.cgs-pc-badges').html(badges);
        $card.find('.cgs-badge-vip').css({ background: st.vip_badge_color || '#fbbf24', color:'#78350f', padding:'2px 8px', borderRadius:'6px', fontSize:'11px', fontWeight:'700' });

        $card.find('.cgs-pc-title').css({ color: st.title_color||'#1a237e', fontSize: (st.title_size||18)+'px' }).text(title);
        $card.find('.cgs-pc-desc').text(desc);

        if (st.show_icon === '0' || st.show_icon === 0) {
            $card.find('.cgs-pc-icon').hide();
        } else {
            $card.find('.cgs-pc-icon').show();
            if (icon) $card.find('.cgs-pc-icon').html('<img src="'+icon+'" alt="" style="max-height:40px;">');
            else $card.find('.cgs-pc-icon').text(emoji);
        }

        var durs = collectDurations();
        var dhtml = '';
        durs.forEach(function(d){
            dhtml += '<div class="cgs-pc-period"><strong>'+d.months+' ماه</strong>';
            if (d.steps && d.steps.length) {
                dhtml += '<ul class="cgs-pc-steps">';
                d.steps.forEach(function(s){
                    dhtml += '<li>'+(s.step_label||('هر '+s.interval+' ماه'))+' — سود <b>'+(s.rate||'—')+'٪</b></li>';
                });
                dhtml += '</ul>';
            } else {
                dhtml += ' · سود '+(d.rate||'—')+'٪ · '+(d.step_label||('فاصله '+d.step_interval));
            }
            dhtml += '</div>';
        });
        $card.find('.cgs-pc-durs').html(dhtml || '<div>مدت بازپرداخت تعریف نشده</div>');

        var extra = '';
        $('.cgs-check-grid').each(function(){
            var $grid = $(this);
            var catTitle = $grid.prevAll('h3.cgs-plan-sec').first().text();
            var items = [];
            $grid.find('.plan-sel-opt:checked').each(function(){
                var t = $(this).closest('label').find('span').first().text();
                items.push(t);
            });
            if (items.length) {
                extra += '<div style="margin-bottom:4px;"><strong>'+catTitle+':</strong> '+items.join('، ')+'</div>';
            }
        });
        $card.find('.cgs-pc-extra').html(extra);

        var meta = [];
        var useM = parseInt($('#plan_facility_use').val(),10)||0;
        if (useM > 0) meta.push('مهلت استفاده از اعتبار: '+useM+' ماه');
        if ($('#plan_prepayment').is(':checked')) meta.push('پیش‌پرداخت: دارد');
        else meta.push('پیش‌پرداخت: ندارد');
        if ($('#plan_guarantor').is(':checked')) meta.push('ضامن: لازم است');
        else meta.push('ضامن: لازم نیست');
        var minA = $('#plan_min').val(), maxA = $('#plan_max').val();
        if (parseInt(minA,10)>0 || parseInt(maxA,10)>0) meta.push('مبلغ: '+(minA||0)+' تا '+(maxA||'∞')+' ریال');
        $card.find('.cgs-pc-meta').html(meta.join('<br>'));

        // style tab preview
        var $sp = $('#cgs-style-preview-card');
        if ($sp.length) {
            applyCardChrome($sp, st, { featured: true, showStars: true });
            $sp.find('.cgs-pc-title').css({ color: st.title_color||'#1a237e', fontSize: (st.title_size||18)+'px' });
            if (!$sp.find('.cgs-pc-badges').length) {
                $sp.find('.cgs-pc-title').before('<div class="cgs-pc-badges"><span class="cgs-badge-vip">VIP</span> <span class="cgs-badge-star">★ ویژه</span></div>');
            }
            $sp.find('.cgs-badge-vip').css({ background: st.vip_badge_color||'#fbbf24', color:'#78350f', padding:'2px 8px', borderRadius:'6px', fontSize:'11px', fontWeight:'700' });
        }
    }

    function rebuildStarColorInputs() {
        var n = Math.max(1, Math.min(10, parseInt($('#ps-star-count').val(), 10) || 5));
        var $wrap = $('#ps-star-colors');
        var existing = [];
        $wrap.find('.ps-star-color').each(function(){ existing.push($(this).val()); });
        $wrap.empty();
        for (var i = 0; i < n; i++) {
            var col = existing[i] || '#f59e0b';
            $wrap.append('<input type="color" class="ps-star-color" value="'+col+'" title="ستاره '+(i+1)+'" style="width:28px;height:28px;padding:0;border:none;margin:0 2px;">');
        }
    }

    function applyStyleInputsToState() {
        var starColors = [];
        $('.ps-star-color').each(function(){ starColors.push($(this).val()||'#f59e0b'); });
        cgsPlans.styles = {
            card_bg: $('#ps-card-bg').val() || '#ffffff',
            card_border: $('#ps-card-border').val() || '#c5cae9',
            title_color: $('#ps-title-color').val() || '#1a237e',
            title_size: $('#ps-title-size').val() || '18',
            text_color: $('#ps-text-color').val() || '#333333',
            accent: $('#ps-accent').val() || '#1a237e',
            radius: $('#ps-radius').val() || '14',
            show_icon: $('#ps-show-icon').is(':checked') ? '1' : '0',
            btn_text: $('#ps-btn-text').val() || 'انتخاب این طرح',
            btn_bg: $('#ps-btn-bg').val() || '#1a237e',
            btn_style: $('#ps-btn-style').val() || 'solid',
            card_shadow: $('#ps-card-shadow').val() || '0 6px 24px rgba(15,23,42,0.08)',
            glass_btn: $('#ps-glass-btn').is(':checked') ? '1' : '0',
            btn_sound: $('#ps-btn-sound').is(':checked') ? '1' : '0',
            vip_badge_color: $('#ps-vip-badge').val() || '#fbbf24',
            star_count: String($('#ps-star-count').val() || '5'),
            star_colors: starColors.join(','),
            featured_glow: $('#ps-featured-glow').is(':checked') ? '1' : '0',
            featured_color: $('#ps-featured-color').val() || '#4338ca'
        };
        updatePlanPreview();
    }

    function resetForm() {
        $('#plan_id').val('');
        $('#plan_title,#plan_desc,#plan_icon,#plan_field_keys').val('');
        $('#plan_icon_emoji').val('📋');
        $('#plan_sort').val(0);
        $('#plan_min,#plan_max').val(0);
        $('#plan_facility_use').val(0);
        $('#plan_active').prop('checked', true);
        $('#plan_featured,#plan_vip').prop('checked', false);
        $('#plan_design_id').val('');
        $('#plan_prepayment,#plan_guarantor').prop('checked', false);
        $('.plan-sel-opt').prop('checked', false);
        $('.cgs-plan-detail-edit').hide();
        $('#cgs-durations-list').empty();
        addDurationRow({ months: 6, rate: '5.7', step_interval: '1' });
        addDurationRow({ months: 12, rate: '4.8', step_interval: '1' });
        addDurationRow({ months: 18, rate: '4.5', step_interval: '1' });
        $('#cgs-plan-editor-title').text('طرح جدید');
        $('#cgs-delete-plan').hide();
        $('.cgs-plan-item').removeClass('active');
        $('#cgs-plan-msg').text('');
        updatePlanPreview();
    }

    function loadPlan(id) {
        $.post(cgsPlans.ajax_url, { action:'cgs_get_plan', nonce:cgsPlans.nonce, id:id }).done(function(res){
            if (!res.success) { alert(res.data || 'خطا در بارگذاری طرح'); return; }
            var p = res.data;
            $('#plan_id').val(p.id);
            $('#plan_title').val(p.title||'');
            $('#plan_desc').val(p.description||'');
            $('#plan_icon_emoji').val(p.icon_emoji||'📋');
            $('#plan_icon').val(p.icon||'');
            $('#plan_sort').val(p.sort_order||0);
            $('#plan_min').val(p.min_amount||0);
            $('#plan_max').val(p.max_amount||0);
            $('#plan_facility_use').val(p.facility_use_months||12);
            $('#plan_active').prop('checked', !!p.active);
            $('#plan_featured').prop('checked', !!p.featured);
            $('#plan_vip').prop('checked', !!p.vip);
            $('#plan_design_id').val(p.design_id||'');
            $('#plan_prepayment').prop('checked', !!p.prepayment);
            $('#plan_guarantor').prop('checked', !!p.guarantor_required);
            $('#plan_status').val(p.status || (p.active ? 'published' : 'draft'));
            $('#plan_form_template_id').val(p.form_template_id || '');
            $('#plan_rule_amount_gt').val(p.rule_amount_gt || 0);
            $('#plan_rule_force_guarantor').prop('checked', !!p.rule_force_guarantor);
            $('#plan_field_keys').val((p.field_keys||[]).join(','));
            $('.plan-sel-opt').prop('checked', false);
            $('.cgs-plan-detail-edit').hide();
            var sel = p.selected || {};
            $.each(sel, function(cat, opts){
                $.each(opts, function(oid, od){
                    var on = (typeof od === 'object') ? od.on : od;
                    var det = (typeof od === 'object') ? (od.detail||'') : '';
                    var $cb = $('.plan-sel-opt[data-cat="'+cat+'"][value="'+oid+'"]');
                    $cb.prop('checked', !!on);
                    var $lab = $cb.closest('label');
                    if (on) $lab.find('.cgs-plan-detail-edit').show();
                    if (det && det.indexOf('-')!==-1 && $lab.find('.plan-det-min').length) {
                        var pp = det.split('-');
                        $lab.find('.plan-det-min').val(pp[0]); $lab.find('.plan-det-max').val(pp[1]);
                    } else if (det && $lab.find('.plan-det-list').length) {
                        $lab.find('.plan-det-list').val(det);
                    }
                });
            });
            $('#cgs-durations-list').empty();
            if (p.durations && p.durations.length) p.durations.forEach(addDurationRow);
            else addDurationRow();
            $('#cgs-plan-editor-title').text('ویرایش: '+p.title);
            $('#cgs-delete-plan').show();
            $('.cgs-plan-item').removeClass('active');
            $('.cgs-plan-item[data-id="'+id+'"]').addClass('active');
            updatePlanPreview();
        }).fail(function(){ alert('خطای شبکه در بارگذاری طرح'); });
    }

    function collectCategories() {
        var cats = [];
        $('#cgs-categories-editor .cgs-cat-block').each(function(ci){
            var $cat = $(this);
            var opts = [];
            $cat.find('.cgs-opt-item').each(function(oi){
                var $o = $(this);
                opts.push({
                    id: $o.attr('data-id') || $o.data('id') || ('opt_'+Date.now()+'_'+oi),
                    label: $o.find('.cgs-opt-label').val()||'',
                    detail_type: $o.find('.cgs-opt-dtype').val()||'none',
                    detail: collectDetail($o),
                    sort: oi+1
                });
            });
            cats.push({
                id: $cat.attr('data-id') || $cat.data('id') || ('cat_'+Date.now()+'_'+ci),
                title: $cat.find('.cgs-cat-title').val()||'دسته',
                sort: ci+1,
                options: opts
            });
        });
        return cats;
    }


    function initSortableJS() {
        // DISABLED
        document.body.classList.remove('cgs-is-sorting');
        $('#cgs-categories-editor input, #cgs-categories-editor select, #cgs-categories-editor textarea, #cgs-categories-editor button')
            .prop('disabled', false).css({pointerEvents:'auto', opacity:1, zIndex:50});
    }




    $(document).ready(function(){
        // Tabs
        $('.cgs-plans-tabs .nav-tab').on('click', function(e){
            e.preventDefault();
            var t = $(this).data('tab');
            $('.cgs-plans-tabs .nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.cgs-tab-panel').hide();
            $('#tab-'+t).show();
            if (t==='style' || t==='plans') updatePlanPreview();
            if (t==='cats') initSortableJS();
        });

        initSortableJS();

        // Category CRUD
        $('#cgs-add-category').on('click', function(){
            var id = 'cat_'+Date.now();
            var block = '<div class="cgs-cat-block" data-id="'+id+'">'+
                '<div class="cgs-cat-head"><span class="dashicons dashicons-menu cgs-cat-handle" style="cursor:grab;"></span>'+
                '<input type="text" class="cgs-cat-title" value="دسته جدید" placeholder="عنوان دسته">'+
                '<button type="button" class="button button-small cgs-add-option">+ گزینه</button>'+
                '<button type="button" class="button-link cgs-del-cat" style="color:#c00;">حذف دسته</button></div>'+
                '<ul class="cgs-opt-sortable"></ul></div>';
            $('#cgs-categories-editor').append(block);
            initSortableJS();
        });

        $(document).on('click', '.cgs-del-cat', function(){
            if (!confirm('این دسته و همه گزینه‌هایش حذف شود؟')) return;
            $(this).closest('.cgs-cat-block').remove();
        });

        $(document).on('click', '.cgs-add-option', function(){
            var id = 'opt_'+Date.now();
            var html = '<li class="cgs-opt-item" data-id="'+id+'">'+
                '<span class="dashicons dashicons-menu cgs-opt-handle" style="cursor:grab;"></span>'+
                '<input type="text" class="cgs-opt-label" value="گزینه جدید" style="min-width:140px;flex:1;">'+
                '<select class="cgs-opt-dtype">'+dtypeOptions('none')+'</select>'+
                '<span class="cgs-opt-detail-wrap"></span>'+
                '<button type="button" class="button-link cgs-del-opt" style="color:#c00;">×</button></li>';
            $(this).closest('.cgs-cat-block').find('.cgs-opt-sortable').append(html);
            initSortableJS();
        });

        $(document).on('click', '.cgs-del-opt', function(){
            $(this).closest('.cgs-opt-item').remove();
        });

        $(document).on('change', '.cgs-opt-dtype', function(){
            var $item = $(this).closest('.cgs-opt-item');
            $item.find('.cgs-opt-detail-wrap').html(detailFieldsHtml($(this).val(), ''));
        });

        $('#cgs-save-categories').on('click', function(){
            var cats = collectCategories();
            $('#cgs-cats-msg').text('در حال ذخیره...').css('color','#666');
            $.ajax({
                url: cgsPlans.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'cgs_save_plan_categories',
                    nonce: cgsPlans.nonce,
                    categories: JSON.stringify(cats)
                }
            }).done(function(res){
                $('#cgs-cats-msg').text(res.success ? 'ذخیره شد ✓ — در حال تازه‌سازی…' : (res.data||'خطا'))
                    .css('color', res.success?'green':'red');
                if (res.success) {
                    cgsPlans.categories = (res.data && res.data.categories) ? res.data.categories : cats;
                    setTimeout(function(){ location.reload(); }, 700);
                }
            }).fail(function(xhr){
                $('#cgs-cats-msg').text('خطای شبکه: '+(xhr.status||'')).css('color','red');
            });
        });

        $('#cgs-reset-categories').on('click', function(){
            if (!confirm('همه دسته‌ها به پیش‌فرض برگردند؟')) return;
            $.ajax({
                url: cgsPlans.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'cgs_save_plan_categories',
                    nonce: cgsPlans.nonce,
                    categories: '[]'
                }
            }).done(function(){ location.reload(); })
              .fail(function(){ alert('خطا در ریست'); });
        });

        $(document).on('change', '.plan-sel-opt', function(){
            var $lab = $(this).closest('label');
            if ($(this).is(':checked')) $lab.find('.cgs-plan-detail-edit').slideDown(150);
            else $lab.find('.cgs-plan-detail-edit').slideUp(150);
            updatePlanPreview();
        });

        // Styles — all controls
        $(document).on('input change', '#ps-card-bg,#ps-card-border,#ps-title-color,#ps-title-size,#ps-text-color,#ps-accent,#ps-radius,#ps-show-icon,#ps-btn-text,#ps-btn-bg,#ps-btn-style,#ps-card-shadow,#ps-glass-btn,#ps-btn-sound,#ps-vip-badge,#ps-star-count,#ps-featured-glow,#ps-featured-color,.ps-star-color', function(){
            if (this.id === 'ps-star-count') rebuildStarColorInputs();
            applyStyleInputsToState();
        });

        $(document).on('click', '.cgs-pc-btn', function(){
            if (cgsPlans.styles && (cgsPlans.styles.btn_sound === '1' || cgsPlans.styles.btn_sound === 1)) {
                playBtnSound();
            }
        });

        $('#cgs-save-plan-styles').on('click', function(){
            applyStyleInputsToState();
            $('#cgs-style-plan-msg').text('در حال ذخیره...').css('color','#666');
            $.ajax({
                url: cgsPlans.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'cgs_save_plan_styles',
                    nonce: cgsPlans.nonce,
                    styles: JSON.stringify(cgsPlans.styles)
                }
            }).done(function(res){
                $('#cgs-style-plan-msg').text(res.success?'ذخیره شد ✓':'خطا').css('color',res.success?'green':'red');
            }).fail(function(){
                $('#cgs-style-plan-msg').text('خطای شبکه').css('color','red');
            });
        });

        $('#cgs-reset-plan-styles').on('click', function(){
            if (!confirm('ظاهر به پیش‌فرض برگردد؟')) return;
            cgsPlans.styles = {
                card_bg:'#ffffff', card_border:'#c5cae9', title_color:'#1a237e', title_size:'18',
                text_color:'#333333', accent:'#1a237e', radius:'14', show_icon:'1',
                btn_text:'انتخاب این طرح', btn_bg:'#1a237e', btn_style:'solid',
                card_shadow:'0 6px 24px rgba(15,23,42,0.08)', glass_btn:'0', btn_sound:'0',
                vip_badge_color:'#fbbf24', star_count:'5',
                star_colors:'#f59e0b,#f59e0b,#f59e0b,#f59e0b,#f59e0b',
                featured_glow:'0', featured_color:'#4338ca'
            };
            $('#ps-card-bg').val('#ffffff');
            $('#ps-card-border').val('#c5cae9');
            $('#ps-title-color').val('#1a237e');
            $('#ps-title-size').val(18);
            $('#ps-text-color').val('#333333');
            $('#ps-accent').val('#1a237e');
            $('#ps-radius').val(14);
            $('#ps-show-icon').prop('checked', true);
            $('#ps-btn-text').val('انتخاب این طرح');
            $('#ps-btn-bg').val('#1a237e');
            $('#ps-btn-style').val('solid');
            $('#ps-card-shadow').val('0 6px 24px rgba(15,23,42,0.08)');
            $('#ps-glass-btn,#ps-btn-sound,#ps-featured-glow').prop('checked', false);
            $('#ps-vip-badge').val('#fbbf24');
            $('#ps-star-count').val(5);
            $('#ps-featured-color').val('#4338ca');
            rebuildStarColorInputs();
            applyStyleInputsToState();
            $.ajax({
                url: cgsPlans.ajax_url, type:'POST', dataType:'json',
                data: { action:'cgs_save_plan_styles', nonce:cgsPlans.nonce, styles: JSON.stringify(cgsPlans.styles) }
            });
        });

        // Plan form
        if ($('#cgs-plan-form').length) {
            if (!$('#cgs-durations-list .cgs-duration-row').length) {
                addDurationRow({ months:6, rate:'5.7', step_interval:'1' });
                addDurationRow({ months:12, rate:'4.8', step_interval:'1' });
                addDurationRow({ months:18, rate:'4.5', step_interval:'1' });
            }
            $('#cgs-new-plan, #cgs-reset-plan-form').on('click', resetForm);
            $('#cgs-add-duration').on('click', function(){ addDurationRow(); });
            $(document).on('click', '.cgs-remove-dur', function(){
                if ($('#cgs-durations-list .cgs-duration-row').length<=1){ alert('حداقل یک دوره لازم است'); return; }
                $(this).closest('.cgs-duration-row').remove(); updatePlanPreview();
            });
            $(document).on('click', '.cgs-add-step', function(){
                addStepRow($(this).closest('.cgs-duration-row').find('.cgs-steps-list'), { interval:'1', rate:'' });
                updatePlanPreview();
            });
            $(document).on('click', '.cgs-remove-step', function(){
                var $list = $(this).closest('.cgs-steps-list');
                if ($list.find('.cgs-step-row').length <= 1) { alert('هر دوره حداقل یک گام لازم دارد'); return; }
                $(this).closest('.cgs-step-row').remove(); updatePlanPreview();
            });
            $(document).on('click', '.cgs-plan-item', function(){ loadPlan($(this).attr('data-id')||$(this).data('id')); });
            $(document).on('input change', '#plan_title,#plan_desc,#plan_icon_emoji,#plan_icon,#plan_prepayment,#plan_guarantor,#plan_active,#plan_featured,#plan_vip,#plan_facility_use,#plan_min,#plan_max,.dur-months,.dur-rate,.dur-step', updatePlanPreview);
            $(document).on('change', '#plan_design_id', function(){ updatePlanPreview(); $('#cgs-plan-editor-title').append(''); var n=$('#plan_design_id option:selected').text(); if($('#plan_design_id').val()){ $('#cgs-design-link-msg').text('قالب «'+n+'» انتخاب شد — ذخیره طرح را بزنید').css('color','#1565c0'); } else { $('#cgs-design-link-msg').text(''); } });

            $('#cgs-pick-icon').on('click', function(e){
                e.preventDefault();
                if (typeof wp==='undefined'||!wp.media) { alert('رسانه وردپرس بارگذاری نشده'); return; }
                var frame = wp.media({ title:'آیکون', button:{text:'انتخاب'}, multiple:false });
                frame.on('select', function(){ $('#plan_icon').val(frame.state().get('selection').first().toJSON().url); updatePlanPreview(); });
                frame.open();
            });

            $('#cgs-plan-form').on('submit', function(e){
                e.preventDefault();
                var plan = {
                    id: $('#plan_id').val(),
                    title: $('#plan_title').val(),
                    description: $('#plan_desc').val(),
                    icon_emoji: $('#plan_icon_emoji').val(),
                    icon: $('#plan_icon').val(),
                    sort_order: $('#plan_sort').val(),
                    min_amount: $('#plan_min').val(),
                    max_amount: $('#plan_max').val(),
                    facility_use_months: $('#plan_facility_use').val(),
                    active: $('#plan_active').is(':checked')?1:0,
                    featured: $('#plan_featured').is(':checked')?1:0,
                    vip: $('#plan_vip').is(':checked')?1:0,
                    design_id: $('#plan_design_id').val()||'',
                    prepayment: $('#plan_prepayment').is(':checked')?1:0,
                    guarantor_required: $('#plan_guarantor').is(':checked')?1:0,
                    status: $('#plan_status').val()||'draft',
                    form_template_id: $('#plan_form_template_id').val()||'',
                    rule_amount_gt: parseInt($('#plan_rule_amount_gt').val(),10)||0,
                    rule_force_guarantor: $('#plan_rule_force_guarantor').is(':checked')?1:0,
                    durations: collectDurations(),
                    selected: collectSelected(),
                    field_keys: ($('#plan_field_keys').val()||'').split(',').map(function(s){return s.trim();}).filter(Boolean)
                };
                if (!plan.title){ alert('عنوان الزامی است'); return; }
                $('#cgs-plan-msg').text('در حال ذخیره...').css('color','#666');
                $.ajax({
                    url: cgsPlans.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'cgs_save_plan',
                        nonce: cgsPlans.nonce,
                        plan: JSON.stringify(plan)
                    }
                }).done(function(res){
                    $('#cgs-plan-msg').text(res.success?'ذخیره شد ✓':(res.data||'خطا')).css('color',res.success?'green':'red');
                    if (res.success) setTimeout(function(){ location.reload(); }, 700);
                }).fail(function(xhr){
                    $('#cgs-plan-msg').text('خطای شبکه '+(xhr.status||'')).css('color','red');
                });
            });

            $('#cgs-delete-plan').on('click', function(){
                var id = $('#plan_id').val();
                if (!id||!confirm('حذف شود؟')) return;
                $.post(cgsPlans.ajax_url, { action:'cgs_delete_plan', nonce:cgsPlans.nonce, id:id })
                    .done(function(res){ if(res.success) location.reload(); else alert(res.data||'خطا'); });
            });


        // ── بانک قالب ظاهر طرح‌ها ──
        function cgsFillDesignSelect(designs) {
            var $s = $('#cgs-design-select');
            if (!$s.length) return;
            var cur = $s.val();
            $s.empty().append('<option value="">— انتخاب قالب ظاهر —</option>');
            if (designs && typeof designs === 'object') {
                Object.keys(designs).forEach(function(id){
                    var d = designs[id];
                    var name = (d && d.name) ? d.name : id;
                    $s.append($('<option>').val(d.id || id).text(name));
                });
            }
            if (cur) $s.val(cur);
            cgsPlans.designs = designs || {};
        }

        function cgsApplyDesignStyles(st) {
            if (!st) return;
            if ($('#ps-card-bg').length) {
                $('#ps-card-bg').val(st.card_bg || '#ffffff');
                $('#ps-card-border').val(st.card_border || '#c5cae9');
                $('#ps-title-color').val(st.title_color || '#1a237e');
                $('#ps-title-size').val(st.title_size || 18);
                $('#ps-text-color').val(st.text_color || '#333333');
                $('#ps-accent').val(st.accent || '#1a237e');
                $('#ps-radius').val(st.radius || 14);
                $('#ps-show-icon').prop('checked', st.show_icon !== '0');
                $('#ps-btn-text').val(st.btn_text || 'انتخاب این طرح');
                $('#ps-btn-bg').val(st.btn_bg || '#1a237e');
                $('#ps-btn-style').val(st.btn_style || 'solid');
                if ($('#ps-card-shadow').length) $('#ps-card-shadow').val(st.card_shadow || '0 6px 24px rgba(15,23,42,0.08)');
                $('#ps-glass-btn').prop('checked', st.glass_btn === '1');
                $('#ps-btn-sound').prop('checked', st.btn_sound === '1');
                $('#ps-vip-badge').val(st.vip_badge_color || '#fbbf24');
                $('#ps-star-count').val(st.star_count || 5);
                $('#ps-featured-glow').prop('checked', st.featured_glow === '1');
                $('#ps-featured-color').val(st.featured_color || '#4338ca');
                if (typeof rebuildStarColorInputs === 'function') {
                    rebuildStarColorInputs();
                    var cols = String(st.star_colors || '').split(',');
                    $('.ps-star-color').each(function(i){ if (cols[i]) $(this).val(cols[i]); });
                }
            }
            cgsPlans.styles = st;
            if (typeof applyStyleInputsToState === 'function') applyStyleInputsToState();
            else if (typeof updatePlanPreview === 'function') updatePlanPreview();
        }

        $('#cgs-design-save').on('click', function(){
            var name = ($('#cgs-design-name').val() || '').trim();
            if (!name) { alert('نام قالب ظاهر را وارد کنید'); return; }
            if (typeof applyStyleInputsToState === 'function') applyStyleInputsToState();
            $('#cgs-design-msg').text('در حال ذخیره...').css('color','#666');
            $.ajax({
                url: cgsPlans.ajax_url, type: 'POST', dataType: 'json',
                data: {
                    action: 'cgs_save_plan_design',
                    nonce: cgsPlans.nonce,
                    name: name,
                    id: $('#cgs-design-select').val() || '',
                    styles: JSON.stringify(cgsPlans.styles || {})
                }
            }).done(function(res){
                if (res.success) {
                    $('#cgs-design-msg').text(res.data.message || 'ذخیره شد').css('color','green');
                    cgsFillDesignSelect(res.data.designs || {});
                    if (res.data.id) $('#cgs-design-select').val(res.data.id);
                } else {
                    $('#cgs-design-msg').text(res.data || 'خطا').css('color','red');
                }
            }).fail(function(){ $('#cgs-design-msg').text('خطای شبکه').css('color','red'); });
        });

        $('#cgs-design-apply').on('click', function(){
            var id = $('#cgs-design-select').val();
            if (!id) { alert('قالب را انتخاب کنید'); return; }
            var d = (cgsPlans.designs || {})[id];
            if (!d || !d.styles) {
                // از سرور بگیر
                $.post(cgsPlans.ajax_url, { action:'cgs_list_plan_designs', nonce:cgsPlans.nonce }).done(function(res){
                    if (res.success) {
                        cgsFillDesignSelect(res.data.designs);
                        d = (cgsPlans.designs || {})[id];
                        if (d && d.styles) cgsApplyDesignStyles(d.styles);
                    }
                });
                return;
            }
            cgsApplyDesignStyles(d.styles);
            $('#cgs-design-msg').text('اعمال شد — برای ماندگاری «ذخیره ظاهر» را بزنید').css('color','#1565c0');
        });

        $('#cgs-design-delete').on('click', function(){
            var id = $('#cgs-design-select').val();
            if (!id || !confirm('این قالب ظاهر حذف شود؟')) return;
            $.post(cgsPlans.ajax_url, { action:'cgs_delete_plan_design', nonce:cgsPlans.nonce, id:id }).done(function(res){
                if (res.success) {
                    cgsFillDesignSelect(res.data.designs || {});
                    $('#cgs-design-msg').text('حذف شد').css('color','green');
                }
            });
        });


        // بارگذاری کامل لیست قالب‌های ظاهر
        function cgsRefreshDesignLists() {
            $.post(cgsPlans.ajax_url, { action: 'cgs_list_plan_designs', nonce: cgsPlans.nonce })
            .done(function(res){
                if (!res.success) return;
                var designs = res.data.designs || {};
                cgsPlans.designs = designs;
                var opts = '<option value="">— پیش‌فرض سراسری —</option>';
                var opts2 = '<option value="">— انتخاب قالب ظاهر —</option>';
                Object.keys(designs).forEach(function(id){
                    var d = designs[id];
                    var name = (d && d.name) ? d.name : id;
                    opts += '<option value="'+id+'">'+ $('<span>').text(name).html() +'</option>';
                    opts2 += '<option value="'+id+'">'+ $('<span>').text(name).html() +'</option>';
                });
                if (!Object.keys(designs).length) {
                    opts2 += '<option value="" disabled>هنوز قالبی ذخیره نشده — از تب ظاهر ذخیره کنید</option>';
                }
                var curPlan = $('#plan_design_id').val();
                var curGlobal = $('#cgs-design-select').val();
                $('#plan_design_id').html(opts);
                $('#cgs-design-select').html(opts2);
                if (curPlan) $('#plan_design_id').val(curPlan);
                if (curGlobal) $('#cgs-design-select').val(curGlobal);
            });
        }
        cgsRefreshDesignLists();
        // بعد از ذخیره قالب ظاهر، لیست را تازه کن
        $(document).on('click', '#cgs-design-save', function(){
            setTimeout(cgsRefreshDesignLists, 800);
        });

        if (cgsPlans.designs) cgsFillDesignSelect(cgsPlans.designs);

            updatePlanPreview();
            cgsInitPlanListDraggable();
        }
        cgsInitPlanListDraggable();
    });


    // ── Draggable.js مکمل Sortable: جابجایی آزاد کارت‌های فهرست طرح‌ها ──
    function cgsInitPlanListDraggable() {
        // DISABLED to prevent lock
    }


})(jQuery);
