<?php
	/**
	 * Global functions available to the application.
	 *
	 * @author  Ryan Masters
	 *
	 * @package Core_0
	 * @version 0.2
	 */

	/**
	 * Convenience function to return configuration settings.
	 *
	 * @param	boolean		$bGetEverything		True to get all config info,
	 * 											false for only environment data.
	 *
	 * @return	&array 		Reference to original array.
	 */
	function GetConfig( $bGetEverything = false )
	{
		// get access to the configs
		global $aConfigs;

		// check if the environment is set
		if( isset( $aConfigs[ sAPPLICATION_ENV ] ) && !$bGetEverything )
		{
			return $aConfigs[ sAPPLICATION_ENV ];
		}

		return $aConfigs;
	}

	/**
	 * Returns the current host.
	 *
	 * @return string
	 */
	function GetHost()
	{
		// check if we're running from a browser
		if( isset( $_SERVER[ 'HTTP_HOST' ] ) )
		{
			$sHost = strtolower( $_SERVER[ 'HTTP_HOST'] );
		}
		// otherwise, it's from the command line
		else
		{
			$sHost = gethostname();
		}

		return $sHost;
	}

	/**
	 * Returns either all contacts or all contacts with the given criteria.
	 *
	 * @param	string	$aCriteria OPTIONAL Array of nodes and values that is
     *                                      used to limit contacts returned.
	 *
	 * @return	array
	 */
    function GetContacts( $aCriteria = array() )
    {
        // initialize  the return array
        $aContacts = array();

        // get the contacts for this app
        $aConfig = GetConfig( true );
        if( isset( $aConfig[ 'contact' ] ) )
        {
            // get the contacts
            $aContacts = $aConfig[ 'contact' ];

            // format into an array if needed
            if( !is_array( $aContacts ) )
            {
                $aContacts = array( $aContacts );
            }

            // check if options were provided
            if( !empty( $aCriteria ) && is_array( $aCriteria ) )
            {
                // remove users that do not meet the criteria
                $iContactCount = count( $aContacts );
                for( $i = 0; $i < $iContactCount; ++$i )
                {
                    // cycle through the criteria
                    foreach( $aCriteria as $sNode => $sValue )
                    {
                        // remove contacts that do not meet the criteria
                        if( isset( $aContacts[ $i ][ $sNode ] )
                            && ( is_array( $aContacts[ $i ][ $sNode ] ) && !in_array( $sValue, $aContacts[ $i ][ $sNode ] ) )
                               || ( ( !is_array( $aContacts[ $i ][ $sNode ] ) ) && $aContacts[ $i ][ $sNode ] != $sValue ) )
                        {
                            unset( $aContacts[ $i ] );
                            break;
                        }
                    }
                }

                // reset array keys
                sort( $aContacts );
            }
        }

        return $aContacts;
    }

	/**
	 * Returns the name of the application as defined in app.xml.
	 *
	 * @return string
	 */
	function GetApplicationName()
	{
		// get the configs
		$aConfigs = GetConfig( true );

		if( isset( $aConfigs[ 'application-name' ] ) )
		{
			if( !is_string( $aConfigs[ 'application-name' ] ) )
			{
				throw new Exception( 'Configuration option "application-name" is not a string.' );
			}
			return $aConfigs[ 'application-name' ];
		}
		else
		{
			throw new Exception( 'Set application-name in app.xml' );
		}
	}

	/**
     * Returns whether or not a developer is currently logged in.
     *
     * @return boolean
     */
    function IsDevLoggedIn()
    {
        // initialize  dev override flag
        $bDevLoggedIn = false;

        // try to get the currently logged in user
        $sUser = cBaseAuth::GetUser();

        // only check contacts if a user is logged in
        if( !empty( $sUser ) )
        {
            // check if user is in the contacts for this app
            $aConfig = GetConfig( true );
            if( isset( $aConfig[ 'contact' ] ) )
            {
                // format into an array if needed
                if( !is_array( $aConfig[ 'contact' ] ) )
                {
                    $aConfig[ 'contact' ] = array( $aConfig[ 'contact' ] );
                }

                // check if the user logged in is a developer
                $iContactCount = count( $aConfig[ 'contact' ] );
                for( $i = 0; $i < $iContactCount; ++$i )
                {
                    if( strtolower( $aConfig[ 'contact' ][ $i ][ 'username' ] ) === strtolower( $sUser )
                        && $aConfig[ 'contact' ][ $i ][ 'role' ] === 'dev'
                      )
                    {
                        $bDevLoggedIn = true;
                        break;
                    }
                }
            }
            else
            {
                throw new Exception( 'No contacts have been added for this application.' );
            }
        }

        return $bDevLoggedIn;
    }
?>