<?php
/**
 * Frontend assets for the Audiophile block theme.
 *
 * @package Audiophile
 */

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'woocommerce' );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		$theme = wp_get_theme();
		wp_enqueue_style(
			'audiophile-theme-style',
			get_stylesheet_uri(),
			array(),
			$theme->get( 'Version' )
		);
	}
);

/**
 * Use catalog-backed product detail links for the homepage promotions.
 *
 * @param string    $html    The default loop button markup.
 * @param WC_Product $product The product represented by the loop item.
 * @return string
 */
add_filter(
	'woocommerce_loop_add_to_cart_link',
	static function ( $html, $product ) {
		if ( ! is_front_page() || ! ( $product instanceof WC_Product ) ) {
			return $html;
		}

		return sprintf(
			'<a href="%1$s" class="audiophile-home-cta__link" aria-label="%2$s">%3$s</a>',
			esc_url( $product->get_permalink() ),
			esc_attr( sprintf( __( 'See %s product', 'audiophile' ), wp_strip_all_tags( $product->get_name() ) ) ),
			esc_html__( 'See product', 'audiophile' )
		);
	},
	10,
	2
);

/**
 * Prioritize the first product image in the homepage's featured hero.
 *
 * @param array $attributes Image element attributes.
 * @return array
 */
add_filter(
	'wp_get_attachment_image_attributes',
	static function ( $attributes ) {
		static $hero_image_prioritized = false;
		global $post;

		if (
			$hero_image_prioritized ||
			! is_front_page() ||
			! ( $post instanceof WP_Post ) ||
			'product' !== $post->post_type ||
			! isset( $attributes['data-testid'] ) ||
			'product-image' !== $attributes['data-testid']
		) {
			return $attributes;
		}

		$attributes['loading']       = 'eager';
		$attributes['fetchpriority'] = 'high';
		$hero_image_prioritized      = true;

		return $attributes;
	},
	10,
	1
);
