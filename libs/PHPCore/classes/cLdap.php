<?php
    require_once( sCORE_INC_PATH . '/classes/cWebValidation.php' );

    /**
     * The Lightweight Directory Access Protocol is an application protocol for accessing and maintaining
     * distributed directory information services over an (IP) network.
     *
     * @author  Dana Freudenberger
     *
     * @package Core_0.1
     * @version 0.3
     */
    class cLdap
    {
        /**
         * ldap error code number
         */
        const iLDAP_ERR_CODE = -4;

        /**
         * url to use
         *
         * @var string
         */
        private $sUrl = '';

        /**
         * used to refer to the "user" that we're logging in as.
         *
         * @var string
         */
        private $sBindRdn = '';

        /**
         * "dn" is the distinguished name of the entry; it is neither an attribute nor a part of the entry
         * tells the search function where to look for results
         *
         * @var string
         */
        private $sDn = '';

        /**
         * port to make the connection on
         *
         * @var integer
         */
        private $iPort = 389;

        /**
         * Password to use
         *
         * @var string
         */
        private $sPassword = '';

        /**
         * LDAP connection object to use
         *
         * @var object
         */
        private $oConnection = null;

        /**
         * Constructor for the ldap class.
         *
         * @param  array  $aSpecConf      array of all configuration settings
         * @param  string $sTag           name of the connection
         *
         * @throws Exception              rethrows anything caught at a lower level
         *                                thrown if the url property is empty or not a string
         *                                thrown if the base_dn property is empty or not a string
         */
        public function __construct( array $aSpecConf, $sTag )
        {
            try
            {
                // check for the existence of this connection
                if( ( !isset( $aSpecConf[ $sTag ] ) && is_string( $sTag ) ) || !is_array( $aSpecConf ) )
                {
                    throw new Exception( 'The specified LDAP connection does not exist in ldap.xml.' );
                }
                else
                {
                    // check for the existence of the url field
                    if( !empty( $aSpecConf[ $sTag ][ 'url' ] ) )
                    {
                        // get a webvalidation object to check the url
                        $oWebValidation = new cWebValidation();

                        // make sure the url is a valid url
                        if( $oWebValidation->ValidateURL( $aSpecConf[ $sTag ][ 'url' ] ) === true )
                        {
                            $this->sUrl = $aSpecConf[ $sTag ][ 'url' ];
                        }
                        // url isn't a valid url, tell the user to fix it
                        else
                        {
                            throw new Exception( 'The URL of the LDAP connection: ' . $sTag . ' is not a valid url.' );
                        }
                    }
                    // it's either not set or set and empty
                    else
                    {
                        throw new Exception( 'The LDAP connection: ' . $sTag . ' is missing a URL.');
                    }

                    // check for the existence of the base directory field
                    if( !empty( $aSpecConf[ $sTag ][ 'dn' ] ) )
                    {
                        // make sure the base directory is a string
                        if( is_string( $aSpecConf[ $sTag ][ 'dn' ] ) )
                        {
                            $this->sDn = $aSpecConf[ $sTag ][ 'dn' ];
                        }
                        // base directory isn't a string, tell the user to fix it
                        else
                        {
                            throw new Exception( 'The base directory of the LDAP connection: ' . $sTag . ' is malformed.' );
                        }
                    }
                    // it's either not set or set and empty
                    else
                    {
                        throw new Exception( 'The LDAP connection: ' . $sTag . ' is missing a base directory.');
                    }

                    // port is optional and defaults to 389
                    if( !empty( $aSpecConf[ $sTag ][ 'port' ] ) && is_numeric( $aSpecConf[ $sTag ][ 'port' ] ) )
                    {
                        // only set the field if what is entered is non-emtpy and numeric
                        $this->iPort = $aSpecConf[ $sTag ][ 'port' ];
                    }

                    // these 2 fields are optional, won't use ldap_bind without them...
                    if( !empty( $aSpecConf[ $sTag ][ 'bindrdn' ] ) && is_string( $aSpecConf[ $sTag ][ 'bindrdn' ] ) )
                    {
                        // only set the field if what's entered is non-empty and a string
                        $this->sBindRdn = $aSpecConf[ $sTag ][ 'bindrdn' ];
                    }

                    if( !empty( $aSpecConf[ $sTag ][ 'password' ] ) && is_string( $aSpecConf[ $sTag ][ 'password' ] ) )
                    {
                        // only set the field if what's entered is non-empty and a string
                        $this->sPassword = $aSpecConf[ $sTag ][ 'password' ];
                    }
                }
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Creates a connection to the ldap service.
         *
         * @throws Exception rethrows anything caught at a lower level
         *                   thrown if $this->oConnection is invalid
         *                   thrown if ldap_bind fails
         *
         */
        public function CreateConnection()
        {
            try
            {
                // check if a connection exists
                if( $this->oConnection === null )
                {
                    // default the bind boolean to true. if we don't call it we want to not throw an exception
                    $bLdapBind = true;

                    // try to create the connection
                    $this->oConnection = ldap_connect( $this->sUrl, $this->iPort );

                    // throw an exception if connection fails
                    if ( !$this->oConnection )
                    {
                        $aError = ldap_error( $this->oConnection );
                        throw new Exception( htmlentities( 'Could not connect to ldap service ' . $this->sUrl
                                                           . '. LDAP Error Message: ' . $aError[ 'message' ], ENT_QUOTES ), iLDAP_ERR_CODE );
                    }

                    // check for credentials to bind on
                    if( !empty( $this->sBindRdn ) && !empty( $this->sPassword ) )
                    {
                        // try to bind using credentials
                        $bLdapBind = ldap_bind( $this->oConnection, $this->sBindRdn, $this->sPassword );
                    }

                    // throw an exception if binding failed
                    if( !$bLdapBind )
                    {
                        throw new Exception( htmlentities( 'Could not bind to ldap service ' . $this->sUrl
                                                           . ' using base_rdn ' . $this->sBindRdn
                                                           . ' and password ' . $this->sPassword
                                                           . '. LDAP Error Message: ' . $aError[ 'message' ], ENT_QUOTES ), iLDAP_ERR_CODE );
                    }
                }
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * accepts a username/password combo and attempts to login to the ldap server
         *
         * @param string $sUserName username attempting to login
         * @param string $sPassword password of user
         *
         * @throws Exception rethrows anything caught a lower level
         *
         * @return boolean true:  the user submitted a valid username/password combo
         *                 false: the user didn't submit a valid username/password combo
         */
        public function AuthUser( $sUserName, $sPassword )
        {
            try
            {
                // initalize
                $this->CreateConnection();
                $bLdapBind = false;

                // check for credentials to bind on
                if( !empty( $sUserName ) && !empty( $sPassword ) )
                {
                    // try to bind using credentials
                    // @ symbol suppresses errors so you don't have
                    // people mistyping something and it splashing up to the screen
                    $bLdapBind = @ldap_bind( $this->oConnection, 'cn=' . $sUserName .',ou=users,ou=people,o=cuid', $sPassword );
                }

                return $bLdapBind;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Ensures that the search arguments provided is a string
         * and returns the search trimmed of preceding
         * or trailing whitespace.
         *
         * @param  string    $sSearch  Search to validate and trim.
         *
         * @throws Exception           rethrows anything caught at a lower level
         *                             Thrown if query provided is not a string or is empty.
         *
         * @return string    $sTrimmed Search trimmed of whitespace.
         */
        public function CleanSearch( $sSearch )
        {
            try
            {
                // check if the query was supplied correctly
                if( !is_string( $sSearch ) )
                {
                    throw new Exception( 'Search provided is not a string', 4 );
                }

                // trim the query
                $sTrimmed = trim( $sSearch );

                // check if the query is not empty
                if( empty( $sTrimmed ) )
                {
                    throw new Exception( 'Search provided is empty.' );
                }

                return $sTrimmed;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Runs an LDAP search with the specified parameters
         *
         * @param    string      $sSearch     search string to use
         *                                    ( ie. $sSearch = 'sn=Smith', for more info on ldap query syntax go to:
         *                                      http://technet.microsoft.com/en-us/library/aa996205(v=exchg.65).aspx )
         * @param    array       $aAttributes list of attributes to return
         *                                    ( ie. array(
         *                                              0 => sn,
         *                                              1 => cn
         *                                          )
         *                                    )
         * @param    integer     $iBehavior   return list of attrs (1), return attr => value pairs (0)
         * @param    integer     $iSizeLimit  number of entries to return
         * @param    integer     $iTimeLimit  number of seconds to run before error
         *
         * @throws   Exception                rethrows anything caught at a lower level
         *                                    ldap_search encounters an error
         *
         * @return   array       $aResults    results of the ldap search
         *                       ie. $aResults = array(
         *                           'count' => 2,
         *                           0 => array( 'sn' => Freudenberger ),
         *                           1 => array( 'sn' => Masters )
         *                       )
         */
        public function GetSearchResults( $sSearch, array $aAttributes = array(), $iBehavior = 0, $iSizeLimit = 0, $iTimeLimit = 0 )
        {
            try
            {
                // clean the search request
                $sCleanSearch  = $this->CleanSearch( $sSearch );

                // ensure that there is a connection
                $this->CreateConnection();

                // set up an empty results array
                $aResults = array();

                // run the "query" and get the search result object
                $oSearchResults = ldap_search( $this->oConnection, $this->sDn, $sCleanSearch, $aAttributes, $iBehavior, $iSizeLimit );

                // make sure the search returned something valid
                if( $oSearchResults === false )
                {
                    // it didn't so throw an exception
                    $sErrNo      = ldap_errno( $this->oConnection );
                    $sErrMessage = ldap_error( $this->oConnection );

                    throw new Exception( 'ldap_search returned the following error: ' . $sErrNo . ' ' . $sErrMessage );
                }
                else
                {
                    // set the search result property
                    $this->oSearchResults = $oSearchResults;

                    // make the call to actually to get the results
                    $aResults = ldap_get_entries( $this->oConnection, $this->oSearchResults );

                    // check for an error thrown by ldap_get_entries
                    if( $aResults === false )
                    {
                        $sErrNo      = ldap_errno( $this->oConnection );
                        $sErrMessage = ldap_error( $this->oConnection );

                        throw new Exception( 'ldap_get_entries returned the following error: ' . $sErrNo . ' ' . $sErrMessage );
                    }
                }

                return $aResults;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Runs an LDAP search with the specified parameters
         * Returns the first result
         *
         * @param    string      $sSearch     LDAP search to run ( see entry on GetSearchResults for more info )
         * @param    array       $aAttributes list of attributes to return ( see entry on GetSearchResults for more info )
         *
         * @throws   Exception                rethrows anything caught at a lower level
         *
         * @return   array       $aResults    results of the ldap search
         *                       ie. $aResults = array(
         *                           'count' => 1,
         *                           'sn' => Freudenberger
         *                       )
         */
        public function GetSingleSearchResults( $sSearch, array $aAttributes = array() )
        {
            try
            {
                // get results of the search
                $aResult  = array( 'count' => 0 );
                $aResults = $this->GetSearchResults( $sSearch, $aAttributes );

                // pull out only the result we want
                // valid ldap searches return an array with at least 1 entry (ie array( 'count' => 0 ))
                // therefore we only have actual results if there are more than entries in the result array
                if( count( $aResults ) > 1 )
                {
                    $aResult            = $aResults[ 0 ];
                    $aResult[ 'count' ] = 1;
                }

                return $aResult;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Runs an LDAP search and limits between the upper and lower bounds.
         *
         * @param  string    $sSearch      LDAP search string ( see entry on GetSearchResults for more info )
         * @param  array     $aAttributes  list of attributes to return ( see entry on GetSearchResults for more info )
         * @param  int       $iLowerBound  Lower bounds to apply to the results.
         * @param  int       $iUpperBound  Upper bounds to apply to the results.
         *
         * @throws Exception               rethrows anything caught at a lower level
         *                                 $iLowerBound > $iUpperBound
         *                                 result that should be in the limited range isn't there
         *
         * @return array     $aReturn      The results of the query limited by the lower and upper bounds.
         */
        public function GetLimitResults( $sSearch, array $aAttributes, $iLowerBound, $iUpperBound )
        {
            try
            {
                // check for valid bounds
                if( $iUpperBound < $iLowerBound )
                {
                    // reverse them.. people can be stupid
                    $iSwap       = $iLowerBound;
                    $iLowerBound = $iUpperBound;
                    $iUpperBound = $iSwap;
                }

                // initialize variables
                $aResults     = $this->GetSearchResults( $sSearch, $aAttributes );
                $iLimitSize   = $iUpperBound - $iLowerBound;
                $iResultCount = $aResults[ 'count' ];

                // check if there are more results than the limit size
                if( $iLimitSize < $iResultCount )
                {
                    // loop through results
                    $j = 0;
                    for( $i = $iLowerBound; $i < $iUpperBound; ++$i )
                    {
                        // check existence of this data
                        if( isset( $aResults[ $i ] ) )
                        {
                            // put data in the return array
                            $aReturn[ $j ] = $aResults[ $i ];
                        }
                        else
                        {
                            throw new Exception( 'The requested result does not exist.' );
                        }
                    }

                    // set the count index
                    $aReturn[ 'count' ] = $iLimitSize;
                }
                // our limits are bigger than our result set
                else
                {
                    // return everything
                    $aReturn = $aResults;
                }

                return $aReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Just gets a search object for use by other functions
         *
         * @param  string    $sFilter     filter to search by ( see entry on GetSearchResults for more info )
         * @param  array     $aAttributes attributes to return ( see entry on GetSearchResults for more info )
         * @param  integer   $iBehavior   return list of attrs (1), return attr => value pairs (0)
         * @param  integer   $iSizeLimit  number of entries to return
         *
         * @throws Exception              rethrows anything it catches
         *
         * @return object    $oSearch     the search result object
         */
        public function GetSearchObject( $sFilter, array $aAttributes = array(), $iBehavior = 0, $iSizeLimit = 0 )
        {
            try
            {
                // ensure that there is a connection
                $this->CreateConnection();

                // make the ldap call
                $oSearch = ldap_search( $this->oConnection, $this->sDn, $sFilter, $aAttributes, $iBehavior, $iSizeLimit );
                return $oSearch;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Gets the unique identifier for the entry
         *
         * @param  object    $oEntry individual entry
         *                           ( use return values from GetFirstEntry and GetNextEntry for this )
         *
         * @throws Exception         rethrows anything it catches
         *
         * @return string    $sDn    string that uniquely identifies a record
         */
        public function GetDn( $oEntry )
        {
            try
            {
                // ensure that there is a connection
                $this->CreateConnection();

                // make the ldap call
                $sDn = ldap_get_dn( $this->oConnection, $oEntry );
                return $sDn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Gets the first entry from a list of search results
         *
         * @param  object    $oSearchResults results of a ldap_search call
         *
         * @throws Exception                 rethrows anything it catches
         *
         * @return object    $oEntry         the first entry
         */
        public function GetFirstEntry( $oSearchResults )
        {
            try
            {
                // ensure that there is a connection
                $this->CreateConnection();

                // make the ldap call
                $oEntry = ldap_first_entry( $this->oConnection, $oSearchResults );
                return $oEntry;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Gets the next entry, meant to be called after GetFirstEntry()
         *
         * @param  object    $oEntry the preceding entry
         *
         * @throws Exception         rethrows anything it catches
         *
         * @return object    $oEntry the next entry
         */
        public function GetNextEntry( $oEntry )
        {
            try
            {
                // ensure that there is a connection
                $this->CreateConnection();

                // make the ldap call
                $oEntry = ldap_next_entry( $this->oConnection, $oEntry );
                return $oEntry;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Gets the attributes from an entry
         *
         * @param  object    $oEntry      entry to retireve attibutes/attribute values for
         *                                ( use return values from GetFirstEntry and GetNextEntry for this )
         *
         * @throws Exception              rethrows anything it catches
         *
         * @return object    $aAttributes attributes and attribute values for the entry
         */
        public function GetAttributes( $oEntry )
        {
            try
            {
                // ensure that there is a connection
                $this->CreateConnection();

                // make the ldap call
                $aAttributes = ldap_get_attributes( $this->oConnection, $oEntry );
                return $aAttributes;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Gets the attributes of an entry specified by sDn
         *
         * @param string  $sDn         where to look for entry ( use output of GetDn )
         * @param string  $sFilter     which entries to return ( see entry on GetSearchResults for more info )
         * @param array   $aAttributes what attributes of the entry to return ( see entry on GetSearchResults for more info )
         * @param integer $iBehavior   0 or 1: 0 for normal behavior, 1 for attribute types only
         *
         * @throws Exception rethrows anything it catches
         *
         * @return array $aResult the attributes/attribute values of the result
         */
        public function Read( $sDn, $sFilter, array $aAttributes = array(), $iBehavior = 0 )
        {
            try
            {
                // ensure that there is a connection
                $this->CreateConnection();

                // make the ldap call
                $aResult = ldap_read( $this->oConnection, $sDn, $sFilter, $aAttributes, $iBehavior );
                return $aResult;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Gets the number of entries returned by a search
         *
         * @param  object    $oSearchResults search object already generated ( output of GetSearchObject )
         *
         * @throws Exception                 rethrows anything it catches
         *
         * @return integer   $iEntryCount    the number of entries in the search
         */
        public function GetEntryCount( $oSearchResults )
        {
            try
            {
                // ensure that there is a connection
                $this->CreateConnection();

                // make the ldap call
                $iEntryCount = ldap_count_entries( $this->oConnection, $oSearchResults );
                return $iEntryCount;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>