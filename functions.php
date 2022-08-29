/*
* "Order more for... for free shipping" message
*/

add_action( 'woocommerce_before_cart', 'truejb_free_shipping_notice' );
 
function truejb_free_shipping_notice() {
 
	$min_amount = 1000; // enter the minimum amount for free shipping here
 
	$current = WC()->cart->get_subtotal();
 
	if ( $current < $min_amount ) { // if there is less than necessary in the basket
 
		wc_print_notice(
			sprintf(
				'<a href="%s" class="button wc-forward">%s</a> %s',
				get_permalink( wc_get_page_id( 'shop' ) ),
				'See other products',
				'Order more for ' . wc_price( $min_amount - $current ) . ' for free shipping!'
			),
			'notice'
		);
 
	}
 
}




/*
* show cost 0 or "Free" next to free shipping method
*/

add_filter( 'woocommerce_cart_shipping_method_full_label', 'truemisha_free_shipping_label', 10, 2 );
 
function truejb_free_shipping_label( $label, $method ) {
 
	if ( ! ( $method->cost > 0 ) ) { // if value is less than zero
		$label .= ': <strong>Free</strong>';
	}
 
	return $label;
 
}




/*
* Hide shipping methods when free shipping is available
*/

add_filter( 'woocommerce_package_rates', 'truejb_remove_all_shippings', 10, 2 );
 
function truejb_remove_all_shippings( $rates, $package ) {
 
	$new_rates = array();
 
	// in the cycle we check if among the deliveries there is a free
	foreach ( $rates as $rate_id => $rate ) {
		if ( 'free_shipping' === $rate->method_id ) {
			$new_rates[ $rate_id ] = $rate;
			break; // free shipping found, exit the cycle
		}
	}
 
	return ! empty( $new_rates ) ? $new_rates : $rates;
 
}




/*
* Available shipping methods depending on order amount
*/

add_filter( 'woocommerce_package_rates', 'truejb_remove_shipping_on_price', 25, 2 );
 
function truejb_remove_shipping_on_price( $rates, $package ) {
 
	// if the sum of all items in the cart is less than 1000, disable the shipping method
	if ( WC()->cart->subtotal < 1000 ) {
	    unset( $rates[ 'flat_rate:2' ] );
	}
 
	return $rates;
 
}




/*
* Calculation of shipping costs by weight
*/

add_filter( 'woocommerce_package_rates', 'truejb_shipping_by_weight', 25, 2 );
 
function truejb_shipping_by_weight( $rates, $package ) {
 
	// weight of items in the cart
	$cart_weight = WC()->cart->cart_contents_weight;
 
	// shipping method ID
	$method_id = 'flat_rate:84';
 
	if ( isset( $rates[ $method_id ] ) ) {
		// there should be your own formula, mine is:
		// shipping cost = 5 * weight of items in cart
		$rates[ $method_id ]->cost = 5 * round ( $cart_weight );
	}
 
	return $rates;
 
}




/*
* Display of payment methods depending on the buyer's country or city
*/

add_filter( 'woocommerce_available_payment_gateways', 'truejb_gateway_by_country' );
 
function truejb_gateway_by_country( $gateways ) {
 
	if ( isset( $gateways[ 'paypal' ] ) && 'MC' === WC()->customer->get_billing_country() ) {
		unset( $gateways[ 'paypal' ] );
	}
	return $gateways;
 
}




/*
* Available payment methods depending on the selected shipping method
*/

add_filter( 'woocommerce_available_payment_gateways', 'truejb_payments_on_shipping' );
 
function truejb_payments_on_shipping( $available_gateways ) {
 
	if( is_admin() ) {
		return $available_gateways;
	}
 
	if( is_wc_endpoint_url( 'order-pay' ) ) {
		return $available_gateways;
	}
 
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
 
	//echo '<pre>';print_r( $chosen_methods );
 
	if ( isset( $available_gateways[ 'cod' ] ) && 'free_shipping:1' == $chosen_methods[0] ) {
		unset( $available_gateways[ 'cod' ] ); // disable payment on delivery
	}
 
	return $available_gateways;
 
}




/*
* Disable payment methods for certain product categories
*/

