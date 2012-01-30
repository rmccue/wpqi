<?php
/**
 * {@internal Missing Short Description}}
 *
 * @since unknown
 *
 * @return unknown
 */
function get_temp_dir() {
	$temp = ABSPATH;
	if ( is_dir( $temp ) && is_writable( $temp ) )
		return $temp;

	if  ( function_exists( 'sys_get_temp_dir' ) )
		return trailingslashit( sys_get_temp_dir() );

	return '/tmp/';
}

/**
 * Modified from Core WP.
 *
 * @since unknown
 *
 * @param unknown_type $filename
 * @param unknown_type $dir
 * @return unknown
 */
function wp_tempnam( $filename = '', $dir = '' ) {
	if ( empty( $dir ) )
		$dir = get_temp_dir();
	$filename = basename( $filename );
	if ( empty( $filename ) )
		$filename = time();

	$_filename = $filename = $dir . $filename;
	$i = 0;
	while ( file_exists( $filename ) )
		$filename = $_filename . $i++;
	touch( $filename );
	return $filename;
}

/**
 * Downloads a url to a local file using the Snoopy HTTP Class.
 *
 * @since unknown
 * @todo Transition over to using the new HTTP Request API (jacob).
 *
 * @param string $url the URL of the file to download
 * @return mixed WP_Error on failure, string Filename on success.
 */
function wpqi_download_url( $url ) {
	//WARNING: The file is not automatically deleted, The script must unlink() the file.
	if ( ! $url )
		return new WP_Error( 'http_no_url', __( 'Invalid URL Provided' ) );

	$tmpfname = wp_tempnam( $url );
	if ( ! $tmpfname )
		return new WP_Error( 'http_no_file', __( 'Could not create Temporary file' ) );

	$response = wp_remote_get( $url, array( 'timeout' => 60, 'stream' => true, 'filename' => $tmpfname ) );

	if ( is_wp_error( $response ) ) {
		unlink( $tmpfname );
		return $response;
	}

	if ( $response['response']['code'] != 200 ){
		unlink( $tmpfname );
		return new WP_Error( 'http_404', trim( $response['response']['message'] ) );
	}

	return array( $tmpfname, $response );
}

/**
 * Modified from Core File, Added Tick handler to allow for progress meter, as well as striping out wordpress/ from filenames.
 *
 * @since unknown
 *
 * @param unknown_type $file
 * @param unknown_type $to
 * @return unknown
 */
function unzip_file( $file, $to, $tick = null ) {
	global $wp_filesystem;

	if ( ! $wp_filesystem || !is_object( $wp_filesystem ) )
		return new WP_Error( 'fs_unavailable', __( 'Could not access filesystem.' ) );

	// Unzip uses a lot of memory
	@ini_set( 'memory_limit', '256M' );

	$fs =& $wp_filesystem;

	require_once( ABSPATH . 'wp-files/wp-admin/includes/class-pclzip.php' );

	$archive = new PclZip( $file );

	// Is the archive valid?
	if ( false == ( $archive_files = $archive->extract( PCLZIP_OPT_EXTRACT_AS_STRING ) ) )
		return new WP_Error( 'incompatible_archive', __( 'Incompatible archive' ), $archive->errorInfo( true ) );

	if ( 0 == count( $archive_files ) )
		return new WP_Error( 'empty_archive', __( 'Empty archive' ) );

	$path = explode( '/', untrailingslashit( $to ) );
	for ( $i = count( $path ); $i > 0; $i-- ) { //>0 = first element is empty allways for paths starting with '/'
		$tmppath = implode( '/', array_slice( $path, 0, $i ) );
		if ( $fs->is_dir( $tmppath ) ) { //Found the highest folder that exists, Create from here(ie +1)
			for ( $i = $i + 1; $i <= count( $path ); $i++ ) {
				$tmppath = implode( '/', array_slice( $path, 0, $i ) );
				if ( ! $fs->mkdir( $tmppath, FS_CHMOD_DIR ) )
					return new WP_Error( 'mkdir_failed', __( 'Could not create directory' ), $tmppath );
			}
			break; //Exit main for loop
		}
	}

	$to = trailingslashit( $to );
	foreach ( $archive_files as $index => $file ) {
		$file['filename'] = preg_replace( '|^wordpress/|i', '', $file['filename'] ); //Override the base WP folder if it exists.

		$path = $file['folder'] ? $file['filename'] : dirname( $file['filename'] );
		$path = explode( '/', $path );
		for ( $i = count( $path ); $i >= 0; $i-- ) { //>=0 as the first element contains data
			if ( empty( $path[$i] ) )
				continue;
			$tmppath = $to . implode( '/', array_slice( $path, 0, $i ) );
			if ( $fs->is_dir( $tmppath ) ) {//Found the highest folder that exists, Create from here
				for ( $i = $i + 1; $i <= count( $path ); $i++ ) { //< count() no file component please.
					$tmppath = $to . implode( '/', array_slice( $path, 0, $i ) );
					if ( ! $fs->is_dir( $tmppath ) && ! $fs->mkdir( $tmppath, FS_CHMOD_DIR ) )
						return new WP_Error( 'mkdir_failed', __( 'Could not create directory' ), $tmppath );
				}
				break; //Exit main for loop
			}
		}

		// We've made sure the folders are there, so let's extract the file now:
		if ( ! $file['folder'] ) {
			if ( !$fs->put_contents( $to . $file['filename'], $file['content'] ) )
				return new WP_Error( 'copy_failed', __( 'Could not copy file' ), $to . $file['filename'] );
			$fs->chmod( $to . $file['filename'], FS_CHMOD_FILE );
		}
		if ( is_callable( $tick ) ) {
			call_user_func( $tick, array( 'count' => count( $archive_files ), 'process' => $index, 'filename' =>  $file ) );
		}
	}
	return true;
}


