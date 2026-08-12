<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="cgs-login-wrapper">
    <div class="cgs-login-card">
        <div class="cgs-login-header">
            <h2><span class="cgs-icon cgs-icon-lock"></span> ورود به پنل شهر قسط</h2>
            <p>سامانه مدیریت همکاری و اعتبار</p>
        </div>

        <form id="cgs-login-form" method="post">
            <div class="cgs-field-group">
                <label for="cgs-login-user"><span class="cgs-icon cgs-icon-user"></span> نام کاربری یا موبایل</label>
                <input type="text" id="cgs-login-user" name="log" required autocomplete="username">
            </div>
            <div class="cgs-field-group">
                <label for="cgs-login-pass"><span class="cgs-icon cgs-icon-lock"></span> رمز عبور</label>
                <input type="password" id="cgs-login-pass" name="pwd" required autocomplete="current-password">
            </div>
            <div class="cgs-field-group cgs-remember">
                <label>
                    <input type="checkbox" name="rememberme" value="forever"> مرا به خاطر بسپار
                </label>
            </div>
            <button type="submit" class="cgs-btn cgs-btn-primary cgs-btn-block"><span class="cgs-icon cgs-icon-check"></span> ورود</button>
        </form>

        <div class="cgs-login-message" style="display:none;"></div>
    </div>
</div>

<script>
jQuery(function($){
    $('#cgs-login-form').on('submit', function(e){
        e.preventDefault();
        var $form = $(this);
        var $msg  = $('.cgs-login-message');

        $.post('<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>', {
            log: $form.find('[name=log]').val(),
            pwd: $form.find('[name=pwd]').val(),
            rememberme: $form.find('[name=rememberme]').is(':checked') ? 'forever' : '',
            redirect_to: '<?php echo esc_url( cgs_get_dashboard_url() ); ?>'
        }).done(function(){
            window.location.href = '<?php echo esc_url( cgs_get_dashboard_url() ); ?>';
        }).fail(function(){
            $msg.removeClass('success').addClass('error').text('نام کاربری یا رمز عبور اشتباه است.').show();
        });
    });
});
</script>
