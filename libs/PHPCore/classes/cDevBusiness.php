<?php
    require_once( sCORE_INC_PATH . '/classes/cBaseBusiness.php' );
    require_once( sCORE_INC_PATH . '/classes/cLogger.php' );
    require_once( sCORE_INC_PATH . '/classes/cBaseConfig.php' );
    require_once( sCORE_INC_PATH . '/classes/cFileUtilities.php' );
    require_once( sCORE_INC_PATH . '/classes/cBaseConfig.php' );
    require_once( sCORE_INC_PATH . '/classes/cDbAbs.php' );

    /**
     * Business functionality for developers.
     *
     * @author  Ryan Masters
     *
     * @package Core_0
     * @version 0.2
     */
    class cDevBusiness extends cBaseBusiness
    {
        public function HandleLogForm()
        {
            try
            {
                // get access to form utilities
                $oUtilities = new cFormUtilities();

                // initialize the contents to return
                $sFile     = '';
                $sContents = '';
                $aLogFiles = cLogger::GetLogFiles();

                // check if the form has been submitted
                if( $oUtilities->IsFormSubmitted( 'clearLogBefore' ) && $oUtilities->IsValid() )
                {
                    // get the file
                    $aData = $oUtilities->GetCleanFormData();
                    $sFile = $aData[ 'logFile' ];

                    // clear the log file
                    if( substr( $sFile, -4 ) === 'xml' )
                    {
                        $bSuccess = cLogger::ClearXmlLogBefore( $sFile, $aData[ 'number' ] * $aData[ 'length' ] );
                        if( $bSuccess === false )
                        {
                            throw new Exception( 'Could not clear contents.' );
                        }
                    }
                    else
                    {
                        // @TODO: clean non-xml based log file
                        throw new Exception( 'Non-XML log file clearing is not suported yet.' );
                    }

                    // get the contents of the file
                    $sContents = cLogger::GetLogContents( $sFile );
                }
                elseif( $oUtilities->IsFormSubmitted( 'clearLog' ) )
                {
                    // get the file
                    $aData = $oUtilities->GetFormData();
                    $sFile = $aData[ 'logFile' ];

                    // clear the file contents
                    $bSuccess = cLogger::ClearLogFile( $sFile );
                    if( $bSuccess === false )
                    {
                        throw new Exception( 'Could not clear contents.' );
                    }
                }
                elseif( $oUtilities->IsFormSubmitted() )
                {
                    // get the file
                    $aData = $oUtilities->GetFormData();
                    $sFile = $aData[ 'logFile' ];

                    // get the contents of the file
                    $sContents = cLogger::GetLogContents( $sFile );
                }
                // otherwise, load the first log file
                elseif( !empty( $aLogFiles ) )
                {
                    $sFile     = $aLogFiles[ 0 ];
                    $sContents = cLogger::GetLogContents( $sFile );
                }

                return array( $sFile, $sContents, $aLogFiles );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        public function HandleConfigForm()
        {
            try
            {
                // get access to form utilities
                $oUtilities = new cFormUtilities();

                // initialize the contents to return
                $sFile     = '';
                $sContents = '';
                $oConfig      = new cBaseConfig();
                $aConfigFiles = $oConfig->GetConfigFiles();

                // check if the form has been submitted
                if( $oUtilities->IsFormSubmitted( 'submitConfig' ) )
                {
                    // get the file
                    $aData = $oUtilities->GetFormData();
                    $sFile = $aData[ 'configFile' ];

                    // get the contents of the file
                    $sContents = $oConfig->GetConfigContents( $sFile );
                }
                elseif( $oUtilities->IsFormSubmitted( 'saveConfig' ) )
                {
                    // get the data
                    $aData = $oUtilities->GetFormData();
                    $sFile = $aData[ 'configFile' ];

                    // save the new version of the file
                    $oConfig->Write( $sFile, stripcslashes( $aData[ 'configContents' ] ) );

                    // get the contents of the newly saved file
                    $sContents = $oConfig->GetConfigContents( $sFile );
                }
                // otherwise, load the first log file
                elseif( !empty( $aConfigFiles ) )
                {
                    $sFile     = $aConfigFiles[ 0 ];
                    $sContents = $oConfig->GetConfigContents( $sFile );
                }

                return array( $sFile, $sContents, $aConfigFiles );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Reads package and version information for the the core files.
         *
         * Reads anything in the classes or includes as well as config, Error, and DevConsole.
         * Update if any new files are created outside of the classes or includes directories.
         *
         * @return  string  Hashed version of core files.
         */
        public function GetBeacon()
        {
            try
            {
                // get all the core files
                $aBeaconFiles = array_merge(
                    cFileUtilities::GetDirectoryContents( sCORE_INC_PATH . DIRECTORY_SEPARATOR . 'classes', array( 'include_path', 'files_only' ) ),
                    cFileUtilities::GetDirectoryContents( sCORE_INC_PATH . DIRECTORY_SEPARATOR . 'includes', array( 'include_path', 'files_only' ) ),
                    array(
                        sBASE_INC_PATH . DIRECTORY_SEPARATOR . 'config.php',
                        sCORE_INC_PATH . DIRECTORY_SEPARATOR . 'Error.php',
                        sCORE_INC_PATH . DIRECTORY_SEPARATOR . 'DevConsole.php'
                    )
                );

                // initialize  beacon hash
                $sBeaconHash = '';

                // pull out package and version information in the files
                $iBeaconCount = count( $aBeaconFiles );
                for( $i = 0; $i < $iBeaconCount; ++$i )
                {
                    // get the file contents
                    $sContents = file_get_contents( $aBeaconFiles[ $i ] );

                    // add the file name to the hash
                    $sBeaconHash .= basename( $aBeaconFiles[ $i ] );

                    // look for package information
                    preg_match( "/@package[[:blank:]]+[a-zA-Z_0-9]+/", $sContents, $aMatches );
                    if( isset( $aMatches[ 0 ] ) )
                    {
                        // add package information to the hash
                        $sBeaconHash .= preg_replace( "/@package[[:blank:]]+/", '', $aMatches[ 0 ] );
                    }

                    // look for version information
                    preg_match( "/@version[[:blank:]]+[0-9]+\.[0-9]+/", $sContents, $aMatches );
                    if( isset( $aMatches[ 0 ] ) )
                    {
                        // add version information to the hash
                        $sBeaconHash .= preg_replace( "/@version[[:blank:]]+/", '', $aMatches[ 0 ] );
                    }
                }

                return md5( $sBeaconHash );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Talks to the core beacon service to get the newest beacon hash.
         *
         * Update when service is created.
         *
         * @return string
         */
        public function GetCoreBeacon()
        {
            // @todo: update this to talk to core beacon service
            return false;
        }

        function CheckPort( $sServer, $iPort, $iTimeout = 5 )
        {
            // open socket to port...
            $bPass = false;
            if ( $sServer && $iPort && $iTimeout )
            {
                if ( @fsockopen("$sServer", $iPort, $iErrno, $sErrstr, $iTimeout) )
                {
                    $bPass = true;
                }
            }

            return $bPass;
        }

        public function GetDatabaseConnections()
        {
            try
            {
                // initialize list of databases and whether or not we can connect to them
                $aConnectionStatuses = array();

                // initialize  list of ports to check
                $aPorts = array();

                // get a config handling object
                $oConfig = new cBaseConfig();

                // check if db file exists
                if( file_exists( $oConfig->GetConfigDirectory() . DIRECTORY_SEPARATOR . 'db.xml' ) )
                {
                    // read contents of files into an array
                    $aDbConfigs = $oConfig->Read( 'db.xml' );
                    $aDbConfigs = $aDbConfigs[ sAPPLICATION_ENV ];

                    foreach( $aDbConfigs as $sDb => $aOptions )
                    {
                        // make sure everything is formatted correctly
                        foreach( $aOptions as $sKey => $vValue )
                        {
                            if( is_array( $vValue ) )
                            {
                                if( empty( $vValue ) )
                                {
                                    $aOptions[ $sKey ] = '';
                                }
                                elseif( count( $vValue ) == 1 )
                                {
                                    $aOptions[ $sKey ] = $vValue[ key( $vValue ) ];
                                }
                                else
                                {
                                    throw new Exception( 'Too may values for option "' . $sKey . '" in db.xml' );
                                }
                            }
                        }

                        // initialize db connection data
                        $aConnectionStatuses[ $sDb ] = array();
                        $aConnectionStatuses[ $sDb ][ 'adapter' ] = $aOptions[ 'sAdapter' ];
                        $aConnectionStatuses[ $sDb ][ 'host' ]    = $aOptions[ 'sHost' ];

                        // add port if possible
                        if( isset( $aOptions[ 'iPort' ] ) )
                        {
                            $aPorts[ $sDb ] = $aOptions[ $iPort ];
                        }
                        elseif( strtolower( $aOptions[ 'sAdapter' ] ) == 'oracle' )
                        {
                            $aPorts[ $sDb ] = 1521;
                        }
                        elseif( strtolower( $aOptions[ 'sAdapter' ] ) == 'mysql' )
                        {
                            $aPorts[ $sDb ] = 3306;
                        }

                        // check if this is an oracle connection and the oci8 extension is loaded
                        if( strtolower( $aOptions[ 'sAdapter' ] ) == 'oracle' && !extension_loaded( 'oci8' ) )
                        {
                            $aConnectionStatuses[ $sDb ][ 'connection' ] = 'oci8 extension is not loaded.';
                            continue;
                        }

                        try
                        {
                            // try to make a connection
                            cDbAbs::GetDbObj( $aDbConfigs, $sDb );

                            // if object was made, save this
                            $aConnectionStatuses[ $sDb ][ 'connection' ] = true;
                        }
                        catch( Exception $oException )
                        {
                            $aConnectionStatuses[ $sDb ][ 'connection' ] = $oException->GetMessage();
                        }
                    }

                    // check if ports are open
                    foreach( $aPorts as $sDb => $iPort )
                    {
                        $aConnectionStatuses[ $sDb ][ 'port'] = $iPort;
                        $aConnectionStatuses[ $sDb ][ 'port_connection' ] = $this->CheckPort( $sDb, $iPort );
                    }
                }

                return $aConnectionStatuses;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>