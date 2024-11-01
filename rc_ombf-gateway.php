<?php
/**
 * CreatedDate: 10/12/2018
 * UpdateDate: 26/01/2022
 * UpdateTime: 19:34
 *
 * Plugin Name:     WooCommerce Orange Money Payment Gateway By DigAgi
 * Plugin URI:      https://wordpress.org/plugins/woo-orange-money-payment-gateway-by-rc
 * Description:     Take mobile payments on your store.
 * Author URI:      https://digagi.com
 * Version:         1.1.0
 * License:         GPL2+
 * Text Domain:     rc_ombf_gateway
 * Domain Path:     /languages/
 * @class           WC_RC_OMBF_Gateway
 * @extends         WC_Payment_Gateway
 * @package         WooCommerce/Classes/Payment
 * @author          Sheldon
 * @copyright       2021 DigAgi
 */

    if( !defined( "RCOMBF_VERSION" ) )
        define( "RCOMBF_VERSION", '1.1.0' );

    if(!defined( 'RCOMBF_URL'))
        define("RCOMBF_URL", "https://digagi.com/rc_woo_pay/ombf/");

    add_filter( 'woocommerce_payment_gateways', 'rc_ombf_add_gateway_class' );
    if ( !function_exists( 'rc_ombf_add_gateway_class' ) ) {
        function rc_ombf_add_gateway_class( $gateways ) {
            $gateways[] = 'WC_RC_OMBF_Gateway';
            return $gateways;
        }
    }

	if ( !function_exists( 'rc_ombf_gateway_load_plugin_textdomain' ) ) {
		function rc_ombf_gateway_load_plugin_textdomain() {
			load_plugin_textdomain( 'rc_ombf_gateway', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
		}
		add_action( 'plugins_loaded', 'rc_ombf_gateway_load_plugin_textdomain' );
	}

    add_action( 'plugins_loaded', 'rc_ombf_init_gateway_class' );
    if ( !function_exists( 'rc_ombf_init_gateway_class' ) ) {
        function rc_ombf_init_gateway_class() {

	        /**
	         * @property string id
	         * @property string icon
	         * @property bool has_fields
	         * @property string method_title
	         * @property string method_description
	         * @property array supports
	         * @property  title
	         * @property  description
	         * @property  enabled
	         * @property array form_fields
	         */
	        class WC_RC_OMBF_Gateway extends WC_Payment_Gateway {

                public function __construct() {
                    $this->id = 'rc_ombf_gateway';
                    $this->icon = '';
                    $this->has_fields = true;
                    $this->method_title = __('RC OMBF Gateway', 'rc_ombf_gateway');
                    $this->method_description = __('Orange Money payment gateway By RConsolidate', 'rc_ombf_gateway');

                    $this->supports = array(
                        'default_credit_card_form'
                    );

                    $this->init_form_fields();

                    $this->init_form_fields();

                    $this->init_settings();
                    $this->title = $this->get_option( 'title' );
                    $this->description = $this->get_option( 'description' );
                    $this->enabled = $this->get_option( 'enabled' );

                    foreach ( $this->settings as $setting_key => $value ) {
                        $this->$setting_key = $value;
                    }

                    if(is_admin()){
                        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                    }
                }

                public function init_form_fields(){
                    $this->form_fields = array(
                        'enabled' => array(
                            'title'       => __('(Enable / Disable', 'rc_ombf_gateway'),
                            'label'       => __('Enable RC OMBF Gateway', 'rc_ombf_gateway'),
                            'type'        => 'checkbox',
                            'default'     => 'no'
                        ),
                        'title' => array(
                            'title'       => __('Title','rc_ombf_gateway'),
                            'type'        => 'text',
                            'description' => __('This controls the title which the user sees during checkout.', 'rc_ombf_gateway'),
                            'default'     => __('Orange Money', 'rc_ombf_gateway'),
                            'desc_tip'    => true,
                        ),
                        'description' => array(
                            'title'       => __('Description', 'rc_ombf_gateway'),
                            'type'        => 'textarea',
                            'description' => __('This controls the description which the user sees during checkout.', 'rc_ombf_gateway'),
                            'default'     => __('Pay with your mobile wallet via our super-cool payment gateway.', 'rc_ombf_gateway'),
                        ),
                        'gateway_key'     => array(
                            'title'       => __('Gateway Key', 'rc_ombf_gateway'),
                            'type'        => 'text',
                            'description' => __('This controls is the unique key for the module on RConsolidate.', 'rc_ombf_gateway'),
                        ),
                        'om_api_environment'    => array(
                            'title'		        => __('Test / Production', 'rc_ombf_gateway'),
                            'label'             => __('Enable OMBF environment Platform', 'rc_ombf_gateway'),
                            'type'		        => 'checkbox',
                            'description'       => __('Disabled for Test Environment or Enabled for Production Environment with appropriate credentials', 'rc_ombf_gateway'),
                            'default'	        => 'no',
                        ),
                        'om_api_msisdn'     => array(
                            'title'         => __('Orange Money API MSISDN', 'rc_ombf_gateway'),
                            'type'          => 'text'
                        ),
                        'om_api_username'   => array(
                            'title'         => __('Orange Money API Username', 'rc_ombf_gateway'),
                            'type'          => 'text'
                        ),
                        'om_api_password'   => array(
                            'title'         => __('Orange Money API Password', 'rc_ombf_gateway'),
                            'type'          => 'text'
                        ),
                    );
                }

                public function payment_fields() {
                    $environment = ( $this->om_api_environment == "yes" ) ? 'TRUE' : 'FALSE';
                    $environment_otp_generation = ( "FALSE" == $environment )
                        ? ' (*866*4*6*'
                        : ' (*144*4*6*'
                    ;

                    echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

                do_action( 'woocommerce_credit_card_form_start', $this->id );

                echo '
                    <div class="form-row form-row-wide">
                        <label>'.__('Phone Number', 'rc_ombf_gateway').'<span class="required">*</span></label>
                        <input id="rc_ombf_customerMSISDN" name="rc_ombf_customerMSISDN" type="text" autocomplete="on" minlength="8" maxlength="8">
                    </div>
                    <div class="form-row form-row-first">
                        <label>'. __('OTP Code', 'rc_ombf_gateway'). '<span class="required">* </span>' .$environment_otp_generation .'Price#)</label>
                        <input id="rc_ombf_customerOTP" name="rc_ombf_customerOTP" type="text" autocomplete="on" placeholder="Ex 123456" minlength="6" maxlength="6">
                    </div>
                    <div class="clear"></div>
                ';

                    do_action( 'woocommerce_rcombf_payment_form_end', $this->id );

                    echo '<div class="clear"></div></fieldset>';
                }

                public function validate_fields() {
                    if( empty( $_POST[ 'billing_first_name' ]) ) {
                        wc_add_notice(  __('First name is required!', 'rc_ombf_gateway'), 'error' );
                        return false;
                    }
					if(empty($_POST['rc_ombf_customerMSISDN'])){
						wc_add_notice(  __('Orane Money Customer MSISDN is required!', 'rc_ombf_gateway'), 'error' );
                        return false;
					}
					if(empty($_POST['rc_ombf_customerOTP'])){
						wc_add_notice(  __('OTP code is required!', 'rc_ombf_gateway'), 'error' );
                        return false;
					}
                    return true;
                }

                public function process_payment( $order_id ) {
                    global $woocommerce;

                    $order = wc_get_order( $order_id );

                    $payload = array(
                        'rc_ombf_gateway_key'        => $this->gateway_key,
                        'rc_ombf_api_environment'    => $this->om_api_environment,
                        'rc_ombf_api_msisdn'         => $this->om_api_msisdn,
                        'rc_ombf_api_username'       => $this->om_api_username,
                        'rc_ombf_api_password'       => $this->om_api_password,
                        'rc_ombf_version'            => RCOMBF_VERSION,

                        'rc_ombf_amount'             => $order->get_total(),

                        'rc_ombf_customerMSISDN' => ( isset( $_POST['rc_ombf_customerMSISDN'] ) ) ? $_POST['rc_ombf_customerMSISDN'] : '',
                        'rc_ombf_customerOTP'    => ( isset( $_POST['rc_ombf_customerOTP'] ) ) ? $_POST['rc_ombf_customerOTP'] : '',

                        "rc_ombf_type"               	=> 'AUTH_CAPTURE',
                        "rc_ombf_invoice_num"        	=> str_replace( "#", "",$order->get_order_number() ),
                        "rc_ombf_delim_char"         	=> '|',
                        "rc_ombf_encap_char"         	=> '',
                        "rc_ombf_delim_data"         	=> "TRUE",
                        "rc_ombf_relay_response"     	=> "FALSE",
                        "rc_ombf_method"             	=> "CC",

                        "rc_ombf_customer_id"           => $order->get_user_id(),
                        "rc_ombf_customer_ip"        	=> $_SERVER['REMOTE_ADDR'],
                        "rc_ombf_homepage"              => get_home_url()
                    );

                    $response = wp_remote_post( RCOMBF_URL, array(
                        'method'    => 'POST',
                        'body'      => $payload,
                        'timeout'   => 90,
                        'sslverify' => false,
                    ));

                    if ( is_wp_error( $response ) ){
                        wc_add_notice(  __('We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'rc_ombf_gateway'), 'error' );
                    }

                    if ( empty( $response['body'] ) ){
                        wc_add_notice(  __('Connection error.', 'rc_ombf_gateway'), 'error' );
                    }

                    $response_body = wp_remote_retrieve_body( $response );
					
					$response_data = json_decode($response_body, true);
					$transactionStatus = $response_data['transaction_status'];
					$transactionMessage = $response_data['transaction_message'];

					if( $transactionStatus == null){
						wc_add_notice(  __('Payment gateway connexion error with Orange Money.', 'rc_ombf_gateway'), 'error' );
					}else{
						switch ($transactionStatus){
							case "200":
								$order->add_order_note( __('Hey, your order is paid! Thank you!', 'rc_ombf_gateway'), true );

								$order->payment_complete();

								$woocommerce->cart->empty_cart();

								return array(
									'result'   => 'success',
									'redirect' => $this->get_return_url( $order ),
								);
							break;
							default:
								wc_add_notice( $transactionMessage, 'error' );
								$order->add_order_note( 'Error: '. $transactionMessage );
							break;
						}
					}
                }
            }
        }
    }
