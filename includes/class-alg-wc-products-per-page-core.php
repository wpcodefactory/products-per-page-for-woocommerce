<?php
/**
 * Products per Page for WooCommerce - Core Class
 *
 * @version 2.5.0
 * @since   1.0.0
 *
 * @author  Algoritmika Ltd.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Products_Per_Page_Core' ) ) :

class Alg_WC_Products_Per_Page_Core {

	/**
	 * id_counter.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 */
	public $id_counter;

	/**
	 * shortcode_loop_props.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 */
	public $shortcode_loop_props;

	/**
	 * Constructor.
	 *
	 * @version 2.3.1
	 * @since   1.0.0
	 *
	 * @todo    (dev) remove `get_option( 'alg_products_per_page_position_priority', 40 )`
	 * @todo    (feature) option to redirect to last page num (i.e., instead of `get_pagenum_link()`)?
	 * @todo    (dev) use `shortcode_atts_products` instead of `woocommerce_shortcode_products_query`?
	 */
	function __construct() {
		if ( 'yes' === get_option( 'alg_wc_products_per_page_enabled', 'yes' ) ) {

			// Session
			if ( 'yes' === get_option( 'alg_wc_products_per_page_session_enabled', 'yes' ) ) {
				add_action( 'init', array( $this, 'set_session' ) );
			}

			// Cookie
			if ( 'yes' === get_option( 'alg_wc_products_per_page_cookie_enabled', 'yes' ) ) {
				add_action( 'init', array( $this, 'set_cookie' ) );
			}

			// Set products per page
			add_filter( 'loop_shop_per_page',                                     array( $this, 'set_products_per_page' ),           PHP_INT_MAX );
			add_filter( 'jet-woo-builder/shortcodes/jet-woo-products/query-args', array( $this, 'set_products_per_page_query_arg' ), PHP_INT_MAX );
			if ( 'yes' === get_option( 'alg_wc_products_per_page_wc_shortcode', 'yes' ) ) {
				add_filter( 'woocommerce_shortcode_products_query',               array( $this, 'set_products_per_page_query_arg' ), PHP_INT_MAX );
				add_filter( 'woocommerce_shortcode_products_query_results',       array( $this, 'save_wc_shortcode_results' ),       PHP_INT_MAX );
			}

			// Frontend
			$positions  = (array) get_option( 'alg_products_per_page_position', array( 'woocommerce_before_shop_loop' ) );
			$priorities = (array) get_option( 'alg_wc_products_per_page_position_priorities', array() );
			foreach ( $positions as $position ) {
				$priority = ( $priorities[ $position ] ?? get_option( 'alg_products_per_page_position_priority', 40 ) );
				add_action( $position, array( $this, 'add_products_per_page_form' ), $priority );
			}
			if ( '' !== ( $custom_positions = get_option( 'alg_wc_products_per_page_position_custom', '' ) ) ) {
				$custom_positions = array_map( 'trim', explode( PHP_EOL, $custom_positions ) );
				foreach ( $custom_positions as $position ) {
					$position = array_map( 'trim', explode( '|', $position ) );
					$priority = ( $position[1] ?? get_option( 'alg_products_per_page_position_priority', 40 ) );
					add_action( $position[0], array( $this, 'add_products_per_page_form' ), $priority );
				}
			}
			if (
				in_array( 'alg_wc_products_per_page_before_pagination', $positions ) ||
				in_array( 'alg_wc_products_per_page_after_pagination',  $positions )
			) {
				add_filter( 'wc_get_template', array( $this, 'replace_pagination_template' ), PHP_INT_MAX, 2 );
			}

			// Custom CSS
			add_action( 'wp_head', array( $this, 'add_custom_css' ) );

			// Shortcodes
			add_shortcode( 'alg_wc_products_per_page', array( $this, 'form_shortcode' ) );
			add_shortcode( 'alg_wc_ppp_form',          array( $this, 'form_shortcode' ) );
			add_shortcode( 'alg_wc_ppp_translate',     array( $this, 'language_shortcode' ) );

		}
	}

	/**
	 * get_allowed_form_html.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function get_allowed_form_html() {
		$allowed_html = array(
			'form' => array(
				'action'         => true,
				'accept'         => true,
				'accept-charset' => true,
				'enctype'        => true,
				'method'         => true,
				'name'           => true,
				'target'         => true,
				'class'          => true,
			),
			'input' => array(
				'type'     => true,
				'id'       => true,
				'name'     => true,
				'class'    => true,
				'style'    => true,
				'value'    => true,
				'onchange' => true,
				'checked'  => true,
			),
			'select' => array(
				'id'       => true,
				'name'     => true,
				'class'    => true,
				'style'    => true,
				'onchange' => true,
			),
			'option' => array(
				'value'    => true,
				'selected' => true,
			),
		);
		return array_merge(
			wp_kses_allowed_html( 'post' ),
			$allowed_html
		);
	}

	/**
	 * add_custom_css.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function add_custom_css() {
		if ( '' !== ( $custom_css = get_option( 'alg_wc_products_per_page_custom_css', '' ) ) ) {
			echo "<style>{$custom_css}</style>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * replace_pagination_template.
	 *
	 * @version 2.1.1
	 * @since   2.0.0
	 *
	 * @todo    (feature) `loop/result-count.php`
	 * @todo    (feature) `loop/orderby.php`
	 * @todo    (dev) check "Product Filters for WooCommerce" plugin filters (i.e., instead of overriding the pagination template)?
	 */
	function replace_pagination_template( $located, $template_name ) {
		return (
			(
				'loop/pagination.php' === $template_name &&
				apply_filters( 'alg_wc_products_per_page_replace_pagination_template', true )
			) ?
			alg_wc_products_per_page()->plugin_path() . '/includes/templates/loop/pagination.php' :
			$located
		);
	}

	/**
	 * language_shortcode.
	 *
	 * @version 2.5.0
	 * @since   1.3.2
	 */
	function language_shortcode( $atts, $content = '' ) {
		// E.g.: `[alg_wc_ppp_translate lang="EN,DE" lang_text="Text for EN & DE" not_lang_text="Text for other languages"]`
		if ( isset( $atts['lang_text'] ) && isset( $atts['not_lang_text'] ) && ! empty( $atts['lang'] ) ) {
			return ( ! defined( 'ICL_LANGUAGE_CODE' ) || ! in_array( strtolower( ICL_LANGUAGE_CODE ), array_map( 'trim', explode( ',', strtolower( $atts['lang'] ) ) ) ) ) ?
				wp_kses_post( $atts['not_lang_text'] ) : wp_kses_post( $atts['lang_text'] );
		}
		// E.g.: `[alg_wc_ppp_translate lang="EN,DE"]Text for EN & DE[/alg_wc_ppp_translate][alg_wc_ppp_translate not_lang="EN,DE"]Text for other languages[/alg_wc_ppp_translate]`
		return (
			( ! empty( $atts['lang'] )     && ( ! defined( 'ICL_LANGUAGE_CODE' ) || ! in_array( strtolower( ICL_LANGUAGE_CODE ), array_map( 'trim', explode( ',', strtolower( $atts['lang'] ) ) ) ) ) ) ||
			( ! empty( $atts['not_lang'] ) &&     defined( 'ICL_LANGUAGE_CODE' ) &&   in_array( strtolower( ICL_LANGUAGE_CODE ), array_map( 'trim', explode( ',', strtolower( $atts['not_lang'] ) ) ) ) )
		) ? '' : wp_kses_post( $content );
	}

	/**
	 * form_shortcode.
	 *
	 * @version 2.5.0
	 * @since   1.5.0
	 *
	 * @todo    (feature) add `select_options` and `form_method` atts?
	 */
	function form_shortcode( $atts, $content = '' ) {

		$default_atts = array(
			'template'     => get_option( 'alg_products_per_page_text', __( 'Products <strong>%from% - %to%</strong> from <strong>%total%</strong>. Products on page %dropdown%', 'products-per-page-for-woocommerce' ) ), // phpcs:ignore WordPress.WP.I18n.UnorderedPlaceholdersText, WordPress.WP.I18n.MissingTranslatorsComment
			'select_class' => get_option( 'alg_wc_products_per_page_select_class', 'sortby rounded_corners_class' ),
			'select_style' => get_option( 'alg_wc_products_per_page_select_style', '' ),
			'before_html'  => get_option( 'alg_wc_products_per_page_before_html', '<div class="clearfix"></div><div>' ),
			'after_html'   => get_option( 'alg_wc_products_per_page_after_html', '</div>' ),
			'radio_glue'   => get_option( 'alg_wc_products_per_page_radio_glue', ' ' ),
		);

		$atts = shortcode_atts( $default_atts, $atts, 'alg_wc_ppp_form' );

		$atts['template']    = str_replace( array( '{', '}' ), array( '[', ']' ), $atts['template'] );
		$atts['before_html'] = str_replace( array( '{', '}' ), array( '[', ']' ), $atts['before_html'] );
		$atts['after_html']  = str_replace( array( '{', '}' ), array( '[', ']' ), $atts['after_html'] );

		if ( '' !== $content ) {
			$atts['template'] = $content;
		}

		$products_per_page_form = $this->get_products_per_page_form( array_merge( $atts, array(
			'form_method'    => get_option( 'alg_wc_products_per_page_form_method', 'POST' ),
			'select_options' => apply_filters( 'alg_wc_products_per_page_select_options',
				implode( PHP_EOL, array( '10|10', '25|25', '50|50', '100|100', 'All|-1' ) ) ),
		) ) );

		return wp_kses(
			$products_per_page_form,
			$this->get_allowed_form_html()
		);

	}

	/**
	 * get_id_suffix.
	 *
	 * @version 1.5.0
	 * @since   1.5.0
	 */
	function get_id_suffix() {
		if ( ! isset( $this->id_counter ) ) {
			$this->id_counter = 0;
		}
		$this->id_counter++;
		return ( $this->id_counter > 1 ? '_' . $this->id_counter : '' );
	}

	/**
	 * save_wc_shortcode_results.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 *
	 * @todo    (dev) do we really need this?
	 */
	function save_wc_shortcode_results( $results ) {
		$this->shortcode_loop_props['post_count']   = ( ! empty( $results->ids ) && is_array( $results->ids ) ? count( $results->ids ) : 0 );
		$this->shortcode_loop_props['found_posts']  = ( ! empty( $results->total ) ? $results->total : 0 );
		$this->shortcode_loop_props['current_page'] = ( ! empty( $results->current_page ) ? $results->current_page : 1 );
		return $results;
	}

	/**
	 * get_loop_prop.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 *
	 * @todo    (dev) do we really need this so complex? maybe `wc_get_loop_prop()` would be good enough?
	 */
	function get_loop_prop( $prop, $args = array() ) {
		global $wp_query;
		switch ( $prop ) {

			case 'per_page':
				if ( '' !== ( $value = wc_get_loop_prop( 'per_page' ) ) ) {
					return $value;
				} else {
					return $this->get_products_per_page();
				}

			case 'current_page':
				if ( '' !== ( $value = wc_get_loop_prop( 'current_page' ) ) ) {
					return $value;
				} elseif ( ! empty( $this->shortcode_loop_props['current_page'] ) ) {
					return $this->shortcode_loop_props['current_page'];
				} elseif ( ! empty( $_GET['product-page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					return absint( $_GET['product-page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				} elseif ( 0 != ( $paged = get_query_var( 'paged' ) ) ) {
					return $paged;
				} else {
					return 1;
				}

			case 'total':
				if ( '' !== ( $value = wc_get_loop_prop( 'total' ) ) ) {
					return $value;
				} elseif ( ! empty( $this->shortcode_loop_props['found_posts'] ) ) {
					return $this->shortcode_loop_props['found_posts'];
				} else {
					return $wp_query->found_posts;
				}

			case 'from':
				return ( $args['per_page'] * $args['current'] ) - $args['per_page'] + 1;

			case 'to':
				if ( -1 == $args['per_page'] ) {
					return $args['total'];
				} elseif ( ! in_array( '', $args ) ) {
					return min( $args['total'], $args['per_page'] * $args['current'] );
				} else {
					$post_count = ( ! empty( $this->shortcode_loop_props['post_count'] ) ? $this->shortcode_loop_props['post_count'] : $wp_query->post_count );
					return ( $args['per_page'] * $args['current'] ) - $args['per_page'] + $post_count;
				}

		}
		return '';
	}

	/**
	 * parse_options.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function parse_options( $raw_options ) {
		$parsed_options = array();
		$raw_options    = array_map( 'trim', explode( PHP_EOL, $raw_options ) );
		foreach ( $raw_options as $option ) {
			$option = array_map( 'trim', explode( '|', $option, 2 ) );
			if ( 2 === count( $option ) ) {
				$parsed_options[ intval( $option[1] ) ] = wc_clean( $option[0] );
			}
		}
		return $parsed_options;
	}

	/**
	 * get_radio.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 *
	 * @todo    (feature) `$args['radio_template']` (defaults to `%input%%label%`)
	 */
	function get_radio( $per_page, $args ) {
		$fields    = array();
		$id_prefix = 'alg_wc_products_per_page' . $this->get_id_suffix();
		foreach ( $this->parse_options( $args['select_options'] ) as $value => $title ) {
			$id = $id_prefix . '_' . sanitize_key( $title );
			$fields[] = '<input' .
					' type="radio"' .
					' id="' . $id . '"' .
					' name="alg_wc_products_per_page"' .
					' class="' . $args['select_class'] . '"' .
					' style="' . $args['select_style'] . '"' .
					' value="' . $value . '"' .
					' onchange="this.form.submit()"' .
					checked( $per_page, $value, false ) . '>' .
				'<label for="' . $id . '">' . $title . '</label>';
		}
		return implode( $args['radio_glue'], $fields );
	}

	/**
	 * get_select.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function get_select( $per_page, $args ) {
		$options = '';
		foreach ( $this->parse_options( $args['select_options'] ) as $value => $title ) {
			$options .= '<option value="' . $value . '"' . selected( $per_page, $value, false ) . '>' . $title . '</option>';
		}
		return '<select' .
				' name="alg_wc_products_per_page"' .
				' id="alg_wc_products_per_page' . $this->get_id_suffix() . '"' .
				' class="' . $args['select_class'] . '"' .
				' style="' . $args['select_style'] . '"' .
				' onchange="this.form.submit()">' .
			$options . '</select>';
	}

	/**
	 * get_hidden_fields.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 *
	 * @todo    (dev) pass all `$_POST` params?
	 */
	function get_hidden_fields( $form_method ) {
		$fields = '';
		if ( 'GET' === $form_method && ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			foreach ( $_GET as $name => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( 'alg_wc_products_per_page' != $name ) {
					$fields .= '<input type="hidden" name="' . wc_clean( $name ) .'" value="' . wc_clean( $value ) .'">';
				}
			}
		}
		return $fields;
	}

	/**
	 * get_products_per_page_form.
	 *
	 * @version 2.3.0
	 * @since   1.5.0
	 *
	 * @todo    (feature) separate templates for "Showing the single result" and "Showing all %d results" (see `woocommerce/.../result-count.php`)
	 * @todo    (feature) `select2`
	 * @todo    (feature) customizable HTML `name` attribute, and maybe change the default value from `alg_wc_products_per_page` to `alg_wc_ppp`, or `products_per_page`, or `ppp`
	 * @todo    (feature) customizable HTML `id` attribute
	 * @todo    (dev) check for `wc_get_loop_prop( 'is_paginated' )`? (see https://github.com/woocommerce/woocommerce/blob/5.7.0/includes/wc-template-functions.php#L1424)
	 * @todo    (dev) `get_pagenum_link()`?
	 * @todo    (feature) `%radio%`: fontawesome instead of standard radio buttons (same for the `%checkboxes%` if/when implemented)?
	 * @todo    (feature) `%checkboxes%` (similar to `%radio%`)?
	 * @todo    (feature) `%buttons%` (similar to "page numbers")?
	 * @todo    (feature) `get_option()` for `do_check_for_products`?
	 * @todo    (feature) `get_option()` for `do_apply_shortcodes`?
	 * @todo    (dev) `do_apply_shortcodes`: apply it to `template` only?
	 */
	function get_products_per_page_form( $args ) {

		// Args
		$default_args = array(
			'template'              => __( 'Products <strong>%from% - %to%</strong> from <strong>%total%</strong>. Products on page %dropdown%', 'products-per-page-for-woocommerce' ), // phpcs:ignore WordPress.WP.I18n.UnorderedPlaceholdersText, WordPress.WP.I18n.MissingTranslatorsComment
			'select_class'          => 'sortby rounded_corners_class',
			'select_style'          => '',
			'form_method'           => 'POST',
			'before_html'           => '<div class="clearfix"></div><div>',
			'after_html'            => '</div>',
			'radio_glue'            => ' ',
			'select_options'        => implode( PHP_EOL, array( '10|10', '25|25', '50|50', '100|100', 'All|-1' ) ),
			'do_check_for_products' => true,
			'do_apply_shortcodes'   => true,
		);
		$args = array_replace( $default_args, $args );

		// Loop props
		if ( $args['do_check_for_products'] && ! woocommerce_products_will_display() ) {
			return '';
		}
		$per_page = $this->get_loop_prop( 'per_page' );
		$current  = $this->get_loop_prop( 'current_page' );
		$total    = $this->get_loop_prop( 'total' );

		// Placeholders
		$placeholders = array(
			'%from%'     => $this->get_loop_prop( 'from', array( 'per_page' => $per_page, 'current' => $current ) ),
			'%to%'       => $this->get_loop_prop( 'to',   array( 'per_page' => $per_page, 'current' => $current, 'total' => $total ) ),
			'%total%'    => $total,
			'%dropdown%' => $this->get_select( $per_page, $args ),
			'%radio%'    => $this->get_radio( $per_page, $args ),
		);
		$placeholders['%select_form%'] = $placeholders['%dropdown%']; // deprecated `%select_form%`

		// Final HTML
		$content = str_replace( array_keys( $placeholders ), $placeholders, $args['template'] );
		$action  = remove_query_arg( 'product-page', get_pagenum_link( 1, false ) );
		$form    = '<form action="' . $action . '" method="' . $args['form_method'] . '" class="alg-wc-products-per-page-form">' .
			$content .
			$this->get_hidden_fields( $args['form_method'] ) .
		'</form>';
		$html    = $args['before_html'] . $form . $args['after_html'];
		return ( $args['do_apply_shortcodes'] ? do_shortcode( $html ) : $html );

	}

	/**
	 * add_products_per_page_form.
	 *
	 * @version 2.5.0
	 * @since   1.0.0
	 */
	function add_products_per_page_form() {
		$products_per_page_form = $this->get_products_per_page_form( array(
			'template'       => wp_kses_post( get_option( 'alg_products_per_page_text',
				__( 'Products <strong>%from% - %to%</strong> from <strong>%total%</strong>. Products on page %dropdown%', 'products-per-page-for-woocommerce' ) ) ), // phpcs:ignore WordPress.WP.I18n.UnorderedPlaceholdersText, WordPress.WP.I18n.MissingTranslatorsComment
			'select_class'   => esc_attr( get_option( 'alg_wc_products_per_page_select_class', 'sortby rounded_corners_class' ) ),
			'select_style'   => esc_attr( get_option( 'alg_wc_products_per_page_select_style', '' ) ),
			'form_method'    => esc_attr( get_option( 'alg_wc_products_per_page_form_method', 'POST' ) ),
			'before_html'    => wp_kses_post( get_option( 'alg_wc_products_per_page_before_html', '<div class="clearfix"></div><div>' ) ),
			'after_html'     => wp_kses_post( get_option( 'alg_wc_products_per_page_after_html', '</div>' ) ),
			'radio_glue'     => wp_kses_post( get_option( 'alg_wc_products_per_page_radio_glue', ' ' ) ),
			'select_options' => esc_html( apply_filters( 'alg_wc_products_per_page_select_options',
				implode( PHP_EOL, array( '10|10', '25|25', '50|50', '100|100', 'All|-1' ) ) ) ),
		) );
		echo wp_kses(
			$products_per_page_form,
			$this->get_allowed_form_html()
		);
	}

	/**
	 * set_cookie.
	 *
	 * @version 2.4.0
	 * @since   1.2.0
	 */
	function set_cookie() {
		if ( isset( $_REQUEST['alg_wc_products_per_page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			setcookie(
				'alg_wc_products_per_page',
				intval( $_REQUEST['alg_wc_products_per_page'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				( time() + get_option( 'alg_wc_products_per_page_cookie_sec', 1209600 ) ),
				'/',
				( isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '' ),
				false
			);
		}
	}

	/**
	 * set_session.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function set_session() {
		if (
			isset( $_REQUEST['alg_wc_products_per_page'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			! empty( WC()->session )
		) {
			if (
				! WC()->session->has_session() &&
				'yes' === get_option( 'alg_wc_products_per_page_session_force_start', 'yes' )
			) {
				WC()->session->set_customer_session_cookie( true );
			}
			WC()->session->set(
				'alg_wc_products_per_page',
				intval( $_REQUEST['alg_wc_products_per_page'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);
		}
	}

	/**
	 * check_scope.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function check_scope() {
		$scopes = get_option( 'alg_wc_products_per_page_scopes', array() );
		foreach ( array( 'require', 'exclude' ) as $scope ) {
			if ( ! empty( $scopes[ $scope ] ) ) {
				foreach ( $scopes[ $scope ] as $func ) {
					if (
						function_exists( $func ) &&
						( ( 'require' === $scope && ! $func() ) || ( 'exclude' === $scope && $func() ) )
					) {
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * set_products_per_page.
	 *
	 * @version 2.1.0
	 * @since   1.0.0
	 */
	function set_products_per_page( $products_per_page ) {
		return ( $this->check_scope() ? $this->get_products_per_page() : $products_per_page );
	}

	/**
	 * set_products_per_page_query_arg.
	 *
	 * @version 2.1.0
	 * @since   1.6.0
	 */
	function set_products_per_page_query_arg( $query_args ) {
		if ( $this->check_scope() ) {
			$query_args['posts_per_page'] = $this->get_products_per_page();
		}
		return $query_args;
	}

	/**
	 * get_products_per_page.
	 *
	 * @version 2.0.1
	 * @since   1.2.0
	 */
	function get_products_per_page() {

		if ( isset( $_REQUEST['alg_wc_products_per_page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return intval( $_REQUEST['alg_wc_products_per_page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		} elseif (
			'yes' === get_option( 'alg_wc_products_per_page_session_enabled', 'yes' ) &&
			isset( WC()->session ) &&
			( $value = WC()->session->get( 'alg_wc_products_per_page' ) )
		) {
			return $value;

		} elseif (
			'yes' === get_option( 'alg_wc_products_per_page_cookie_enabled', 'yes' ) &&
			isset( $_COOKIE['alg_wc_products_per_page'] )
		) {
			return intval( $_COOKIE['alg_wc_products_per_page'] );

		} else { // default
			return get_option( 'alg_products_per_page_default', 10 );
		}

	}

}

endif;

return new Alg_WC_Products_Per_Page_Core();
