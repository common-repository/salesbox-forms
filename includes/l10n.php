<?php

function sbf_l10n() {
	static $l10n = array();

	if ( ! empty( $l10n ) ) {
		return $l10n;
	}

	if ( ! is_admin() ) {
		return $l10n;
	}

	require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );

	$api = translations_api( 'plugins', array(
		'slug' => 'salesbox-crm-form',
		'version' => SBF_VERSION,
	) );

	if ( is_wp_error( $api )
	or empty( $api['translations'] ) ) {
		return $l10n;
	}

	foreach ( (array) $api['translations'] as $translation ) {
		if ( ! empty( $translation['language'] )
		and ! empty( $translation['english_name'] ) ) {
			$l10n[$translation['language']] = $translation['english_name'];
		}
	}

	return $l10n;
}

function sbf_is_valid_locale( $locale ) {
	$pattern = '/^[a-z]{2,3}(?:_[a-zA-Z_]{2,})?$/';
	return (bool) preg_match( $pattern, $locale );
}

function sbf_is_rtl( $locale = '' ) {
	static $rtl_locales = array(
		'ar' => 'Arabic',
		'ary' => 'Moroccan Arabic',
		'azb' => 'South Azerbaijani',
		'fa_IR' => 'Persian',
		'haz' => 'Hazaragi',
		'he_IL' => 'Hebrew',
		'ps' => 'Pashto',
		'ug_CN' => 'Uighur',
	);

	if ( empty( $locale )
	and function_exists( 'is_rtl' ) ) {
		return is_rtl();
	}

	if ( empty( $locale ) ) {
		$locale = determine_locale();
	}

	return isset( $rtl_locales[$locale] );
}

function sbf_load_textdomain( $locale = '' ) {
	static $locales = array();

	if ( empty( $locales ) ) {
		$locales = array( determine_locale() );
	}

	$available_locales = array_merge(
		array( 'en_US' ),
		get_available_languages()
	);

	if ( ! in_array( $locale, $available_locales ) ) {
		$locale = $locales[0];
	}

	if ( $locale === end( $locales ) ) {
		return false;
	} else {
		$locales[] = $locale;
	}

	$domain = SBF_TEXT_DOMAIN;

	if ( is_textdomain_loaded( $domain ) ) {
		unload_textdomain( $domain );
	}

	$mofile = sprintf( '%s-%s.mo', $domain, $locale );

	$domain_path = path_join( SBF_PLUGIN_DIR, 'languages' );
	$loaded = load_textdomain( $domain, path_join( $domain_path, $mofile ) );

	if ( ! $loaded ) {
		$domain_path = path_join( WP_LANG_DIR, 'plugins' );
		load_textdomain( $domain, path_join( $domain_path, $mofile ) );
	}

	return true;
}
