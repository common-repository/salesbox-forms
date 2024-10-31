<?php
/**
 * All the functions and classes in this file are deprecated.
 * You shouldn't use them. The functions and classes will be
 * removed in a later version.
 */

function sbf_add_shortcode( $tag, $func, $has_name = false ) {
	sbf_deprecated_function( __FUNCTION__, '4.6', 'sbf_add_form_tag' );

	return sbf_add_form_tag( $tag, $func, $has_name );
}

function sbf_remove_shortcode( $tag ) {
	sbf_deprecated_function( __FUNCTION__, '4.6', 'sbf_remove_form_tag' );

	return sbf_remove_form_tag( $tag );
}

function sbf_do_shortcode( $content ) {
	sbf_deprecated_function( __FUNCTION__, '4.6',
		'sbf_replace_all_form_tags' );

	return sbf_replace_all_form_tags( $content );
}

function sbf_scan_shortcode( $cond = null ) {
	sbf_deprecated_function( __FUNCTION__, '4.6', 'sbf_scan_form_tags' );

	return sbf_scan_form_tags( $cond );
}

class SBF_ShortcodeManager {

	private static $form_tags_manager;

	private function __construct() {}

	public static function get_instance() {
		sbf_deprecated_function( __METHOD__, '4.6',
			'SBF_FormTagsManager::get_instance' );

		self::$form_tags_manager = SBF_FormTagsManager::get_instance();
		return new self;
	}

	public function get_scanned_tags() {
		sbf_deprecated_function( __METHOD__, '4.6',
			'SBF_FormTagsManager::get_scanned_tags' );

		return self::$form_tags_manager->get_scanned_tags();
	}

	public function add_shortcode( $tag, $func, $has_name = false ) {
		sbf_deprecated_function( __METHOD__, '4.6',
			'SBF_FormTagsManager::add' );

		return self::$form_tags_manager->add( $tag, $func, $has_name );
	}

	public function remove_shortcode( $tag ) {
		sbf_deprecated_function( __METHOD__, '4.6',
			'SBF_FormTagsManager::remove' );

		return self::$form_tags_manager->remove( $tag );
	}

	public function normalize_shortcode( $content ) {
		sbf_deprecated_function( __METHOD__, '4.6',
			'SBF_FormTagsManager::normalize' );

		return self::$form_tags_manager->normalize( $content );
	}

	public function do_shortcode( $content, $exec = true ) {
		sbf_deprecated_function( __METHOD__, '4.6',
			'SBF_FormTagsManager::replace_all' );

		if ( $exec ) {
			return self::$form_tags_manager->replace_all( $content );
		} else {
			return self::$form_tags_manager->scan( $content );
		}
	}

	public function scan_shortcode( $content ) {
		sbf_deprecated_function( __METHOD__, '4.6',
			'SBF_FormTagsManager::scan' );

		return self::$form_tags_manager->scan( $content );
	}
}

class SBF_Shortcode extends SBF_FormTag {

	public function __construct( $tag ) {
		sbf_deprecated_function( 'SBF_Shortcode', '4.6', 'SBF_FormTag' );

		parent::__construct( $tag );
	}
}
