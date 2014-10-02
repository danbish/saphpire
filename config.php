<?php
	/**
	 * Configuration File for PHP Applications
	 *
	 * This file is to be included in all page controller files
	 * and it will define all constants used throughout the application
	 * as well as handle configuration file loading and authentication.
	 *
	 * @author	Ryan Masters
	 * @author	James Upp
	 *
	 * @package	Core_0
	 * @version	0.4
	 */

	// enable all errors to be shown
	ini_set( 'error_reporting', E_ALL | E_STRICT );
	error_reporting( E_ALL | E_STRICT );

	// set the timezone
	ini_set( 'date.timezone', 'America/New_York' );

	// define format that all timestamps should use
	define( 'sTIMESTAMP_FORMAT', 'Y-m-d H:i:s' );

	// define the base path from which to include files
	define( 'sBASE_INC_PATH',  dirname( str_replace( "\\", DIRECTORY_SEPARATOR, __FILE__ ) ) );
	set_include_path( get_include_path() . PATH_SEPARATOR . sBASE_INC_PATH );

	// define the core path from which to include files
	define( 'sCORE_INC_PATH',  sBASE_INC_PATH . '/libs/PHPCore' );
	set_include_path( get_include_path() . PATH_SEPARATOR . sCORE_INC_PATH );

	// define a flag to check is this is being run through the command line
	define( 'bIS_CLI', ( php_sapi_name() === 'cli' ) );

	try
	{
		// include error/exception handling
		require_once( sCORE_INC_PATH . '/includes/ErrorHandling.php' );

		// include convenience functions
		require_once( sCORE_INC_PATH . '/includes/Convenience.php' );

		// get the logger
		require_once( sCORE_INC_PATH . '/classes/cLogger.php' );

		// read all config files
		require_once( sCORE_INC_PATH . '/classes/cBaseConfig.php' );
    	$oConfig      = new cBaseConfig();
    	$aConfigFiles = $oConfig->GetConfigFiles();

    	// read contents of files into an array
    	$aConfigs = $oConfig->Read( $aConfigFiles );

    	// if no hosts are defined, we're done
    	if( !isset( $aConfigs[ 'host' ] ) )
    	{
    		die( 'Your script is being ran on an unknown environment.' );
    	}
    	// otherwise, format if there's only one
    	elseif( !isset( $aConfigs[ 'host' ][ 0 ] ) )
    	{
    		$aConfigs[ 'host' ] = array( $aConfigs[ 'host' ] );
    	}

		// get the current host
		$sHost = GetHost();

		// try to find the environment for this host
		$iHostCount = count( $aConfigs[ 'host' ] );
		for( $i = 0; $i < $iHostCount; ++$i )
		{
			// check if the host name matches
			if( $aConfigs[ 'host' ][ $i ][ 'name' ] == $sHost )
			{
				// define application environment for use throughout application
				define( 'sAPPLICATION_ENV', $aConfigs[ 'host' ][ $i ][ 'env' ] );
				break;
			}
		}

		// ensure that the application can be run from this environment
		if( !defined( 'sAPPLICATION_ENV' ) )
		{
		    die( 'Your script is being ran on an unknown environment.' );
		}

		// load any extensions that have been provided
		if( isset( $aConfigs[ 'extensions' ] ) )
		{
			$aConfigs = $oConfig->ReadExtensions( $aConfigs );
		}

		// define debugging functions in dev or local environments
		if( sAPPLICATION_ENV != 'prod' )
		{
		    // display all errors
		    ini_set( 'display_errors', true );
		    ini_set( 'display_startup_errors', true );

		    // get debugging functions
			require_once( sCORE_INC_PATH . '/includes/Debug.php' );
		}
		elseif( sAPPLICATION_ENV == 'prod' && !isset( $_GET[ 'error' ] ) )
		{
		    // hide all errors from the user
		    ini_set( 'display_errors', false );
		    ini_set( 'display_startup_errors', false );

			// check if the release date has been met
			$iNow = strtotime( 'now' );
			if( strtotime( $aConfigs[ 'release-date' ] ) > $iNow )
			{
				throw new Exception( 'Application is not yet released.', 1 );
			}

			// check if the application is down for maintenance
			$iStartMaintenance = strtotime( $aConfigs[ 'maintenance-window' ][ 'start' ] );
			$iEndMaintenance   = strtotime( $aConfigs[ 'maintenance-window' ][ 'end' ] );
			if( $iNow > $iStartMaintenance && $iNow < $iEndMaintenance )
			{
				throw new Exception( 'Application is down for maintenance.', 1 );
			}
		}

		// begin or resume the session
		session_start();
	}
	catch( Exception $oException )
	{
		ExceptionHandler( $oException );
	}
?>