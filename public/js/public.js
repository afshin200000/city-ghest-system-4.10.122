(function($){
    'use strict';

    function cgsUpdateProgress(current) {
        try {
            var ac = sessionStorage.getItem('cgs_area_code');
            if (ac) {
                $('input.cgs-area-code, input[data-role="area_code"], input[name="area_code"]').val(ac).prop('readonly', true);
            }
        } catch(e) {}

        var $inds = $('.cgs-form-wrapper .cgs-step-indicator, .cgs-preview-mode .cgs-step-indicator');
        $inds.removeClass('active done');
        $inds.each(function(){
            var s = parseInt($(this).data('step'), 10);
            if (s < current) $(this).addClass('done');
            else if (s === current) $(this).addClass('active');
            if (typeof cgsUpdateProgress === 'function' && typeof step !== 'undefined') cgsUpdateProgress(step);;
        });
        var total = $inds.length;
        $('.cgs-current-step-num').text(current);
        $('.cgs-remaining-text').text(Math.max(0, total - current) + ' مرحله باقی‌مانده');
        var pct = total ? Math.round((current / total) * 100) : 0;
        $('.cgs-progress-fill').css('width', pct + '%');
    }


    function cgsPlayBtnSound() {
        var enabled = 1;
        if (typeof cgsPublic !== 'undefined') {
            if (typeof cgsPublic.btn_sound !== 'undefined') enabled = parseInt(cgsPublic.btn_sound, 10);
            else if (typeof cgsPublic.sound_enabled !== 'undefined') enabled = parseInt(cgsPublic.sound_enabled, 10);
        }
        if (!enabled) return;
        try {
            var ctx = window.__cgsAudioCtx || (window.__cgsAudioCtx = new (window.AudioContext || window.webkitAudioContext)());
            var o = ctx.createOscillator();
            var g = ctx.createGain();
            o.connect(g); g.connect(ctx.destination);
            o.frequency.value = 880;
            g.gain.value = (typeof cgsPublic !== 'undefined' && cgsPublic.sound_volume) ? (cgsPublic.sound_volume/100)*0.15 : 0.08;
            o.start();
            g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
            o.stop(ctx.currentTime + 0.12);
        } catch(e) {}
    }
    $(document).on('click', '.cgs-form-wrapper .cgs-btn, .cgs-preview-mode .cgs-btn', function(){
        cgsPlayBtnSound();
    });


    /* ========== PROVINCE / CITY / AREA CODE (always on) ========== */
    function cgsGetLocations() {
        if (typeof cgsPublic !== 'undefined' && cgsPublic.locations) return cgsPublic.locations;
        if (window.cgsLocations) return window.cgsLocations;
        return {};
    }
    function cgsSetAreaCode($ctx, code) {
        code = (code || '').toString();
        if (!$ctx || !$ctx.length) $ctx = $(document);
        var sel = 'input.cgs-area-code, input[data-role="area_code"], input[name="area_code"], input[name="phone_code"]';
        var $inputs = $ctx.find(sel);
        if (!$inputs.length) {
            $inputs = $('.cgs-form-wrapper, .cgs-preview-mode').find(sel);
        }
        if (!$inputs.length) {
            $inputs = $(document).find(sel);
        }
        $inputs.each(function(){
            $(this).val(code).prop('readonly', true).attr('value', code);
        });
        $ctx.find('.cgs-area-code-display, .cgs-area-code-box').add($('.cgs-area-code-display, .cgs-area-code-box')).text(code || '—');
        try { sessionStorage.setItem('cgs_area_code', code); } catch(e) {}
    }
    function cgsCityCode(locations, province, cityName) {
        if (!province || !locations[province]) return '';
        var entry = locations[province];
        var fallback = entry.code || '';
        var cities = entry.cities || [];
        for (var i = 0; i < cities.length; i++) {
            var c = cities[i];
            if (typeof c === 'string') {
                if (c === cityName) return fallback;
            } else if (c && c.name === cityName) {
                return c.code || fallback;
            }
        }
        return fallback;
    }
    function cgsBindProvinceCascade(root) {
        var $root = root ? $(root) : $(document);
        $root.off('change.cgsProvAll').on('change.cgsProvAll', 'select.cgs-province, select[data-role="province"], select[name="province"], select[name*="province"]', function() {
            var $sel = $(this);
            var province = $sel.val();
            var $ctx = $sel.closest('.cgs-form-wrapper, .cgs-preview-mode, form, .cgs-form-step, .cgs-step-fields');
            if (!$ctx.length) $ctx = $(document);
            // Prefer form wrapper for area code fields that may be on another step
            var $form = $sel.closest('.cgs-form-wrapper, .cgs-preview-mode, form');
            if ($form.length) $ctx = $form;
            var locations = cgsGetLocations();
            var $city = $ctx.find('select.cgs-city, select[data-role="city"], select[name="city"], select[name*="city"]').first();
            $city.empty().append('<option value="">انتخاب شهر...</option>');
            if (province && locations[province] && locations[province].cities && locations[province].cities.length) {
                var entry = locations[province];
                var cities = entry.cities || [];
                for (var i = 0; i < cities.length; i++) {
                    var cn = (typeof cities[i] === 'string') ? cities[i] : (cities[i].name || '');
                    var cc = (typeof cities[i] === 'object' && cities[i].code) ? cities[i].code : (entry.code || '');
                    $city.append($('<option></option>').val(cn).text(cn).attr('data-code', cc));
                }
                cgsSetAreaCode($ctx, entry.code || '');
            } else if (province) {
                // Optimized: load cities via AJAX
                var pcode = (locations[province] && locations[province].code) ? locations[province].code : '';
                cgsSetAreaCode($ctx, pcode);
                $city.append('<option value="">در حال بارگذاری...</option>');
                var ajaxUrl = (typeof cgsPublic !== 'undefined') ? cgsPublic.ajax_url : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
                if (ajaxUrl) {
                    $.getJSON(ajaxUrl, { action: 'cgs_get_cities', province: province })
                        .done(function(res){
                            $city.empty().append('<option value="">انتخاب شهر...</option>');
                            if (res && res.success && res.data) {
                                var list = res.data.cities || [];
                                var code = res.data.code || pcode;
                                for (var i = 0; i < list.length; i++) {
                                    var cn = (typeof list[i] === 'string') ? list[i] : (list[i].name || '');
                                    var cc = (typeof list[i] === 'object' && list[i].code) ? list[i].code : code;
                                    if (!cn) continue;
                                    $city.append($('<option></option>').val(cn).text(cn).attr('data-code', cc));
                                }
                                cgsSetAreaCode($ctx, code);
                                // cache into locations for next time
                                if (!locations[province]) locations[province] = {};
                                locations[province].cities = list;
                                locations[province].code = code;
                            }
                        })
                        .fail(function(){
                            $city.empty().append('<option value="">خطا در بارگذاری شهرها</option>');
                        });
                }
            } else {
                cgsSetAreaCode($ctx, '');
            }
        });
        // CITY change → set area code from that city
        $root.off('change.cgsCityAll').on('change.cgsCityAll', 'select.cgs-city, select[data-role="city"], select[name="city"], select[name*="city"]', function() {
            var $sel = $(this);
            var cityName = $sel.val();
            var $form = $sel.closest('.cgs-form-wrapper, .cgs-preview-mode, form');
            if (!$form.length) $form = $(document);
            var code = $sel.find('option:selected').attr('data-code') || '';
            if (!code) {
                var province = $form.find('select.cgs-province, select[data-role="province"]').first().val();
                code = cgsCityCode(cgsGetLocations(), province, cityName);
            }
            cgsSetAreaCode($form, code);
        });
    }
    $(function(){ cgsBindProvinceCascade(document); });


    /* ========== FIELD CONSTRAINTS (maxlength + numeric) ========== */
    $(document).on('input', '.cgs-form-wrapper input, .cgs-form-wrapper textarea, .cgs-preview-mode input, .cgs-preview-mode textarea', function() {
        var $el = $(this);
        var maxLen = parseInt($el.attr('maxlength') || $el.data('maxlen') || $el.data('maxlength') || 0, 10);
        var charset = $el.data('charset') || '';
        var val = String(this.value || '');
        var isNum = charset === 'numeric' || $el.hasClass('cgs-numeric') || $el.hasClass('cgs-tel') || $el.hasClass('cgs-national-id') || $el.attr('type') === 'tel' || $el.attr('inputmode') === 'numeric';
        if (isNum) {
            val = val.replace(/[^0-9]/g, '');
        }
        if (maxLen > 0 && val.length > maxLen) {
            val = val.substring(0, maxLen);
        }
        if (this.value !== val) this.value = val;
    });
    $(document).on('keypress', '.cgs-form-wrapper input.cgs-numeric, .cgs-form-wrapper input.cgs-tel, .cgs-form-wrapper input.cgs-national-id, .cgs-form-wrapper input[type=tel], .cgs-preview-mode input.cgs-numeric, .cgs-preview-mode input.cgs-tel', function(e) {
        if (e.ctrlKey || e.metaKey || e.which === 0 || e.which === 8) return;
        var ch = String.fromCharCode(e.which);
        if (!/[0-9]/.test(ch)) e.preventDefault();
    });
    /* paste filter */
    $(document).on('paste', '.cgs-form-wrapper input.cgs-numeric, .cgs-form-wrapper input.cgs-tel, .cgs-form-wrapper input[type=tel]', function(e) {
        var $el = $(this);
        setTimeout(function() {
            var maxLen = parseInt($el.attr('maxlength') || $el.data('maxlen') || 0, 10);
            var v = $el.val().replace(/[^0-9]/g, '');
            if (maxLen > 0) v = v.substring(0, maxLen);
            $el.val(v);
        }, 0);
    });

    /* ========== FILE type/size enforcement ========== */
    $(document).on('change', '.cgs-form-wrapper input[type=file], .cgs-preview-mode input[type=file]', function() {
        var input = this;
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];
        var allowed = ($(input).data('allowed') || '').toString().toLowerCase().split(',').map(function(s){ return s.trim(); }).filter(Boolean);
        var maxKb = parseInt($(input).data('maxkb') || 0, 10);
        var ext = (file.name.split('.').pop() || '').toLowerCase();
        var ok = true;
        var msg = '';
        if (allowed.length && allowed.indexOf(ext) === -1) {
            ok = false;
            msg = 'فرمت مجاز نیست. مجاز: ' + allowed.join('، ');
        }
        if (ok && maxKb > 0 && file.size > maxKb * 1024) {
            ok = false;
            msg = 'حجم فایل بیش از حد مجاز (' + maxKb + ' کیلوبایت) است.';
        }
        var $hint = $(input).closest('.cgs-field-group, .cgs-file-upload').find('.cgs-file-error');
        if (!$hint.length) {
            $hint = $('<div class="cgs-file-error" style="color:#c62828;font-size:0.85rem;margin-top:6px;"></div>');
            $(input).closest('.cgs-field-group, .cgs-file-upload').append($hint);
        }
        var $sel = $(input).closest('.cgs-field-group, .cgs-file-upload').find('.cgs-file-selected');
        if (!$sel.length) {
            $sel = $('<div class="cgs-file-selected"></div>');
            $(input).closest('.cgs-file-upload, .cgs-field-group').append($sel);
        }
        if (!ok) {
            input.value = '';
            $hint.text(msg).show();
            $(input).addClass('cgs-error');
            $sel.hide().text('');
        } else {
            $hint.hide();
            $(input).removeClass('cgs-error');
            var sizeKb = Math.round(file.size / 1024);
            var sizeStr = sizeKb >= 1024 ? (Math.round(sizeKb/1024*10)/10) + ' مگابایت' : sizeKb + ' کیلوبایت';
            $sel.html('✓ ' + file.name + ' <span style="color:#666;">(' + sizeStr + ')</span>').show();
        }
    });



    
    // Happy success sound (Web Audio - no external file needed)
    function cgsPlaySuccessSound() {
        try {
            if (typeof cgsPublic !== 'undefined' && cgsPublic.sound_enabled == 0) return;
            // Respect reduced motion / accessibility preference
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            var vol = 0.25;
            if (typeof cgsPublic !== 'undefined' && cgsPublic.sound_volume !== undefined) {
                vol = Math.max(0, Math.min(100, parseInt(cgsPublic.sound_volume, 10) || 40)) / 100 * 0.4;
            }
            if (vol <= 0) return;
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var notes = [523.25, 659.25, 783.99, 1046.50];
            notes.forEach(function(freq, i) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = 'sine';
                osc.frequency.value = freq;
                var start = ctx.currentTime + i * 0.12;
                gain.gain.setValueAtTime(0, start);
                gain.gain.linearRampToValueAtTime(vol, start + 0.03);
                gain.gain.exponentialRampToValueAtTime(0.01, start + 0.35);
                osc.start(start);
                osc.stop(start + 0.4);
            });
        } catch(e) {}
    }

    
    // Iranian National ID validation (کد ملی)
    function cgsValidateNationalId(code) {
        code = (code || '').toString().replace(/\D/g, '');
        if (!/^\d{10}$/.test(code)) return false;
        if (/^(\d)\1{9}$/.test(code)) return false; // all same digits
        var sum = 0;
        for (var i = 0; i < 9; i++) {
            sum += parseInt(code.charAt(i), 10) * (10 - i);
        }
        var r = sum % 11;
        var check = parseInt(code.charAt(9), 10);
        return (r < 2 && check === r) || (r >= 2 && check === (11 - r));
    }

    // Iranian SHABA / IBAN validation
    function cgsValidateSheba(sheba) {
        sheba = (sheba || '').toString().replace(/\s/g, '').toUpperCase();
        if (sheba.indexOf('IR') === 0) {
            sheba = sheba.substring(2);
        }
        if (!/^\d{24}$/.test(sheba)) return false;
        // IBAN mod-97: rearrange IR + 24 digits
        var rearranged = sheba + '1827'; // IR = 18, 27
        // Compute mod 97 on large number in chunks
        var remainder = 0;
        for (var i = 0; i < rearranged.length; i++) {
            remainder = (remainder * 10 + parseInt(rearranged.charAt(i), 10)) % 97;
        }
        return remainder === 1;
    }


    function initMultiStep() {
        var $wrapper = $('.cgs-form-wrapper');
        if (!$wrapper.length) return;

        var $steps = $wrapper.find('.cgs-form-step');
        var total  = $steps.length;
        var current = 1;

        function goToStep(step) {
            $steps.removeClass('active');
            $steps.filter('[data-step="'+step+'"]').addClass('active');
            var percent = (step / total) * 100;
            $wrapper.find('.cgs-progress-fill').css('width', percent + '%');
            $wrapper.find('.cgs-step-indicator').each(function(){
                var s = parseInt($(this).data('step'), 10);
                $(this).removeClass('active done');
                if (s < step) $(this).addClass('done');
                if (s === step) $(this).addClass('active');
            });
            current = step;
            // Update remaining indicator
            var stepIndex = $wrapper.find('.cgs-step-indicator[data-step="'+step+'"]').index() + 1;
            if (!stepIndex) stepIndex = step;
            $wrapper.find('.cgs-current-step-num').text(stepIndex);
            var remain = total - stepIndex;
            $wrapper.find('.cgs-remaining-text').text(remain > 0 ? (remain + ' مرحله باقی‌مانده') : 'مرحله پایانی');
            $('html, body').animate({ scrollTop: $wrapper.offset().top - 40 }, 300);
        }

        // Validate current step - returns array of error objects {label, message}
        function validateStep(stepNum) {
            var errors = [];
            var $step = $steps.filter('[data-step="'+stepNum+'"]');

            $step.find('.cgs-field-group').each(function(){
                var $group = $(this);
                var $input = $group.find('input, select, textarea').first();
                if (!$input.length) return;

                var label = $group.find('label').clone().children().remove().end().text().replace('*','').trim();
                var val = $input.val();
                var isRequired = $input.prop('required') || $input.attr('required');
                var type = $input.attr('type') || $input.prop('tagName').toLowerCase();

                // Required check
                if (isRequired && (!val || (typeof val === 'string' && !val.trim()))) {
                    $input.css('border-color', '#c62828');
                    errors.push({ label: label, message: 'این فیلد الزامی است و تکمیل نشده.' });
                    return;
                }

                // Numeric only
                if ($input.hasClass('cgs-numeric') || type === 'number') {
                    if (val && !/^[0-9]+$/.test(val.toString().replace(/,/g,''))) {
                        $input.css('border-color', '#c62828');
                        errors.push({ label: label, message: 'فقط عدد مجاز است.' });
                        return;
                    }
                }

                // Tel / mobile length
                if (type === 'tel' || $input.hasClass('cgs-tel')) {
                    var maxLen = parseInt($input.attr('maxlength') || $input.data('maxlen') || 0, 10);
                    var minLen = parseInt($input.attr('minlength') || $input.data('minlen') || 0, 10);
                    var digits = (val || '').replace(/\D/g, '');
                    if (minLen && digits.length < minLen) {
                        $input.css('border-color', '#c62828');
                        errors.push({ label: label, message: 'حداقل ' + minLen + ' رقم وارد کنید.' });
                        return;
                    }
                    if (maxLen && digits.length > maxLen) {
                        $input.css('border-color', '#c62828');
                        errors.push({ label: label, message: 'حداکثر ' + maxLen + ' رقم مجاز است.' });
                        return;
                    }
                }

                // Email basic
                if (type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                    $input.css('border-color', '#c62828');
                    errors.push({ label: label, message: 'فرمت ایمیل صحیح نیست.' });
                    return;
                }

                // URL validation
                if ((type === 'url' || $input.attr('type') === 'url') && val) {
                    if (!/^https?:\/\/.+/i.test(val) && !/^[a-z0-9].*\.[a-z]{2,}/i.test(val)) {
                        $input.css('border-color', '#c62828');
                        errors.push({ label: label, message: 'نشانی اینترنتی معتبر نیست (مثال: https://example.com).' });
                        return;
                    }
                }

                // National ID (کد ملی) including guarantor
                var nameAttr = ($input.attr('name') || '').toLowerCase();
                if ((nameAttr === 'national_id' || nameAttr.indexOf('national_id') !== -1 || nameAttr === 'guarantor_national_id' || $input.hasClass('cgs-national-id')) && val) {
                    if (!cgsValidateNationalId(val)) {
                        $input.css('border-color', '#c62828');
                        errors.push({ label: label, message: 'کد ملی واردشده معتبر نیست.' });
                        return;
                    }
                }

                // SHABA
                if ((nameAttr === 'sheba' || nameAttr.indexOf('sheba') !== -1 || nameAttr === 'check_sheba' || $input.hasClass('cgs-sheba')) && val) {
                    if (!cgsValidateSheba(val)) {
                        $input.css('border-color', '#c62828');
                        errors.push({ label: label, message: 'شماره شبا معتبر نیست (باید IR + ۲۴ رقم یا ۲۴ رقم باشد).' });
                        return;
                    }
                }

                // Bank card - 16 digits
                if ((nameAttr === 'bank_card' || nameAttr.indexOf('bank_card') !== -1) && val) {
                    var card = val.replace(/\D/g, '');
                    if (card.length !== 16) {
                        $input.css('border-color', '#c62828');
                        errors.push({ label: label, message: 'شماره کارت باید ۱۶ رقم باشد.' });
                        return;
                    }
                }

                // File type and size validation
                if (type === 'file' || $input.attr('type') === 'file') {
                    var fileInput = $input[0];
                    if (fileInput.files && fileInput.files.length > 0) {
                        var file = fileInput.files[0];
                        var allowed = ($input.data('allowed') || 'jpg,jpeg,png,pdf,webp').toString().toLowerCase().split(',');
                        var maxKb = parseInt($input.data('maxkb') || 2048, 10);
                        var ext = file.name.split('.').pop().toLowerCase();
                        if (allowed.indexOf(ext) === -1) {
                            $input.css('border-color', '#c62828');
                            errors.push({ label: label, message: 'فرمت فایل مجاز نیست. فرمت‌های مجاز: ' + allowed.join('، ') });
                            return;
                        }
                        if (file.size > maxKb * 1024) {
                            var sizeLabel = maxKb >= 1024 ? (maxKb/1024).toFixed(1) + ' مگابایت' : maxKb + ' کیلوبایت';
                            $input.css('border-color', '#c62828');
                            errors.push({ label: label, message: 'حجم فایل بیشتر از حد مجاز است (حداکثر ' + sizeLabel + ').' });
                            return;
                        }
                    } else if (isRequired) {
                        $input.css('border-color', '#c62828');
                        errors.push({ label: label, message: 'انتخاب فایل الزامی است.' });
                        return;
                    }
                }

                $input.css('border-color', '');
            });

            return errors;
        }

        function showErrorReport(errors) {
            var html = '<div class="cgs-error-box">';
            html += '<div class="cgs-error-header">⚠ لطفاً موارد زیر را اصلاح کنید</div>';
            html += '<table class="cgs-error-table"><thead><tr><th>فیلد</th><th>شرح خطا</th></tr></thead><tbody>';
            errors.forEach(function(e){
                html += '<tr><td>' + $('<div>').text(e.label).html() + '</td><td>' + $('<div>').text(e.message).html() + '</td></tr>';
            });
            html += '</tbody></table></div>';

            var $msg = $wrapper.find('.cgs-form-message');
            $msg.removeClass('success').addClass('error').html(html).show();
            $('html, body').animate({ scrollTop: $msg.offset().top - 60 }, 400);
        }

        // Next step
        $wrapper.on('click', '.cgs-next-step', function(e){
            e.preventDefault();
            var errors = validateStep(current);
            if (errors.length) {
                showErrorReport(errors);
                return;
            }
            $wrapper.find('.cgs-form-message').hide();
            if (current < total) goToStep(current + 1);
        });

        // Prev step
        $wrapper.on('click', '.cgs-prev-step', function(e){
            e.preventDefault();
            $wrapper.find('.cgs-form-message').hide();
            if (current > 1) goToStep(current - 1);
        });

        // Final submit
        $('#cgs-application-form').on('submit', function(e){
            e.preventDefault();
            // Validate all steps
            var allErrors = [];
            for (var s = 1; s <= total; s++) {
                allErrors = allErrors.concat(validateStep(s));
            }
            if (allErrors.length) {
                // Go to first step with error
                for (var s = 1; s <= total; s++) {
                    if (validateStep(s).length) {
                        goToStep(s);
                        break;
                    }
                }
                showErrorReport(allErrors);
                return;
            }

            var $form = $(this);
            var $msg  = $wrapper.find('.cgs-form-message');
            var $btn  = $form.find('.cgs-submit-form');
            $btn.prop('disabled', true).text('در حال ارسال...');

            var formData = new FormData(this);
            $.ajax({
                url: cgsPublic.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res){
                    if (res.success) {
                        cgsPlaySuccessSound();
                        $msg.removeClass('error').addClass('success').html(
                            '<div class="cgs-celebrate">' +
                            '<div class="cgs-celebrate-icon"><span class="cgs-icon cgs-icon-xl cgs-icon-thumbs-up"></span> <span class="cgs-icon cgs-icon-xl cgs-icon-pulse-check"></span></div>' +
                            '<div class="cgs-celebrate-title"><span class="cgs-icon cgs-icon-star"></span> تبریک!</div>' +
                            '<div class="cgs-celebrate-text">' + (res.data.message || 'درخواست شما با موفقیت ثبت شد.') + '</div>' +
                            '<div class="cgs-celebrate-sub">به زودی با شما تماس گرفته می‌شود.</div>' +
                            '</div>'
                        ).show();
                        $form.hide();
                        $wrapper.find('.cgs-progress').hide();
                    } else {
                        showErrorReport([{ label: 'سیستم', message: res.data || 'خطایی رخ داد.' }]);
                        $btn.prop('disabled', false).text('ثبت نهایی درخواست');
                    }
                },
                error: function(){
                    showErrorReport([{ label: 'ارتباط', message: 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.' }]);
                    $btn.prop('disabled', false).text('ثبت نهایی درخواست');
                }
            });
        });

        
        // Guarantee owner: show/hide third-party fields
        function cgsToggleGuarantorFields($form) {
            var owner = $form.find('select.cgs-guarantee-owner, select[name=guarantee_owner]').val();
            var $gfields = $form.find('.cgs-guarantor-field');
            if (owner === 'شخص دیگر') {
                $gfields.slideDown(200);
                $gfields.find('input, select, textarea').prop('disabled', false);
            } else {
                $gfields.slideUp(200);
                $gfields.find('input, select, textarea').prop('disabled', true).val('');
            }
        }
        $wrapper.on('change', 'select.cgs-guarantee-owner, select[name=guarantee_owner]', function(){
            cgsToggleGuarantorFields($wrapper);
        });
        // Initial state
        cgsToggleGuarantorFields($wrapper);

        // Guarantor national id validation
        $wrapper.on('input', 'input[name=guarantor_national_id]', function(){
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
        });

        // File label
        $wrapper.on('change', 'input[type=file]', function(){
            var name = this.files[0] ? this.files[0].name : 'انتخاب فایل';
            $(this).closest('.cgs-file-upload').find('.cgs-file-text').text(name);
        });

        // Numeric only enforcement
        $wrapper.on('input keypress', '.cgs-numeric, input[type=number]', function(e){
            if (e.type === 'keypress') {
                var char = String.fromCharCode(e.which);
                if (!/[0-9]/.test(char)) {
                    e.preventDefault();
                }
            } else {
                this.value = this.value.replace(/[^0-9]/g, '');
            }
        });

        // Tel digits only + length
        $wrapper.on('input', 'input[type=tel], .cgs-tel', function(){
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // National ID - digits only
        $wrapper.on('input', 'input[name=national_id], input[name*=national_id]', function(){
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
        });

        // SHABA - uppercase, allow IR and digits
        $wrapper.on('input', 'input[name=sheba], input[name*=sheba], input[name=check_sheba]', function(){
            this.value = this.value.replace(/[^0-9IRir]/g, '').toUpperCase();
        });

        // Bank card digits
        $wrapper.on('input', 'input[name=bank_card]', function(){
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 16);
        });
    }

    // Cascading Province -> City + Area code
    
    function initLocationCascade() {
        var locations = window.cgsLocations || (typeof cgsPublic !== 'undefined' && cgsPublic.locations) || {};

        function findCtx($el) {
            var $c = $el.closest('form, .cgs-form-wrapper, .cgs-preview-mode');
            return $c.length ? $c : $(document);
        }
        function findCitySelect($ctx) {
            return $ctx.find('select.cgs-city, select[name="city"], select[name*="city"], select[data-field-key="city"], select[data-role="city"]');
        }
        function findAreaInput($ctx) {
            return $ctx.find('input.cgs-area-code, input[name="area_code"], input[name="phone_code"], input[data-field-key="area_code"], input[data-role="area_code"]');
        }
        function findLandline($ctx) {
            return $ctx.find('input[name="landline"], input[name="phone_fixed"], input.cgs-landline, input[data-role="landline"], input[name*="landline"]');
        }

        function normalizeCities(cities) {
            var out = [];
            if (!cities) return out;
            for (var i = 0; i < cities.length; i++) {
                var c = cities[i];
                if (typeof c === 'string') {
                    out.push({ name: c, code: '' });
                } else if (c && typeof c === 'object') {
                    out.push({ name: c.name || c.title || '', code: c.code || '' });
                }
            }
            return out;
        }
        function setAreaCode($ctx, code) {
            code = code || '';
            var $area = findAreaInput($ctx);
            if ($area.length) {
                $area.val(code).prop('readonly', true).trigger('change');
            }
            // landline split field inside same form
            $ctx.find('input.cgs-area-code').val(code).prop('readonly', true);
            $ctx.find('.cgs-area-code-display, .cgs-area-code-box').text(code || '—');
            findLandline($ctx).each(function(){
                var $card = $(this).closest('.cgs-field-group, .cgs-field-card, .cgs-landline-wrap');
                var $inp = $card.find('input.cgs-area-code');
                if ($inp.length) {
                    $inp.val(code).prop('readonly', true);
                } else {
                    var $box = $card.find('.cgs-area-code-box');
                    if (!$box.length && code) {
                        $(this).before('<span class="cgs-area-code-box" title="کد مخابراتی">'+code+'</span>');
                    } else if ($box.length) {
                        $box.text(code || '—');
                    }
                }
            });
        }
        function applyCities($ctx, province, cities, code) {
            var $city = findCitySelect($ctx);
            var list = normalizeCities(cities);
            $city.empty().append('<option value="">انتخاب شهر...</option>');
            for (var i = 0; i < list.length; i++) {
                if (!list[i].name) continue;
                $city.append(
                    $('<option></option>')
                        .attr('value', list[i].name)
                        .attr('data-code', list[i].code || code || '')
                        .text(list[i].name)
                );
            }
            setAreaCode($ctx, code || '');
            $city.data('province-code', code || '');
        }

        function onProvinceChange($sel) {
            var province = $sel.val();
            var $ctx = findCtx($sel);
            if (!province) {
                applyCities($ctx, '', [], '');
                return;
            }
            // Local data first
            if (locations[province] && locations[province].cities) {
                applyCities($ctx, province, locations[province].cities, locations[province].code || '');
                return;
            }
            // AJAX fallback
            var ajaxUrl = (typeof cgsPublic !== 'undefined') ? cgsPublic.ajax_url : '';
            if (!ajaxUrl) return;
            $.get(ajaxUrl, { action: 'cgs_get_cities', province: province })
                .done(function(res){
                    if (res && res.success && res.data) {
                        applyCities($ctx, province, res.data.cities || [], res.data.code || '');
                    }
                });
        }

        $(document).off('change.cgsProv').on('change.cgsProv', 'select.cgs-province, select[name="province"], select[name*="province"], select[data-role="province"], select[data-field-key="province"]', function(){
            onProvinceChange($(this));
        });

        // Initial
        $('select.cgs-province, select[name="province"], select[data-role="province"]').each(function(){
            if ($(this).val()) onProvinceChange($(this));
        });
    }


    // Chat
    function initChat() {
        var $box = $('#cgs-chat-box');
        if (!$box.length) return;
        var appId = $box.data('app-id');
        $('#cgs-send-message').on('click', function(){
            var msg = $('#cgs-chat-message').val().trim();
            if (!msg) return;
            $.post(cgsPublic.ajax_url, {
                action: 'cgs_send_message',
                nonce: cgsPublic.nonce,
                application_id: appId,
                message: msg
            }, function(res){
                if (res.success) {
                    var html = '<div class="cgs-msg cgs-msg-member"><div class="cgs-msg-content">'+$('<div>').text(msg).html()+'</div><div class="cgs-msg-time">'+res.data.time+'</div></div>';
                    $box.find('.cgs-chat-empty').remove();
                    $box.append(html);
                    $('#cgs-chat-message').val('');
                    $box.scrollTop($box[0].scrollHeight);
                }
            });
        });
    }

    
    // Digital signature legal notice
    function cgsShowSignLegalNotice(onAccept) {
        var $overlay = $('#cgs-sign-legal-overlay');
        if (!$overlay.length) {
            if (typeof onAccept === 'function') onAccept();
            return;
        }
        $overlay.addClass('active').attr('aria-hidden', 'false');
        $('#cgs-sign-legal-agree').prop('checked', false);
        $('#cgs-sign-legal-accept').prop('disabled', true);

        $('#cgs-sign-legal-agree').off('change.cgsSign').on('change.cgsSign', function(){
            $('#cgs-sign-legal-accept').prop('disabled', !this.checked);
        });
        $('#cgs-sign-legal-cancel').off('click.cgsSign').on('click.cgsSign', function(){
            $overlay.removeClass('active').attr('aria-hidden', 'true');
        });
        $('#cgs-sign-legal-accept').off('click.cgsSign').on('click.cgsSign', function(){
            if (!$('#cgs-sign-legal-agree').is(':checked')) return;
            $overlay.removeClass('active').attr('aria-hidden', 'true');
            if (typeof onAccept === 'function') onAccept();
        });
        // Close on backdrop click
        $overlay.off('click.cgsSign').on('click.cgsSign', function(e){
            if (e.target === this) {
                $overlay.removeClass('active').attr('aria-hidden', 'true');
            }
        });
    }

    // Trigger when user focuses/changes sign status to request signature, or clicks a sign button
    $(document).on('click', '.cgs-btn-digital-sign, #cgs-start-digital-sign', function(e){
        e.preventDefault();
        cgsShowSignLegalNotice(function(){
            // After accept - mark agreed and optionally show success message for now
            var $status = $('select[name=guarantor_sign_status], select.cgs-sign-status');
            if ($status.length) {
                $status.val('لینک امضا ارسال شده');
            }
            // Store agreement in a hidden field if exists
            if (!$('#cgs_sign_legal_accepted').length) {
                $('<input type="hidden" name="cgs_sign_legal_accepted" id="cgs_sign_legal_accepted" value="1">').appendTo('#cgs-application-form');
            } else {
                $('#cgs_sign_legal_accepted').val('1');
            }
            alert('پس از پذیرش قوانین، در صورت اتصال به ارائه‌دهنده امضای دیجیتال، فرآیند احراز هویت و امضا آغاز می‌شود.');
        });
    });

    // Also when selecting "شخص دیگر" and then they proceed - optional auto-hint
    $(document).on('change', 'select.cgs-guarantee-owner, select[name=guarantee_owner]', function(){
        if ($(this).val() === 'شخص دیگر') {
            // Soft notice only
        }
    });

    
    // Global strict input enforcement (character type + max length)
    function cgsEnforceInput($el) {
        var $input = $($el);
        var maxLen = parseInt($input.attr('maxlength') || $input.data('maxlen') || 0, 10);
        var onlyDigits = $input.hasClass('cgs-numeric') || $input.hasClass('cgs-tel') || $input.attr('type') === 'tel' || $input.attr('inputmode') === 'numeric';
        var onlyNational = $input.hasClass('cgs-national-id') || ($input.attr('name') || '').indexOf('national_id') !== -1;
        var isSheba = $input.hasClass('cgs-sheba') || ($input.attr('name') || '').toLowerCase().indexOf('sheba') !== -1;
        var val = $input.val() || '';
        if (onlyNational || onlyDigits) {
            val = val.replace(/[^0-9]/g, '');
        } else if (isSheba) {
            val = val.replace(/[^0-9IRir]/g, '').toUpperCase();
        }
        if (maxLen > 0 && val.length > maxLen) {
            val = val.substring(0, maxLen);
        }
        if ($input.val() !== val) $input.val(val);
    }

    $(document).on('input', '.cgs-form-wrapper input, .cgs-preview-mode input', function(){
        cgsEnforceInput(this);
    });
    $(document).on('keypress', '.cgs-form-wrapper input.cgs-numeric, .cgs-form-wrapper input.cgs-tel, .cgs-form-wrapper input.cgs-national-id, .cgs-form-wrapper input[type=tel]', function(e){
        var char = String.fromCharCode(e.which);
        if (e.which !== 0 && e.which !== 8 && !/[0-9]/.test(char)) {
            e.preventDefault();
        }
    });

    // File validation on change (immediate feedback)
    $(document).on('change', '.cgs-form-wrapper input[type=file]', function(){
        var $input = $(this);
        if (!this.files || !this.files.length) return;
        var file = this.files[0];
        var allowed = ($input.data('allowed') || 'jpg,jpeg,png,pdf,webp').toString().toLowerCase().split(',');
        var maxKb = parseInt($input.data('maxkb') || 2048, 10);
        var ext = (file.name.split('.').pop() || '').toLowerCase();
        var $group = $input.closest('.cgs-field-group');
        $group.find('.cgs-file-error').remove();
        if (allowed.indexOf(ext) === -1) {
            $input.val('');
            $group.append('<div class="cgs-file-error" style="color:#c62828;font-size:0.85rem;margin-top:6px;">فرمت مجاز نیست. مجاز: '+allowed.join('، ')+'</div>');
            return;
        }
        if (file.size > maxKb * 1024) {
            $input.val('');
            var sizeLabel = maxKb >= 1024 ? (maxKb/1024).toFixed(1)+' مگابایت' : maxKb+' کیلوبایت';
            $group.append('<div class="cgs-file-error" style="color:#c62828;font-size:0.85rem;margin-top:6px;">حجم بیشتر از حد مجاز ('+sizeLabel+')</div>');
            return;
        }
        $group.find('.cgs-file-text').text(file.name);
    });

    
    // Credit plan selection: show only related fields
    $(document).on('change', '#cgs-plan-select', function(){
        var fields = ($(this).find(':selected').data('fields') || '').toString();
        var $wrap = $(this).closest('.cgs-form-wrapper');
        if (!fields) {
            $wrap.find('.cgs-field-group').show();
            $('#cgs-plan-info').text('');
            return;
        }
        var keys = fields.split(',').map(function(s){ return s.trim(); }).filter(Boolean);
        $wrap.find('.cgs-field-group').each(function(){
            var key = $(this).data('field-key') || $(this).find('[name]').first().attr('name') || '';
            if (keys.indexOf(key) !== -1 || !key) $(this).show();
            else $(this).hide();
        });
        $('#cgs-plan-info').text('فیلدهای مرتبط با این طرح نمایش داده شد ('+keys.length+' فیلد).');
    });

    $(document).ready(function(){
        initMultiStep();
        initLocationCascade();
        initChat();
    });


    // Global field constraints (max length + numeric)
    $(document).on('input', '.cgs-form-wrapper input, .cgs-form-wrapper textarea, .cgs-preview-mode input, .cgs-preview-mode textarea', function(){
        var $el = $(this);
        var maxLen = parseInt($el.attr('maxlength') || $el.data('maxlen') || $el.data('maxlength') || 0, 10);
        var charset = $el.data('charset') || '';
        var val = this.value;
        if (charset === 'numeric' || $el.hasClass('cgs-numeric') || $el.hasClass('cgs-tel') || $el.attr('inputmode') === 'numeric' || $el.attr('type') === 'tel') {
            val = val.replace(/[^0-9]/g, '');
        }
        if (maxLen > 0 && val.length > maxLen) {
            val = val.substring(0, maxLen);
        }
        if (this.value !== val) {
            this.value = val;
        }
    });
    $(document).on('keypress', '.cgs-form-wrapper input.cgs-numeric, .cgs-form-wrapper input.cgs-tel, .cgs-form-wrapper input[type=tel], .cgs-preview-mode input.cgs-numeric, .cgs-preview-mode input.cgs-tel', function(e){
        var char = String.fromCharCode(e.which);
        if (!/[0-9]/.test(char) && !e.ctrlKey && !e.metaKey && e.which !== 8 && e.which !== 0) {
            e.preventDefault();
        }
    });


    /* ========== AREA CODE (single source of truth) ========== */
    function cgsFillAreaCode(code) {
        code = (code || '').toString().replace(/[^0-9]/g, '');
        var $all = $('input.cgs-area-code, input[data-role="area_code"], input[name="area_code"], input[name$="_code"]');
        $all.each(function(){
            $(this).val(code).attr('value', code).prop('readonly', true);
        });
        $('.cgs-area-code-display, .cgs-area-code-box').text(code || '—');
        try { sessionStorage.setItem('cgs_area_code', code); } catch(e) {}
    }
    function cgsLookupCityCode(province, city) {
        var locations = (typeof cgsPublic !== 'undefined' && cgsPublic.locations) ? cgsPublic.locations : (window.cgsLocations || {});
        if (!province || !locations[province]) return '';
        var entry = locations[province];
        var fallback = entry.code || '';
        var cities = entry.cities || [];
        for (var i = 0; i < cities.length; i++) {
            var c = cities[i];
            if (typeof c === 'string' && c === city) return fallback;
            if (c && c.name === city) return c.code || fallback;
        }
        return fallback;
    }
    $(document).on('change', 'select.cgs-city, select[data-role="city"], select[name="city"]', function(){
        var city = $(this).val();
        var code = $(this).find('option:selected').attr('data-code') || '';
        if (!code) {
            var $form = $(this).closest('form, .cgs-form-wrapper, .cgs-preview-mode');
            var province = $form.find('select.cgs-province, select[data-role="province"], select[name="province"]').first().val();
            code = cgsLookupCityCode(province, city);
        }
        cgsFillAreaCode(code);
    });
    $(document).on('change', 'select.cgs-province, select[data-role="province"], select[name="province"]', function(){
        var province = $(this).val();
        var locations = (typeof cgsPublic !== 'undefined' && cgsPublic.locations) ? cgsPublic.locations : (window.cgsLocations || {});
        var code = (locations[province] && locations[province].code) ? locations[province].code : '';
        cgsFillAreaCode(code);
    });

    /* ========== IMAGE UPLOAD LIVE PREVIEW ========== */
    $(document).on('change', 'input[type=file], input.cgs-file-input', function(){
        var input = this;
        var $input = $(input);
        var $card = $input.closest('.cgs-field-group, .cgs-field-card, .cgs-file-upload, .cgs-upload-row, .cgs-field-control');
        var targetId = $input.data('preview-target');
        var $prev = targetId ? $('#' + targetId) : $card.find('.cgs-upload-preview').first();
        if (!$prev.length) {
            $prev = $('<div class="cgs-upload-preview" style="flex:0 0 120px;width:120px;height:120px;border:2px dashed #c5cae9;border-radius:14px;background:#f8fafc;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-top:8px;"></div>');
            $card.append($prev);
        }
        $prev.empty().css({borderStyle:'dashed', color:'#94a3b8'}).text('پیش‌نمایش تصویر');
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];
        $card.find('.cgs-file-selected').text(file.name + ' (' + Math.round(file.size/1024) + ' KB)').show();
        if (file.type && file.type.indexOf('image/') === 0) {
            var url = URL.createObjectURL(file);
            $prev.empty().css({borderStyle:'solid', borderColor:'#9fa8da', padding:0}).append(
                $('<img>', { src: url, alt: file.name, css: { width:'100%', height:'100%', objectFit:'cover', display:'block' } })
            );
        } else if (file.type === 'application/pdf' || /\.pdf$/i.test(file.name)) {
            $prev.empty().css({borderStyle:'solid', color:'#1a237e', fontWeight:700}).html('📄 PDF<br><span style="font-size:10px;font-weight:400;">'+file.name+'</span>');
        } else {
            $prev.empty().text(file.name);
        }
    });


    /* ========== CONDITIONAL LOGIC ========== */
    function cgsEvalConditionRule(rule, values) {
        var have = values[rule.field] != null ? String(values[rule.field]) : '';
        var want = rule.value != null ? String(rule.value) : '';
        switch (rule.op) {
            case 'equals': return have === want;
            case 'not_equals': return have !== want;
            case 'contains': return want !== '' && have.indexOf(want) !== -1;
            case 'empty': return have === '';
            case 'not_empty': return have !== '';
            default: return true;
        }
    }
    function cgsCollectFormValues($root) {
        var values = {};
        $root.find('input, select, textarea').each(function(){
            var n = this.name;
            if (!n) return;
            if (this.type === 'checkbox' || this.type === 'radio') {
                if (this.checked) values[n] = $(this).val();
            } else {
                values[n] = $(this).val();
            }
        });
        return values;
    }
    function cgsApplyConditions($root) {
        $root = $root && $root.length ? $root : $('.cgs-form-wrapper');
        var values = cgsCollectFormValues($root);
        $root.find('[data-cgs-conditions]').each(function(){
            var $el = $(this);
            var raw = $el.attr('data-cgs-conditions');
            if (!raw) return;
            var cond;
            try { cond = JSON.parse(raw); } catch(e) { return; }
            if (!cond || !cond.enabled || !cond.rules || !cond.rules.length) {
                $el.show().removeClass('cgs-cond-hidden');
                return;
            }
            var results = cond.rules.map(function(r){ return cgsEvalConditionRule(r, values); });
            var match = (cond.logic === 'or') ? results.some(Boolean) : results.every(Boolean);
            var visible = (cond.action === 'hide') ? !match : match;
            if (visible) {
                $el.show().removeClass('cgs-cond-hidden').find('input,select,textarea').prop('disabled', false);
            } else {
                $el.hide().addClass('cgs-cond-hidden').find('input,select,textarea').prop('disabled', true);
            }
        });
    }
    $(document).on('change input', '.cgs-form-wrapper input, .cgs-form-wrapper select, .cgs-form-wrapper textarea', function(){
        cgsApplyConditions($(this).closest('.cgs-form-wrapper'));
    });
    $(function(){ cgsApplyConditions($('.cgs-form-wrapper')); });

})(jQuery);


