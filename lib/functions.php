<?php
/**
 * File Description
 *
 * @author timothybjacobs
 * @since  9/8/14
 */

/**
 * Get the encoded URL for an external product.
 *
 * @param $product_id int
 *
 * @return string|bool
 */
function ite_epa_get_product_url( $product_id ) {

	if ( ! it_exchange_product_has_feature( $product_id, 'external-product' ) ) {
		return false;
	}

	$base = site_url();

	$url = add_query_arg( 'ite_epa_product', $product_id, $base );

	/**
	 * The URL that tracks and redirects a purchase.
	 *
	 * @param $url        string
	 * @param $product_id int
	 */

	return apply_filters( 'it_exchange_epa_get_product_url', $url, $product_id );
}

/**
 * Retrieve the purchase count of a product.
 *
 * @param $product_id int
 *
 * @return bool|string
 */
function ite_epa_get_product_purchase_count( $product_id ) {

	if ( ! it_exchange_product_has_feature( $product_id, 'external-product' ) ) {
		return false;
	}

	$purchases = (int) get_post_meta( $product_id, '_it_exchange_external_product_purchases_count' );

	/**
	 * Retrieve the purchase count of a product.
	 *
	 * @param $purchases  int
	 * @param $product_id int
	 */

	return apply_filters( 'it_exchange_epa_product_purchase_count', $purchases, $product_id );
}

/**
 * Get an array of all of the purchases.
 *
 * Where each key is an array
 *      'customer'   => (int) customer ID
 *      'time'       => (int) epoch time of purchase
 *      'product_id' => (int) ID of the product "purchased"
 *
 * @param $product_id  int
 *
 * @return bool|array[]
 */
function ite_epa_get_product_purchases( $product_id ) {

	if ( ! it_exchange_product_has_feature( $product_id, 'external-product' ) ) {
		return false;
	}

	$purchases = (array) get_post_meta( $product_id, '_it_exchange_external_product_purchases', true );

	/**
	 * Allows modification of the purchases of a certain product.
	 *
	 * @param $purchases  array
	 * @param $product_id int
	 */

	return apply_filters( 'it_exchange_epa_get_product_purchases', $purchases, $product_id );
}

/**
 * Retrieve all of the purchases of a customer.
 *
 * @param $customer_id int
 *
 * @return array
 */
function ite_epa_get_customer_purchases( $customer_id ) {

	$purchases = (array) get_user_meta( $customer_id, '_it_exchange_external_product_purchases', true );

	/**
	 * Allows modification of the customer's purchases.
	 *
	 * @param $purchases   array
	 * @param $customer_id int
	 */

	return apply_filters( 'it_exchange_epa_customer_purchases', $purchases, $customer_id );
}

/**
 * Record a product purchase.
 *
 * @param int      $product_id
 * @param int|null $customer
 * @param int|null $time
 */
function ite_epa_add_product_purchase( $product_id, $customer = null, $time = null ) {
	if ( null === $customer ) {
		$customer = it_exchange_get_current_customer_id();
	}

	if ( null === $time ) {
		$time = time();
	}

	$count = ite_epa_get_product_purchase_count( $product_id );

	$count += 1;

	update_post_meta( $product_id, '_it_exchange_external_product_purchases_count', $count );

	$purchase = array(
		'customer' => $customer,
		'time'     => $time,
		'product'  => $product_id
	);

	/**
	 * Allows modification of purchase data saved.
	 *
	 * @param $purchase   array Array of purchase data.
	 * @param $product_id int Product ID of the product being purchased.
	 */
	$purchase = apply_filters( 'it_exchange_epa_product_purchase_data', $purchase, $product_id );

	$purchases   = ite_epa_get_product_purchases( $product_id );
	$purchases[] = $purchase;
	update_post_meta( $product_id, '_it_exchange_external_product_purchases', $purchases );

	// A user doesn't have to be logged in to "purchase" an external product.
	if ( ! empty( $customer ) ) {
		$customer_purchases = ite_epa_get_customer_purchases( $customer );

		$customer_purchases[] = $purchase;

		update_user_meta( $customer, 'it_exchange_epa_customer_purchases', $customer_purchases );

	}

	/**
	 * Record when an external product is purchased.
	 *
	 * @param $product_id int Product ID of the product being purchased
	 * @param $customer   int Customer ID of the current customer ( WP User ID )
	 * @param $time       int Epoch time the purchase occurred at
	 */
	do_action( 'it_exchange_epa_add_product_purchase', $product_id, $customer, $time );
}