add_filter( 'woocommerce_available_payment_gateways', 'truejb_gateway_by_category', 25 );
 
function truejb_gateway_by_category( $available_gateways ) {
 
	// do nothing in the admin panel
	if ( is_admin() ) {
		return $available_gateways;
	}
 
	$gateway_slug = 'paypal'; // payment method label
	$is_available = true; // on or off? default - enabled
	$cat_ids = array( 5, 10 ); // IDs of product categories for which we disable
 
	// cycle for cart contents
	foreach ( WC()->cart->get_cart_contents() as $key => $value ) {
		// get all categories of this product from the cart
		if( $product_cats = get_the_terms( $value[ 'product_id' ], 'product_cat' ) ) {
			foreach ( $product_cats as $product_cat ) {        
				if ( in_array( $product_cat->term_id, $cat_ids ) ) {
					$is_available = false; // product from the specified category, disable the payment method
					break 2;
				}
			}
		}
	}
 
	// disabling payment method
	if ( false === $is_available ){
		unset( $available_gateways[ $gateway_slug ] );
	}
 
	return $available_gateways; // return result
 
}





/*
* Enable or disable payment methods depending on the number of items in the cart
*/

add_filter( 'woocommerce_available_payment_gateways', 'truejb_gateway_by_cart_count', 25 );
 
function truejb_gateway_by_cart_count( $available_gateways ) {
 
	// do nothing in the admin panel
	if ( is_admin() ) {
		return $available_gateways;
	}
 
	// if there are less than 5 products
	if( WC()->cart->get_cart_contents_count() < 5 ) {
		// check that the payment method is not already disabled
		if( isset( $available_gateways[ 'cod' ] ) ) {
			unset( $available_gateways[ 'cod' ] ); // disable
		}
	}
 
	return $available_gateways; // return result
 
}




/*
* redirect user to specific url after authorization
*/

add_filter( 'woocommerce_login_redirect', 'truejb_login_redirect', 25, 2 );
 
function truejb_login_redirect( $redirect, $user ) {
 
	$redirect = site_url();
	return $redirect;
 
}




/*
* Add text or HTML before login and registration forms
*/

//  Adding text to the login form
add_action( 'woocommerce_login_form_start', 'truejb_add_login_text', 25 );
 
function truejb_add_login_text() {
 
	if ( is_checkout() ) {
		return;
	}
 
	echo '<p>Welcome to your personal account</p>';
 
}

//  Adding text or HTML to the registration forms
add_action( 'woocommerce_register_form_start', 'truejb_add_register_text', 25 );
 
function truejb_add_register_text() {
 
	echo '<p>Good news for you - when registering, we will not force you to create a password from small and large letters, two special characters and one emoji.</p>';
 
}




/*
* Adding consent to the Privacy Policy
*/

//  To the checkout page
// Adding a checkbox
add_action( 'woocommerce_review_order_before_submit', 'truejb_privacy_checkbox', 25 );
 
function truejb_privacy_checkbox() {
 
	woocommerce_form_field( 'privacy_policy_checkbox', array(
		'type'          => 'checkbox',
		'class'         => array( 'form-row' ),
		'label_class'   => array( 'woocommerce-form__label-for-checkbox' ),
		'input_class'   => array( 'woocommerce-form__input-checkbox' ),
		'required'      => true,
		'label'         => 'Принимаю <a href="' . get_privacy_policy_url() . '">Privacy Policy</a>',
	));
 
}
 
// Validation
add_action( 'woocommerce_checkout_process', 'truejb_privacy_checkbox_error', 25 );
 
function truejb_privacy_checkbox_error() {
 
	if ( empty( $_POST[ 'privacy_policy_checkbox' ] ) ) {
		wc_add_notice( 'Your need to accept the privacy policy.', 'error' );
	}
 
}


//  To the registration form
// Adding a checkbox
add_action( 'woocommerce_register_form', 'truejb_registration_privacy_checkbox', 25 );
 
