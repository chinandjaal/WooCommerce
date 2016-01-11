<?php

/**
 * Plugin Name: WooCommerce Payment Gateway - Exalt
 * Plugin URI: http://www.exaltcommerce.com/woocommerce/
 * Description: Accept all major credit cards directly on your WooCommerce site in a seamless and secure checkout environment with Exalt.
 * Version: 1.7.6
 * Author: innerfire
 * Author URI: http://www.exaltcommerce.com/
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @package WordPress
 * @author innerfire
 * @since 1.0.0
 */

add_action( 'plugins_loaded', 'woocommerce_exalt_init', 0 );

function woocommerce_exalt_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
    return;
  };

  DEFINE ('PLUGIN_DIR', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) . '/' );
  DEFINE ('GATEWAY_URL', 'http://172.16.9.248:82/api/ApiEnvoicePayment/CreatePaymentToMerchent/');
  DEFINE ('QUERY_URL', 'http://172.16.9.248:82/api/ApiEnvoicePayment/CreatePaymentToMerchent/');

	/**
	 * Exalt Gateway Class
	 */
		class WC_Exalt extends WC_Payment_Gateway {

			function __construct() {

        // Register plugin information
	      $this->id			    = 'exalt';
	      $this->has_fields = true;
	      $this->supports   = array(
               'products', 
               'subscriptions',
               'subscription_cancellation', 
               'subscription_suspension', 
               'subscription_reactivation',
               'subscription_amount_changes',
               'subscription_date_changes',
               'subscription_payment_method_change',
               'refunds'
               );

        // Create plugin fields and settings
				$this->init_form_fields();
				$this->init_settings();

				// Get setting values
				foreach ( $this->settings as $key => $val ) $this->$key = $val;

        // Load plugin checkout icon
	      $this->icon = PLUGIN_DIR . 'images/cards.png';

				add_action( 'woocommerce_before_my_account',                            array( $this, 'add_payment_method_options' ) );
				add_action( 'woocommerce_receipt_exalt',                              array( $this, 'receipt_page' ) );
				add_action( 'woocommerce_update_options_payment_gateways',              array( $this, 'process_admin_options' ) );
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				add_action( 'wp_enqueue_scripts',                                       array( $this, 'add_exalt_scripts' ) );
				add_action( 'scheduled_subscription_payment_exalt',                   array( $this, 'process_scheduled_subscription_payment'), 0, 3 );

		  }

   
	    function init_form_fields() {

	      $this->form_fields = array(
	      'enabled'     => array(
	        'title'       => __( 'Enable/Disable', 'woothemes' ),
	        'label'       => __( 'Enable Exalt Gateway', 'woothemes' ),
	        'type'        => 'checkbox',
	        'description' => '',
	        'default'     => 'no'
	        ),
	      'title'       => array(
	        'title'       => __( 'Title', 'woothemes' ),
	        'type'        => 'text',
	        'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ),
	        'default'     => __( 'Credit Card (Exalt)', 'woothemes' )
	        ),
	      'description' => array(
	        'title'       => __( 'Description', 'woothemes' ),
	        'type'        => 'textarea',
	        'description' => __( 'This controls the description which the user sees during checkout.', 'woothemes' ),
	        'default'     => 'Pay with your credit card via Exalt Gateway.'
	        ),
	      'MerchantID'    => array(
	        'title'       => __( 'MerchantID', 'woothemes' ),
	        'type'        => 'text',
	        'description' => __( 'This is the API username generated within the Exalt gateway.', 'woothemes' ),
	        'default'     => ''
	        ),
	      'APIKey'    => array(
	        'title'       => __( 'Apikey', 'woothemes' ),
	        'type'        => 'text',
	        'description' => __( 'This is the API merchant key generated within the Exalt gateway.', 'woothemes' ),
	        'default'     => ''
	        ),
	      'salemethod'  => array(
	        'title'       => __( 'Sale Method', 'woothemes' ),
	        'type'        => 'select',
	        'description' => __( 'Select which sale method to use. Authorize Only will authorize the customers card for the purchase amount only.  Authorize &amp; Capture will authorize the customer\'s card and collect funds.', 'woothemes' ),
	        'options'     => array(
	          'sale' => 'Authorize &amp; Capture',
	          'auth' => 'Authorize Only'
	          ),
	        'default'     => 'Authorize &amp; Capture'
	        ),
	      'cardtypes'   => array(
	        'title'       => __( 'Accepted Cards', 'woothemes' ),
	        'type'        => 'multiselect',
	        'description' => __( 'Select which card types to accept.', 'woothemes' ),
	        'default'     => '',
	        'options'     => array(
	          'MasterCard'	      => 'MasterCard',
	          'Visa'			        => 'Visa',
	          'Discover'		      => 'Discover',
	          'American Express'  => 'American Express'
	          ),
	        ),
	      'cvv'         => array(
	        'title'       => __( 'CVV', 'woothemes' ),
	        'type'        => 'checkbox',
	        'label'       => __( 'Require customer to enter credit card CVV code', 'woothemes' ),
	        'description' => __( '', 'woothemes' ),
	        'default'     => 'yes'
	        ),
	      'saveinfo'    => array(
	        'title'       => __( 'Billing Information Storage', 'woothemes' ),
	        'type'        => 'checkbox',
	        'label'       => __( 'Allow customers to save billing information for future use (requires Exalt Customer Vault)', 'woothemes' ),
	        'description' => __( '', 'woothemes' ),
	        'default'     => 'no'
	        ),

			);
		  }


      /**
       * UI - Admin Panel Options
       */
			function admin_options() { ?>
				<h3><?php _e( 'Exalt','woothemes' ); ?></h3>
			    <p><?php _e( 'The Exalt Gateway is simple and powerful.  The plugin works by adding credit card fields on the checkout page, and then sending the details to Exalt for verification.  <a href="http://www.exaltcommerce.com/woocommerce/">Click here to get paid like the pros</a>.', 'woothemes' ); ?></p>
			    <table class="form-table">
					<?php $this->generate_settings_html(); ?>
				</table>
			<?php }
      /**
       * UI - Payment page fields for Exalt.
       */
			function payment_fields() {
          		// Description of payment method from settings
          		if ( $this->description ) { ?>
            		<p><?php echo $this->description; ?></p>
      		<?php } ?>
			<fieldset  style="padding-left: 40px;">
		        <?php
		          $user = wp_get_current_user();
		          $this->check_payment_method_conversion( $user->user_login, $user->ID );
		          if ( $this->user_has_stored_data( $user->ID ) ) { ?>
						<fieldset>
							<input type="radio" name="exalt-use-stored-payment-info" id="exalt-use-stored-payment-info-yes" value="yes" checked="checked" onclick="document.getElementById('exalt-new-info').style.display='none'; document.getElementById('exalt-stored-info').style.display='block'"; /><label for="exalt-use-stored-payment-info-yes" style="display: inline;"><?php _e( 'Use a stored credit card', 'woocommerce' ) ?></label>
								<div id="exalt-stored-info" style="padding: 10px 0 0 40px; clear: both;">
						            <?php
						              $i = 0;
						              $method = $this->get_payment_method( $i );
						              while( $method != null ) {
						            ?>
				                    <p>
				              			<input type="radio" name="exalt-payment-method" id="<?php echo $i; ?>" value="<?php echo $i; ?>" <?php if($i == 0){echo 'checked';}?>/> &nbsp;
											<?php echo $method->cc_number; ?> (<?php
				                                  $exp = $method->cc_exp;
				                                  echo substr( $exp, 0, 2 ) . '/' . substr( $exp, -2 );
				              				?>)
											<br />
				                    </p>
				          			<?php
				                  		$method = $this->get_payment_method( ++$i );
				                  	} ?>
						</fieldset>
						<fieldset>
							<p>
								<input type="radio" name="exalt-use-stored-payment-info" id="exalt-use-stored-payment-info-no" value="no" onclick="document.getElementById('exalt-stored-info').style.display='none'; document.getElementById('exalt-new-info').style.display='block'"; />
		                  		<label for="exalt-use-stored-payment-info-no"  style="display: inline;"><?php _e( 'Use a new payment method', 'woocommerce' ) ?></label>
		                	</p>
		                	<div id="exalt-new-info" style="display:none">
						</fieldset>
				<?php } else { ?>
              			<fieldset>
              				<!-- Show input boxes for new data -->
              				<div id="exalt-new-info">
              					<?php } ?>
								<!-- Credit card number -->
                    			<p class="form-row form-row-first">
									<label for="ccnum"><?php echo __( 'Credit Card number', 'woocommerce' ) ?> <span class="required">*</span></label>
									<input type="text" class="input-text" id="ccnum" name="ccnum" maxlength="16" />
                    			</p>
								<!-- Credit card type -->
                    			<p class="form-row form-row-last">
                      				<label for="cardtype"><?php echo __( 'Card type', 'woocommerce' ) ?> <span class="required">*</span></label>
                      				<select name="cardtype" id="cardtype" class="woocommerce-select">
                  						<?php  foreach( $this->cardtypes as $type ) { ?>
                            				<option value="<?php echo $type ?>"><?php _e( $type, 'woocommerce' ); ?></option>
                  						<?php } ?>
                       				</select>
                    			</p>
								<div class="clear"></div>
								<!-- Credit card expiration -->
                    			<p class="form-row form-row-first">
                      				<label for="cc-expire-month"><?php echo __( 'Expiration date', 'woocommerce') ?> <span class="required">*</span></label>
                      				<select name="expmonth" id="expmonth" class="woocommerce-select woocommerce-cc-month">
                        				<option value=""><?php _e( 'Month', 'woocommerce' ) ?></option><?php
				                        $months = array();
				                        for ( $i = 1; $i <= 12; $i ++ ) {
				                          $timestamp = mktime( 0, 0, 0, $i, 1 );
				                          $months[ date( 'n', $timestamp ) ] = date( 'F', $timestamp );
				                        }
				                        foreach ( $months as $num => $name ) {
				                          printf( '<option value="%u">%s</option>', $num, $name );
				                        } ?>
                      				</select>
                      				<select name="expyear" id="expyear" class="woocommerce-select woocommerce-cc-year">
                        				<option value=""><?php _e( 'Year', 'woocommerce' ) ?></option><?php
				                        $years = array();
				                        for ( $i = date( 'y' ); $i <= date( 'y' ) + 15; $i ++ ) {
				                          printf( '<option value="20%u">20%u</option>', $i, $i );
				                        } ?>
                      				</select>
                    			</p>
								<?php

				                    // Credit card security code
				                    if ( $this->cvv == 'yes' ) { ?>
				                      <p class="form-row form-row-last">
				                        <label for="cvv"><?php _e( 'Card security code', 'woocommerce' ) ?> <span class="required">*</span></label>
				                        <input oninput="validate_cvv(this.value)" type="text" class="input-text" id="cvv" name="cvv" maxlength="4" style="width:45px" />
				                        <span class="help"><?php _e( '3 or 4 digits usually found on the signature strip.', 'woocommerce' ) ?></span>
				                      </p><?php
				                    }

			                    // Option to store credit card data
			                    if ( $this->saveinfo == 'yes' && ! ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) ) { ?>
			                      	<div style="clear: both;"></div>
										<p>
			                        		<label for="saveinfo"><?php _e( 'Save this billing method?', 'woocommerce' ) ?></label>
			                        		<input type="checkbox" class="input-checkbox" id="saveinfo" name="saveinfo" />
			                        		<span class="help"><?php _e( 'Select to store your billing information for future use.', 'woocommerce' ) ?></span>
			                      		</p>
									<?php  } ?>
            			</fieldset>
			</fieldset>
<?php
    }

		/**
		 * Process the payment and return the result.
		 */
		function process_payment( $order_id ) {

			global $woocommerce;

			$order = new WC_Order( $order_id );
      $user = new WP_User( $order->user_id );
      $this->check_payment_method_conversion( $user->user_login, $user->ID );

			// Convert CC expiration date from (M)M-YYYY to MMYY
			$expmonth = $this->get_post( 'expmonth' );
			if ( $expmonth < 10 ) $expmonth = '0' . $expmonth;
			if ( $this->get_post( 'expyear' ) != null ) $expyear = substr( $this->get_post( 'expyear' ), -2 );

      // Create server request using stored or new payment details
			if ( $this->get_post( 'exalt-use-stored-payment-info' ) == 'yes' ) {

        // Short request, use stored billing details
        $customer_vault_ids = get_user_meta( $user->ID, 'customer_vault_ids', true );
        $id = $customer_vault_ids[ $this->get_post( 'exalt-payment-method' ) ];
        if( substr( $id, 0, 1 ) !== '_' ) $base_request['customer_vault_id'] = $id;
        else {
          $base_request['customer_vault_id'] = $user->user_login;
          $base_request['billing_id']        = substr( $id , 1 );
          $base_request['ver']               = 2;
        }

      } else {

        // Full request, new customer or new information
        $base_request = array (
          'card_number' 	=> $this->get_post( 'ccnum' ),
          'cvv' 		=> $this->get_post( 'cvv' ),
          'expmonth' 	=> $this->get_post('expmonth'),
		  'expyear'    =>  $this->get_post('expyear'),
         'card_holder_name'=> $order->billing_first_name,
		   
		 
          );

        // If "save billing data" box is checked or order is a subscription, also request storage of customer payment information.
        if ( $this->get_post( 'saveinfo' ) || $this->is_subscription( $order ) ) {

          $base_request['customer_vault'] = 'add_customer';

          // Generate a new customer vault id for the payment method
          $new_customer_vault_id = $this->random_key();

          // Set customer ID for new record
          $base_request['customer_vault_id'] = $new_customer_vault_id;

          // Set 'recurring' flag for subscriptions
          if( $this->is_subscription( $order ) ) $base_request['billing_method'] = 'recurring';

        }
      }

      // Add transaction-specific details to the request
      $transaction_details = array (
       
        'Apikey'  => $this->Apikey,
        'IP_Address' => $_SERVER['REMOTE_ADDR'],
        );

      // Send request and get response from server
      $response = $this->post_and_get_response( array_merge( $base_request, $transaction_details ) );

      // Check response
      if ( $response['response'] == 1 ) {
        // Success
        $order->add_order_note( __( 'Exalt payment completed. Transaction ID: ' , 'woocommerce' ) . $response['transactionid'] );
        $order->payment_complete();

        if ( $this->get_post( 'exalt-use-stored-payment-info' ) == 'yes' ) {

          if ( $this->is_subscription( $order ) ) {
            // Store payment method number for future subscription payments
            update_post_meta( $order->id, 'payment_method_number', $this->get_post( 'exalt-payment-method' ) );
          }

        } else if ( $this->get_post( 'saveinfo' ) || $this->is_subscription( $order ) ) {

          // Store the payment method number/customer vault ID translation table in the user's metadata
          $customer_vault_ids = get_user_meta( $user->ID, 'customer_vault_ids', true );
          $customer_vault_ids[] = $new_customer_vault_id;
          update_user_meta( $user->ID, 'customer_vault_ids', $customer_vault_ids );

          if ( $this->is_subscription( $order ) ) {
            // Store payment method number for future subscription payments
            update_post_meta( $order->id, 'payment_method_number', count( $customer_vault_ids ) - 1 );
          }

        }

        // Return thank you redirect
        return array (
          'result'   => 'success',
          'redirect' => $this->get_return_url( $order ),
        );

      } else if ( $response['response'] == 2 ) {
        // Decline
        $order->add_order_note( __( 'Exalt payment failed. Payment declined.', 'woocommerce' ) );
        wc_add_notice( __( 'Sorry, the transaction was declined.', 'woocommerce' ), $notice_type = 'error' );

      } else if ( $response['response'] == 3 ) {
        // Other transaction error
        $order->add_order_note( __( 'Exalt payment failed. Error: ', 'woocommerce' ) . $response['responsetext'] );
        wc_add_notice( __( 'Sorry, there was an error: ', 'woocommerce' ) . $response['responsetext'], $notice_type = 'error' );

      } else {
        // No response or unexpected response
        $order->add_order_note( __( "Exalt payment failed. Couldn't connect to gateway server.", 'woocommerce' ) );
        wc_add_notice( __( 'No response from payment gateway server. Try again later or contact the site administrator.', 'woocommerce' ), $notice_type = 'error' );

      }

		}

		/**
		 * Process a payment for an ongoing subscription.
		 */
    function process_scheduled_subscription_payment( $amount_to_charge, $order, $product_id ) {

      $user = new WP_User( $order->user_id );
      $this->check_payment_method_conversion( $user->user_login, $user->ID );
      $customer_vault_ids = get_user_meta( $user->ID, 'customer_vault_ids', true );
      $payment_method_number = get_post_meta( $order->id, 'payment_method_number', true );

      $exalt_request = array (
				'MerchantID' 		      => $this->MerchantID,
				'APIKey' 	      	=> $this->APIKey,
				'NetAmount'   	=> $amount_to_charge,
        		        );

      $id = $customer_vault_ids[ $payment_method_number ];
      if( substr( $id, 0, 1 ) !== '_' ) $exalt_request['customer_vault_id'] = $id;
      else {
        $exalt_request['customer_vault_id'] = $user->user_login;
        $exalt_request['billing_id']        = substr( $id , 1 );
        $exalt_request['ver']               = 2;
      }

      $response = $this->post_and_get_response( $exalt_request );

      if ( $response['response'] == 1 ) {
        // Success
        $order->add_order_note( __( 'Exalt scheduled subscription payment completed. Transaction ID: ' , 'woocommerce' ) . $response['transactionid'] );
        WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );

			} else if ( $response['response'] == 2 ) {
        // Decline
        $order->add_order_note( __( 'Exalt scheduled subscription payment failed. Payment declined.', 'woocommerce') );
        WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );

      } else if ( $response['response'] == 3 ) {
        // Other transaction error
        $order->add_order_note( __( 'Exalt scheduled subscription payment failed. Error: ', 'woocommerce') . $response['responsetext'] );
        WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );

      } else {
        // No response or unexpected response
        $order->add_order_note( __('Exalt scheduled subscription payment failed. Couldn\'t connect to gateway server.', 'woocommerce') );

      }
    }

    /**
     * Get details of a payment method for the current user from the Customer Vault
     */
  function get_payment_method( $payment_method_number ) {

      if( $payment_method_number < 0 ) die( 'Invalid payment method: ' . $payment_method_number );

      $user = wp_get_current_user();
      $customer_vault_ids = get_user_meta( $user->ID, 'customer_vault_ids', true );
      if( $payment_method_number >= count( $customer_vault_ids ) ) return null;

      $query = array (
        'MerchantID' 		      => $this->MerchantID,
        'APIKey' 	      	=> $this->APIKey,
        'report_type'       => 'customer_vault',
        );

      $id = $customer_vault_ids[ $payment_method_number ];
      if( substr( $id, 0, 1 ) !== '_' ) $query['customer_vault_id'] = $id;
      else {
        $query['customer_vault_id'] = $user->user_login;
        $query['billing_id']        = substr( $id , 1 );
        $query['ver']               = 2;
      }
      $response = wp_remote_post( QUERY_URL, array(
        'body'  => $query,
        'timeout' => 120,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'cookies' => array(),
        'ssl_verify' => false
        )
      );

      //Do we have an error?
      if( is_wp_error( $response ) ) return null;

      // Check for empty response, which means method does not exist
      if ( trim( strip_tags( $response['body'] ) ) == '' ) return null;

      // Format result
      $content = simplexml_load_string( $response['body'] )->customer_vault->customer;
      if( substr( $id, 0, 1 ) === '_' ) $content = $content->billing;

      return $content;
    }


     function check_payment_method_conversion( $user_login, $user_id ) {
      if( ! $this->user_has_stored_data( $user_id ) && $this->get_mb_payment_methods( $user_login ) != null ) $this->convert_mb_payment_methods( $user_login, $user_id );
    }

    /**
     * Convert any Multiple Billing records stored by the user into Single Billing records
     */
    function convert_mb_payment_methods( $user_login, $user_id ) {

      $mb_methods = $this->get_mb_payment_methods( $user_login );
      foreach ( $mb_methods->billing as $method ) $customer_vault_ids[] = '_' . ( (string) $method['id'] );
      // Store the payment method number/customer vault ID translation table in the user's metadata
      add_user_meta( $user_id, 'customer_vault_ids', $customer_vault_ids );

      // Update subscriptions to reference the new records
      if( class_exists( 'WC_Subscriptions_Manager' ) ) {

        $payment_method_numbers = array_flip( $customer_vault_ids );
        foreach( (array) ( WC_Subscriptions_Manager::get_users_subscriptions( $user_id ) ) as $subscription ) {
          update_post_meta( $subscription['order_id'], 'payment_method_number', $payment_method_numbers[ '_' . get_post_meta( $subscription['order_id'], 'billing_id', true ) ] );
          delete_post_meta( $subscription['order_id'], 'billing_id' );
        }

      }
    }

    /**
     * Get the user's Multiple Billing records from the Customer Vault
     */
    function get_mb_payment_methods( $user_login ) {

      if( $user_login == null ) return null;

      $query = array (
        'username' 		      => $this->username,
        'password' 	      	=> $this->password,
        'report_type'       => 'customer_vault',
        'customer_vault_id' => $user_login,
        'ver'               => '2',
        );
      $content = wp_remote_post( QUERY_URL, array(
        'body'  => $query,
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'cookies' => array(),
        'ssl_verify' => false
        )
      );

      if ( trim( strip_tags( $content['body'] ) ) == '' ) return null;
      return simplexml_load_string( $content['body'] )->customer_vault->customer;

    }

    /**
     * Check if the user has any billing records in the Customer Vault
     */
    function user_has_stored_data( $user_id ) {
      return get_user_meta( $user_id, 'customer_vault_ids', true ) != null;
    }

    /**
     * Update a stored billing record with new CC number and expiration
     */
    function update_payment_method( $payment_method, $ccnumber, $ccexp ) {

      global $woocommerce;
      $user =  wp_get_current_user();
      $customer_vault_ids = get_user_meta( $user->ID, 'customer_vault_ids', true );

      $id = $customer_vault_ids[ $payment_method ];
      if( substr( $id, 0, 1 ) == '_' ) {
        // Copy all fields from the Multiple Billing record
        $mb_method = $this->get_payment_method( $payment_method );
        $exalt_request = (array) $mb_method[0];
        // Make sure values are strings
        foreach( $exalt_request as $key => $val ) $exalt_request[ $key ] = "$val";
        // Add a new record with the updated details
        $exalt_request['customer_vault'] = 'add_customer';
        $new_customer_vault_id = $this->random_key();
        $exalt_request['customer_vault_id'] = $new_customer_vault_id;
      } else {
        // Update existing record
   
      }

      $exalt_request['MerchantID'] = $this->MerchantID;
      $exalt_request['APIKey'] = $this->APIKey;
      // Overwrite updated fields
      $exalt_request['card_number']=$ccnumber;
      $exalt_request['cc_exp'] = $ccexp;

      $response = $this->post_and_get_response( $exalt_request );

      if( $response ['response'] == 1 ) {
        if( substr( $id, 0, 1 ) === '_' ) {
          // Update references
          $customer_vault_ids[ $payment_method ] = $new_customer_vault_id;
          update_user_meta( $user->ID, 'customer_vault_ids', $customer_vault_ids );
        }
        wc_add_notice( __('Successfully updated your information!', 'woocommerce'), $notice_type = 'success' );
      } else wc_add_notice( __( 'Sorry, there was an error: ', 'woocommerce') . $response['responsetext'], $notice_type = 'error' );

    }

    /**
     * Delete a stored billing method
     */
    function delete_payment_method( $payment_method ) {

      global $woocommerce;
      $user = wp_get_current_user();
      $customer_vault_ids = get_user_meta( $user->ID, 'customer_vault_ids', true );

      $id = $customer_vault_ids[ $payment_method ];
      // If method is Single Billing, actually delete the record
      if( substr( $id, 0, 1 ) !== '_' ) {

        $exalt_request = array (
          'MerchantID' 		      => $this->MerchantID,
          'Apikey' 	      	=> $this->Apikey,
          'customer_vault'    => 'delete_customer',
          'customer_vault_id' => $id,
          );
        $response = $this->post_and_get_response( $exalt_request );
        if( $response['response'] != 1 ) {
          wc_add_notice( __( 'Sorry, there was an error: ', 'woocommerce') . $response['responsetext'], $notice_type = 'error' );
          return;
        }

      }

      $last_method = count( $customer_vault_ids ) - 1;

      // Update subscription references
      if( class_exists( 'WC_Subscriptions_Manager' ) ) {
        foreach( (array) ( WC_Subscriptions_Manager::get_users_subscriptions( $user->ID ) ) as $subscription ) {
          $subscription_payment_method = get_post_meta( $subscription['order_id'], 'payment_method_number', true );
          // Cancel subscriptions that were purchased with the deleted method
          if( $subscription_payment_method == $payment_method ) {
            delete_post_meta( $subscription['order_id'], 'payment_method_number' );
            WC_Subscriptions_Manager::cancel_subscription( $user->ID, WC_Subscriptions_Manager::get_subscription_key( $subscription['order_id'] ) );
          }
          else if( $subscription_payment_method == $last_method && $subscription['status'] != 'cancelled') {
            update_post_meta( $subscription['order_id'], 'payment_method_number', $payment_method );
          }
        }
      }

      // Delete the reference by replacing it with the last method in the array
      if( $payment_method < $last_method ) $customer_vault_ids[ $payment_method ] = $customer_vault_ids[ $last_method ];
      unset( $customer_vault_ids[ $last_method ] );
      update_user_meta( $user->ID, 'customer_vault_ids', $customer_vault_ids );

      wc_add_notice( __('Successfully deleted your information!', 'woocommerce'), $notice_type = 'success' );

    }

    /**
     * Check payment details for valid format
     */
		function validate_fields() {

      if ( $this->get_post( 'exalt-use-stored-payment-info' ) == 'yes' ) return true;

			global $woocommerce;

			// Check for saving payment info without having or creating an account
			if ( $this->get_post( 'saveinfo' )  && ! is_user_logged_in() && ! $this->get_post( 'createaccount' ) ) {
        wc_add_notice( __( 'Sorry, you need to create an account in order for us to save your payment information.', 'woocommerce'), $notice_type = 'error' );
        return false;
      }

			$cardType            = $this->get_post( 'cardtype' );
			$cardNumber          = $this->get_post( 'ccnum' );
			$cardCSC             = $this->get_post( 'cvv' );
			$cardExpirationMonth = $this->get_post( 'expmonth' );
			$cardExpirationYear  = $this->get_post( 'expyear' );

			// Check card number
			if ( empty( $cardNumber ) || ! ctype_digit( $cardNumber ) ) {
				wc_add_notice( __( 'Card number is invalid.', 'woocommerce' ), $notice_type = 'error' );
				return false;
			}

			if ( $this->cvv == 'yes' ){
				// Check security code
				if ( ! ctype_digit( $cardCSC ) ) {
					wc_add_notice( __( 'Card security code is invalid (only digits are allowed).', 'woocommerce' ), $notice_type = 'error' );
					return false;
				}
				if ( ( strlen( $cardCSC ) != 3 && in_array( $cardType, array( 'Visa', 'MasterCard', 'Discover' ) ) ) || ( strlen( $cardCSC ) != 4 && $cardType == 'American Express' ) ) {
					wc_add_notice( __( 'Card security code is invalid (wrong length).', 'woocommerce' ), $notice_type = 'error' );
					return false;
				}
			}

			// Check expiration data
			$currentYear = date( 'Y' );

			if ( ! ctype_digit( $cardExpirationMonth ) || ! ctype_digit( $cardExpirationYear ) ||
				 $cardExpirationMonth > 12 ||
				 $cardExpirationMonth < 1 ||
				 $cardExpirationYear < $currentYear ||
				 $cardExpirationYear > $currentYear + 20
			) {
				wc_add_notice( __( 'Card expiration date is invalid', 'woocommerce' ), $notice_type = 'error' );
				return false;
			}

			// Strip spaces and dashes
			$cardNumber = str_replace( array( ' ', '-' ), '', $cardNumber );

			return true;

		}

		/**
     * Send the payment data to the gateway server and return the response.
     */
    private function post_and_get_response( $request ) {
      global $woocommerce;

      // Encode request
      $post = http_build_query( $request, '', '&' );

			// Send request
      $content = wp_remote_post( GATEWAY_URL, array(
          'body'  => $post,
          'timeout' =>120,
          'redirection' => 5,
          'httpversion' => '1.0',
          'blocking' => true,
          'headers' => array'('Authorization-Token: sk_test_d0da2d00-d7e9-456b-bb50-cba2e2d05107',
'CurrentUserID:1'
),
          'cookies' => array(),
          'ssl_verify' => false
         )
      );

      // Quit if it didn't work
      if ( is_wp_error( $content ) ) {
        wc_add_notice( __( 'Problem connecting to server at ', 'woocommerce' ) . GATEWAY_URL . ' ( ' . $content->get_error_message() . ' )', $notice_type = 'error' );
        return null;
      }

      // Convert response string to array
      $vars = explode( '&', $content['body'] );
      foreach ( $vars as $key => $val ) {
        $var = explode( '=', $val );
        $data[ $var[0] ] = $var[1];
      }

      // Return response array
      return $data;

    }
  function add_payment_method_options() {

      $user = wp_get_current_user();
      $this->check_payment_method_conversion( $user->user_login, $user->ID );
      if ( ! $this->user_has_stored_data( $user->ID ) ) return;

      if( $this->get_post( 'delete' ) != null ) {

        $method_to_delete = $this->get_post( 'delete' );
        $response = $this->delete_payment_method( $method_to_delete );

      } else if( $this->get_post( 'update' ) != null ) {

        $method_to_update = $this->get_post( 'update' );
        $ccnumber = $this->get_post( 'edit-cc-number-' . $method_to_update );

        if ( empty( $ccnumber ) || ! ctype_digit( $ccnumber ) ) {

          global $woocommerce;
          wc_add_notice( __( 'Card number is invalid.', 'woocommerce' ), $notice_type = 'error' );

        } else {

          $ccexp = $this->get_post( 'edit-cc-exp-' . $method_to_update );
          $expmonth = substr( $ccexp, 0, 2 );
          $expyear = substr( $ccexp, -2 );
          $currentYear = substr( date( 'Y' ), -2);

          if( empty( $ccexp ) || ! ctype_digit( str_replace( '/', '', $ccexp ) ) ||
            $expmonth > 12 || $expmonth < 1 ||
            $expyear < $currentYear || $expyear > $currentYear + 20 )
            {

            global $woocommerce;
            wc_add_notice( __( 'Card expiration date is invalid', 'woocommerce' ), $notice_type = 'error' );

          } else {

            $response = $this->update_payment_method( $method_to_update, $ccnumber, $ccexp );

          }
        }
      }

      ?>

      <h2>Saved Payment Methods</h2>
      <p>This information is stored to save time at the checkout and to pay for subscriptions.</p>

      <?php $i = 0;
      $current_method = $this->get_payment_method( $i );
      while( $current_method != null ) {

        if( $method_to_delete === $i && $response['response'] == 1 ) { $method_to_delete = null; continue; } // Skip over a deleted entry ?>

        <header class="title">

          <h3>
            Payment Method <?php echo $i + 1; ?>
          </h3>
          <p>

            <button style="float:right" class="button" id="unlock-delete-button-<?php echo $i; ?>"><?php _e( 'Delete', 'woocommerce' ); ?></button>

            <button style="float:right; display:none" class="button" id="cancel-delete-button-<?php echo $i; ?>"><?php _e( 'No', 'woocommerce' ); ?></button>
            <form action="<?php echo get_permalink( woocommerce_get_page_id( 'myaccount' ) ) ?>" method="post" style="float:right" >
              <input type="submit" value="<?php _e( 'Yes', 'woocommerce' ); ?>" class="button alt" id="delete-button-<?php echo $i; ?>" style="display:none">
              <input type="hidden" name="delete" value="<?php echo $i ?>">
            </form>
            <span id="delete-confirm-msg-<?php echo $i; ?>" style="float:left_; display:none">Are you sure? (Subscriptions purchased with this card will be canceled.)&nbsp;</span>

            <button style="float:right" class="button" id="edit-button-<?php echo $i; ?>" ><?php _e( 'Edit', 'woocommerce' ); ?></button>
            <button style="float:right; display:none" class="button" id="cancel-button-<?php echo $i; ?>" ><?php _e( 'Cancel', 'woocommerce' ); ?></button>

            <form action="<?php echo get_permalink( woocommerce_get_page_id( 'myaccount' ) ) ?>" method="post" >

              <input type="submit" value="<?php _e( 'Save', 'woocommerce' ); ?>" class="button alt" id="save-button-<?php echo $i; ?>" style="float:right; display:none" >

              <span style="float:left">Credit card:&nbsp;</span>
              <input type="text" style="display:none" id="edit-cc-number-<?php echo $i; ?>" name="edit-cc-number-<?php echo $i; ?>" maxlength="16" />
              <span id="cc-number-<?php echo $i; ?>">
                <?php echo ( $method_to_update === $i && $response['response'] == 1 ) ? ( '<b>' . $ccnumber . '</b>' ) : $current_method->cc_number; ?>
              </span>
              <br />

              <span style="float:left">Expiration:&nbsp;</span>
              <input type="text" style="float:left; display:none" id="edit-cc-exp-<?php echo $i; ?>" name="edit-cc-exp-<?php echo $i; ?>" maxlength="5" value="MM/YY" />
              <span id="cc-exp-<?php echo $i; ?>">
                <?php echo ( $method_to_update === $i && $response['response'] == 1 ) ? ( '<b>' . $ccexp . '</b>' ) : substr( $current_method->cc_exp, 0, 2 ) . '/' . substr( $current_method->cc_exp, -2 ); ?>
              </span>

              <input type="hidden" name="update" value="<?php echo $i ?>">

            </form>

          </p>

        </header><?php

        $current_method = $this->get_payment_method( ++$i );

      }

    }
    /**
     * Add ability to view and edit payment details on the My Account page.(The WooCommerce 'force ssl' option also secures the My Account page, so we don't need to do that.)
     */
  

		function receipt_page( $order ) {
			echo '<p>' . __( 'Thank you for your order.', 'woocommerce' ) . '</p>';
		}

    /**
     * Include jQuery and our scripts
     */
    function add_exalt_scripts() {

      if ( ! $this->user_has_stored_data( wp_get_current_user()->ID ) ) return;

      wp_enqueue_script( 'jquery' );
      wp_enqueue_script( 'edit_billing_details', PLUGIN_DIR . 'js/edit_billing_details.js', array( 'jquery' ), 1.0 );

      if ( $this->cvv == 'yes' ) wp_enqueue_script( 'check_cvv', PLUGIN_DIR . 'js/check_cvv.js', array( 'jquery' ), 1.0 );

    }

   
    private function get_user_login() {
      global $user_login;
      get_currentuserinfo();
      return $user_login;
		}

		/**
		 * Get post data if set
		 */
		private function get_post( $name ) {
			if ( isset( $_POST[ $name ] ) ) {
				return $_POST[ $name ];
			}
			return null;
		}

	
		private function is_subscription( $order ) {
      return class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Order::order_contains_subscription( $order );
		}


	}

	/**
	 * Add the gateway to woocommerce
	 */
	function add_exalt_commerce_gateway( $methods ) {
		$methods[] = 'WC_Exalt';
		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'add_exalt_commerce_gateway' );

}