/**
 * Similar to Core WP's version, But expects the WP-Filesystem classes to be included already. Also allows calling multiple times a bit better.
 *
 * @since unknown
 *
 * @param unknown_type $args
 * @return unknown
 */
function WP_Filesystem( $args = false, $context = false ) {
	global $wp_filesystem;

	$method = get_filesystem_method( $args, $context );

	if ( ! $method )
		return false;

	if ( ! class_exists( "WP_Filesystem_$method" ) )
		return false;

	$method = "WP_Filesystem_$method";

	if ( is_object( $wp_filesystem ) && is_a( $wp_filesystem, $method ) ) //If its already created.
		return true;

	$wp_filesystem = new $method( $args );

	if ( is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() )
		return false;

	if ( !$wp_filesystem->connect() )
		return false; //There was an erorr connecting to the server.

	// Set the permission constants if not already set.
	if ( ! defined( 'FS_CHMOD_DIR' ) )
		define( 'FS_CHMOD_DIR', 0755 );
	if ( ! defined( 'FS_CHMOD_FILE' ) )
		define( 'FS_CHMOD_FILE', 0644 );

	return true;
}

/**
 * {@internal Missing Short Description}}
 *
 * @since unknown
 *
 * @param unknown_type $args
 * @param string $context Full path to the directory that is tested for being writable.
 * @return unknown
 */
function get_filesystem_method( $args = array(), $context = false ) {
	static $use_direct = null;

	$method = defined( 'FS_METHOD' ) ? FS_METHOD : false; //Please ensure that this is either 'direct', 'ssh', 'ftpext' or 'ftpsockets'

	if ( false !== $use_direct && ! $method && function_exists( 'getmyuid' ) && function_exists( 'fileowner' ) ){
		if ( !$context )
			$context = ABSPATH;
		$context = trailingslashit( $context );
		$temp_file_name = $context . '.write-test-' . time();
		$temp_handle = @fopen( $temp_file_name, 'w' );
		if ( $temp_handle ) {
			if ( getmyuid() == fileowner( $temp_file_name ) )
				$method = 'direct';
			else
				$use_direct = false; //Optimization..
			@fclose( $temp_handle );
			unlink( $temp_file_name );
		}

 	}

	if ( ! $method && isset( $args['connection_type'] ) && 'ssh' == $args['connection_type'] && extension_loaded( 'ssh2' ) && function_exists( 'stream_get_contents' ) )
		$method = 'ssh2';
	if ( ! $method && extension_loaded( 'ftp' ) )
		$method = 'ftpext';
	if ( ! $method && ( extension_loaded( 'sockets' ) || function_exists( 'fsockopen' ) ) )
		$method = 'ftpsockets'; //Sockets: Socket extension; PHP Mode: FSockopen / fwrite / fread

	return apply_filters( 'filesystem_method', $method, $args );
}