function truejb_registration_privacy_checkbox() {
 
	woocommerce_form_field(
		'privacy_policy_reg',
		array(
			'type'          => 'checkbox',
			'class'         => array( 'form-row' ),
			'label_class'   => array( 'woocommerce-form__label-for-checkbox' ),
			'input_class'   => array( 'woocommerce-form__input-checkbox' ),
			'required'      => true,
			'label'         => 'I accept <a href="' . get_privacy_policy_url() . '">Privacy Policy</a>',
		)
	);
 
}
 
// Validation
add_filter( 'woocommerce_registration_errors', 'truejb_registration_privacy_checkbox_error', 25 );
 
function truejb_registration_privacy_checkbox_error( $errors ) {
 
	if( is_checkout() ) {
		return $errors;
	}
 
	if ( empty( $_POST[ 'privacy_policy_reg' ] ) ) {
		$errors->add( 'privacy_policy_reg_error', 'Your need to accept the privacy policy.' );
	}
 
	return $errors;
 
}





/*
* rename the link in the personal account menu
*/

add_filter( 'woocommerce_account_menu_items', 'truejb_rename_menu', 25 );
 
function truejb_rename_menu( $menu_links ){
 
	$menu_links[ 'dashboard' ] = 'Home'; 
	$menu_links[ 'downloads' ] = 'My files'; 
 
	return $menu_links;
 
}




/*
* Adding a new tab with a separate page to the personal account menu
*/

//  Adding a Link and an Icon
add_filter ( 'woocommerce_account_menu_items', 'truejb_log_history_link', 25 );
function truejb_log_history_link( $menu_links ){
 
	$menu_links[ 'log-history' ] = 'Visit history';
  $menu_links = array_slice( $menu_links, 0, 5, true ) + array( 'log-history' => 'Visit history' ) + array_slice( $menu_links, 5, NULL, true );
	return $menu_links;
 
}

//  Page creation
add_action( 'init', 'truejb_add_endpoint', 25 );
function truejb_add_endpoint() {
 
	add_rewrite_endpoint( 'log-history', EP_PAGES );
 
}
 
add_action( 'woocommerce_account_log-history_endpoint', 'truejb_content', 25 );
function truejb_content() {
 
	echo 'The last time you logged in yesterday was through a browser Safari.';
 
}





/*
* display all products purchased by the user
*/

add_shortcode( 'customer_products', 'truejb_products_current_user' );
 
function truejb_products_current_user() {
	// do nothing if not authorized
	if ( ! is_user_logged_in() ) {
		return;
	}
 
	// get all paid user orders
	$customer_orders = get_posts( array(
		'posts_per_page' => -1,
		'meta_key'    => '_customer_user',
		'meta_value'  => get_current_user_id(),
		'post_type'   => wc_get_order_types(),
		'post_status' => array_keys( wc_get_is_paid_statuses() ),
	) );
 
	// if orders are not found
	if ( ! $customer_orders ) {
		return;
	}
 
	// create a separate variable for product IDs and write to it
	$ids = array();
	foreach ( $customer_orders as $customer_order ) {
		$order = wc_get_order( $customer_order->ID );
		$items = $order->get_items();
		foreach ( $items as $item ) {
			$ids[] = $item->get_product_id();
		}
	}
 
	// return shortcode
	return do_shortcode( '[products ids="' . join( ",", array_unique( $ids ) ) . '"]' );
 
}




/*
* merge tabs in personal account
*/

// we need to hide the Addresses tab itself first of all
add_filter( 'woocommerce_account_menu_items', 'truejb_remove_addresses', 25 );
 
function truejb_remove_addresses( $menu_links ) {
 
	unset( $menu_links[ 'edit-address' ] );
	return $menu_links;
 
}
 
// using a hook add addresses to the profile edit tab
add_action( 'woocommerce_account_edit-account_endpoint', 'woocommerce_account_edit_address' );




/*
* Redirect when authorizing to the previous URL
*/

add_action( 'woocommerce_login_form_end', 'true_redirect_previous_page' );
 
function true_redirect_previous_page() {
 
	if ( ! wc_get_raw_referer() ) {
		return;
	}
 
	$redirect = wp_validate_redirect(
		wc_get_raw_referer(),
		wc_get_page_permalink( 'myaccount' )
	);
 
	echo '<input type="hidden" name="redirect" value="' . $redirect . '" />';
 
}
