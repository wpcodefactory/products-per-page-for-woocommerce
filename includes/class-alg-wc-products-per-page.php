<?php
/**
 * Products per Page for WooCommerce - Main Class
 *
 * @version 2.6.0
 * @since   1.0.0
 *
 * @author WPFactory
 *
 * @package WPFactory\WC_Products_Per_Page
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Products_Per_Page' ) ) :

	/**
	 * Alg_WC_Products_Per_Page class.
	 *
	 * @version 2.6.0
	 * @since   1.0.0
	 */
	final class Alg_WC_Products_Per_Page {

		/**
		 * Plugin version.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version = ALG_WC_PRODUCTS_PER_PAGE_VERSION;

		/**
		 * Core.
		 *
		 * @version 2.2.0
		 * @since   2.2.0
		 *
		 * @var Alg_WC_Products_Per_Page_Core
		 */
		public $core;

		/**
		 * The single instance of the class.
		 *
		 * @version 2.6.0
		 * @since   1.0.0
		 *
		 * @var Alg_WC_Products_Per_Page
		 */
		protected static $instance = null;

		/**
		 * Main Alg_WC_Products_Per_Page Instance.
		 *
		 * Ensures only one instance of Alg_WC_Products_Per_Page is loaded or can be loaded.
		 *
		 * @version 2.6.0
		 * @since   1.0.0
		 *
		 * @static
		 *
		 * @return Alg_WC_Products_Per_Page
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Alg_WC_Products_Per_Page Constructor.
		 *
		 * @version 2.5.5
		 * @since   1.0.0
		 */
		public function __construct() {
			// Check for active WooCommerce plugin.
			if ( ! function_exists( 'WC' ) ) {
				return;
			}

			// Load libs.
			if ( is_admin() ) {
				require_once plugin_dir_path( ALG_WC_PRODUCTS_PER_PAGE_FILE ) . 'vendor/autoload.php';
			}

			// Declare compatibility with custom order tables for WooCommerce.
			add_action( 'before_woocommerce_init', array( $this, 'wc_declare_compatibility' ) );

			// Pro.
			if ( 'products-per-page-for-woocommerce-pro.php' === basename( ALG_WC_PRODUCTS_PER_PAGE_FILE ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'pro/class-alg-wc-products-per-page-pro.php';
			}

			// Include required files.
			$this->includes();

			// Admin.
			if ( is_admin() ) {
				$this->admin();
			}
		}

		/**
		 * WC declare compatibility.
		 *
		 * @version 2.2.0
		 * @since   2.2.0
		 *
		 * @see https://developer.woocommerce.com/docs/features/high-performance-order-storage/recipe-book/
		 */
		public function wc_declare_compatibility() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				$files = (
					defined( 'ALG_WC_PRODUCTS_PER_PAGE_FILE_FREE' ) ?
					array( ALG_WC_PRODUCTS_PER_PAGE_FILE, ALG_WC_PRODUCTS_PER_PAGE_FILE_FREE ) :
					array( ALG_WC_PRODUCTS_PER_PAGE_FILE )
				);

				foreach ( $files as $file ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
						'custom_order_tables',
						$file,
						true
					);
				}
			}
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @version 2.3.0
		 * @since   1.0.0
		 */
		public function includes() {
			$this->core = require_once plugin_dir_path( __FILE__ ) . 'class-alg-wc-products-per-page-core.php';
		}

		/**
		 * Admin.
		 *
		 * @version 2.5.0
		 * @since   1.2.0
		 */
		public function admin() {
			// Action links.
			add_filter(
				'plugin_action_links_' . plugin_basename( ALG_WC_PRODUCTS_PER_PAGE_FILE ),
				array( $this, 'action_links' )
			);

			// "Recommendations" page.
			add_action( 'init', array( $this, 'add_cross_selling_library' ) );

			// WC Settings tab as WPFactory submenu item.
			add_action( 'init', array( $this, 'move_wc_settings_tab_to_wpfactory_menu' ) );

			// Settings.
			add_filter(
				'woocommerce_get_settings_pages',
				array( $this, 'add_woocommerce_settings_tab' )
			);

			// Version update.
			if ( get_option( 'alg_products_per_page_version', '' ) !== $this->version ) {
				add_action( 'admin_init', array( $this, 'version_updated' ) );
			}
		}

		/**
		 * Show action links on the plugin screen.
		 *
		 * @version 2.3.0
		 * @since   1.0.0
		 *
		 * @param mixed $links Action links for the plugin.
		 *
		 * @return array
		 */
		public function action_links( $links ) {
			$custom_links = array();

			$custom_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_products_per_page' ) . '">' .
				__( 'Settings', 'products-per-page-for-woocommerce' ) .
			'</a>';

			if ( 'products-per-page-for-woocommerce.php' === basename( ALG_WC_PRODUCTS_PER_PAGE_FILE ) ) {
				$custom_links[] = '<a' .
				' target="_blank"' .
				' style="font-weight: bold; color: green;"' .
				' href="https://wpfactory.com/item/products-per-page-woocommerce/"' .
				'>' .
					__( 'Go Pro', 'products-per-page-for-woocommerce' ) .
				'</a>';
			}

			return array_merge( $custom_links, $links );
		}

		/**
		 * Add cross selling library.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 */
		public function add_cross_selling_library() {
			if ( ! class_exists( '\WPFactory\WPFactory_Cross_Selling\WPFactory_Cross_Selling' ) ) {
				return;
			}

			$cross_selling = new \WPFactory\WPFactory_Cross_Selling\WPFactory_Cross_Selling();
			$cross_selling->setup( array( 'plugin_file_path' => ALG_WC_PRODUCTS_PER_PAGE_FILE ) );
			$cross_selling->init();
		}

		/**
		 * Move WC settings tab to WPFactory menu.
		 *
		 * @version 2.5.1
		 * @since   2.3.0
		 */
		public function move_wc_settings_tab_to_wpfactory_menu() {
			if ( ! class_exists( '\WPFactory\WPFactory_Admin_Menu\WPFactory_Admin_Menu' ) ) {
				return;
			}

			$wpfactory_admin_menu = \WPFactory\WPFactory_Admin_Menu\WPFactory_Admin_Menu::get_instance();

			if ( ! method_exists( $wpfactory_admin_menu, 'move_wc_settings_tab_to_wpfactory_menu' ) ) {
				return;
			}

			$wpfactory_admin_menu->move_wc_settings_tab_to_wpfactory_menu(
				array(
					'wc_settings_tab_id' => 'alg_wc_products_per_page',
					'menu_title'         => __( 'Products per Page', 'products-per-page-for-woocommerce' ),
					'page_title'         => __( 'Number of Products per Page - Pagination Manager for WooCommerce', 'products-per-page-for-woocommerce' ),
					'plugin_icon'        => array(
						'get_url_method'    => 'wporg_plugins_api',
						'wporg_plugin_slug' => 'products-per-page-for-woocommerce',
					),
				)
			);
		}

		/**
		 * Add Products per Page settings tab to WooCommerce settings.
		 *
		 * @version 2.3.0
		 * @since   1.0.0
		 *
		 * @param array $settings WooCommerce settings array.
		 *
		 * @return array Modified WooCommerce settings array.
		 */
		public function add_woocommerce_settings_tab( $settings ) {
			$settings[] = require_once plugin_dir_path( __FILE__ ) . 'settings/class-alg-wc-settings-products-per-page.php';
			return $settings;
		}

		/**
		 * Version updated.
		 *
		 * @version 1.2.0
		 * @since   1.2.0
		 */
		public function version_updated() {
			update_option( 'alg_products_per_page_version', $this->version );
		}

		/**
		 * Get the plugin url.
		 *
		 * @version 1.6.0
		 * @since   1.0.0
		 *
		 * @return string
		 */
		public function plugin_url() {
			return untrailingslashit( plugin_dir_url( ALG_WC_PRODUCTS_PER_PAGE_FILE ) );
		}

		/**
		 * Get the plugin path.
		 *
		 * @version 1.6.0
		 * @since   1.0.0
		 *
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( ALG_WC_PRODUCTS_PER_PAGE_FILE ) );
		}
	}

endif;
