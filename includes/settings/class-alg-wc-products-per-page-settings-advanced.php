<?php
/**
 * Products per Page for WooCommerce - Advanced Section Settings
 *
 * @version 2.5.6
 * @since   2.0.0
 *
 * @author  Algoritmika Ltd.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Products_Per_Page_Settings_Advanced' ) ) :

class Alg_WC_Products_Per_Page_Settings_Advanced extends Alg_WC_Products_Per_Page_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function __construct() {
		$this->id   = 'advanced';
		$this->desc = __( 'Advanced', 'products-per-page-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.6
	 * @since   2.0.0
	 *
	 * @todo    (desc) `alg_wc_products_per_page_scopes`: better desc?
	 * @todo    (desc) `alg_wc_products_per_page_wc_shortcode`: better desc?
	 * @todo    (desc) `alg_wc_products_per_page_form_method`: better desc, e.g., "Useful if user wants to bookmark the result"?
	 */
	function get_settings() {
		return array(
			array(
				'title'             => __( 'Advanced Options', 'products-per-page-for-woocommerce' ),
				'type'              => 'title',
				'id'                => 'alg_wc_products_per_page_advanced_options',
			),
			array(
				'title'             => __( 'Form method', 'products-per-page-for-woocommerce' ),
				'desc'              => sprintf(
					/* Translators: %1$s: Method name, %2$s: Parameter name, %3$s: URL example. */
					__( '%1$s method will append %2$s parameter to the URL, e.g.: %3$s', 'products-per-page-for-woocommerce' ),
					'<code>GET</code>',
					'<code>alg_wc_products_per_page</code>',
					'<code>https://example.com/?alg_wc_products_per_page=25</code>'
				),
				'id'                => 'alg_wc_products_per_page_form_method',
				'default'           => 'POST',
				'type'              => 'select',
				'class'             => 'chosen_select',
				'options'           => array(
					'POST' => 'POST',
					'GET'  => 'GET',
				),
			),
			array(
				'title'             => __( 'Apply in WooCommerce shortcodes', 'products-per-page-for-woocommerce' ),
				'desc'              => __( 'Enable', 'products-per-page-for-woocommerce' ),
				'desc_tip'          => sprintf(
					/* Translators: %s: Shortcode name. */
					__( 'Apply selector in WooCommerce %s shortcode.', 'products-per-page-for-woocommerce' ),
					'<code>[products]</code>'
				),
				'id'                => 'alg_wc_products_per_page_wc_shortcode',
				'default'           => 'yes',
				'type'              => 'checkbox',
			),
			array(
				'title'             => __( 'Scopes', 'products-per-page-for-woocommerce' ),
				'desc'              => __( 'Require', 'products-per-page-for-woocommerce' ),
				'desc_tip'          => __( 'Apply selector on selected pages only. Ignored if empty.', 'products-per-page-for-woocommerce' ),
				'id'                => 'alg_wc_products_per_page_scopes[require]',
				'default'           => array(),
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'options'           => Alg_WC_Products_Per_Page_Core::get_scope_functions(),
			),
			array(
				'desc'              => __( 'Exclude', 'products-per-page-for-woocommerce' ),
				'desc_tip'          => __( 'Do not apply selector on selected pages.', 'products-per-page-for-woocommerce' ),
				'id'                => 'alg_wc_products_per_page_scopes[exclude]',
				'default'           => array(),
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'options'           => Alg_WC_Products_Per_Page_Core::get_scope_functions(),
			),
			array(
				'title'             => __( 'Save in cookie', 'products-per-page-for-woocommerce' ),
				'desc'              => __( 'Enable', 'products-per-page-for-woocommerce' ),
				'desc_tip'          => sprintf(
					/* Translators: %s: Cookie name. */
					__( '%s cookie is used to save user\'s "products per page" selection.', 'products-per-page-for-woocommerce' ),
					'<code>alg_wc_products_per_page</code>'
				),
				'id'                => 'alg_wc_products_per_page_cookie_enabled',
				'default'           => 'yes',
				'type'              => 'checkbox',
			),
			array(
				'desc'              => __( 'Cookie expiration time', 'products-per-page-for-woocommerce' ),
				'desc_tip'          => __( 'In seconds.', 'products-per-page-for-woocommerce' ),
				'id'                => 'alg_wc_products_per_page_cookie_sec',
				'default'           => 1209600,
				'type'              => 'number',
				'custom_attributes' => array( 'min' => 60 ),
			),
			array(
				'title'             => __( 'Save in session', 'products-per-page-for-woocommerce' ),
				'desc'              => __( 'Enable', 'products-per-page-for-woocommerce' ),
				'desc_tip'          => __( 'Store selected "products per page" value in user session.', 'products-per-page-for-woocommerce' ),
				'id'                => 'alg_wc_products_per_page_session_enabled',
				'default'           => 'yes',
				'type'              => 'checkbox',
				'checkboxgroup'     => 'start',
			),
			array(
				'desc'              => __( 'Force session start', 'products-per-page-for-woocommerce' ),
				'desc_tip'          => __( 'Force session start for the non-logged users.', 'products-per-page-for-woocommerce' ),
				'id'                => 'alg_wc_products_per_page_session_force_start',
				'default'           => 'yes',
				'type'              => 'checkbox',
				'checkboxgroup'     => 'end',
			),
			array(
				'type'              => 'sectionend',
				'id'                => 'alg_wc_products_per_page_advanced_options',
			),
		);
	}

}

endif;

return new Alg_WC_Products_Per_Page_Settings_Advanced();
