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