/* ===== ROBUST LANDLINE AREA CODE (final) ===== */
(function($){
  function locs(){
    if (typeof cgsPublic !== 'undefined' && cgsPublic.locations) return cgsPublic.locations;
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
})(jQuery);

(function($){
  function cgsTableRecalc($table){
    var $foot=$table.find('tfoot'); if(!$foot.length) return;
    $foot.find('[data-cgs-agg]').each(function(){
      var col=parseInt($(this).data('col'),10), mode=$(this).data('cgs-agg'), vals=[];
      $table.find('tbody tr').each(function(){
        var v=$(this).find('td').eq(col).find('input').val()||'';
        v=String(v).replace(/,/g,'').replace(/[۰-۹]/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d);});
        var n=parseFloat(v); if(!isNaN(n)) vals.push(n);
      });
      var out='—';
      if(vals.length){
        if(mode==='sum') out=vals.reduce(function(a,b){return a+b;},0);
        else if(mode==='avg') out=(vals.reduce(function(a,b){return a+b;},0)/vals.length).toFixed(2);
        else if(mode==='count') out=vals.length;
        else if(mode==='min') out=Math.min.apply(null,vals);
        else if(mode==='max') out=Math.max.apply(null,vals);
      }
      $(this).text(out);
    });
  }
  $(document).on('input change','.cgs-dynamic-table tbody input',function(){ cgsTableRecalc($(this).closest('table')); });
  $(document).on('click','.cgs-table-add-row',function(){
    var $w=$(this).closest('.cgs-dynamic-table-wrap'); var max=parseInt($w.attr('data-max-rows')||50,10);
    var $tb=$w.find('tbody'); if($tb.find('tr').length>=max){alert('حداکثر ردیف');return;}
    var $c=$tb.find('tr').last().clone(); $c.find('input').val(''); $tb.append($c);
  });
})(jQuery);
