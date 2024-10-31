<?php

class SBF_UploadedFileHandler {

	public function unship( $file, $args = '' ) {
		$args = wp_parse_args( $args, array(
			'required' => false,
			'filetypes' => '',
			'limit' => MB_IN_BYTES,
		) );

		if ( ! empty( $file['error'] ) and UPLOAD_ERR_NO_FILE !== $file['error'] ) {
			return new WP_Error( 'sbf_upload_failed_php_error',
				sbf_get_message( 'upload_failed_php_error' )
			);
		}

		if ( empty( $file['tmp_name'] ) and $args['required'] ) {
			return new WP_Error( 'sbf_invalid_required',
				sbf_get_message( 'invalid_required' )
			);
		}

		if ( empty( $file['tmp_name'] )
		or ! is_uploaded_file( $file['tmp_name'] ) ) {
			return null;
		}

		/* File type validation */

		$file_type_pattern = sbf_acceptable_filetypes(
			$args['filetypes'], 'regex'
		);

		$file_type_pattern = '/\.(' . $file_type_pattern . ')$/i';

		if ( empty( $file['name'] )
		or ! preg_match( $file_type_pattern, $file['name'] ) ) {
			return new WP_Error( 'sbf_upload_file_type_invalid',
				sbf_get_message( 'upload_file_type_invalid' )
			);
		}

		/* File size validation */

		if ( ! empty( $file['size'] ) and $args['limit'] < $file['size'] ) {
			return new WP_Error( 'sbf_upload_file_too_large',
				sbf_get_message( 'upload_file_too_large' )
			);
		}

		sbf_init_uploads(); // Confirm upload dir
		$uploads_dir = sbf_upload_tmp_dir();
		$uploads_dir = sbf_maybe_add_random_dir( $uploads_dir );

		$filename = $file['name'];
		$filename = sbf_canonicalize( $filename, 'as-is' );
		$filename = sbf_antiscript_file_name( $filename );

		$filename = apply_filters( 'sbf_upload_file_name', $filename,
			$file['name'], $args
		);

		$filename = wp_unique_filename( $uploads_dir, $filename );
		$new_file = path_join( $uploads_dir, $filename );

		if ( false === @move_uploaded_file( $file['tmp_name'], $new_file ) ) {
			return new WP_Error( 'sbf_upload_failed',
				sbf_get_message( 'upload_failed' )
			);
		}

		// Make sure the uploaded file is only readable for the owner process
		chmod( $new_file, 0400 );

		return $new_file;
	}

}
