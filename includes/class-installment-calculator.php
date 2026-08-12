<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * محاسبه‌گر اقساط شهر قسط — موتور چندفرمولی
 *
 * روش‌ها (قابل گسترش):
 * - flat_coef: (اصل×مدت)×ضریب٪ ÷ مدت
 * - flat_principal_fee: (اصل + سود دوره) ÷ مدت
 * - bank: قسط ثابت بانکی
 * - tamin_social: منطق جدول تامین اجتماعی (نرخ ماهانه تامین‌کننده اولیه روی اصل نقدی)
 *
 * سهم نماینده:
 * - from_city: از سهم سود شهر قسط کسر می‌شود
 * - from_credit: از اعتبار تخصیص‌یافته مشتری کسر می‌شود
 */
class CGS_Installment_Calculator {

	const OPT_PLANS = 'cgs_installment_plans';

	public static function init() {
		add_shortcode( 'cgs_installment_calculator', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_ajax_cgs_calc_installment', array( __CLASS__, 'ajax_calc' ) );
		add_action( 'wp_ajax_nopriv_cgs_calc_installment', array( __CLASS__, 'ajax_calc' ) );
		add_action( 'wp_ajax_cgs_save_installment_plans', array( __CLASS__, 'ajax_save_plans' ) );
		add_action( 'wp_ajax_cgs_discover_coef', array( __CLASS__, 'ajax_discover_coef' ) );
		add_action( 'wp_ajax_cgs_calc_sensitivity', array( __CLASS__, 'ajax_sensitivity' ) );
		add_action( 'wp_ajax_cgs_calc_compare', array( __CLASS__, 'ajax_compare' ) );
		add_action( 'wp_ajax_cgs_calc_bank_all', array( __CLASS__, 'ajax_bank_all' ) );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'menu' ), 25 );
		}
	}

	public static function menu() {
		add_submenu_page(
			'city-ghest',
			'محاسبه‌گر اقساط',
			'محاسبه‌گر اقساط',
			'manage_options',
			'cgs-calculator',
			array( __CLASS__, 'admin_page' )
		);
	}

	/** فهرست روش‌های محاسبه — برای UI و اعتبارسنجی */
	
	/** سازمان‌های کسر از حقوق — پویا توسط ادمین */
	public static function salary_organizations() {
		$defaults = array(
			'tamin'   => 'تامین اجتماعی',
			'armed'   => 'نیروهای مسلح',
			'other'   => 'سایر',
		);
		$custom = get_option( 'cgs_salary_organizations', array() );
		if ( is_array( $custom ) && $custom ) {
			return $custom;
		}
		return $defaults;
	}

	/** وضعیت شغلی — پویا توسط ادمین */
	public static function employment_statuses() {
		$defaults = array(
			'employed'  => 'شاغل',
			'retired'   => 'بازنشسته',
			'pensioner' => 'مستمری‌بگیر',
		);
		$custom = get_option( 'cgs_employment_statuses', array() );
		if ( is_array( $custom ) && $custom ) {
			return $custom;
		}
		return $defaults;
	}

	/**
	 * سقف قدرت خرید از فیش حقوقی
	 * max_monthly = net_salary × payslip_pct/100
	 * برای تامین: L = C×(1+r×E)/E → C_max = L_max×E/(1+r×E)
	 */
	public static function payslip_ceiling( $net_salary, $plan, $months ) {
		$net = max( 0, (float) $net_salary );
		$pct = (float) ( $plan['payslip_net_pct'] ?? 0 );
		if ( $net <= 0 || $pct <= 0 ) {
			return array( 'max_monthly' => 0, 'max_principal' => 0, 'max_credit' => 0 );
		}
		$max_monthly = $net * ( $pct / 100 );
		$E = max( 1, (int) $months );
		$method = $plan['method'] ?? 'tamin_social';
		$max_principal = 0;
		if ( $method === 'tamin_social' ) {
			$rate = (float) ( $plan['primary_monthly_rate'] ?? 0.035 );
			if ( $rate > 1 ) {
				$rate = $rate / 100;
			}
			$denom = 1 + $rate * $E;
			$max_principal = $denom > 0 ? ( $max_monthly * $E / $denom ) : 0;
		} else {
			// تقریبی: جمع بازپرداخت حداکثر = قسط×مدت
			$coef = 0;
			if ( ! empty( $plan['durations'] ) ) {
				foreach ( $plan['durations'] as $d ) {
					if ( (int) ( $d['months'] ?? 0 ) === $E ) {
						$coef = (float) ( $d['coef'] ?? 0 );
						break;
					}
				}
			}
			// H = S*(1+coef/100*E)
			$factor = 1 + ( $coef / 100 ) * $E;
			$max_principal = $factor > 0 ? ( $max_monthly * $E / $factor ) : ( $max_monthly * $E );
		}
		$floor = (float) ( $plan['cash_floor'] ?? 0 );
		$ceiling = (float) ( $plan['cash_ceiling'] ?? 0 );
		if ( $ceiling > 0 ) {
			$max_principal = min( $max_principal, $ceiling );
		}
		if ( $floor > 0 && $max_principal < $floor ) {
			// قدرت خرید کمتر از کف طرح
		}
		return array(
			'max_monthly'   => round( $max_monthly ),
			'max_principal' => round( $max_principal ),
			'max_credit'    => round( $max_principal ),
			'payslip_pct'   => $pct,
			'net_salary'    => round( $net ),
		);
	}

	public static function plan_categories() {
		$defaults = array(
			'salary_auto'       => 'طرح خودکار کسر از حقوق متقاضی',
			'check'             => 'طرح چکی',
			'self_deposit'      => 'طرح واریز به حساب خود متقاضی توسط خودش',
			'supplier_deposit'  => 'طرح واریز به حساب بانکی تامین‌کننده اعتبار توسط متقاضی',
		);
		$custom = get_option( 'cgs_plan_category_labels', array() );
		if ( is_array( $custom ) ) {
			foreach ( $custom as $k => $v ) {
				if ( $v !== '' ) {
					$defaults[ $k ] = $v;
				}
			}
		}
		return $defaults;
	}

	/** روش‌های مجاز هر دسته طرح */
	public static function methods_for_category( $category ) {
		$map = array(
			'salary_auto'      => array( 'tamin_social', 'flat_coef', 'flat_principal_fee' ),
			'check'            => array( 'manisa_digital', 'flat_coef', 'razi_leasing' ),
			'self_deposit'     => array( 'flat_coef', 'flat_principal_fee', 'bank' ),
			'supplier_deposit' => array( 'flat_coef', 'flat_principal_fee', 'bank', 'razi_leasing' ),
		);
		return $map[ $category ] ?? array_keys( self::methods_list_raw() );
	}

	public static function methods_list_raw() {
		return array(
			'tamin_social'       => 'کسر از حقوق / تامین اجتماعی',
			'flat_coef'          => 'ضریب ثابت روی اصل',
			'flat_principal_fee' => 'کارمزد ثابت دوره روی اصل',
			'bank'               => 'قسط ثابت بانکی تقریبی',
			'manisa_digital'     => 'مانسیا — طرح چکی',
			'razi_leasing'       => 'لیزینگ رازی',
		);
	}

	public static function methods_list() {
		$raw = self::methods_list_raw();
		$custom = get_option( 'cgs_method_labels', array() );
		if ( is_array( $custom ) ) {
			foreach ( $custom as $k => $v ) {
				if ( isset( $raw[ $k ] ) && $v !== '' ) {
					$raw[ $k ] = $v;
				}
			}
		}
		return $raw;
	}


	/**
	 * فرمت پول ایرانی با جداکننده /
	 */
	public static function format_money( $n, $decimals = 0 ) {
		$n = (float) $n;
		$neg = $n < 0;
		$n = abs( $n );
		if ( $decimals > 0 ) {
			$s = number_format( $n, $decimals, '.', '' );
			$parts = explode( '.', $s );
			$int = $parts[0];
			$dec = isset( $parts[1] ) ? $parts[1] : '';
			$int = preg_replace( '/\B(?=(\d{3})+(?!\d))/', '/', $int );
			$out = $dec !== '' ? ( $int . '.' . $dec ) : $int;
		} else {
			$int = (string) (int) round( $n );
			$out = preg_replace( '/\B(?=(\d{3})+(?!\d))/', '/', $int );
		}
		return ( $neg ? '-' : '' ) . $out;
	}

	public static function default_plans() {
		return array(
			array(
				'id'          => 'salary_tamin',
				'name'        => 'کسر از حقوق بازنشستگان تامین اجتماعی',
				'active'      => 1,
				'method'      => 'tamin_social',
				'digital_fee' => 0,
				'digital_fee_percent' => 0,
				'supplier_base_on' => 'principal',
				'city_base_on'     => 'principal',
				// نرخ ماهانه تامین‌کننده اولیه (مثل 0.035 = 3.5٪ ماهانه)
				// ۱) نرخ ماهانه تامین‌کننده اولیه — درصد (۳.۵٪ = 0.035 اعشار اکسل)
				'primary_monthly_rate' => 3.5,
				'primary_rate_is_percent' => 1,
				// ۲) کسر ثانویه: ۳٪ از اصل = ۹۰ میلیون روی ۳ میلیارد (یا مبلغ ثابت)
				'supplier_coef' => 3.0,
				'supplier_fixed_fee' => 0,
				// ۳) سهم شهر قسط: ۷٪ از اصل = ۲۱۰ میلیون روی ۳ میلیارد
				'city_coef' => 7.0,
				'city_fixed_fee' => 0,
				// سهم نماینده (درصد از اصل یا مبلغ ثابت)
				'agent_coef'    => 0,
				'agent_mode'    => 'from_city', // from_city | from_credit
				'guarantee_pct' => 120,
				'need_guarantee_check' => 1,
				'guarantee_on_pi' => 1,
				'installment_checks' => 0,
				'applicant_guarantee_check' => 0,
				'guarantor_guarantee_check' => 0,
				'cancel_penalty_pct' => 25,
				'cash_ceiling'  => 3000000000,
				'cash_floor'     => 0,
				'guarantor_threshold' => 500000000,
				'ceiling_source' => 'supplier',
				'city_base_on' => 'principal',
				'durations'   => array(
					array( 'months' => 6,  'coef' => 5.7407407407, 'steps' => array( 1 ) ),
					array( 'months' => 12, 'coef' => 4.8148148148, 'steps' => array( 1 ) ),
					array( 'months' => 18, 'coef' => 4.5061728395, 'steps' => array( 1 ) ),
					array( 'months' => 24, 'coef' => 4.3518518519, 'steps' => array( 1 ) ),
					array( 'months' => 30, 'coef' => 4.2592592593, 'steps' => array( 1 ) ),
					array( 'months' => 36, 'coef' => 4.1975308642, 'steps' => array( 1 ) ),
				),
				'result_fields' => array(
					'plan_name' => 1, 'principal' => 1, 'months' => 1, 'step' => 1,
					'digital_fee' => 1, 'final_coef' => 1, 'purchasing_power' => 1,
					'monthly_installment' => 1, 'period_installment' => 1, 'total_repay' => 1,
				),
			),
			array(
				'id'          => 'sayadi_check',
				'name'        => 'طرح چک صیادی',
				'active'      => 1,
				'method'      => 'flat_coef',
				'digital_fee' => 500000,
				'digital_fee_percent' => 0,
				'supplier_base_on' => 'principal',
				'city_base_on'     => 'supplier_deposit',
				'primary_monthly_rate' => 0,
				'supplier_coef' => 1.5,
				'city_coef'     => 2.0,
				'agent_coef'    => 0,
				'agent_mode'    => 'from_city',
				'guarantee_pct' => 120,
				'cancel_penalty_pct' => 25,
				'cash_ceiling'  => 0,
				'cash_floor'     => 10000000,
				'guarantor_threshold' => 200000000,
				'ceiling_source' => 'admin',
				'durations'   => array(
					array( 'months' => 6,  'coef' => 5.7,  'steps' => array( 1 ) ),
					array( 'months' => 12, 'coef' => 10.5, 'steps' => array( 1, 2 ) ),
				),
				'result_fields' => array(
					'plan_name' => 1, 'principal' => 1, 'months' => 1, 'step' => 1,
					'digital_fee' => 1, 'final_coef' => 1, 'purchasing_power' => 1,
					'monthly_installment' => 1, 'period_installment' => 1, 'total_repay' => 1,
				),
			),

			array(
				'id' => 'manisa_digital', 'name' => 'مانسیا — طرح چکی', 'active' => 1,
				'plan_category' => 'check',
				'method' => 'manisa_digital',
				'secondary_chain_pct' => 6.6,
				'city_chain_pct' => 6.6, 'digital_fee' => 0, 'digital_fee_percent' => 0,
				'supplier_base_on' => 'principal', 'city_base_on' => 'supplier_deposit',
				'primary_monthly_rate' => 0, 'primary_fee_per_month' => 35000000, 'chain_deduction_coef' => 0.132,
				'supplier_coef' => 0, 'city_coef' => 0, 'agent_coef' => 0, 'agent_mode' => 'from_city',
				'guarantee_pct' => 150, 'cancel_penalty_pct' => 0, 'cash_ceiling' => 3000000000,
				'cash_floor' => 0, 'guarantor_threshold' => 0, 'ceiling_source' => 'supplier', 'always_guarantor' => 0,
				'durations' => array(
					array( 'months' => 12, 'coef' => 0, 'sum_rate' => 1.128916, 'steps' => array( 1 ) ),
					array( 'months' => 18, 'coef' => 0, 'sum_rate' => 1.19185866676, 'steps' => array( 1 ) ),
				),
				'result_fields' => array( 'plan_name'=>1,'principal'=>1,'months'=>1,'step'=>1,'digital_fee'=>1,'final_coef'=>1,'purchasing_power'=>1,'monthly_installment'=>1,'period_installment'=>1,'total_repay'=>1 ),
			),
			array(
				'id' => 'razi_diana', 'name' => 'لیزینگ رازی (دایانا) — فروش چکی', 'active' => 1,
				'method' => 'razi_leasing', 'digital_fee' => 0, 'digital_fee_percent' => 0,
				'supplier_base_on' => 'principal', 'city_base_on' => 'supplier_deposit',
				'primary_monthly_rate' => 0, 'supplier_fixed_fee' => 90000000, 'supplier_vat_rate' => 0.1, 'city_vat_rate' => 0.1,
				'supplier_coef' => 0, 'city_coef' => 0, 'agent_coef' => 0, 'agent_mode' => 'from_city',
				'guarantee_pct' => 140, 'cancel_penalty_pct' => 0, 'cash_ceiling' => 3000000000,
				'cash_floor' => 0, 'guarantor_threshold' => 0, 'ceiling_source' => 'admin', 'always_guarantor' => 1,
				'durations' => array(
					array( 'months' => 2, 'coef' => 0, 'sum_rate' => 1.0433333332, 'prepay' => 135300000, 'city_share' => 169836363.3, 'steps' => array( 2 ) ),
					array( 'months' => 4, 'coef' => 0, 'sum_rate' => 1.0547471308, 'prepay' => 171600000, 'city_share' => 156630000, 'steps' => array( 1 ) ),
					array( 'months' => 4, 'coef' => 0, 'sum_rate' => 1.0654594892, 'prepay' => 201300000, 'city_share' => 164849999.7, 'steps' => array( 2 ) ),
					array( 'months' => 8, 'coef' => 0, 'sum_rate' => 1.11062995216, 'prepay' => 320100000, 'city_share' => 153769999.8, 'steps' => array( 2 ) ),
					array( 'months' => 12, 'coef' => 0, 'sum_rate' => 1.14636166792, 'prepay' => 412500000, 'city_share' => 159780000, 'steps' => array( 1 ) ),
					array( 'months' => 12, 'coef' => 0, 'sum_rate' => 1.15702223404, 'prepay' => 432300000, 'city_share' => 158700000, 'steps' => array( 2 ) ),
					array( 'months' => 12, 'coef' => 0, 'sum_rate' => 1.16761096264, 'prepay' => 374220000, 'city_share' => 156780000, 'steps' => array( 3 ) ),
					array( 'months' => 14, 'coef' => 0, 'sum_rate' => 1.17003564536, 'prepay' => 465300000, 'city_share' => 180000000, 'steps' => array( 1 ) ),
					array( 'months' => 14, 'coef' => 0, 'sum_rate' => 1.1806752556, 'prepay' => 481800000, 'city_share' => 180000000, 'steps' => array( 2 ) ),
				),
				'result_fields' => array( 'plan_name'=>1,'principal'=>1,'months'=>1,'step'=>1,'digital_fee'=>1,'final_coef'=>1,'purchasing_power'=>1,'monthly_installment'=>1,'period_installment'=>1,'total_repay'=>1 ),
			),

		);
	}

	public static function get_plans() {
		$plans = get_option( 'cgs_installment_plans', null );
		if ( ! is_array( $plans ) || empty( $plans ) ) {
			$plans = self::default_plans();
			update_option( 'cgs_installment_plans', $plans, false );
			return $plans;
		}
		// نرمال‌سازی نرخ اولیه: اعشار ذخیره‌شده → درصد برای UI
		foreach ( $plans as &$pl ) {
			if ( ( $pl['method'] ?? '' ) === 'tamin_social' || ( $pl['id'] ?? '' ) === 'salary_tamin' ) {
				$pl['city_base_on'] = 'principal';
				if ( ! empty( $pl['primary_monthly_rate'] ) && (float) $pl['primary_monthly_rate'] > 0 && (float) $pl['primary_monthly_rate'] < 1 ) {
					$pl['primary_monthly_rate'] = round( (float) $pl['primary_monthly_rate'] * 100, 4 );
				}
				if ( empty( $pl['supplier_coef'] ) && empty( $pl['supplier_fixed_fee'] ) ) {
					$pl['supplier_coef'] = 3.0;
				}
				if ( empty( $pl['city_coef'] ) && empty( $pl['city_fixed_fee'] ) ) {
					$pl['city_coef'] = 7.0;
				}
			}
		}
		unset( $pl );
		return $plans;
	}

	public static function get_plan( $id ) {
		foreach ( self::get_plans() as $pl ) {
			if ( ( $pl['id'] ?? '' ) === $id ) {
				return $pl;
			}
		}
		return null;
	}

	/**
	 * کشف ضریب از: اصل درخواستی + واریزی تامین‌کننده + مدت
	 * ضریب ماهانه تامین‌کننده اولیه ≈ (اصل‌وفرع / اصل) − ۱  سپس / مدت
	 * اگر واریزی داده شود، کارمزد ثانویه = اصل − واریزی
	 */
	public static function discover_coef( $principal, $supplier_deposit, $months, $total_repay = 0 ) {
		$principal = max( 0, (float) $principal );
		$deposit   = max( 0, (float) $supplier_deposit );
		$months    = max( 1, (int) $months );
		$secondary_fee = max( 0, $principal - $deposit );
		$secondary_pct = $principal > 0 ? ( $secondary_fee / $principal * 100 ) : 0;

		$result = array(
			'principal'           => round( $principal ),
			'supplier_deposit'    => round( $deposit ),
			'months'              => $months,
			'secondary_fee'       => round( $secondary_fee ),
			'secondary_fee_pct'   => round( $secondary_pct, 4 ),
			'primary_monthly_rate'=> null,
			'primary_monthly_pct' => null,
			'implied_total_factor'=> null,
			'note'                => '',
		);

		if ( $total_repay > 0 && $principal > 0 ) {
			// اصل‌وفرع معلوم: rate = (total/principal - 1) / months
			$factor = $total_repay / $principal;
			$rate   = ( $factor - 1 ) / $months;
			$result['implied_total_factor'] = round( $factor, 6 );
			$result['primary_monthly_rate'] = round( $rate, 6 );
			$result['primary_monthly_pct']  = round( $rate * 100, 4 );
			$result['note'] = 'ضریب از روی جمع بازپرداخت (اصل‌وفرع) و اصل استخراج شد.';
		} elseif ( $principal > 0 && $deposit > 0 ) {
			$result['note'] = 'کارمزد تامین‌کننده ثانویه از اختلاف اصل و واریزی به‌دست آمد. برای نرخ ماهانه اولیه، جمع اصل‌وفرع (یا مبلغ هر قسط×تعداد) را هم وارد کنید.';
		} else {
			$result['note'] = 'اصل و مدت الزامی است.';
		}
		return $result;
	}

	/**
	 * موتور اصلی — بر اساس method به فرمول مربوطه می‌رود
	 */
	public static function calculate_full( $args ) {
		$principal = max( 0, (float) ( $args['principal'] ?? 0 ) );
		$plan_id   = sanitize_key( $args['plan_id'] ?? '' );
		$months    = max( 1, (int) ( $args['months'] ?? 12 ) );
		$step      = max( 1, (int) ( $args['step'] ?? 1 ) );
		$plan      = $plan_id ? self::get_plan( $plan_id ) : null;

		$method = $args['method'] ?? ( $plan['method'] ?? 'flat_coef' );
		if ( ! array_key_exists( $method, self::methods_list() ) ) {
			$method = 'flat_coef';
		}

		$supplier_coef = isset( $args['supplier_coef'] ) ? (float) $args['supplier_coef'] : (float) ( $plan['supplier_coef'] ?? 0 );
		$city_coef     = isset( $args['city_coef'] ) ? (float) $args['city_coef'] : (float) ( $plan['city_coef'] ?? 0 );
		$agent_coef    = isset( $args['agent_coef'] ) ? (float) $args['agent_coef'] : (float) ( $plan['agent_coef'] ?? 0 );
		$agent_mode    = $args['agent_mode'] ?? ( $plan['agent_mode'] ?? 'from_city' );
		$digital_fee   = isset( $args['digital_fee'] ) ? (float) $args['digital_fee'] : (float) ( $plan['digital_fee'] ?? 0 );
		$digital_pct   = (float) ( $plan['digital_fee_percent'] ?? 0 );
		$primary_rate  = isset( $args['primary_monthly_rate'] ) ? (float) $args['primary_monthly_rate'] : (float) ( $plan['primary_monthly_rate'] ?? 0 );
		// ادمین درصد می‌دهد (مثلاً ۳.۵). اگر > ۱ باشد یعنی درصد است → اعشار
		if ( $primary_rate > 1 ) {
			$primary_rate = $primary_rate / 100;
		}
		// مبالغ ثابت کسورات یک‌باره (اولویت بر درصد)
		// اولویت با درصد از اصل؛ مبلغ ثابت فقط اگر درصد صفر باشد
		$supplier_fixed = 0;
		$city_fixed = 0;
		if ( $supplier_coef <= 0 ) {
			if ( isset( $args['supplier_fixed_fee'] ) && (float) $args['supplier_fixed_fee'] > 0 ) {
				$supplier_fixed = (float) $args['supplier_fixed_fee'];
			} elseif ( $plan && (float) ( $plan['supplier_fixed_fee'] ?? 0 ) > 0 ) {
				$supplier_fixed = (float) $plan['supplier_fixed_fee'];
			}
		}
		if ( $city_coef <= 0 ) {
			if ( isset( $args['city_fixed_fee'] ) && (float) $args['city_fixed_fee'] > 0 ) {
				$city_fixed = (float) $args['city_fixed_fee'];
			} elseif ( $plan && (float) ( $plan['city_fixed_fee'] ?? 0 ) > 0 ) {
				$city_fixed = (float) $plan['city_fixed_fee'];
			}
		}
		$guarantee_pct = (float) ( $plan['guarantee_pct'] ?? 120 );
		$cancel_pct    = (float) ( $plan['cancel_penalty_pct'] ?? 25 );
		$ceiling       = (float) ( $plan['cash_ceiling'] ?? 0 );

		// سقف نقدی
		$cash = $principal;
		$amount_warning = '';
		$amount_ok = true;
		$floor_chk = (float) ( $plan['cash_floor'] ?? 0 );
		if ( $floor_chk > 0 && $principal > 0 && $principal < $floor_chk ) {
			$amount_ok = false;
			$amount_warning = 'مبلغ واردشده از کف اعتبار مجاز کمتر است. کف مجاز: ' . number_format( $floor_chk, 0, '', '/' ) . ' ریال';
		}
		if ( $ceiling > 0 && $principal > $ceiling ) {
			$amount_ok = false;
			$amount_warning = 'مبلغ واردشده از سقف اعتبار مجاز بیشتر است. سقف مجاز: ' . number_format( $ceiling, 0, '', '/' ) . ' ریال';
			$cash = $ceiling;
		}

		// سقف فیش حقوقی
		$payslip_info = null;
		if ( ! empty( $args['net_salary'] ) && $plan ) {
			$payslip_info = self::payslip_ceiling( $args['net_salary'], $plan, $months );
			if ( ( $plan['ceiling_source'] ?? '' ) === 'payslip' && $payslip_info['max_principal'] > 0 ) {
				if ( $principal > $payslip_info['max_principal'] ) {
					$amount_ok = false;
					$amount_warning = 'مبلغ از سقف قدرت خرید بر اساس فیش حقوقی بیشتر است. سقف مجاز: ' . number_format( $payslip_info['max_principal'], 0, '', '/' ) . ' ریال';
					$cash = $payslip_info['max_principal'];
				}
			}
		}


		$coef = isset( $args['coef'] ) ? (float) $args['coef'] : null;
		if ( $coef === null && $plan && ! empty( $plan['durations'] ) ) {
			foreach ( $plan['durations'] as $d ) {
				if ( (int) ( $d['months'] ?? 0 ) === $months ) {
					$coef = (float) ( $d['coef'] ?? 0 );
					$allowed_steps = (array) ( $d['steps'] ?? array( 1 ) );
					if ( ! in_array( $step, array_map( 'intval', $allowed_steps ), true ) ) {
						$step = (int) ( $allowed_steps[0] ?? 1 );
					}
					if ( isset( $d['supplier_coef'] ) && $d['supplier_coef'] !== '' && ! isset( $args['supplier_coef'] ) ) {
						$supplier_coef = (float) $d['supplier_coef'];
					}
					if ( isset( $d['city_coef'] ) && $d['city_coef'] !== '' && ! isset( $args['city_coef'] ) ) {
						$city_coef = (float) $d['city_coef'];
					}
					if ( isset( $d['digital_fee'] ) && $d['digital_fee'] !== '' && ! isset( $args['digital_fee'] ) ) {
						$digital_fee = (float) $d['digital_fee'];
					}
					if ( isset( $d['primary_monthly_rate'] ) && $d['primary_monthly_rate'] !== '' ) {
						$primary_rate = (float) $d['primary_monthly_rate'];
					}
					break;
				}
			}
		}
		if ( $coef === null ) {
			$coef = (float) ( $args['rate'] ?? 0 );
		}

		// ——— فرمول‌ها ———
		// پارامترهای مدت (sum_rate، پیش‌پرداخت، …)
		$dur_meta = array();
		if ( $plan && ! empty( $plan['durations'] ) ) {
			foreach ( $plan['durations'] as $d ) {
				if ( (int) ( $d['months'] ?? 0 ) === $months ) {
					$st = array_map( 'intval', (array) ( $d['steps'] ?? array( 1 ) ) );
					if ( in_array( $step, $st, true ) || count( $st ) === 1 ) {
						$dur_meta = $d;
						if ( ! in_array( $step, $st, true ) ) {
							$step = (int) ( $st[0] ?? 1 );
						}
						break;
					}
					$dur_meta = $d; // fallback same months
				}
			}
		}

		if ( $method === 'tamin_social' ) {
			// ضریب طرح از مدت انتخاب‌شده
			$plan_coef_arg = null;
			if ( $plan && ! empty( $plan['durations'] ) ) {
				foreach ( $plan['durations'] as $_d ) {
					if ( (int) ( $_d['months'] ?? 0 ) === (int) $months && isset( $_d['coef'] ) && $_d['coef'] !== '' ) {
						$plan_coef_arg = (float) $_d['coef'];
						break;
					}
				}
			}
			if ( $plan_coef_arg === null && $coef !== null ) {
				$plan_coef_arg = (float) $coef;
			}
			$out = self::formula_tamin_social( array(
				'cash' => $cash,
				'months' => $months,
				'step' => $step,
				'plan_coef' => $plan_coef_arg !== null ? $plan_coef_arg : 0,
				'coef' => $plan_coef_arg !== null ? $plan_coef_arg : 0,
				'primary_monthly_rate' => $primary_rate,
				'supplier_coef' => $supplier_coef,
				'supplier_fixed_fee' => $supplier_fixed,
				'city_coef' => $city_coef,
				'city_fixed_fee' => $city_fixed,
				'agent_coef' => $agent_coef,
				'agent_mode' => $agent_mode,
				'digital_fee' => $digital_fee + ( $cash * $digital_pct / 100 ),
				'guarantee_pct' => $guarantee_pct,
				'cancel_pct' => $cancel_pct,
				'need_guarantee_check' => isset( $args['need_guarantee_check'] ) ? $args['need_guarantee_check'] : ( $plan['need_guarantee_check'] ?? 1 ),
				'installment_checks' => $plan['installment_checks'] ?? 0,
				'applicant_guarantee_check' => $plan['applicant_guarantee_check'] ?? 0,
				'guarantor_guarantee_check' => $plan['guarantor_guarantee_check'] ?? 0,
				'guarantor_threshold' => (float) ( $plan['guarantor_threshold'] ?? 0 ),
				'city_base_on' => $plan['city_base_on'] ?? ( $args['city_base_on'] ?? 'principal' ),
				'guarantee_pct_applicant' => (float) ( $plan['guarantee_pct_applicant'] ?? $plan['guarantee_pct'] ?? 120 ),
				'guarantee_pct_guarantor' => (float) ( $plan['guarantee_pct_guarantor'] ?? 0 ),
			) );
		} elseif ( $method === 'manisa_digital' ) {
			$pc = null;
			if ( $plan && ! empty( $plan['durations'] ) ) {
				foreach ( $plan['durations'] as $_d ) {
					if ( (int) ( $_d['months'] ?? 0 ) === (int) $months ) {
						$pc = isset( $_d['coef'] ) ? (float) $_d['coef'] : null;
						$primary_from_dur = isset( $_d['primary_pct'] ) ? (float) $_d['primary_pct'] : null;
						break;
					}
				}
			}
			$out = self::formula_manisa_digital( array(
				'cash' => $cash,
				'months' => $months,
				'step' => $step,
				'plan_coef' => $pc !== null ? $pc : (float) ( $coef ?? 0 ),
				'coef' => $pc !== null ? $pc : (float) ( $coef ?? 0 ),
				'primary_pct' => $primary_from_dur ?? ( $dur_meta['primary_pct'] ?? null ),
				'secondary_chain_pct' => (float) ( $plan['secondary_chain_pct'] ?? 6.6 ),
				'secondary_base_on' => (string) ( $plan['secondary_base_on'] ?? ( $args['secondary_base_on'] ?? 'primary_deposit' ) ),
				'city_chain_pct' => (float) ( $plan['city_chain_pct'] ?? 6.6 ),
				'digital_infra_pct' => (float) ( $plan['digital_infra_pct'] ?? $plan['supplier_coef'] ?? 0 ),
				'supplier_coef' => (float) ( $plan['supplier_coef'] ?? 0 ),
				'agent_coef' => $agent_coef,
				'agent_mode' => $agent_mode,
				'guarantee_pct' => $guarantee_pct ?: 150,
				'guarantee_pct_applicant' => (float) ( $plan['guarantee_pct_applicant'] ?? 150 ),
				'guarantee_pct_guarantor' => (float) ( $plan['guarantee_pct_guarantor'] ?? 0 ),
				'need_guarantee_check' => $plan['need_guarantee_check'] ?? 1,
			) );
				} elseif ( $method === 'razi_leasing' ) {
			$pc_r = null; $pri_r = null; $prepay_r = null;
			if ( $plan && ! empty( $plan['durations'] ) ) {
				foreach ( $plan['durations'] as $_d ) {
					if ( (int) ( $_d['months'] ?? 0 ) === (int) $months ) {
						$pc_r = isset( $_d['coef'] ) ? (float) $_d['coef'] : null;
						$pri_r = isset( $_d['primary_pct'] ) ? (float) $_d['primary_pct'] : null;
						$prepay_r = isset( $_d['prepay'] ) ? (float) $_d['prepay'] : null;
						break;
					}
				}
			}
			$out = self::formula_razi_leasing( array(
				'cash' => $cash,
				'months' => $months,
				'step' => $step,
				'plan_coef' => $pc_r !== null ? $pc_r : (float) ( $coef ?? 0 ),
				'coef' => $pc_r !== null ? $pc_r : (float) ( $coef ?? 0 ),
				'primary_pct' => $pri_r,
				'prepay' => $prepay_r ?? 0,
				'prepay_pct' => (float) ( $plan['prepay_pct'] ?? $plan['secondary_chain_pct'] ?? 6.6 ),
				'city_chain_pct' => (float) ( $plan['city_chain_pct'] ?? 6.6 ),
				'guarantee_pct' => $guarantee_pct ?: 140,
				'guarantee_pct_applicant' => (float) ( $plan['guarantee_pct_applicant'] ?? 140 ),
				'guarantee_pct_guarantor' => (float) ( $plan['guarantee_pct_guarantor'] ?? 0 ),
				'need_guarantee_check' => $plan['need_guarantee_check'] ?? 1,
			) );
		} elseif ( $method === 'bank' ) {
			$out = self::formula_bank( $cash, $coef, $months, $step, $supplier_coef, $city_coef, $agent_coef, $agent_mode, $digital_fee, $digital_pct, $plan );
		} elseif ( $method === 'flat_principal_fee' ) {
			$out = self::formula_flat_principal_fee( $cash, $coef, $months, $step, $supplier_coef, $city_coef, $agent_coef, $agent_mode, $digital_fee, $digital_pct, $plan );
		} else {
			$out = self::formula_flat_coef( $cash, $coef, $months, $step, $supplier_coef, $city_coef, $agent_coef, $agent_mode, $digital_fee, $digital_pct, $plan );
		}

		$out['plan_id']   = $plan_id;
		$out['plan_name'] = $plan['name'] ?? '';
		$out['method']    = $method;

		// ضریب سود نهایی مشتری = دقیقاً «ضریب طرح» مدت انتخاب‌شده (تب طرح‌ها)
		$plan_coef = null;
		if ( $plan && ! empty( $plan['durations'] ) ) {
			foreach ( $plan['durations'] as $d ) {
				if ( (int) ( $d['months'] ?? 0 ) === (int) $months ) {
					if ( isset( $d['coef'] ) && $d['coef'] !== '' && $d['coef'] !== null ) {
						$plan_coef = (float) $d['coef'];
					}
					break;
				}
			}
		}
		if ( ( $plan_coef === null || $plan_coef <= 0 ) && isset( $args['coef'] ) && $args['coef'] !== '' && $args['coef'] !== null && (float) $args['coef'] > 0 ) {
			$plan_coef = (float) $args['coef'];
		}
		// فقط اگر ضریب طرح در جدول >0 باشد جایگزین می‌شود؛ وگرنه همان محاسبه اکسل در فرمول می‌ماند
		if ( $plan_coef !== null && $plan_coef > 0 ) {
			$out['plan_coef'] = $plan_coef;
			$out['final_coef'] = $plan_coef;
			$out['coef'] = $plan_coef;
			$out['monthly_coef_exact'] = $plan_coef;
			$out['monthly_coef_rounded'] = round( $plan_coef, 7 );
			$out['monthly_coef_rounded_int'] = (int) round( $plan_coef );
			$out['customer_monthly_coef'] = $plan_coef;
		}

		$out['result_fields'] = $plan['result_fields'] ?? array();
		$out['preview_fields'] = $plan['preview_fields'] ?? ( $plan['result_fields'] ?? array() );
		// سیاست چک — فقط برای نمایش/ downstream؛ محاسبات عددی H/L/S تغییر نمی‌کند
		$need_g = ! empty( $plan['need_guarantee_check'] );
		$on_pi = ! isset( $plan['guarantee_on_pi'] ) || ! empty( $plan['guarantee_on_pi'] );
		$pi = (float) ( $out['principal_interest'] ?? 0 );
		$g_pct = (float) ( $plan['guarantee_pct'] ?? 120 );
		if ( $need_g && $on_pi && $pi > 0 ) {
			$out['guarantee_check'] = round( $pi * ( $g_pct / 100 ) );
		} elseif ( ! $need_g ) {
			$out['guarantee_check'] = 0;
		}
		$thr = (float) ( $plan['guarantor_threshold'] ?? 0 );
		$cash = (float) ( $out['principal'] ?? 0 );
		$out['check_policy'] = array(
			'need_guarantee_check' => $need_g ? 1 : 0,
			'guarantee_pct' => $g_pct,
			'guarantee_amount' => (float) ( $out['guarantee_check'] ?? 0 ),
			'installment_checks' => ! empty( $plan['installment_checks'] ) ? 1 : 0,
			'applicant_guarantee_check' => ! empty( $plan['applicant_guarantee_check'] ) ? 1 : 0,
			'guarantor_guarantee_check' => ! empty( $plan['guarantor_guarantee_check'] ) ? 1 : 0,
			'guarantor_threshold' => $thr,
			'above_threshold' => ( $thr > 0 && $cash > $thr ) ? 1 : 0,
		);
		// پیام راهنمای چک
		$msgs = array();
		if ( $need_g ) {
			$msgs[] = 'چک تضمین بر اساس اصل‌وفرع با ضریب ' . $g_pct . '٪: ' . number_format( (float) ( $out['guarantee_check'] ?? 0 ), 0, '', '/' ) . ' ریال';
		} else {
			$msgs[] = 'این طرح نیاز به چک تضمین ندارد.';
		}
		if ( ! empty( $plan['installment_checks'] ) ) {
			$msgs[] = 'چک اقساط برای هر قسط لازم است.';
		} else {
			$msgs[] = 'چک اقساط لازم نیست.';
		}
		if ( ! empty( $plan['applicant_guarantee_check'] ) ) {
			$msgs[] = 'متقاضی یک فقره چک تضمین می‌دهد.';
		}
		if ( ! empty( $plan['guarantor_guarantee_check'] ) ) {
			if ( $thr > 0 && $cash > $thr ) {
				$msgs[] = 'بالای سقف بدون‌ضامن: متقاضی و ضامن هر کدام یک فقره چک تضمین.';
			} elseif ( $thr > 0 ) {
				$msgs[] = 'تا سقف ' . number_format( $thr, 0, '', '/' ) . ' ریال فقط یک برگ چک تضمین متقاضی.';
			} else {
				$msgs[] = 'ضامن نیز چک تضمین می‌دهد.';
			}
		}
		$out['check_policy_messages'] = $msgs;

		$out['principal_requested'] = round( $principal );
		$out['cash_used'] = round( $cash );

		// کف / سقف / ضامن / نرخ مؤثر / ریسک / توضیح منطق
		$out = self::enrich_result( $out, $plan, $args );

		return $out;
	}

	/**
	 * غنی‌سازی مشترک همه روش‌ها: کف/سقف، ضامن، نرخ اسمی/مؤثر، ریسک نکول، توضیح فارسی
	 */
	public static function enrich_result( $out, $plan, $args = array() ) {
		$plan = is_array( $plan ) ? $plan : array();
		// پر کردن فیلدهای خالی تا در UI صفر اشتباه نیاید
		if ( empty( $out['principal_interest'] ) && ! empty( $out['total_repay'] ) ) {
			$out['principal_interest'] = $out['total_repay'];
		}
		if ( empty( $out['principal_interest'] ) && ! empty( $out['total'] ) ) {
			$out['principal_interest'] = $out['total'];
		}
		if ( empty( $out['period_installment'] ) && ! empty( $out['installment'] ) ) {
			$out['period_installment'] = $out['installment'];
		}
		if ( empty( $out['period_installment'] ) && ! empty( $out['monthly_installment'] ) ) {
			$out['period_installment'] = $out['monthly_installment'];
		}
		if ( empty( $out['monthly_installment'] ) && ! empty( $out['period_installment'] ) ) {
			$out['monthly_installment'] = $out['period_installment'];
		}
		if ( empty( $out['total_repay'] ) && ! empty( $out['principal_interest'] ) ) {
			$out['total_repay'] = $out['principal_interest'];
		}
		// H/S فقط برای نسبت نمایشی — ضریب نهایی مشتری فقط از «ضریب طرح»
		$h = (float) ( $out['principal_interest'] ?? 0 );
		$s_credit = (float) ( $out['purchasing_power'] ?? 0 );
		$e_months = max( 1, (int) ( $out['months'] ?? 1 ) );
		if ( $h > 0 && $s_credit > 0 ) {
			$x = $h / $s_credit;
			$out['ratio_factor'] = $x;
			$out['excel_x'] = $x;
			$out['excel_H'] = $h;
			$out['excel_S'] = $s_credit;
			$out['excel_E'] = $e_months;
		}
		if ( isset( $out['plan_coef'] ) && (float) $out['plan_coef'] > 0 ) {
			$pc = (float) $out['plan_coef'];
			$out['final_coef'] = $pc;
			$out['coef'] = $pc;
			$out['monthly_coef_exact'] = $pc;
			$out['monthly_coef_rounded'] = round( $pc, 7 );
			$out['customer_monthly_coef'] = $pc;
		}
		$cash = (float) ( $out['principal'] ?? 0 );
		$months = max( 1, (int) ( $out['months'] ?? 1 ) );
		$total = (float) ( $out['total_repay'] ?? $out['principal_interest'] ?? 0 );
		$credit = (float) ( $out['purchasing_power'] ?? 0 );
		$monthly = (float) ( $out['monthly_installment'] ?? 0 );
		$step = max( 1, (int) ( $out['step'] ?? 1 ) );
		$method = $out['method'] ?? 'flat_coef';

		$floor = (float) ( $plan['cash_floor'] ?? 0 );
		$ceiling = (float) ( $plan['cash_ceiling'] ?? 0 );
		$guarantor_th = (float) ( $plan['guarantor_threshold'] ?? 0 );
		$ceiling_source = $plan['ceiling_source'] ?? 'admin'; // admin | supplier | payslip

		$warnings = array();
		$ok_amount = true;
		if ( $floor > 0 && $cash < $floor ) {
			$ok_amount = false;
			$warnings[] = 'مبلغ کمتر از کف مجاز طرح است (کف: ' . number_format( $floor ) . ' ریال).';
		}
		if ( $ceiling > 0 && $cash > $ceiling && $ceiling_source !== 'payslip' ) {
			$warnings[] = 'مبلغ بالاتر از سقف طرح است؛ محاسبه روی سقف اعمال شد یا باید کاهش یابد (سقف: ' . number_format( $ceiling ) . ' ریال).';
		}
		if ( $ceiling_source === 'payslip' ) {
			$warnings[] = 'سقف این طرح بر اساس فیش حقوقی است (فرمول فیش بعداً اعمال می‌شود). فعلاً سقف ثابت طرح در صورت تعریف استفاده شد.';
		}

		$always_g = ! empty( $plan['always_guarantor'] ) || ( ( $out['method'] ?? '' ) === 'razi_leasing' );
		$needs_guarantor = $always_g || ( $guarantor_th > 0 && $cash > $guarantor_th );
		if ( $always_g ) {
			$guarantor_msg = 'در این طرح در هر صورت معرفی ضامن و چک ضمانت الزامی است.';
		} elseif ( $needs_guarantor ) {
			$guarantor_msg = 'برای این مبلغ معرفی ضامن الزامی است (آستانه: ' . number_format( $guarantor_th ) . ' ریال).';
		} elseif ( $guarantor_th > 0 ) {
			$guarantor_msg = 'تا سقف ' . number_format( $guarantor_th ) . ' ریال نیاز به ضامن نیست.';
		} else {
			$guarantor_msg = 'آستانه ضامن برای این طرح تعریف نشده است.';
		}

		// نرخ اسمی تامین‌کننده (سالانه از نرخ ماهانه اولیه) — با «سود ماهانه تحمیلی اکسل» فرق دارد
		$nominal_annual = null;
		$effective_annual = null;
		$primary = (float) ( $out['primary_monthly_rate'] ?? $plan['primary_monthly_rate'] ?? 0 );
		if ( $primary > 1 ) {
			$primary = $primary / 100;
		}
		if ( $method === 'tamin_social' && $primary > 0 ) {
			$nominal_annual = $primary * 12 * 100; // ۳.۵٪ ماهانه → ۴۲٪ سالانه اسمی ساده
		} elseif ( ! empty( $out['coef'] ) && $method !== 'tamin_social' ) {
			$nominal_annual = ( (float) $out['coef'] ) * ( 12 / max( 1, $months ) );
		}
		// اطمینان از وجود فیلدهای دقیق/گردشده برای همه روش‌ها
		if ( ! isset( $out['monthly_coef_exact'] ) && isset( $out['final_coef'] ) ) {
			$out['monthly_coef_exact'] = (float) $out['final_coef'];
			$out['monthly_coef_rounded'] = round( (float) $out['final_coef'], 2 );
			$out['monthly_coef_rounded_int'] = (int) round( (float) $out['final_coef'] );
		}
		if ( $cash > 0 && $total > 0 && $months > 0 ) {
			// نرخ مؤثر تقریبی سالانه از نسبت کل پرداخت به اصل
			$factor = $total / $cash;
			$effective_annual = ( pow( $factor, 12 / $months ) - 1 ) * 100;
		}

		// تحلیل ریسک نکول (امتیاز ۰–۱۰۰؛ بالاتر = ریسک بیشتر)
		$risk_score = 20;
		$risk_factors = array();
		if ( $credit > 0 && $total / $credit > 1.5 ) {
			$risk_score += 20;
			$risk_factors[] = 'نسبت بازپرداخت به اعتبار تخصیص‌یافته بالاست.';
		}
		if ( $months >= 24 ) {
			$risk_score += 15;
			$risk_factors[] = 'مدت بازپرداخت بلندمدت است.';
		}
		if ( $step >= 3 ) {
			$risk_score += 10;
			$risk_factors[] = 'فاصله اقساط زیاد است (ریسک تجمع بدهی).';
		}
		if ( $needs_guarantor ) {
			$risk_score += 15;
			$risk_factors[] = 'مبلغ بالاتر از آستانه بدون‌ضامن است.';
		}
		if ( $effective_annual !== null && $effective_annual > 40 ) {
			$risk_score += 15;
			$risk_factors[] = 'نرخ مؤثر سالانه نسبتاً بالاست.';
		}
		if ( $monthly > 0 && $credit > 0 && ( $monthly / max( $credit / max( $months, 1 ), 1 ) ) > 0.5 ) {
			$risk_score += 10;
			$risk_factors[] = 'فشار قسط ماهانه نسبت به اعتبار محسوس است.';
		}
		$risk_score = max( 0, min( 100, $risk_score ) );
		if ( $risk_score < 35 ) {
			$risk_level = 'low';
			$risk_label = 'کم';
		} elseif ( $risk_score < 60 ) {
			$risk_level = 'medium';
			$risk_label = 'متوسط';
		} else {
			$risk_level = 'high';
			$risk_label = 'بالا';
		}

		$logic = self::explain_logic( $out, $method, $plan );

		$out['cash_floor'] = $floor;
		$out['cash_ceiling'] = $ceiling;
		$out['ceiling_source'] = $ceiling_source;
		$out['amount_ok'] = isset( $amount_ok ) ? ( $amount_ok && $ok_amount ) : $ok_amount;
		if ( ! empty( $amount_warning ) ) {
			array_unshift( $warnings, $amount_warning );
		}
		$out['warnings'] = $warnings;
		$out['amount_warning'] = $amount_warning ?? '';
		if ( ! empty( $payslip_info ) ) { $out['payslip_info'] = $payslip_info; }
		$out['guarantor_threshold'] = $guarantor_th;
		$out['needs_guarantor'] = $needs_guarantor;
		$out['guarantor_message'] = $guarantor_msg;
		$out['nominal_annual_rate'] = $nominal_annual !== null ? round( $nominal_annual, 4 ) : null;
		$out['effective_annual_rate'] = $effective_annual !== null ? round( $effective_annual, 4 ) : null;
		$out['rate_spread'] = ( $nominal_annual !== null && $effective_annual !== null )
			? round( $effective_annual - $nominal_annual, 4 ) : null;
		$out['risk_score'] = $risk_score;
		$out['risk_level'] = $risk_level;
		$out['risk_label'] = $risk_label;
		$out['risk_factors'] = $risk_factors;
		$out['logic_explanation'] = $logic;

		// مقایسه با وام بانکی قسط ثابت (فقط مرجع — در لیزینگ استفاده نمی‌شود)
		$bank_rate = (float) ( $out['primary_monthly_rate'] ?? $plan['primary_monthly_rate'] ?? 0 );
		if ( $bank_rate > 1 ) {
			$bank_rate = $bank_rate / 100;
		}
		if ( $bank_rate <= 0 ) {
			$bank_rate = 0.035;
		}
		$bn = max( 1, $months );
		$bp = $cash > 0 ? $cash : (float) ( $out['principal'] ?? 0 );
		if ( $bank_rate > 0 && $bp > 0 ) {
			$pow = pow( 1 + $bank_rate, $bn );
			$bank_inst = $bp * $bank_rate * $pow / ( $pow - 1 );
		} else {
			$bank_inst = $bn > 0 ? $bp / $bn : 0;
		}
		$out['bank_compare'] = array(
			'installment' => round( $bank_inst ),
			'total'       => round( $bank_inst * $bn ),
			'rate_pct'    => round( $bank_rate * 100, 4 ),
			'note'        => 'قسط ثابت بانکی (EMI) فقط برای مقایسه؛ محاسبه لیزینگ از این فرمول پیروی نمی‌کند.',
		);
		return $out;
	}

	/** توضیح فارسی شفاف منطق اقساط بر اساس روش */
	public static function explain_logic( $out, $method, $plan = array() ) {
		$lines = array();
		$name = $out['plan_name'] ?? '';
		$cash = number_format( (float) ( $out['principal'] ?? 0 ) );
		$months = (int) ( $out['months'] ?? 0 );
		$step = (int) ( $out['step'] ?? 1 );

		$lines[] = 'طرح: ' . ( $name ?: '—' ) . ' | روش محاسبه: ' . ( self::methods_list()[ $method ] ?? $method );

		if ( $method === 'tamin_social' ) {
			$rate = (float) ( $out['primary_monthly_rate'] ?? 0 );
			$lines[] = 'اکسل H = ((C×G)×E)+C | قسط L = H÷E | بازپرداخت نهایی = L×E = H';
			$lines[] = 'اکسل N=(C×٪ثانویه) O=C−N | P=C×٪شهر | R=N+P | S=C−R';
			$lines[] = 'اکسل X=H÷S | T=(X×100−100)÷E | چک M=H×120٪ | جریمه K=H×25٪';
			$lines[] = 'وضعیت B: اگر C>D بالای سقف وگرنه مجاز.';
		} elseif ( $method === 'flat_coef' ) {
			$lines[] = '۱) ضریب ثابت روی اصل در تمام ماه‌ها اعمال می‌شود: جزء سود ماهانه ≈ اصل × ضریب٪ (مستقل از مانده بدهی).';
			$lines[] = '۲) این روش سود را مثل بهره مرکب بانکی کاهش نمی‌دهد؛ برای طرح‌های کارمزد ثابت مناسب است.';
		} elseif ( $method === 'flat_principal_fee' ) {
			$lines[] = '۱) سود کل دوره = اصل × مدت × ضریب٪؛ سپس (اصل + سود) بر تعداد اقساط تقسیم می‌شود.';
			$lines[] = '۲) مشتری هم اصل و هم کارمزد را در اقساط بازمی‌گرداند.';
		} elseif ( $method === 'bank' ) {
			$lines[] = '۱) قسط ثابت با نرخ تقریبی ماهانه‌شده از ضریب دوره (شبیه وام بانکی کاهش‌یابنده).';
			$lines[] = '۲) در این حالت ساختار شبیه بهره مرکب است و با روش ضریب ثابت تفاوت دارد.';
		} elseif ( $method === 'manisa_digital' ) {
			$lines[] = '۱) اصل‌وفرع = قیمت نقدی × «جمع نرخ محاسبه» (ضریب جدولی مدت).';
			$lines[] = '۲) کارمزد تامین‌کننده اولیه متناسب با مدت از نقدی کسر می‌شود؛ سپس ضریب زنجیره (مثلاً ۱۳.۲٪) نصف برای ثانویه و نصف برای شهر قسط.';
			$lines[] = '۳) اعتبار مشتری = نقدی − اولیه − ثانویه − شهر قسط؛ چک تضمین معمولاً ۱۵۰٪ اصل‌وفرع.';
		} elseif ( $method === 'razi_leasing' ) {
			$lines[] = '۱) پیش‌پرداخت مشتری از اصل کم می‌شود؛ اصل‌وفرع همچنان از نقدی × جمع‌نرخ به‌دست می‌آید.';
			$lines[] = '۲) سهم تامین‌کننده + ارزش افزوده، سپس واریز به شهر قسط و سهم شهر قسط (+مالیات در گزارش).';
			$lines[] = '۳) در این طرح همیشه ضامن و چک ضمانت الزامی است؛ تعداد چک = تعداد اقساط دوره‌ای.';
		}

		if ( ( $out['agent_share'] ?? 0 ) > 0 ) {
			$mode = ( $out['agent_mode'] ?? '' ) === 'from_credit' ? 'از اعتبار مشتری' : 'از سهم سود شهر قسط';
			$lines[] = 'سهم نماینده: ' . number_format( (float) $out['agent_share'] ) . ' ریال — محل کسر: ' . $mode . '.';
		}

		$lines[] = 'مبلغ مبنا در این محاسبه: ' . $cash . ' ریال | مدت: ' . $months . ' ماه | گام: هر ' . $step . ' ماه.';
		return $lines;
	}

	/** روش جدول تامین: اصل‌وفرع = نقدی × (1 + نرخ_ماهانه × مدت) */

	/** مانسیا: اصل‌وفرع = نقدی × جمع‌نرخ؛ کسورات زنجیره اولیه + ضریب روی باقیمانده */
	/**
	 * مانیسا چکی (طبق توضیح ادمین):
	 * ۱) تامین اولیه: ۱۲ماه ۱۴٪ / ۱۸ماه ۲۱٪ از اصل → واریز به ثانویه
	 * ۲) ثانویه ۶.۶٪ از واریزی → واریز به شهر قسط
	 * ۳) شهر قسط ۶.۶٪ از واریزی ثانویه → باقیمانده = اعتبار مشتری (S)
	 * ۴) ضریب ماهانه از جدول مدت (بر اساس S)
	 * ۵) H = S × (1 + ضریب/100 × مدت)   |   قسط = H ÷ مدت
	 * فیلد نرخ ماهانه تامین اولیه در این روش استفاده نمی‌شود.
	 */
	public static function formula_manisa_digital( $a ) {
		$C = max( 0, (float) $a['cash'] );
		$E = max( 1, (int) $a['months'] );
		$F = max( 1, (int) $a['step'] );

		// هزینه زیرساخت دیجیتال = درصد کسر تامین‌کننده اولیه (از اصل)
		// اولویت: primary_pct از مدت → digital_infra_pct / supplier_coef از طرح → پیش‌فرض ۱۲م۱۴٪ / ۱۸م۲۱٪
		$primary_pct = isset( $a['primary_pct'] ) ? (float) $a['primary_pct'] : 0;
		if ( $primary_pct <= 0 && ! empty( $a['digital_infra_pct'] ) ) {
			$primary_pct = (float) $a['digital_infra_pct'];
		}
		if ( $primary_pct <= 0 && ! empty( $a['supplier_coef'] ) ) {
			$primary_pct = (float) $a['supplier_coef'];
		}
		if ( $primary_pct <= 0 ) {
			$primary_pct = ( $E <= 12 ) ? 14 : 21;
		}
		// کارمزد ثانویه: پایه قابل انتخاب — از اصل یا از واریزی تامین‌کننده اولیه
		$sec_pct  = isset( $a['secondary_chain_pct'] ) ? (float) $a['secondary_chain_pct'] : 6.6;
		$sec_base = isset( $a['secondary_base_on'] ) ? (string) $a['secondary_base_on'] : 'primary_deposit';
		if ( ! in_array( $sec_base, array( 'principal', 'primary_deposit' ), true ) ) {
			$sec_base = 'primary_deposit';
		}
		// کارمزد شهر قسط: ٪ از مبلغ واریزی توسط ثانویه
		$city_pct = isset( $a['city_chain_pct'] ) ? (float) $a['city_chain_pct'] : 6.6;
		$plan_coef = isset( $a['plan_coef'] ) ? (float) $a['plan_coef'] : ( isset( $a['coef'] ) ? (float) $a['coef'] : 0 );

		// ۱) کسر هزینه زیرساخت (تامین اولیه) از اصل → واریز به ثانویه
		$infra_cut = $C * ( $primary_pct / 100 );
		$to_secondary = max( 0, $C - $infra_cut );
		// ۲) ثانویه: از اصل مبلغ یا از واریزی تامین اولیه
		if ( $sec_base === 'principal' ) {
			$secondary_cut = $C * ( $sec_pct / 100 );
		} else {
			$secondary_cut = $to_secondary * ( $sec_pct / 100 );
		}
		$to_city = max( 0, $to_secondary - $secondary_cut );
		// ۳) شهر قسط کارمزد خود را از واریزی ثانویه کسر می‌کند → اعتبار مشتری
		$city_cut = $to_city * ( $city_pct / 100 );
		$S = max( 0, $to_city - $city_cut );
		$primary_cut = $infra_cut; // سازگاری با فیلدهای قبلی

		// اصل‌وفرع از اعتبار نهایی و ضریب طرح
		$H = $S * ( 1 + ( $plan_coef / 100 ) * $E );
		$I = $H - $S;
		$L = $E > 0 ? ( $H / $E ) : 0;
		$payments = (int) ceil( $E / $F );
		$period_inst = $payments > 0 ? ( $H / $payments ) : 0;

		$g_pct_a = (float) ( $a['guarantee_pct_applicant'] ?? $a['guarantee_pct'] ?? 150 );
		$g_pct_g = (float) ( $a['guarantee_pct_guarantor'] ?? 0 );
		$need_g = ! isset( $a['need_guarantee_check'] ) || ! empty( $a['need_guarantee_check'] );
		$M_a = $need_g ? ( $H * ( $g_pct_a / 100 ) ) : 0;
		$M_g = ( $need_g && $g_pct_g > 0 ) ? ( $H * ( $g_pct_g / 100 ) ) : 0;

		$schedule = array();
		$start_ts = current_time( 'timestamp' );
		for ( $i = 1; $i <= $payments; $i++ ) {
			$due = strtotime( '+' . ( $i * $F ) . ' months', $start_ts );
			$schedule[] = array( 'n' => $i, 'due' => date_i18n( 'Y/m/d', $due ), 'amount' => round( $period_inst ) );
		}

		return array(
			'principal' => round( $C ),
			'months' => $E,
			'step' => $F,
			'primary_pct' => $primary_pct,
			'digital_infra_pct' => $primary_pct,
			'digital_infra_fee' => round( $infra_cut ),
			'supplier_cut' => round( $primary_cut ),
			'secondary_deposit_in' => round( $to_secondary ),
			'supplier_deposit' => round( $to_city ),
			'secondary_cut' => round( $secondary_cut ),
			'city_cut' => round( $city_cut ),
			'city_share_gross' => round( $city_cut ),
			'purchasing_power' => round( $S ),
			'principal_interest' => round( $H ),
			'period_profit' => round( $I ),
			'monthly_installment' => $L,
			'period_installment' => $period_inst,
			'installment' => $period_inst,
			'total_repay' => round( $H ),
			'total' => round( $H ),
			'plan_coef' => $plan_coef,
			'coef' => $plan_coef,
			'final_coef' => $plan_coef,
			'monthly_coef_exact' => $plan_coef,
			'guarantee_check' => round( $M_a + $M_g ),
			'guarantee_check_applicant' => round( $M_a ),
			'guarantee_check_guarantor' => round( $M_g ),
			'total_deductions' => round( $primary_cut + $secondary_cut + $city_cut ),
			'digital_fee' => 0,
			'payments' => $payments,
			'schedule' => $schedule,
			'agent_share' => 0,
			'agent_mode' => 'from_city',
		);
	}


	/**
	 * رازی (چکی) — مانند مانیسا با این تفاوت که سهم تامین‌کننده ثانویه
	 * به‌صورت پیش‌پرداخت نقدی از متقاضی دریافت می‌شود (نه کسر از زنجیره واریز).
	 */
	public static function formula_razi_leasing( $a ) {
		$C = max( 0, (float) $a['cash'] );
		$E = max( 1, (int) $a['months'] );
		$F = max( 1, (int) $a['step'] );

		$primary_pct = isset( $a['primary_pct'] ) ? (float) $a['primary_pct'] : 0;
		if ( $primary_pct <= 0 ) {
			$primary_pct = ( $E <= 12 ) ? 14 : 21;
		}
		// پیش‌پرداخت نقدی متقاضی = سهم ثانویه (٪ از اصل یا مبلغ ثابت از duration)
		$prepay_pct = isset( $a['prepay_pct'] ) ? (float) $a['prepay_pct'] : (float) ( $a['secondary_chain_pct'] ?? 6.6 );
		$prepay_fixed = (float) ( $a['prepay'] ?? 0 );
		$city_pct = isset( $a['city_chain_pct'] ) ? (float) $a['city_chain_pct'] : 6.6;
		$plan_coef = isset( $a['plan_coef'] ) ? (float) $a['plan_coef'] : ( isset( $a['coef'] ) ? (float) $a['coef'] : 0 );

		$primary_cut = $C * ( $primary_pct / 100 );
		$after_primary = max( 0, $C - $primary_cut );

		// پیش‌پرداخت نقدی از متقاضی (سهم ثانویه)
		$prepay = $prepay_fixed > 0 ? $prepay_fixed : ( $C * ( $prepay_pct / 100 ) );

		// واریز به شهر قسط پس از کسر اولیه (ثانویه نقداً جدا گرفته شده)
		$to_city = $after_primary;
		$city_cut = $to_city * ( $city_pct / 100 );
		$S = max( 0, $to_city - $city_cut - $prepay ); // اعتبار پس از پیش‌پرداخت

		$H = $S * ( 1 + ( $plan_coef / 100 ) * $E );
		$I = $H - $S;
		$L = $E > 0 ? ( $H / $E ) : 0;
		$payments = (int) ceil( $E / $F );
		$period_inst = $payments > 0 ? ( $H / $payments ) : 0;

		$g_pct_a = (float) ( $a['guarantee_pct_applicant'] ?? $a['guarantee_pct'] ?? 140 );
		$g_pct_g = (float) ( $a['guarantee_pct_guarantor'] ?? 0 );
		$need_g = ! isset( $a['need_guarantee_check'] ) || ! empty( $a['need_guarantee_check'] );
		$M_a = $need_g ? ( $H * ( $g_pct_a / 100 ) ) : 0;
		$M_g = ( $need_g && $g_pct_g > 0 ) ? ( $H * ( $g_pct_g / 100 ) ) : 0;

		$schedule = array();
		$start_ts = current_time( 'timestamp' );
		for ( $i = 1; $i <= $payments; $i++ ) {
			$due = strtotime( '+' . ( $i * $F ) . ' months', $start_ts );
			$schedule[] = array( 'n' => $i, 'due' => date_i18n( 'Y/m/d', $due ), 'amount' => round( $period_inst ) );
		}

		return array(
			'principal' => round( $C ),
			'months' => $E,
			'step' => $F,
			'primary_pct' => $primary_pct,
			'supplier_cut' => round( $primary_cut ),
			'prepay_cash' => round( $prepay ),
			'secondary_cut' => round( $prepay ),
			'supplier_deposit' => round( $to_city ),
			'city_cut' => round( $city_cut ),
			'purchasing_power' => round( $S ),
			'principal_interest' => round( $H ),
			'period_profit' => round( $I ),
			'monthly_installment' => $L,
			'period_installment' => $period_inst,
			'installment' => $period_inst,
			'total_repay' => round( $H ),
			'total' => round( $H ),
			'plan_coef' => $plan_coef,
			'coef' => $plan_coef,
			'final_coef' => $plan_coef,
			'guarantee_check' => round( $M_a + $M_g ),
			'guarantee_check_applicant' => round( $M_a ),
			'guarantee_check_guarantor' => round( $M_g ),
			'total_deductions' => round( $primary_cut + $prepay + $city_cut ),
			'digital_fee' => 0,
			'payments' => $payments,
			'schedule' => $schedule,
			'note' => 'سهم ثانویه به‌صورت پیش‌پرداخت نقدی از متقاضی دریافت می‌شود.',
		);
	}


	public static function formula_tamin_social( $a ) {
		// C — قیمت نقدی فروش کالا
		$C = max( 0, (float) $a['cash'] );
		// E — تعداد اقساط
		$E = max( 1, (int) $a['months'] );
		// F — فاصله گام
		$F = max( 1, (int) $a['step'] );
		// G — نرخ درصد سود ماهانه تامین‌کننده اولیه (اعشار اکسل مثل 0.035)
		$G = max( 0, (float) $a['primary_monthly_rate'] );
		if ( $G > 1 ) {
			$G = $G / 100; // ادمین 3.5 وارد کرده → 0.035
		}
		// D — سقف نقدی
		$D = max( 0, (float) ( $a['ceiling'] ?? 0 ) );
		if ( $D <= 0 ) {
			$D = $C;
		}

		$sup_pct    = max( 0, (float) ( $a['supplier_coef'] ?? 0 ) );
		$city_pct   = max( 0, (float) ( $a['city_coef'] ?? 0 ) );
		$sup_fixed  = (float) ( $a['supplier_fixed_fee'] ?? 0 );
		$city_fixed = (float) ( $a['city_fixed_fee'] ?? 0 );
		$agent_pct  = max( 0, (float) ( $a['agent_coef'] ?? 0 ) );
		$digital    = max( 0, (float) ( $a['digital_fee'] ?? 0 ) );
		$g_pct      = max( 0, (float) ( $a['guarantee_pct'] ?? 120 ) );
		$c_pct      = max( 0, (float) ( $a['cancel_pct'] ?? 25 ) );

		// H = ((C*G)*E)+C   ← عین فرمول سلول H7 اکسل
		$H = ( ( $C * $G ) * $E ) + $C;

		// I = H-C
		$I = $H - $C;

		// L = H/E   ← مبلغ هر قسط (عین L7)
		$L = $E > 0 ? ( $H / $E ) : 0;

		// با گام پرداخت: تعداد پرداخت = ceil(E/F) ، قسط دوره‌ای = H / تعداد
		$payments_count = (int) ceil( $E / $F );
		$period_installment = $payments_count > 0 ? ( $H / $payments_count ) : 0;

		// N — کارمزد ثانویه یک‌بار (اکسل: (C*3)/100)
		if ( $sup_pct > 0 ) {
			$N = ( $C * $sup_pct ) / 100;
		} elseif ( $sup_fixed > 0 ) {
			$N = $sup_fixed;
		} else {
			$N = ( $C * 3 ) / 100; // پیش‌فرض اکسل
		}

		// O = C-N
		$O = $C - $N;

		// P — سهم شهر قسط یک‌بار: پایه از اصل (C) یا از واریزی ثانویه (O)
		$city_base_on = $a['city_base_on'] ?? 'principal';
		$city_base_amount = ( $city_base_on === 'supplier_deposit' ) ? $O : $C;
		if ( $city_pct > 0 ) {
			$P = $city_base_amount * ( $city_pct / 100 );
		} elseif ( $city_fixed > 0 ) {
			$P = $city_fixed;
		} else {
			$P = $city_base_amount * 0.07;
		}

		// Q — سهم نماینده (اکسل نمونه: O*0%)
		$Q = $O * ( $agent_pct / 100 );

		// R = N+P  (اکسل؛ نماینده در نمونه صفر است)
		$R = $N + $P + $digital;

		// S = C-R  اعتبار تخصیص‌یافته
		$S = $C - $R;
		if ( $agent_pct > 0 && ( $a['agent_mode'] ?? 'from_city' ) === 'from_credit' ) {
			$S = max( 0, $S - $Q );
		}

		// X = H/S
		$X = ( $S > 0 ) ? ( $H / $S ) : 0;

		// T = (X*100-100)/E  ضریب ماهانه
		$T = ( $E > 0 ) ? ( ( ( $X * 100 ) - 100 ) / $E ) : 0;

		// اگر ادمین ضریب طرح جدول را >0 گذاشته، همان برای نمایش
		$plan_coef = isset( $a['plan_coef'] ) ? (float) $a['plan_coef'] : ( isset( $a['coef'] ) ? (float) $a['coef'] : 0 );
		if ( $plan_coef > 0 ) {
			$T = $plan_coef;
		}

		// K = (H*25)/100
		$K = ( $H * $c_pct ) / 100;

		// چک تضمین: درصد جدا برای مشتری و ضامن × اصل‌وفرع H
		$need_g = ! isset( $a['need_guarantee_check'] ) || ! empty( $a['need_guarantee_check'] );
		$g_pct_applicant = isset( $a['guarantee_pct_applicant'] ) ? (float) $a['guarantee_pct_applicant'] : $g_pct;
		$g_pct_guarantor = isset( $a['guarantee_pct_guarantor'] ) ? (float) $a['guarantee_pct_guarantor'] : 0;
		$M_applicant = $need_g ? ( $H * ( $g_pct_applicant / 100 ) ) : 0;
		$M_guarantor = ( $need_g && $g_pct_guarantor > 0 ) ? ( $H * ( $g_pct_guarantor / 100 ) ) : 0;
		$M = $M_applicant + $M_guarantor; // مجموع برای سازگاری با فیلد قبلی

		// B = IF(C>D,"بالای سقف","مجاز")
		$B = ( $C > $D ) ? 'بالای سقف' : 'مجاز';

		$schedule = array();
		$start_ts = current_time( 'timestamp' );
		for ( $i = 1; $i <= $payments_count; $i++ ) {
			$due = strtotime( '+' . ( $i * $F ) . ' months', $start_ts );
			$schedule[] = array(
				'n'      => $i,
				'due'    => date_i18n( 'Y/m/d', $due ),
				'amount' => round( $period_installment ),
			);
		}

		return array(
			'facility_status'          => $B,
			'principal'                => round( $C ),
			'months'                   => $E,
			'step'                     => $F,
			'primary_monthly_rate'     => $G,
			'primary_monthly_rate_pct' => round( $G * 100, 4 ),
			'principal_interest'       => round( $H ), // H
			'period_profit'            => round( $I ), // I
			'monthly_installment'      => $L, // L = H/E بدون گرد (عین اکسل)
			'period_installment'       => $period_installment,
			'installment'              => $period_installment,
			'total_repay'              => round( $H ), // = L * E
			'total'                    => round( $H ),
			'profit'                   => round( $I ),
			'supplier_cut'             => round( $N ), // N
			'supplier_deposit'         => round( $O ), // O
			'city_cut'                 => round( $P ), // P
			'city_share_gross'         => round( $P ),
			'agent_share'              => round( $Q ), // Q
			'total_deductions'         => round( $R ), // R
			'purchasing_power'         => round( $S ), // S
			'ratio_factor'             => $X,          // X
			'excel_x'                  => $X,
			'excel_H'                  => round( $H ),
			'excel_S'                  => round( $S ),
			'excel_E'                  => $E,
			'plan_coef'                => $T,          // T
			'coef'                     => $T,
			'final_coef'               => $T,
			'customer_monthly_coef'    => $T,
			'monthly_coef_exact'       => $T,
			'monthly_coef_rounded'     => round( $T, 7 ),
			'cancel_penalty'           => round( $K ), // K
			'guarantee_check'          => round( $M ),
			'guarantee_check_applicant'=> round( $M_applicant ),
			'guarantee_check_guarantor'=> round( $M_guarantor ),
			'guarantee_pct_applicant'  => $g_pct_applicant,
			'guarantee_pct_guarantor'  => $g_pct_guarantor,
			'digital_fee'              => round( $digital ),
			'supplier_coef'            => $sup_pct,
			'city_coef'                => $city_pct,
			'agent_coef'               => $agent_pct,
			'agent_mode'               => ( $a['agent_mode'] ?? 'from_city' ),
			'payments'                 => $payments_count,
			'schedule'                 => $schedule,
			'rate'                     => $T,
			'collateral'               => array(
				'type'   => 'check_guarantee',
				'pct'    => $g_pct,
				'amount' => round( $M ),
				'label'  => 'چک تضمین ' . $g_pct . '٪',
			),
		);
	}


	protected static function apply_chain( $cash, $supplier_coef, $city_coef, $agent_coef, $agent_mode, $digital_fee, $digital_pct, $plan ) {
		$plan = is_array( $plan ) ? $plan : array();
		$sup_fixed = (float) ( $plan['supplier_fixed_fee'] ?? 0 );
		$city_fixed = (float) ( $plan['city_fixed_fee'] ?? 0 );
		// پایه کسر ثانویه: از اصل یا از واریزی تامین‌کننده اولیه (پس از کسر اولیه یک‌باره در صورت وجود)
		$sec_base = (string) ( $plan['secondary_base_on'] ?? $plan['supplier_base_on'] ?? 'principal' );
		$primary_one_time = (float) ( $plan['digital_infra_pct'] ?? $plan['primary_pct'] ?? 0 );
		$after_primary = $cash;
		if ( $sec_base === 'primary_deposit' && $primary_one_time > 0 ) {
			$after_primary = max( 0, $cash - ( $cash * ( $primary_one_time / 100 ) ) );
		} elseif ( $sec_base === 'primary_deposit' ) {
			// بدون درصد اولیه تعریف‌شده: واریزی اولیه = اصل (رفتار امن)
			$after_primary = $cash;
		}
		$sec_base_amount = ( $sec_base === 'primary_deposit' ) ? $after_primary : $cash;
		$supplier_cut = ( $supplier_coef > 0 )
			? ( $sec_base_amount * ( $supplier_coef / 100 ) )
			: ( $sup_fixed > 0 ? $sup_fixed : 0 );
		$deposit = max( 0, $cash - $supplier_cut );
		$city_base_on = $plan['city_base_on'] ?? 'principal';
		$city_base = ( $city_base_on === 'supplier_deposit' ) ? $deposit : $cash;
		$city_raw = ( $city_coef > 0 )
			? ( $city_base * ( $city_coef / 100 ) )
			: ( $city_fixed > 0 ? $city_fixed : 0 );
		$agent = $cash * ( $agent_coef / 100 );
		$digital = $digital_fee + ( $cash * $digital_pct / 100 );
		if ( $agent_mode === 'from_credit' ) {
			$credit = max( 0, $cash - $supplier_cut - $city_raw - $agent - $digital );
			$city = $city_raw;
		} else {
			$city = max( 0, $city_raw - $agent );
			$credit = max( 0, $cash - $supplier_cut - $city_raw - $digital );
		}
		return compact( 'supplier_cut', 'deposit', 'city', 'city_raw', 'agent', 'credit', 'digital' );
	}

	public static function formula_flat_coef( $cash, $coef, $months, $step, $supplier_coef, $city_coef, $agent_coef, $agent_mode, $digital_fee, $digital_pct, $plan ) {
		$ch = self::apply_chain( $cash, $supplier_coef, $city_coef, $agent_coef, $agent_mode, $digital_fee, $digital_pct, $plan ?: array() );
		$profit_total = $cash * $months * ( $coef / 100 );
		$monthly = $months > 0 ? ( $profit_total / $months ) : 0;
		$payments = (int) ceil( $months / $step );
		$period = $monthly * $step;
		$total = $monthly * $months;
		return self::pack_generic( $cash, $months, $step, $coef, $ch, $monthly, $period, $total, $profit_total, $payments );
	}

	public static function formula_flat_principal_fee( $cash, $coef, $months, $step, $supplier_coef, $city_coef, $agent_coef, $agent_mode, $digital_fee, $digital_pct, $plan ) {
		$ch = self::apply_chain( $cash, $supplier_coef, $city_coef, $agent_coef, $agent_mode, $digital_fee, $digital_pct, $plan ?: array() );
		$profit_total = $cash * $months * ( $coef / 100 );
		$total = $cash + $profit_total;
		$payments = (int) ceil( $months / $step );
		$period = $payments > 0 ? ( $total / $payments ) : 0;
		$monthly = $months > 0 ? ( $total / $months ) : 0;
		return self::pack_generic( $cash, $months, $step, $coef, $ch, $monthly, $period, $total, $profit_total, $payments );
	}

	public static function formula_bank( $cash, $coef, $months, $step, $supplier_coef, $city_coef, $agent_coef, $agent_mode, $digital_fee, $digital_pct, $plan ) {
		$ch = self::apply_chain( $cash, $supplier_coef, $city_coef, $agent_coef, $agent_mode, $digital_fee, $digital_pct, $plan ?: array() );
		$payments = (int) ceil( $months / $step );
		$monthly_rate = ( $coef / 100 ) / max( 1, $months );
		if ( $monthly_rate <= 0 ) {
			$period = $payments > 0 ? ( $cash / $payments ) : 0;
		} else {
			$r = $monthly_rate * $step;
			$n = $payments;
			$period = $cash * ( $r * pow( 1 + $r, $n ) ) / ( pow( 1 + $r, $n ) - 1 );
		}
		$total = $period * $payments;
		$monthly = $period / $step;
		$profit = $total - $cash;
		return self::pack_generic( $cash, $months, $step, $coef, $ch, $monthly, $period, $total, $profit, $payments );
	}

	protected static function pack_generic( $cash, $months, $step, $coef, $ch, $monthly, $period, $total, $profit, $payments ) {
		$schedule = array();
		$start = current_time( 'timestamp' );
		for ( $i = 1; $i <= $payments; $i++ ) {
			$due = strtotime( '+' . ( $i * $step ) . ' months', $start );
			$schedule[] = array( 'n' => $i, 'due' => date_i18n( 'Y/m/d', $due ), 'amount' => round( $period ) );
		}
		$final_coef = $coef;
		if ( $cash > 0 ) {
			$final_coef = $coef + ( $ch['supplier_cut'] + $ch['city_raw'] + $ch['digital'] ) / $cash * 100;
		}
		return array(
			'principal'           => round( $cash ),
			'months'              => $months,
			'step'                => $step,
			'coef'                => $coef,
			'supplier_coef'       => 0,
			'supplier_cut'        => round( $ch['supplier_cut'] ),
			'supplier_deposit'    => round( $ch['deposit'] ),
			'city_cut'            => round( $ch['city'] ),
			'city_share_gross'    => round( $ch['city_raw'] ),
			'agent_share'         => round( $ch['agent'] ),
			'agent_mode'          => '',
			'digital_fee'         => round( $ch['digital'] ),
			'final_coef'          => round( $final_coef, 4 ),
			'purchasing_power'    => round( $ch['credit'] ),
			'monthly_installment' => round( $monthly ),
			'period_installment'  => round( $period ),
			'total_repay'         => round( $total ),
			'profit'              => round( $profit ),
			'payments'            => $payments,
			'schedule'            => $schedule,
			'installment'         => round( $period ),
			'total'               => round( $total ),
			'rate'                => $coef,
			'guarantee_check'     => 0,
			'cancel_penalty'      => 0,
			'principal_interest'  => round( $total ),
			'period_profit'       => round( $profit ),
			'ratio_factor'        => 0,
		);
	}

	public static function calculate( $principal, $rate_percent, $months, $step_months = 1, $method = 'flat' ) {
		$map = array( 'flat' => 'flat_coef', 'reducing' => 'bank', 'bank' => 'bank' );
		return self::calculate_full( array(
			'principal' => $principal,
			'coef'      => $rate_percent,
			'months'    => $months,
			'step'      => $step_months,
			'method'    => $map[ $method ] ?? 'flat_coef',
			'supplier_coef' => 0,
			'city_coef' => 0,
			'digital_fee' => 0,
		) );
	}

	public static function ajax_calc() {
		check_ajax_referer( 'cgs_calc', 'nonce' );
		$parse_num = function( $v ) {
			$v = is_string( $v ) ? $v : (string) $v;
			$fa = array( '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9' );
			$v = strtr( $v, $fa );
			return floatval( preg_replace( '/[^\d.]/', '', $v ) );
		};
		try {
			$result = self::calculate_full( array(
				'principal'     => $parse_num( $_POST['principal'] ?? 0 ),
				'plan_id'       => sanitize_key( $_POST['plan_id'] ?? '' ),
				'months'        => intval( $_POST['months'] ?? 12 ),
				'step'          => intval( $_POST['step'] ?? 1 ),
				'coef'          => isset( $_POST['coef'] ) ? $parse_num( $_POST['coef'] ) : ( isset( $_POST['rate'] ) ? $parse_num( $_POST['rate'] ) : null ),
				'method'        => sanitize_key( $_POST['method'] ?? '' ),
				'supplier_coef' => isset( $_POST['supplier_coef'] ) ? $parse_num( $_POST['supplier_coef'] ) : null,
				'city_coef'     => isset( $_POST['city_coef'] ) ? $parse_num( $_POST['city_coef'] ) : null,
				'agent_coef'    => isset( $_POST['agent_coef'] ) ? $parse_num( $_POST['agent_coef'] ) : null,
				'agent_mode'    => sanitize_key( $_POST['agent_mode'] ?? 'from_city' ),
				'digital_fee'   => isset( $_POST['digital_fee'] ) ? $parse_num( $_POST['digital_fee'] ) : null,
				'primary_monthly_rate' => isset( $_POST['primary_monthly_rate'] ) ? $parse_num( $_POST['primary_monthly_rate'] ) : null,
				'supplier_fixed_fee' => isset( $_POST['supplier_fixed_fee'] ) ? $parse_num( $_POST['supplier_fixed_fee'] ) : null,
				'city_fixed_fee' => isset( $_POST['city_fixed_fee'] ) ? $parse_num( $_POST['city_fixed_fee'] ) : null,
				'net_salary' => isset( $_POST['net_salary'] ) ? $parse_num( $_POST['net_salary'] ) : null,
				'salary_org' => sanitize_key( $_POST['salary_org'] ?? '' ),
				'employment_status' => sanitize_key( $_POST['employment_status'] ?? '' ),
			) );
			wp_send_json_success( $result );
		} catch ( \Throwable $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	public static function ajax_sensitivity() {
		check_ajax_referer( 'cgs_calc_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden' );
		}
		$args = array(
			'principal' => $_POST['principal'] ?? 0,
			'plan_id' => $_POST['plan_id'] ?? '',
			'months' => $_POST['months'] ?? 12,
			'step' => $_POST['step'] ?? 1,
			'method' => $_POST['method'] ?? null,
			'coef' => $_POST['coef'] ?? null,
			'sum_rate' => $_POST['sum_rate'] ?? null,
			'supplier_coef' => $_POST['supplier_coef'] ?? null,
			'city_coef' => $_POST['city_coef'] ?? null,
			'primary_monthly_rate' => $_POST['primary_monthly_rate'] ?? null,
		);
		wp_send_json_success( self::sensitivity_analysis( $args ) );
	}


	public static function ajax_bank_all() {
		check_ajax_referer( 'cgs_calc_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden' );
		}
		$raw = isset( $_POST['principal'] ) ? (string) $_POST['principal'] : '0';
		$fa = array( '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9' );
		$raw = strtr( $raw, $fa );
		$principal = floatval( preg_replace( '/[^\d.]/', '', $raw ) );
		$months = max( 1, intval( $_POST['months'] ?? 12 ) );
		$step = max( 1, intval( $_POST['step'] ?? 1 ) );
		$primary = floatval( $_POST['primary_monthly_rate'] ?? 0 );
		if ( $primary > 1 ) {
			$primary = $primary / 100;
		}
		if ( $primary <= 0 ) {
			$primary = 0.035;
		}
		if ( $principal <= 0 ) {
			wp_send_json_success( array() );
		}
		$pow = pow( 1 + $primary, $months );
		$bank_inst = $principal * $primary * $pow / max( $pow - 1, 1e-12 );
		$bank_total = $bank_inst * $months;
		$rows = array();
		foreach ( self::get_plans() as $pl ) {
			if ( empty( $pl['active'] ) ) {
				continue;
			}
			$r = self::calculate_full( array(
				'principal' => $principal,
				'plan_id'   => $pl['id'] ?? '',
				'months'    => $months,
				'step'      => $step,
				'method'    => $pl['method'] ?? null,
				'primary_monthly_rate' => $primary * 100, // به درصد برای نرمال‌سازی موتور
			) );
			$p_inst = (float) ( $r['period_installment'] ?? $r['monthly_installment'] ?? 0 );
			$p_tot  = (float) ( $r['principal_interest'] ?? $r['total_repay'] ?? 0 );
			$rows[] = array(
				'plan' => $pl['name'] ?? ( $pl['id'] ?? '' ),
				'method' => $pl['method'] ?? '',
				'months' => $months,
				'plan_installment' => round( $p_inst ),
				'plan_total' => round( $p_tot ),
				'bank_installment' => round( $bank_inst ),
				'bank_total' => round( $bank_total ),
				'diff_installment' => round( $p_inst - $bank_inst ),
				'diff_total' => round( $p_tot - $bank_total ),
				'monthly_coef' => $r['monthly_coef_exact'] ?? $r['final_coef'] ?? null,
			);
		}
		wp_send_json_success( $rows );
	}

	public static function ajax_compare() {
		check_ajax_referer( 'cgs_calc_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden' );
		}
		$principal = floatval( $_POST['principal'] ?? 0 );
		wp_send_json_success( self::comparison_table( $principal ) );
	}

	public static function ajax_discover_coef() {
		check_ajax_referer( 'cgs_calc_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden' );
		}
		$r = self::discover_coef(
			$_POST['principal'] ?? 0,
			$_POST['supplier_deposit'] ?? 0,
			$_POST['months'] ?? 12,
			$_POST['total_repay'] ?? 0
		);
		wp_send_json_success( $r );
	}

	public static function ajax_save_plans() {
		check_ajax_referer( 'cgs_calc_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden' );
		}
		$raw = isset( $_POST['plans'] ) ? wp_unslash( $_POST['plans'] ) : '';
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			wp_send_json_error( 'invalid' );
		}
		$methods = array_keys( self::methods_list() );
		$clean = array();
		foreach ( $data as $pl ) {
			$durs = array();
			foreach ( (array) ( $pl['durations'] ?? array() ) as $d ) {
				$steps = array_map( 'intval', (array) ( $d['steps'] ?? array( 1 ) ) );
				$steps = array_values( array_filter( $steps, function( $s ) { return $s >= 1; } ) );
				if ( empty( $steps ) ) {
					$steps = array( 1 );
				}
				$row = array(
					'months' => max( 1, intval( $d['months'] ?? 1 ) ),
					'coef'   => floatval( $d['coef'] ?? 0 ),
					'steps'  => $steps,
				);
				if ( isset( $d['supplier_coef'] ) && $d['supplier_coef'] !== '' ) {
					$row['supplier_coef'] = floatval( $d['supplier_coef'] );
				}
				if ( isset( $d['city_coef'] ) && $d['city_coef'] !== '' ) {
					$row['city_coef'] = floatval( $d['city_coef'] );
				}
				if ( isset( $d['digital_fee'] ) && $d['digital_fee'] !== '' ) {
					$row['digital_fee'] = floatval( $d['digital_fee'] );
				}
				if ( isset( $d['primary_monthly_rate'] ) && $d['primary_monthly_rate'] !== '' ) {
					$row['primary_monthly_rate'] = floatval( $d['primary_monthly_rate'] );
				}
				if ( isset( $d['sum_rate'] ) && $d['sum_rate'] !== '' ) {
					$row['sum_rate'] = floatval( $d['sum_rate'] );
				}
				if ( isset( $d['prepay'] ) && $d['prepay'] !== '' ) {
					$row['prepay'] = floatval( $d['prepay'] );
				}
				if ( isset( $d['city_share'] ) && $d['city_share'] !== '' ) {
					$row['city_share'] = floatval( $d['city_share'] );
				}
				$durs[] = $row;
			}
			$rf = array();
			foreach ( array( 'plan_name', 'principal', 'months', 'step', 'digital_fee', 'final_coef', 'purchasing_power', 'monthly_installment', 'period_installment', 'total_repay' ) as $k ) {
				$rf[ $k ] = ! empty( $pl['result_fields'][ $k ] ) ? 1 : 0;
			}
			$m = $pl['method'] ?? 'flat_coef';
			if ( ! in_array( $m, $methods, true ) ) {
				$m = 'flat_coef';
			}
			$clean[] = array(
				'id'          => sanitize_key( $pl['id'] ?? uniqid( 'plan' ) ),
				'name'        => sanitize_text_field( $pl['name'] ?? '' ),
				'active'      => ! empty( $pl['active'] ) ? 1 : 0,
				'method'      => $m,
				'digital_fee' => floatval( $pl['digital_fee'] ?? 0 ),
				'digital_fee_percent' => floatval( $pl['digital_fee_percent'] ?? 0 ),
				'supplier_base_on' => 'principal',
				'city_base_on'     => in_array( ( $pl['city_base_on'] ?? '' ), array( 'principal', 'supplier_deposit' ), true ) ? $pl['city_base_on'] : 'supplier_deposit',
				'primary_monthly_rate' => floatval( $pl['primary_monthly_rate'] ?? 0 ),
				'durations'   => $durs,
				'supplier_coef' => floatval( $pl['supplier_coef'] ?? 0 ),
				'city_coef'     => floatval( $pl['city_coef'] ?? 0 ),
				'agent_coef'    => floatval( $pl['agent_coef'] ?? 0 ),
				'agent_mode'    => ( ( $pl['agent_mode'] ?? '' ) === 'from_credit' ) ? 'from_credit' : 'from_city',
				'guarantee_pct' => floatval( $pl['guarantee_pct'] ?? 120 ),
				'cancel_penalty_pct' => floatval( $pl['cancel_penalty_pct'] ?? 25 ),
				'cash_ceiling'  => floatval( $pl['cash_ceiling'] ?? 0 ),
				'cash_floor'    => floatval( $pl['cash_floor'] ?? 0 ),
				'guarantor_threshold' => floatval( $pl['guarantor_threshold'] ?? 0 ),
				'ceiling_source' => in_array( ( $pl['ceiling_source'] ?? '' ), array( 'admin', 'supplier', 'payslip' ), true ) ? $pl['ceiling_source'] : 'admin',
				'always_guarantor' => ! empty( $pl['always_guarantor'] ) ? 1 : 0,
				'primary_fee_per_month' => floatval( $pl['primary_fee_per_month'] ?? 0 ),
				'chain_deduction_coef' => floatval( $pl['chain_deduction_coef'] ?? 0 ),
				'supplier_fixed_fee' => floatval( $pl['supplier_fixed_fee'] ?? 0 ),
				'supplier_vat_rate' => floatval( $pl['supplier_vat_rate'] ?? 0.1 ),
				'city_vat_rate' => floatval( $pl['city_vat_rate'] ?? 0.1 ),
				'result_fields' => $rf,
			);
		}
		update_option( self::OPT_PLANS, $clean, false );
		wp_send_json_success( array( 'count' => count( $clean ) ) );
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'plan' => '' ), $atts, 'cgs_installment_calculator' );
		$plans = array_values( array_filter( self::get_plans(), function( $p ) {
			return ! empty( $p['active'] );
		} ) );
		$nonce = wp_create_nonce( 'cgs_calc' );
		$ajax  = admin_url( 'admin-ajax.php' );
		ob_start();
		?>
		<div class="cgs-calc-wrap" data-ajax="<?php echo esc_url( $ajax ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>" dir="rtl">
			<h3 class="cgs-calc-title">محاسبه‌گر اقساط شهر قسط</h3>
			<div class="cgs-calc-grid">
				<label>طرح اعتباری
					<select class="cgs-calc-plan">
						<?php foreach ( $plans as $pl ) : ?>
							<option value="<?php echo esc_attr( $pl['id'] ); ?>" <?php selected( $atts['plan'], $pl['id'] ); ?>
								data-durations="<?php echo esc_attr( wp_json_encode( $pl['durations'] ?? array() ) ); ?>"
								data-method="<?php echo esc_attr( $pl['method'] ?? 'flat_coef' ); ?>"
								data-supplier="<?php echo esc_attr( $pl['supplier_coef'] ?? 0 ); ?>"
								data-city="<?php echo esc_attr( $pl['city_coef'] ?? 0 ); ?>"
								data-digital="<?php echo esc_attr( $pl['digital_fee'] ?? 0 ); ?>"
								data-primary="<?php echo esc_attr( $pl['primary_monthly_rate'] ?? 0 ); ?>"
								data-agent="<?php echo esc_attr( $pl['agent_coef'] ?? 0 ); ?>"
								data-agent-mode="<?php echo esc_attr( $pl['agent_mode'] ?? 'from_city' ); ?>">
								<?php echo esc_html( $pl['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>اصل مبلغ اعتبار (ریال)
					<input type="text" class="cgs-calc-principal" inputmode="numeric" placeholder="مثلاً ۳۰۰۰۰۰۰۰۰۰">
				</label>
				<label>مدت بازپرداخت (ماه)
					<select class="cgs-calc-months"></select>
				</label>
				<label>گام بازپرداخت
					<select class="cgs-calc-step"></select>
				</label>
			</div>
			<button type="button" class="cgs-calc-btn">محاسبه</button>
			<div class="cgs-calc-result" hidden>
				<div class="cgs-calc-stats"></div>
			</div>
		</div>
		<style>
		.cgs-calc-wrap{font-family:Tahoma,Vazirmatn,sans-serif;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px;max-width:720px;box-shadow:0 8px 28px rgba(15,23,42,.06)}
		.cgs-calc-title{margin:0 0 14px;color:#1e3a8a}
		.cgs-calc-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px}
		.cgs-calc-grid label{display:flex;flex-direction:column;gap:6px;font-size:12px;font-weight:700;color:#475569}
		.cgs-calc-grid input,.cgs-calc-grid select{padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-weight:400}
		.cgs-calc-btn{background:linear-gradient(135deg,#1e3a8a,#4f46e5);color:#fff;border:0;border-radius:10px;padding:12px 20px;font-weight:800;cursor:pointer}
		.cgs-calc-stats{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:14px 0}
		.cgs-calc-stats div{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px}
		.cgs-calc-stats span{display:block;font-size:11px;color:#64748b}
		.cgs-calc-stats strong{font-size:1.05rem}
		@media(max-width:560px){.cgs-calc-grid,.cgs-calc-stats{grid-template-columns:1fr}}
		</style>
		<script>
		(function(){
		  function num(v){ return String(v||'').replace(/[^\d.]/g,''); }
		  function fmt(n){ n=Number(n||0); var neg=n<0; n=Math.abs(Math.round(n)); var s=String(n).replace(/\B(?=(\d{3})+(?!\d))/g,'/'); return (neg?'-':'')+s; }
		  var root=document.querySelector('.cgs-calc-wrap');
		  if(!root) return;
		  var planSel=root.querySelector('.cgs-calc-plan');
		  var monthsSel=root.querySelector('.cgs-calc-months');
		  var stepSel=root.querySelector('.cgs-calc-step');
		  function syncPlan(){
		    var opt=planSel.options[planSel.selectedIndex]; if(!opt) return;
		    var durs=[]; try{ durs=JSON.parse(opt.getAttribute('data-durations')||'[]'); }catch(e){}
		    monthsSel.innerHTML='';
		    durs.forEach(function(d){
		      var o=document.createElement('option');
		      o.value=d.months; o.textContent=d.months+' ماه';
		      o.dataset.coef=d.coef; o.dataset.steps=JSON.stringify(d.steps||[1]);
		      monthsSel.appendChild(o);
		    });
		    syncSteps();
		  }
		  function syncSteps(){
		    var opt=monthsSel.options[monthsSel.selectedIndex];
		    stepSel.innerHTML='';
		    var steps=[1]; if(opt){ try{ steps=JSON.parse(opt.dataset.steps||'[1]'); }catch(e){} }
		    steps.forEach(function(s){ var o=document.createElement('option'); o.value=s; o.textContent=s===1?'هر ماه':('هر '+s+' ماه'); stepSel.appendChild(o); });
		  }
		  planSel.addEventListener('change', syncPlan);
		  monthsSel.addEventListener('change', syncSteps);
		  syncPlan();
		  root.querySelector('.cgs-calc-btn').addEventListener('click', function(){
		    var opt=planSel.options[planSel.selectedIndex];
		    var mopt=monthsSel.options[monthsSel.selectedIndex];
		    var fd=new FormData();
		    fd.append('action','cgs_calc_installment');
		    fd.append('nonce', root.dataset.nonce);
		    fd.append('principal', num(root.querySelector('.cgs-calc-principal').value));
		    fd.append('plan_id', planSel.value);
		    fd.append('months', monthsSel.value);
		    fd.append('step', stepSel.value);
		    fd.append('coef', mopt ? mopt.dataset.coef : '');
		    fd.append('method', opt ? opt.getAttribute('data-method') : 'flat_coef');
		    fd.append('supplier_coef', opt ? opt.getAttribute('data-supplier') : 0);
		    fd.append('city_coef', opt ? opt.getAttribute('data-city') : 0);
		    fd.append('digital_fee', opt ? opt.getAttribute('data-digital') : 0);
		    fd.append('primary_monthly_rate', opt ? opt.getAttribute('data-primary') : 0);
		    fd.append('agent_coef', opt ? opt.getAttribute('data-agent') : 0);
		    fd.append('agent_mode', opt ? opt.getAttribute('data-agent-mode') : 'from_city');
		    fetch(root.dataset.ajax,{method:'POST',body:fd,credentials:'same-origin'})
		      .then(function(r){return r.json()}).then(function(j){
		        if(!j.success) return;
		        var d=j.data, box=root.querySelector('.cgs-calc-result'), rf=d.preview_fields||d.result_fields||{};
		        function row(k,label,val){ if(rf[k]===0) return ''; return '<div><span>'+label+'</span><strong>'+val+'</strong></div>'; }
		        box.hidden=false;
		        box.querySelector('.cgs-calc-stats').innerHTML=
		          row('plan_name','نام طرح', d.plan_name||'—')+
		          row('principal','اصل مبلغ', fmt(d.principal))+
		          row('months','مدت (ماه)', fmt(d.months))+
		          row('step','گام', fmt(d.step))+
		          row('purchasing_power','قدرت خرید / اعتبار تخصیص‌یافته', fmt(d.purchasing_power))+
		          row('monthly_installment','قسط ماهانه', fmt(d.monthly_installment))+
		          row('period_installment','قسط دوره‌ای', fmt(d.period_installment))+
		          row('total_repay','جمع بازپرداخت (اصل‌وفرع)', fmt(d.total_repay))+
		          row('final_coef','ضریب ماهانه محاسبه‌شده', (Number(d.final_coef||0).toFixed(7)))+
		          row('guarantee_check','چک تضمین', fmt(d.guarantee_check))+
		          row('total_deductions','جمع کسورات', fmt(d.total_deductions));
		      });
		  });
		})();
		</script>
		<?php
		return ob_get_clean();
	}


	/** تحلیل حساسیت نرخ: تغییر جمع‌نرخ / ضریب و اثر روی قسط و اعتبار */
	public static function sensitivity_analysis( $args, $deltas = array( -0.05, -0.02, 0, 0.02, 0.05 ) ) {
		$base = self::calculate_full( $args );
		$rows = array();
		foreach ( $deltas as $d ) {
			$a = $args;
			$method = $a['method'] ?? ( $base['method'] ?? '' );
			if ( in_array( $method, array( 'manisa_digital', 'razi_leasing' ), true ) ) {
				$sr = (float) ( $base['sum_rate'] ?? 1 );
				$a['sum_rate'] = $sr * ( 1 + $d );
			} else {
				$c = (float) ( $base['coef'] ?? $args['coef'] ?? 0 );
				$a['coef'] = $c * ( 1 + $d );
				$a['rate'] = $a['coef'];
			}
			$r = self::calculate_full( $a );
			$rows[] = array(
				'delta_pct' => round( $d * 100, 2 ),
				'installment' => $r['period_installment'] ?? 0,
				'credit' => $r['purchasing_power'] ?? 0,
				'total_repay' => $r['total_repay'] ?? 0,
				'effective_annual_rate' => $r['effective_annual_rate'] ?? null,
			);
		}
		return array( 'base' => $base, 'rows' => $rows );
	}

	/** جدول مقایسه چند مدت / روش برای یک مبلغ */
	public static function comparison_table( $principal, $plan_ids = array(), $months_list = array() ) {
		$out = array();
		$plans = self::get_plans();
		foreach ( $plans as $pl ) {
			if ( $plan_ids && ! in_array( $pl['id'], $plan_ids, true ) ) {
				continue;
			}
			if ( empty( $pl['active'] ) ) {
				continue;
			}
			foreach ( (array) ( $pl['durations'] ?? array() ) as $d ) {
				$m = (int) ( $d['months'] ?? 0 );
				if ( $months_list && ! in_array( $m, $months_list, true ) ) {
					continue;
				}
				$step = (int) ( ( $d['steps'][0] ?? 1 ) );
				$r = self::calculate_full( array(
					'principal' => $principal,
					'plan_id' => $pl['id'],
					'months' => $m,
					'step' => $step,
					'method' => $pl['method'] ?? null,
					'sum_rate' => $d['sum_rate'] ?? null,
					'prepay' => $d['prepay'] ?? null,
					'city_share' => $d['city_share'] ?? null,
				) );
				$out[] = array(
					'plan' => $pl['name'] ?? $pl['id'],
					'method' => $pl['method'] ?? '',
					'months' => $m,
					'step' => $step,
					'installment' => $r['period_installment'] ?? 0,
					'credit' => $r['purchasing_power'] ?? 0,
					'total' => $r['total_repay'] ?? 0,
					'guarantee' => $r['guarantee_check'] ?? 0,
					'effective' => $r['effective_annual_rate'] ?? null,
					'risk' => $r['risk_label'] ?? '',
					'guarantor' => ! empty( $r['needs_guarantor'] ) ? 'بله' : 'خیر',
				);
			}
		}
		return $out;
	}

	public static function admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// Force refresh default tamin plan structure once if missing fields
		$plans = self::get_plans();
		$nonce_admin = wp_create_nonce( 'cgs_calc_admin' );
		$nonce_calc  = wp_create_nonce( 'cgs_calc' );
		$ajax = admin_url( 'admin-ajax.php' );
		$methods = self::methods_list();
		$field_labels = array(
			'plan_name' => 'نام طرح', 'principal' => 'اصل مبلغ', 'months' => 'مدت ماه', 'step' => 'گام',
			'digital_fee' => 'زیرساخت', 'final_coef' => 'ضریب نهایی', 'purchasing_power' => 'قدرت خرید',
			'monthly_installment' => 'قسط ماهانه', 'period_installment' => 'قسط دوره‌ای', 'total_repay' => 'جمع بازپرداخت',
		);
		include dirname( __FILE__ ) . '/../admin/views/installment-calculator-admin.php';
	}
}
