<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$user = wp_get_current_user();
$app  = CGS_Member::get_current_application();
$statuses = cgs_get_statuses();
?>
<div class="cgs-dashboard">
    <div class="cgs-dash-header">
        <div class="cgs-dash-welcome">
            <h2>سلام، <?php echo esc_html( $user->display_name ); ?></h2>
            <p>به پنل کاربری شهر قسط خوش آمدید</p>
        </div>
        <a href="<?php echo esc_url( wp_logout_url( cgs_get_login_url() ) ); ?>" class="cgs-btn cgs-btn-secondary">خروج</a>
    </div>

    <?php if ( $app ) : 
        $status_info = $statuses[ $app->status ] ?? array( 'label' => $app->status, 'color' => '#999' );
    ?>
        <div class="cgs-dash-card">
            <h3>وضعیت درخواست شما</h3>
            <div class="cgs-status-badge" style="background:<?php echo esc_attr( $status_info['color'] ); ?>">
                <?php echo esc_html( $status_info['label'] ); ?>
            </div>
            <p><strong>کد پیگیری:</strong> <?php echo esc_html( $app->code ); ?></p>
            <p><strong>تاریخ ثبت:</strong> <?php echo esc_html( cgs_format_date( strtotime( $app->created_at ) ) ); ?></p>
            <p><strong>نوع:</strong> <?php echo esc_html( cgs_get_application_type( $app->type_key )['label'] ?? $app->type_key ); ?></p>
        </div>

        <!-- Chat Section -->
        <div class="cgs-dash-card cgs-chat-section">
            <h3>گفتگو با پشتیبانی</h3>
            <div class="cgs-chat-messages" id="cgs-chat-box" data-app-id="<?php echo (int) $app->id; ?>">
                <?php
                $messages = CGS_Chat::get_messages( $app->id );
                if ( empty( $messages ) ) {
                    echo '<p class="cgs-chat-empty">هنوز پیامی رد و بدل نشده است.</p>';
                } else {
                    foreach ( $messages as $msg ) {
                        $class = $msg['sender_type'] === 'admin' ? 'cgs-msg-admin' : 'cgs-msg-member';
                        echo '<div class="cgs-msg ' . $class . '">';
                        echo '<div class="cgs-msg-content">' . esc_html( $msg['message'] ) . '</div>';
                        echo '<div class="cgs-msg-time">' . esc_html( $msg['time_formatted'] ) . '</div>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
            <div class="cgs-chat-input">
                <textarea id="cgs-chat-message" placeholder="پیام خود را بنویسید..." rows="2"></textarea>
                <button type="button" id="cgs-send-message" class="cgs-btn cgs-btn-primary">ارسال</button>
            </div>
        </div>
    <?php else : ?>
        <div class="cgs-dash-card">
            <p>هنوز درخواستی برای شما ثبت یا تأیید نشده است.</p>
        </div>
    <?php endif; ?>
</div>
