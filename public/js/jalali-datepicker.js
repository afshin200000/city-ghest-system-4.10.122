/**
 * Jalali Datepicker with year & month dropdowns - City Ghest
 * Respects cgsPublic.jalaliSettings from admin
 */
(function($){
    'use strict';

    function getJalaliSettings() {
        var d = {
            start_year: 1320,
            end_year: 1410,
            format: 'YYYY/MM/DD',
            show_today_btn: '1',
            show_clear_btn: '1',
            close_on_select: '1',
            default_today: '0',
            month_dropdown: '1',
            year_dropdown: '1',
            locale_numbers: '0',
            week_start: '6',
            theme: 'default',
            position: 'auto',
            min_age: '',
            max_age: '',
            calendar_type: 'jalali'
        };
        if (typeof cgsPublic !== 'undefined' && cgsPublic.jalaliSettings) {
            $.extend(d, cgsPublic.jalaliSettings);
        }
        return d;
    }

    function toFaDigits(str) {
        return String(str).replace(/\d/g, function(d){
            return '۰۱۲۳۴۵۶۷۸۹'[parseInt(d,10)];
        });
    }

    function formatDate(jy, jm, jd, fmt, faNums) {
        var Y = String(jy), M = (jm < 10 ? '0' : '') + jm, D = (jd < 10 ? '0' : '') + jd;
        var out = fmt.replace('YYYY', Y).replace('MM', M).replace('DD', D);
        return faNums ? toFaDigits(out) : out;
    }

var MONTHS = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
    var WEEKDAYS = ['ش','ی','د','س','چ','پ','ج'];

    function gregorianToJalali(gy, gm, gd) {
        var g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
        var gy2 = (gm > 2) ? (gy + 1) : gy;
        var days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + g_d_m[gm - 1];
        var jy = -1595 + (33 * Math.floor(days / 12053));
        days %= 12053;
        jy += 4 * Math.floor(days / 1461);
        days %= 1461;
        if (days > 365) {
            jy += Math.floor((days - 1) / 365);
            days = (days - 1) % 365;
        }
        var jm, jd;
        if (days < 186) {
            jm = 1 + Math.floor(days / 31);
            jd = 1 + (days % 31);
        } else {
            jm = 7 + Math.floor((days - 186) / 30);
            jd = 1 + ((days - 186) % 30);
        }
        return [jy, jm, jd];
    }

    function jalaliToGregorian(jy, jm, jd) {
        var gy = jy + 1595;
        var days = -355668 + (365 * jy) + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4) + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30 + 186));
        gy += 400 * Math.floor(days / 146097);
        days %= 146097;
        if (days > 36524) {
            gy += 100 * Math.floor(--days / 36524);
            days %= 36524;
            if (days >= 365) days++;
        }
        gy += 4 * Math.floor(days / 1461);
        days %= 1461;
        if (days > 365) {
            gy += Math.floor((days - 1) / 365);
            days = (days - 1) % 365;
        }
        var gd = days + 1;
        var sal_a = [0,31,(gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0) ? 29 : 28,31,30,31,30,31,31,30,31,30,31];
        var gm;
        for (gm = 1; gm <= 12 && gd > sal_a[gm]; gm++) gd -= sal_a[gm];
        return [gy, gm, gd];
    }

    function daysInJalaliMonth(jy, jm) {
        if (jm <= 6) return 31;
        if (jm <= 11) return 30;
        return ((jy % 33) % 4 === 1) ? 30 : 29;
    }

    function CGSJalaliPicker($input) {
        var self = this;
        this.$input = $input;
        this.$wrap = $('<div class="cgs-jdp"></div>');
        this.jy = 1403; this.jm = 1; this.jd = 1;

        var now = new Date();
        var j = gregorianToJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
        this.jy = j[0]; this.jm = j[1]; this.jd = j[2];

        // Parse existing value
        var existing = ($input.val() || '').match(/(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/);
        if (existing) {
            this.jy = parseInt(existing[1], 10);
            this.jm = parseInt(existing[2], 10);
            this.jd = parseInt(existing[3], 10);
        }

        $input.attr('readonly', true).css('cursor', 'pointer');
        this.$wrap.appendTo(document.body).css({position:'fixed',zIndex:2147483646,display:'none'});
        this.$wrap.hide();

        $input.on('click focus', function(e){
            e.preventDefault();
            self.show();
        });

        $(document).on('click.cgsjdp', function(e){
            if (!$(e.target).closest('.cgs-jdp, input.cgs-jalali-date').length) {
                self.hide();
            }
        });
    }

    CGSJalaliPicker.prototype.show = function() {
        this.render();
        var $in = this.$input;
        var $w = this.$wrap;
        // همیشه به body بچسبد تا زیر overflow پیش‌نمایش نرود
        if (!$w.parent().is('body')) {
            $w.appendTo(document.body);
        }
        $w.css({ position: 'fixed', zIndex: 2147483646, visibility: 'hidden', display: 'block' });
        var rect = $in[0].getBoundingClientRect();
        var wh = $w.outerHeight() || 280;
        var ww = $w.outerWidth() || 280;
        var top = rect.bottom + 4;
        if (top + wh > window.innerHeight - 8) {
            top = Math.max(8, rect.top - wh - 4);
        }
        var left = rect.left;
        if (left + ww > window.innerWidth - 8) {
            left = Math.max(8, window.innerWidth - ww - 8);
        }
        if (left < 8) left = 8;
        $w.css({ top: top + 'px', left: left + 'px', visibility: 'visible', display: 'block' });
    };
    CGSJalaliPicker.prototype.hide = function() {
        this.$wrap.hide();
    };

    CGSJalaliPicker.prototype.render = function() {
        var self = this;
        var cfg = getJalaliSettings();
        var yStart = parseInt(cfg.start_year, 10) || 1320;
        var yEnd = parseInt(cfg.end_year, 10) || 1410;
        var faNums = cfg.locale_numbers === '1' || cfg.locale_numbers === 1;
        var theme = cfg.theme || 'default';

        var yearOpts = '';
        if (cfg.year_dropdown !== '0') {
            for (var y = yStart; y <= yEnd; y++) {
                var yl = faNums ? toFaDigits(y) : y;
                yearOpts += '<option value="'+y+'"'+(y===self.jy?' selected':'')+'>'+yl+'</option>';
            }
        }
        var monthOpts = '';
        if (cfg.month_dropdown !== '0') {
            for (var m = 1; m <= 12; m++) {
                monthOpts += '<option value="'+m+'"'+(m===self.jm?' selected':'')+'>'+MONTHS[m-1]+'</option>';
            }
        }

        var html = '<div class="cgs-jdp-header">';
        if (yearOpts) html += '<select class="cgs-jdp-year">'+yearOpts+'</select>';
        if (monthOpts) html += '<select class="cgs-jdp-month">'+monthOpts+'</select>';
        html += '</div><div class="cgs-jdp-weekdays">';
        WEEKDAYS.forEach(function(d){ html += '<span>'+d+'</span>'; });
        html += '</div><div class="cgs-jdp-days">';

        var g = jalaliToGregorian(this.jy, this.jm, 1);
        var firstDate = new Date(g[0], g[1]-1, g[2]);
        // week_start: 6=Sat (Iran default) → (getDay()+1)%7 already assumes Sat start
        var startDay = (firstDate.getDay() + 1) % 7;
        if (String(cfg.week_start) === '0') startDay = firstDate.getDay();
        else if (String(cfg.week_start) === '1') startDay = (firstDate.getDay() + 6) % 7;

        for (var i = 0; i < startDay; i++) html += '<span class="empty"></span>';
        var dim = daysInJalaliMonth(this.jy, this.jm);
        for (var d = 1; d <= dim; d++) {
            var cls = (d === this.jd) ? ' selected' : '';
            var dl = faNums ? toFaDigits(d) : d;
            html += '<span class="cgs-jdp-day'+cls+'" data-day="'+d+'">'+dl+'</span>';
        }
        html += '</div><div class="cgs-jdp-footer">';
        if (cfg.show_today_btn !== '0') {
            html += '<button type="button" class="cgs-jdp-today">امروز</button>';
        }
        if (cfg.show_clear_btn !== '0') {
            html += '<button type="button" class="cgs-jdp-clear">پاک کردن</button>';
        }
        html += '</div>';

        this.$wrap.html(html);
        this.$wrap.removeClass('cgs-jdp-theme-default cgs-jdp-theme-gold cgs-jdp-theme-dark cgs-jdp-theme-green');
        this.$wrap.addClass('cgs-jdp-theme-' + theme);

        this.$wrap.find('.cgs-jdp-year').on('change', function(e){
            e.stopPropagation();
            self.jy = parseInt($(this).val(), 10);
            self.render();
        });
        this.$wrap.find('.cgs-jdp-month').on('change', function(e){
            e.stopPropagation();
            self.jm = parseInt($(this).val(), 10);
            self.render();
        });
        this.$wrap.find('.cgs-jdp-day').on('click', function(e){
            e.stopPropagation();
            self.jd = parseInt($(this).data('day'), 10);
            var val = formatDate(self.jy, self.jm, self.jd, cfg.format || 'YYYY/MM/DD', faNums);
            self.$input.val(val).trigger('change');
            if (cfg.close_on_select !== '0') self.hide();
            else self.render();
        });
        this.$wrap.find('.cgs-jdp-today').on('click', function(e){
            e.stopPropagation();
            var now = new Date();
            var j = gregorianToJalali(now.getFullYear(), now.getMonth()+1, now.getDate());
            self.jy = j[0]; self.jm = j[1]; self.jd = j[2];
            var val = formatDate(self.jy, self.jm, self.jd, cfg.format || 'YYYY/MM/DD', faNums);
            self.$input.val(val).trigger('change');
            if (cfg.close_on_select !== '0') self.hide();
            else self.render();
        });
        this.$wrap.find('.cgs-jdp-clear').on('click', function(e){
            e.stopPropagation();
            self.$input.val('').trigger('change');
            self.hide();
        });
    };

    $(document).ready(function(){
        $('input.cgs-jalali-date, input[data-jdp="1"]').each(function(){
            if (!$(this).data('jdp-init')) {
                new CGSJalaliPicker($(this));
                $(this).data('jdp-init', 1);
            }
        });
    });
    window.CGSJalaliPicker = CGSJalaliPicker;
})(jQuery);
