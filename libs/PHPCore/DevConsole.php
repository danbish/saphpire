<?php
	/**
	 * Developer console.
	 *
	 * Displays config files, log files, and phpinfo().
	 *
	 * @author  Ryan Masters
	 *
	 * @package Core_0
	 * @version 0.1
	 */
	try
	{
		require_once( '../../config.php' );
		require_once( sCORE_INC_PATH . '/classes/cBaseAuth.php' );
		require_once( sCORE_INC_PATH . '/includes/DevBootstrap.php' );

		// only display console if user is allowed to see it
		if( sAPPLICATION_ENV != 'prod' || IsDevLoggedIn() )
		{
			// build beacon info
			$sBeacon = $oBusiness->GetBeacon();
			$sCoreBeacon = $oBusiness->GetCoreBeacon();

			// get database connection info
			$aDbConnections = $oBusiness->GetDatabaseConnections();

			// capture phpinfo so we can show it when we need to
			ob_start();
			phpinfo();
			$sPHPInfo = ob_get_contents();
			ob_end_clean();

			// output the page
			echo $oPresentation->GetDevConsolePage(
				$oBusiness->HandleLogForm(),
				$oBusiness->HandleConfigForm(),
				$sPHPInfo,
				$sBeacon,
				$sCoreBeacon,
				$aDbConnections
			);
		}
		else
		{
			// let us know who's trying to get in
			cLogger::Log( 'warning', "User \"$sUser\" is trying to get into the dev console when they don't have access." );

			// redirect to the home page, but let us know who's trying to view it
			header( 'Location: ' . GetHost() );
		}
	}
	catch( Exception $e )
	{
		ExceptionHandler( $e );
	}
?>