<?php
/*
Plugin Name: iThemes Exchange External Products Add-on
Plugin URI: http://www.ironbounddesigns.com
Description: iThemes Exchange add-on that adds an external product type.
Version: 0.9
Author: Iron Bound Designs
Author URI: http://www.ironbounddesigns.com
License: GPL v2
Domain: ibd-exchange-addon-external-products
*/

/**
 * Register our External Products Type add-on
 */
function ite_epa_register_addon() {

	$options = array(
		'name'        => __( 'External Products', ITE_EPA::SLUG ),
		'description' => __( 'Sell products on other sites', ITE_EPA::SLUG ),
		'author'      => 'Iron Bound Designs',
		'author_url'  => 'http://www.ironbounddesigns.com',
		'icon'        => ITE_EPA::$url . 'lib/assets/images/icon-50x50.png',
		'wizard-icon' => ITE_EPA::$url . 'lib/assets/images/wizard-icon.png',
		'file'        => dirname( __FILE__ ) . '/init.php',
		'category'    => 'product-type',
		'basename'    => plugin_basename( __FILE__ ),
		'labels'      => array(
			'singular_name' => __( 'External', ITE_EPA::SLUG ),
		)
	);
	it_exchange_register_addon( 'external-product-type', $options );
}

add_action( 'it_exchange_register_addons', 'ite_epa_register_addon' );

/**
 * Loads the translation data for WordPress
 *
 * @uses  load_plugin_textdomain()
 * @since 1.0
 *
 * @return void
 */
function ite_epa_set_textdomain() {
	load_plugin_textdomain( ITE_EPA::SLUG, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}

add_action( 'plugins_loaded', 'ite_epa_set_textdomain' );

/**
 * Class ITE_EPA
 */
class ITE_EPA {
	/**
	 *
	 */
	const SLUG = 'ibd-exchange-addon-external-products';

	/**
	 * @var string
	 */
	static $dir;
	/**
	 * @var string
	 */
	static $url;

	/**
	 * Setup our add-on
	 */
	public function __construct() {
		self::$dir = plugin_dir_path( __FILE__ );
		self::$url = plugin_dir_url( __FILE__ );
		spl_autoload_register( array( __CLASS__, "autoload" ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
	}

	/**
	 * Register our custom scripts and styles
	 */
	public function scripts_and_styles() {
		wp_register_style( 'ite-epa-add-edit-product', self::$url . 'lib/assets/css/add-edit-product.css' );
	}

	/**
	 * Autoload requested classes that are ours
	 *
	 * @param $class_name string
	 */
	public static function autoload( $class_name ) {
		if ( substr( $class_name, 0, 7 ) != "ITE_EPA" ) {
			$path  = self::$dir . "lib/classes";
			$class = strtolower( $class_name );

			$name = str_replace( "_", "-", $class );
		} else {
			$path = self::$dir . "lib";

			$class = substr( $class_name, 7 );
			$class = strtolower( $class );

			$parts = explode( "_", $class );
			$name  = array_pop( $parts );

			$path .= implode( "/", $parts );
		}

		$path .= "/class.$name.php";

		if ( file_exists( $path ) ) {
			require( $path );

			return;
		}

		if ( file_exists( str_replace( "class.", "abstract.", $path ) ) ) {
			require( str_replace( "class.", "abstract.", $path ) );

			return;
		}

		if ( file_exists( str_replace( "class.", "interface.", $path ) ) ) {
			require( str_replace( "class.", "interface.", $path ) );

			return;
		}
	}
}

new ITE_EPA();