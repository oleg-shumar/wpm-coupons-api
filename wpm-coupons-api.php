<?php
/**
 * Coupons API
 *
 * This plugin makes coupons connected to issuer APIs
 *
 * Plugin Name: Coupons API
 * Plugin URI: https://wp-masters.com/
 * Version: 1.0
 * Author: WP Masters
 * Description: This plugin makes coupons connected to issuer APIs
 * Text Domain: wpm-coupons-api
 *
 * @author      WP Masters
 * @version     v.1.0 (24/07/21)
 * @copyright   Copyright (c) 2021
 */

define( 'WPM_COUPONS_API_TEXT_DOMAIN', 'wpm-coupons-api' );

class WPM_Coupons_API {
	/**
	 * WPM_Coupons_API constructor.
	 */
	public function __construct() {
		// Init filters on coupon error
		add_filter( 'woocommerce_coupon_error', array( $this, 'wpm_ca_woocommerce_coupon_code' ), - 9999, 3 );
        add_action('woocommerce_thankyou', [$this, 'redeem_coupon_after_order'], 10, 1 );

        // Init functions
        add_action('init', [$this, 'save_settings']);

        // Create Menu
        add_action('admin_menu', [$this, 'register_menu']);
    }

    /**
     * Save Coupons API Settings
     */
    public function save_settings()
    {
        if(isset($_POST) && isset($_POST['api_settings'])) {
            $data = $_POST['api_settings'];
            update_option('api_settings', json_encode($data));
        }
    }

    /**
     * Create new menu and page in navigation
     */
    public function register_menu()
    {
        add_menu_page('Coupons Settings', 'Coupons Settings', 'edit_others_posts', 'wpm_coupons_settings');
        add_submenu_page('wpm_coupons_settings', 'Coupons Settings', 'Coupons Settings', 'manage_options', 'wpm_coupons_settings', function () {
            $settings = json_decode(get_option('api_settings', true), true);

            include 'templates/settings_template.php';
        });
    }

    /**
     * Check if Order has Coupon and it is from API for Redeem
     */
    public function redeem_coupon_after_order($order_id)
    {
        $order = new WC_Order($order_id);

        foreach($order->get_used_coupons() as $coupon_code){
            // Retrieving the coupon ID
            $coupon_post = get_page_by_title($coupon_code, OBJECT, 'shop_coupon');
            $coupon_id = $coupon_post->ID;
            $is_api_coupon = get_post_meta($coupon_id, 'is_wpm_generated_coupon', true);

            if($is_api_coupon == '1') {
                $this->validateCouponWithAPI($coupon_code, 'Redeem');
                update_post_meta($coupon_id, 'is_wpm_generated_coupon', 'used');
            }
        }
    }

	/**
	 * Action on entered coupon. Call API - and if call is successful - then create new coupon and apply it.
	 */
	public function wpm_ca_woocommerce_coupon_code( $err, $err_code, $coupon ) {

		// 1. Check if this coupon already exists in WC - if yes, do nothing,
		// no need to call API
		if ( $err_code != \WC_Coupon::E_WC_COUPON_NOT_EXIST ) {
			return $err;
		}

		$code_coupon = $coupon->get_code();

		// 2. Now - call API and get response from coupon supplier
		$result = $this->validateCouponWithAPI($code_coupon, 'Validate');

		if(isset($result['TokenDetailsList'][0]) && isset( $result['TokenDetailsList'][0]['TokenValue'] ) &&
            isset( $result['TokenDetailsList'][0]['TokenStatus'] ) && $result['TokenDetailsList'][0]['TokenStatus'] == 'Active' ) {

			$amount        = $result['TokenDetailsList'][0]['TokenValue']; // Amount
			$discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product

			$new_coupon = array(
				'post_title'    => $code_coupon,
				'post_status'   => 'publish',
				'post_author'   => 1,
				'post_type'     => 'shop_coupon',
				'post_excerpt'     => __( 'Generated automatically using api-ppe.tesco.com API response (' . date( 'd.m.Y H:i:s' ) . ')', WPM_COUPONS_API_TEXT_DOMAIN )
			);

			$new_coupon_id = wp_insert_post( $new_coupon );
			update_post_meta( $new_coupon_id, 'is_wpm_generated_coupon', '1');
			update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
			update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
			update_post_meta( $new_coupon_id, 'individual_use', 'no' );
			update_post_meta( $new_coupon_id, 'product_ids', '' );
			update_post_meta( $new_coupon_id, 'exclude_product_ids', '');
			update_post_meta( $new_coupon_id, 'usage_limit', '1');
			update_post_meta( $new_coupon_id, 'expiry_date', date('Y-m-d', strtotime($result['TokenDetailsList'][0]['TokenExpiryDate'])));
			update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
			update_post_meta( $new_coupon_id, 'excerpt', __( 'Generated automatically (' . date( 'd.m.Y H:i:s' ) . ')', WPM_COUPONS_API_TEXT_DOMAIN ) );
			update_post_meta( $new_coupon_id, 'free_shipping', 'no' );

			WC()->cart->add_discount( sanitize_text_field($code_coupon) );

            return null;
		} elseif(isset($result['TokenDetailsList'][0]) && isset( $result['TokenDetailsList'][0]['TokenValue'] ) &&
            isset( $result['TokenDetailsList'][0]['TokenStatus'] ) && $result['TokenDetailsList'][0]['TokenStatus'] == 'Redeemed')
        {
            $coupon_code = $coupon->get_code();

            return "Coupon '$coupon_code' is already Redeemed";
        }

        return $err;
	}

	public function validateCouponWithAPI($coupon_code, $action)
    {
        // Data for POST
        $transaction_id = time();
        $date_transaction = date("d/m/Y H:i:s");
        $settings = json_decode(get_option('api_settings', true), true);

        // Coupon Data
        $post_data = [
            'TransactionID' => $transaction_id,
            'TransactionDateTime' => $date_transaction,
            'RequestType' => $action,
            'SupplierCode' => $settings['supplier'],
            'TokenDetailsList' => [
                0 => [
                    'ReferenceNo' => $settings['referenceNo'],
                    'RequestId' => $transaction_id,
                    'TokenCode' => $coupon_code,
                ]
            ]
        ];

        $post_data = json_encode($post_data, JSON_UNESCAPED_SLASHES);

        // CURL POST Data
	    $headers = array(
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: appKeyToken=$settings[appKeyToken]&appKey=$settings[appKey]"
        );

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $settings['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $result = curl_exec($curl);
        curl_close($curl);

		return json_decode($result, true);
	}
}

$wpm_ca = new WPM_Coupons_API();