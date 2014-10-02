<?php
    // get the interface that we're implementing
    require_once( sCORE_INC_PATH . '/../PHPCoreExtensions/ifConfigExtension.php' );

    /**
     * Loads environment variable information into the configuration.
     *
     * Options allowed for this extension:
     *    put  - A value to save into the configuration. Can be a string or an array.
     *           If value is a string, it can be an ini-style period delimited array structure.
     *           If the value is a period delimited array structure, the first string before a period
     *           must reference a key mapping to an environment variable.
     *           Key mapping:
     *               globals - $GLOBALS
     *               server  - $_SERVER
     *               get     - $_GET
     *               post    - $_POST
     *               files   - $_FILES
     *               cookie  - $_COOKIE
     *               session - $_SESSION
     *               request - $_REQUEST
     *               env     - $_ENV
     *    as   - Optional key to save value as in the array.
     *    into - Location to save the value. Can be an ini-style period delimited array structure.
     *
     * Example option set:
     *     <option>
     *         <put>ServerDb</put>
     *         <into>prod.server-db</into>
     *         <as>sDbName</as>
     *     </option>
     *
     * Evaluates to:
     *     array(
     *         'prod' => array(
     *             'server-db' => array(
     *                 'sDbName' => ServerDb
     *             )
     *         )
     *     )
     *
     * @author  Ryan Masters
     *
     * @package Core_0
     * @version 0.1
     */
    class cConfigExtEnv implements ifConfigExtension
    {
        /**
         * Returns the environment variable referenced by the key provided.
         *
         * Key mapping:
         *    globals - $GLOBALS
         *    server  - $_SERVER
         *    get     - $_GET
         *    post    - $_POST
         *    files   - $_FILES
         *    cookie  - $_COOKIE
         *    session - $_SESSION
         *    request - $_REQUEST
         *    env     - $_ENV
         *
         * @param  string  $sKey  Key for an environment variable.
         *
         * @return array
         */
        public function GetEnvVar( $sKey )
        {
            switch( $sKey )
            {
                case 'globals' :
                    return $GLOBALS;
                case 'server' :
                    return $_SERVER;
                case 'get' :
                    return $_GET;
                case 'post' :
                    return $_POST;
                case 'files' :
                    return $_FILES;
                case 'cookie' :
                    return $_COOKIE;
                case 'session' :
                    return $_SESSION;
                case 'request' :
                    return $_REQUEST;
                case 'env' :
                    return $_ENV;
                default:
                    throw new Exception( "Environment key '$sKey' does not exist." );
            }
        }

        /**
         * Reads a value from a ini-style period delimited array structure.
         *
         * @param  string  $sValueString
         *
         * @return mixed
         */
        public function GetValueFromString( $sValueString )
        {
            // initialize the return value
            $vValue = null;

            // break apart string into keys
            $aKeys = explode( '.', $sValueString );

            // get the environment variable this is referencing
            $aEnvVar = $this->GetEnvVar( strtolower( $aKeys[ 0 ] ) );

            // follow the path to try to find the variable
            $iKeyCount = count( $aKeys );
            for( $j = 1; $j < $iKeyCount; ++$j )
            {
                // check if the key exists
                if( isset( $aEnvVar[ $aKeys[ $j ] ] ) )
                {
                    // save the new value
                    $vValue  = $aEnvVar[ $aKeys[ $j ] ];

                    // set the new value as the new array to search
                    $aEnvVar = $aEnvVar[ $aKeys[ $j ] ];
                }
                else
                {
                    $vValue = null;
                }
            }

            return $vValue;
        }

         /**
         * Loads server information into the configuration.
         *
         * @param  array      $aOptions  Options to use while loading data.
         *
         * @throws Exception             Rethrows anything that is caught.
         *
         * @return array                 Data to merge into configuration.
         */
        public function Load( $aOptions = array() )
        {
            try
            {
                // initialize array to return
                $aMerge = array();

                // build out the array structure to return
                $iOptionCount = count( $aOptions );
                for( $i = 0; $i < $iOptionCount; ++$i )
                {
                    // check if the put value exists and is a string
                    if( !isset( $aOptions[ $i ][ 'put' ] ) )
                    {
                        throw new Exception( 'Value to save was not specified.' );
                    }

                    // get the value to save
                    $vValue = $aOptions[ $i ][ 'put' ];

                    // check if the value is an array reference
                    if( is_string( $aOptions[ $i ][ 'put' ] ) && strpos( $aOptions[ $i ][ 'put' ], '.' ) !== false )
                    {
                        $vValue = $this->GetValueFromString( $aOptions[ $i ][ 'put' ] );
                    }

                    // see if we want to save this as a specific key
                    if( isset( $aOptions[ $i ][ 'as' ] ) )
                    {
                        // check if the key is a string
                        if( !is_string( $aOptions[ $i ][ 'as' ] ) )
                        {
                            throw new Exception( 'Key to save value with is not a string. Value: <pre>' . print_r( $vValue, true ) . '</pre>' );
                        }

                        // save the value as a key => value pair.
                        $vValue = array( $aOptions[ $i ][ 'as' ] => $vValue );
                    }

                    // check if there is a location to store it into
                    if( !isset( $aOptions[ $i ][ 'into' ] ) )
                    {
                        throw new Exception( 'Location to save value was not specified.' );
                    }

                    // check if what we want to merge into is a string
                    if( !is_string( $aOptions[ $i ][ 'into' ] ) )
                    {
                        throw new Exception( 'Location to save value is not a string.' );
                    }

                    // set the value into the appropriate structure and merge with previously read values
                    $aMerge = array_merge_recursive(
                        $aMerge,
                        cStringUtilities::StringToArray(
                            $aOptions[ $i ][ 'into' ],
                            $vValue
                        )
                    );
                }

                return $aMerge;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>