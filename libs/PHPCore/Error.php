<?php
	/**
	 * Error controller.
	 *
	 * Displays message for error code supplied.
	 *
	 * @author  Ryan Masters
	 *
	 * @package Core_0
	 * @version 0.2
	 */
	try
	{
		// get configuration information
		require_once( '../../config.php' );

		// include the base bootstrap
		require_once( sCORE_INC_PATH . '/includes/BaseBootstrap.php' );

		// check if error code has been sent in
		if( isset( $_GET[ 'error' ] ) )
		{
			// get config information
			$aConfig = GetConfig( true );

			// make sure application name is set
			if( !isset( $aConfig[ 'application-name' ] ) )
			{
				echo 'Application name not set.';
				exit( 1 );
			}

			// get the application name
			$sApplication = $aConfig[ 'application-name' ];

			// get the error message for this code
			$sMessage = GetErrorMessage( $_GET[ 'error' ] );

			// output the error page
			echo $oPresentation->GetErrorPage( $sApplication, $sMessage );
		}
		else
		{
			// no error, redirect to home page
			header( 'Location: ' . GetHost() );
			exit( 1 );
		}
	}
	catch( Exception $oException )
	{
		// handle the exception differently because this page is where it would normally end up
		try
		{
			// log this as being VERY bad
			cLogger::Log( 'error', 'Could not display error code to user. Exception: ' . $oException->getErrorMessage() );
		}
		catch( Exception $e ) {}

		// display last resort error message
		echo 'An unexpected error has occured.';
		exit( 1 );
	}
?>