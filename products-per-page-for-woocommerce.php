<?php
/**
 * Plugin Name: Number of Products per Page - Pagination Manager for WooCommerce
 * Plugin URI: https://wpfactory.com/item/products-per-page-woocommerce/
 * Description: Products per page selector for WooCommerce.
 * Version: 2.6.0
 * Author: WPFactory
 * Author URI: https://wpfactory.com
 * Requires at least: 4.4
 * Requires PHP: 7.4
 * Text Domain: products-per-page-for-woocommerce
 * Domain Path: /langs
 * WC tested up to: 11.3
 * Requires Plugins: woocommerce
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WPFactory\WC_Products_Per_Page
 */

defined( 'ABSPATH' ) || exit;

if ( 'products-per-page-for-woocommerce.php' === basename( __FILE__ ) ) {
	if ( ! function_exists( 'alg_wc_products_per_page_is_pro_activated' ) ) {
		/**
		 * Check if Pro plugin version is activated.
		 *
		 * @version 2.6.0
		 * @since   1.6.0
		 */
		function alg_wc_products_per_page_is_pro_activated() {
			$plugin = 'products-per-page-for-woocommerce-pro/products-per-page-for-woocommerce-pro.php';
			return (
				in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) ||
				(
					is_multisite() &&
					array_key_exists( $plugin, (array) get_site_option( 'active_sitewide_plugins', array() ) )
				)
			);
		}
	}

	if ( alg_wc_products_per_page_is_pro_activated() ) {
		defined( 'ALG_WC_PRODUCTS_PER_PAGE_FILE_FREE' ) || define( 'ALG_WC_PRODUCTS_PER_PAGE_FILE_FREE', __FILE__ );
		return;
	}
}

/**
 * Plugin version.
 */
defined( 'ALG_WC_PRODUCTS_PER_PAGE_VERSION' ) || define( 'ALG_WC_PRODUCTS_PER_PAGE_VERSION', '2.6.0' );

/**
 * Plugin file.
 */
defined( 'ALG_WC_PRODUCTS_PER_PAGE_FILE' ) || define( 'ALG_WC_PRODUCTS_PER_PAGE_FILE', __FILE__ );

/**
 * Include the main class.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-alg-wc-products-per-page.php';

if ( ! function_exists( 'alg_wc_products_per_page' ) ) {
	/**
	 * Returns the main instance of Alg_WC_Products_Per_Page to prevent the need to use globals.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function alg_wc_products_per_page() {
		return Alg_WC_Products_Per_Page::instance();
	}
}

/**
 * Initialize the plugin.
 */
add_action( 'plugins_loaded', 'alg_wc_products_per_page' );
