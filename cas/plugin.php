<?php
/*
Plugin Name: CAS Authentication
Plugin URI: http://code.google.com/p/yourls-cas-plugin/
Description: Add support for CAS (Central Auth Service).
Version: 1.0
Author: nicwaller
Author URI: http://code.google.com/u/101717938102134699062/
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

// returns true if the phpCAS environment is set up right
function cas_environment_check() {
	$required_params = array(
		'PHPCAS_PATH', // path to phpCAS loader file (CAS.php)
		'PHPCAS_HOST', // full hostname of your CAS server
		'PHPCAS_CONTEXT', // context of the CAS server (webapp subdirectory)
		'PHPCAS_CERTCHAIN_PATH', // path to a .pem file containing 1 or more CA certs
	);

	foreach ($required_params as $pname) {
		if ( !defined( $pname ) ) {
			$message = 'Missing defined parameter '.$pname.' in plugin '. $thisplugname;
			error_log($message);
			return false;
		}
	}
	
	if ( !defined( 'PHPCAS_PORT' ) )
		define( 'PHPCAS_PORT', 443 );

	if ( !defined( 'PHPCAS_HIJACK_LOGIN' ) )
		define( 'PHPCAS_HIJACK_LOGIN', true );

	if ( !defined( 'PHPCAS_ALL_USERS_ADMIN' ) )
		define( 'PHPCAS_ALL_USERS_ADMIN', true );

	global $cas_authorized_admins;
	if ( !isset( $cas_authorized_admins ) ) {
		if ( !PHPCAS_ALL_USERS_ADMIN ) {
			error_log('Undefined $cas_authorized_admins');
		}
		$cas_authorized_admins = array();
	}

	return true;
}



yourls_add_action( 'redirect_keyword_not_found', 'cas_page_hook' );

function cas_page_hook( $args ) {
	$keyword = $args[0];

	if ( $keyword == 'caslogin' ) {
		if ( !cas_environment_check() ) {
			die( 'Invalid configuration for phpCAS. Check PHP error log.' );
		}

		require_once PHPCAS_PATH; // /*$phpcas_path .*/ 'phpcas/CAS.php';

		//phpCAS::setDebug();
		phpCAS::client(CAS_VERSION_2_0, PHPCAS_HOST, PHPCAS_PORT, PHPCAS_CONTEXT);
		phpCAS::setCasServerCACert(PHPCAS_CERTCHAIN_PATH);
		phpCAS::forceAuthentication();
		// then set up external-auth cookie
		// or just use PHP session management
		session_start();
		$_SESSION['CAS_AUTH_USER'] = phpCAS::getUser();
		header('Location: ' . yourls_admin_url( 'index.php' ) );
		die('');
	}
}



yourls_add_action( 'logout', 'cas_logout_hook' );

function cas_logout_hook( $args ) {
	unset($_SESSION['CAS_USER_AUTH']);
	setcookie('PHPSESSID', '', 0, '/');

	if ( !cas_environment_check() ) {
		die( 'Invalid configuration for phpCAS. Check PHP error log.' );
	}

	// to enable single sign-out, also teardown the CAS session
        require_once PHPCAS_PATH; // /*$phpcas_path .*/ 'phpcas/CAS.php';
        phpCAS::client(CAS_VERSION_2_0, PHPCAS_HOST, PHPCAS_PORT, PHPCAS_CONTEXT);
        phpCAS::setCasServerCACert(PHPCAS_CERTCHAIN_PATH);
	//phpCAS::logout();
	phpCAS::logoutWithRedirectService( yourls_site_url() );
	
	// if we hide the login screen, also hide logout screen
	if ( PHPCAS_HIJACK_LOGIN ) {
		yourls_redirect( yourls_site_url() );
	}
}



yourls_add_filter( 'is_valid_user', 'cas_is_valid_user' );

// returns true/false
function cas_is_valid_user( $value ) {
	// TODO: why would this function ever be called more than once?
	// Well sometimes it is, and then we're starting the session twice. ew.
	// session_status is only defined in PHP >= 5.4.0
	//if ( session_status() === PHP_SESSION_NONE )
		@session_start();

	if ( isset( $_SESSION['CAS_AUTH_USER'] ) ) {
		$username = $_SESSION['CAS_AUTH_USER'];
		if ( cas_is_authorized_user( $username ) ) {
			yourls_set_user( $_SESSION['CAS_AUTH_USER'] );
			return true;
		} else {
			return $username.' is not admin user.';
		}
	} else {
		// If auth is required, and if auth fails, then YOURLS will always
		// display the login screen. We want to hijack that.
		// at this point, we already know that no CAS external auth is available
		if ( PHPCAS_HIJACK_LOGIN ) {
			header('Location: ' . yourls_site_url() . '/caslogin' );
			die();
		}
	}
	
	return $value;
}

function cas_is_authorized_user( $username ) {
        if ( !cas_environment_check() ) {
                die( 'Invalid configuration for phpCAS. Check PHP error log.' );
        }

	// by default, anybody who can authenticate is also
	// authorized as an administrator.
	if ( PHPCAS_ALL_USERS_ADMIN ) {
		return true;
	}

	// users listed in config.php are admin users. let them in.
	global $cas_authorized_admins;
	if ( in_array( $username, $cas_authorized_admins ) ) {
		return true;
	}

	return false;
}