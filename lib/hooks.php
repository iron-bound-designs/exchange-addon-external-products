<?php
/**
 * File Description
 *
 * @author timothybjacobs
 * @since  9/8/14
 */

/**
 * Override the purchase count with ours.
 *
 * @param $column string
 *
 * @return void
 */
function ite_epa_override_purchase_count_in_admin( $column ) {
	if ( $column == 'it_exchange_product_purchases' ) {
		if ( ! has_filter( 'it_exchange_get_transactions_for_product', 'ite_epa_override_transactions_for_product_in_admin', 10, 2 ) ) {
			add_filter( 'it_exchange_get_transactions_for_product', 'ite_epa_override_transactions_for_product_in_admin', 10, 2 );
		}
	}
}

add_action( 'manage_it_exchange_prod_posts_custom_column', 'ite_epa_override_purchase_count_in_admin', 9 );

/**
 * Override the transactions for the product in the admin
 *
 * @param $transactions array
 * @param $product      IT_Exchange_Product
 *
 * @return array
 */
function ite_epa_override_transactions_for_product_in_admin( $transactions, $product ) {

	if ( it_exchange_product_has_feature( $product->ID, 'external-product' ) ) {
		$purchases    = ite_epa_get_product_purchase_count( $product->ID );
		$transactions = array();

		for ( $i = 0; $i < $purchases; $i ++ ) {
			$transactions[] = array( 'stub' );
		}
	}

	remove_filter( 'it_exchange_get_transactions_for_product', 'ite_epa_override_transactions_for_product_in_admin', 10, 2 );

	return $transactions;
}

/**
 * Redirect and log an external product purchase request.
 */
function ite_epa_redirect_and_log_external_product_purchase_request() {
	if ( is_admin() ) {
		return;
	}

	if ( ! isset( $_GET['ite_epa_product'] ) ) {
		return;
	}

	$product_id = absint( $_GET['ite_epa_product'] );

	if ( ! it_exchange_product_has_feature( $product_id, 'external-product' ) ) {
		return;
	}

	$target_url = it_exchange_get_product_feature( $product_id, 'external-product', array( 'field' => 'url' ) );

	if ( empty( $target_url ) ) {
		return;
	}

	ite_epa_add_product_purchase( $product_id );

	do_action( 'it_exchange_epa_before_redirect', $product_id, $target_url );

	wp_redirect( $target_url );
	exit;
}

add_action( 'init', 'ite_epa_redirect_and_log_external_product_purchase_request' );

/**
 * Override the Buy Now button.
 *
 * @param $button  string
 * @param $options array
 *
 * @return string
 */
function ite_epa_override_payment_button( $button, $options ) {

	$product = $GLOBALS['it_exchange']['product'];

	if ( empty( $product ) ) {
		return $button;
	}

	if ( ! it_exchange_product_has_feature( $product->ID, 'external-product' ) ) {
		return $button;
	}

	$button_text = it_exchange_get_product_feature( $product->ID, 'external-product', array( 'field' => 'button_text' ) );

	if ( empty( $button_text ) ) {
		$button_text = $options['label'];
	}

	ob_start();
	?>
	<form action="<?php echo esc_attr( esc_url( ite_epa_get_product_url( $product->ID ) ) ); ?>" method="post" class="it-exchange-sw-purchase-options buy-now-button">
		<input type="submit" value="<?php echo esc_attr( $button_text ); ?>" class="buy-now-button">
	</form>

	<?php
	return ob_get_clean();
}

add_filter( 'it_exchange_theme_api_product_buy_now', 'ite_epa_override_payment_button', 10, 2 );

/**
 * Disable the multi-item cart when we are viewing an external product.
 *
 * @param $allowed bool
 *
 * @return bool
 */
function ite_epa_disable_multi_item_cart_on_external_product( $allowed ) {

	if ( ! isset( $GLOBALS['it_exchange']['product'] ) ) {
		return $allowed;
	}

	$product = $GLOBALS['it_exchange']['product'];

	if ( ! it_exchange_product_has_feature( $product->ID, 'external-product' ) ) {
		return $allowed;
	}

	return false;
}

add_filter( 'it_exchange_multi_item_cart_allowed', 'ite_epa_disable_multi_item_cart_on_external_product', 15 );

/**
 * Display a users external product purchases.
 *
 * @return string
 */
function ite_epa_display_users_external_product_purchases() {
	if ( empty( $_REQUEST['user_id'] ) ) {
		$user_id = get_current_user_id();
	} else {
		$user_id = $_REQUEST['user_id'];
	}

	$headings = array(
		__( 'Products', ITE_EPA::SLUG ),
		__( 'Date', ITE_EPA::SLUG ),
	);

	$list = array();

	foreach ( ite_epa_get_customer_purchases( $user_id ) as $purchase ) {
		if ( empty( $purchase ) || empty( $purchase['product'] ) || empty( $purchase['time'] ) ) {
			continue;
		}

		$product = $purchase['product'];
		$time    = $purchase['time'];

		$product = it_exchange_get_product( $product )->post_title;
		$time    = new DateTime( "@" . $time );
		$time    = $time->format( get_option( 'date_format' ) );

		$list[] = array( $product, $time );
	}

	?>

	<h3>External Products Purchased</h3>
	<div class="user-edit-block products-user-edit-block">

		<div class="heading-row block-row">
			<?php $column = 0; ?>
			<?php foreach ( (array) $headings as $heading ) : ?>
				<?php $column ++ ?>
				<div class="heading-column block-column block-column-<?php echo $column; ?>">
					<p class="heading"><?php echo $heading; ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php foreach ( (array) $list as $item_details ) : ?>
			<?php $column = 0; ?>
			<div class="item-row block-row">
				<?php foreach ( (array) $item_details as $detail ) : ?>
					<?php $column ++ ?>
					<?php if ( is_array( $detail ) ) : ?>
						<div class="item-column block-column block-column-<?php echo $column; ?>">
							<?php foreach ( $detail as $action => $label ) : ?>
								<a class="button" href="<?php esc_attr_e( $action ); ?>"><?php esc_attr_e( $label ); ?></a>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<div class="item-column block-column block-column-<?php echo $column; ?>">
							<p class="item"><?php echo $detail; ?></p>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php
}

add_action( 'it-exchange-after-admin-user-products', 'ite_epa_display_users_external_product_purchases' );