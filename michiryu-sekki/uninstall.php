<?php
/**
 * Remove plugin settings and imported content on uninstall.
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'michiryu_sekki_options' );
delete_option( 'michiryu_sekki_content_import' );

if ( function_exists( 'wp_upload_dir' ) ) {
	$upload_dir = wp_upload_dir( null, false );
	$base_dir   = is_array( $upload_dir ) && ! empty( $upload_dir['basedir'] ) ? $upload_dir['basedir'] : '';

	if ( '' !== $base_dir ) {
		$uploads_realpath = realpath( $base_dir );
		$content_realpath = realpath( rtrim( $base_dir, '/\\' ) . '/michiryu-sekki-content' );

		if (
			false !== $uploads_realpath
			&& false !== $content_realpath
			&& is_dir( $content_realpath )
			&& 0 === strpos( $content_realpath, rtrim( $uploads_realpath, '/\\' ) . DIRECTORY_SEPARATOR )
		) {
			michiryu_sekki_uninstall_delete_directory( $content_realpath );
		}
	}
}

/**
 * Delete a directory tree.
 *
 * @param string $directory Directory path.
 * @return bool
 */
function michiryu_sekki_uninstall_delete_directory( $directory ) {
	if ( ! is_dir( $directory ) ) {
		return true;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $iterator as $item ) {
		if ( $item->isDir() ) {
			if ( ! rmdir( $item->getPathname() ) ) {
				return false;
			}
		} elseif ( ! unlink( $item->getPathname() ) ) {
			return false;
		}
	}

	return rmdir( $directory );
}
