<?php

/**
 * File Description
 *
 * @author timothybjacobs
 * @since  9/5/14
 */
class ITE_EPA_Product_Feature extends IT_Exchange_Product_Feature_Abstract {
	/**
	 * Setup our product feature.
	 */
	function __construct() {
		$args = array(
			'slug'             => 'external-product',
			'description'      => 'Specify URL and output for the External Products Add-on',
			'product_types'    => 'external-product-type',
			'metabox_title'    => 'External Product Options',
			'metabox_context'  => 'it_exchange_normal',
			'metabox_priority' => 'low',
		);

		parent::IT_Exchange_Product_Feature_Abstract( $args );
	}

	/**
	 * This echos the feature metabox.
	 *
	 * @since 1.7.27
	 * @return void
	 */
	function print_metabox( $post ) {
		wp_enqueue_style( 'ite-epa-add-edit-product' );

		$data = it_exchange_get_product_feature( isset( $post->ID ) ? $post->ID : 0, $this->slug );
		?>
		<div class="sections-wrapper" id="it-exchange-product-external">
			<div class="external-section">
				<label for="external-product-url"><?php _e( "Purchase URL", ITE_EPA::SLUG ); ?></label>
				<input type="url" id="external-product-url" name="external_product[url]" value="<?php echo esc_attr( esc_url( $data['url'] ) ); ?>">

				<p><?php _e( "This is where buyers will be redirected to when they \"purchase\" this product.", ITE_EPA::SLUG ); ?></p>
			</div>

			<div class="external-section">
				<label for="external-product-button-text"><?php _e( "Purchase button text", ITE_EPA::SLUG ); ?></label>
				<input type="text" id="external-product-button-text" name="external_product[button_text]" value="<?php echo esc_attr( $data['button_text'] ); ?>">

				<p><?php _e( "This replaces the Buy Now button on the product page", ITE_EPA::SLUG ); ?></p>
			</div>
		</div>
	<?php
	}

	/**
	 * This saves the value
	 *
	 * @since 1.7.27
	 *
	 * @return void
	 */
	function save_feature_on_product_save() {// Abort if we can't determine a product type
		if ( ! $product_type = it_exchange_get_product_type() ) {
			return;
		}

		// Abort if we don't have a product ID
		$product_id = empty( $_POST['ID'] ) ? false : $_POST['ID'];
		if ( ! $product_id ) {
			return;
		}

		// Abort if this product type doesn't support this feature
		if ( ! it_exchange_product_type_supports_feature( $product_type, $this->slug ) ) {
			return;
		}

		if ( ! isset( $_POST['external_product'] ) ) {
			return;
		}

		$defaults   = array(
			'url'         => '',
			'button_text' => ''
		);
		$new_values = ITUtility::merge_defaults( $_POST['external_product'], $defaults );

		$new_values['url']         = esc_url_raw( $new_values['url'] );
		$new_values['button_text'] = sanitize_text_field( $new_values['button_text'] );

		it_exchange_update_product_feature( $product_id, $this->slug, $new_values );
	}

	/**
	 * This updates the feature for a product
	 *
	 * @since 1.7.27
	 *
	 * @param integer $product_id the product id
	 * @param mixed   $new_values the new values
	 * @param array   $options
	 *
	 * @return boolean
	 */
	function save_feature( $product_id, $new_values, $options = array() ) {
		$old_values = it_exchange_get_product_feature( $product_id, $this->slug );

		$new_values = ITUtility::merge_defaults( $new_values, $old_values );

		return update_post_meta( $product_id, '_it_exchange_' . $this->slug, $new_values );
	}

	/**
	 * Return the product's features
	 *
	 * @since 1.7.27
	 *
	 * @param mixed   $existing   the values passed in by the WP Filter API. Ignored here.
	 * @param integer $product_id the WordPress post ID
	 * @param array   $options
	 *
	 * @return array
	 */
	function get_feature( $existing, $product_id, $options = array() ) {
		$defaults = array(
			'url'         => '',
			'button_text' => ''
		);

		$values = get_post_meta( $product_id, '_it_exchange_' . $this->slug, true );

		$raw_meta = ITUtility::merge_defaults( $values, $defaults );

		if ( ! isset( $options['field'] ) ) // if we aren't looking for a particular field
		{
			return $raw_meta;
		}

		$field = $options['field'];

		if ( isset( $raw_meta[ $field ] ) ) { // if the field exists with that name just return it
			return $raw_meta[ $field ];
		} else if ( strpos( $field, "." ) !== false ) { // if the field name was passed using array dot notation
			$pieces  = explode( '.', $field );
			$context = $raw_meta;
			foreach ( $pieces as $piece ) {
				if ( ! is_array( $context ) || ! array_key_exists( $piece, $context ) ) {
					// error occurred
					return null;
				}
				$context = &$context[ $piece ];
			}

			return $context;
		} else {
			return null; // we didn't find the data specified
		}
	}

	/**
	 * Does the product have the feature?
	 *
	 * @since 1.7.27
	 *
	 * @param mixed   $result Not used by core
	 * @param integer $product_id
	 * @param array   $options
	 *
	 * @return boolean
	 */
	function product_has_feature( $result, $product_id, $options = array() ) {
		// Does this product type support this feature?
		if ( false === it_exchange_product_supports_feature( $product_id, $this->slug ) ) {
			return false;
		}

		return (boolean) it_exchange_get_product_feature( $product_id, $this->slug, array( 'field' => 'url' ) );
	}

	/**
	 * Does the product support this feature?
	 *
	 * This is different than if it has the feature, a product can
	 * support a feature but might not have the feature set.
	 *
	 * @since 1.7.27
	 *
	 * @param mixed   $result Not used by core
	 * @param integer $product_id
	 * @param array   $options
	 *
	 * @return boolean
	 */
	function product_supports_feature( $result, $product_id, $options = array() ) {
		$product_type = it_exchange_get_product_type( $product_id );
		if ( ! it_exchange_product_type_supports_feature( $product_type, $this->slug ) ) {
			return false;
		}

		return true;
	}

}