<?php
    // get the interface that this class implements
    require_once( sCORE_INC_PATH . '/classes/ifDb.php' );

    /**
     * Default database class.
     *
     * @author  Ryan Masters
     * @author  James Upp
     * @author  Dana Freudenberger
     *
     * @package Core_0
     * @version 0.4
     */
    class cMySqlDb implements ifDb
    {
        /**
         * Name of the database to connect to.
         * Set in db configuration file.
         *
         * @var string
         */
        private $sDbName = '';

        /**
         * Username to use when connecting to database.
         * Set in db configuration file.
         *
         * @var string
         */
        private $sUsername = '';

        /**
         * Password to use when connecting to database.
         * Set in db configuration file.
         *
         * @var string
         */
        private $sPassword = '';

        /**
         * Host address to use when connecting to database.
         * Set in db configuration file.
         *
         * @var string
         */
        private $sHost = '';

        /**
         * Port to use when connecting to database.
         * Defaults to standard MySQL port, 3306.
         * Set in db configuration file.
         *
         * @var integer
         */
        private $iPort = 3306;

        /**
         * Number of attempts to make when connecting.
         * At least one attempt is made.
         * Set in db configuration file.
         *
         * @var integer
         */
        private $iRetries = 1;

        /**
         * PDO error mode to use.
         * Set in db configuration file.
         *
         * @var integer
         */
        private $iError = PDO::ERRMODE_SILENT;

        /**
         * Sets the character set to be used in the database connection.
         *
         * @var string
         */
        private $sCharset = 'utf8';

        /**
         * Database object.
         *
         * @var object
         */
        private $oConnection  = null;

        /**
         * List of MyISAM tables in the database. MyISAM does not allow transactions,
         * but other engines do. Transactions cannot be started if this string is not
         * empty.
         *
         * @var boolean
         */
        private $sMyISAMTables = '';

        /**
         * Binds variables to a result set before execution.
         *
         * @param  object $oResults
         * @param  array  $aBindVariables
         *
         * @throws Exception  Thrown if bind variables are not supplied correctly.
         *
         * @return null
         */
        protected function BindVariables( &$oResults, array $aBindVariables = array() )
        {
            try
            {
                // check if the correct amount of bind variable were used
                preg_match_all( '/:[a-zA-Z0-9_]+/', $oResults->queryString, $aMatches );
                if( !isset( $aMatches[ 0 ] ) || count( array_unique( $aMatches[ 0 ] ) ) != count( $aBindVariables ) )
                {
                    throw new Exception( 'Incorrect amount of bind params. '
                                         . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                         . print_r( $oResults->queryString, true )
                                         . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                         . print_r( $aBindVariables, true ),
                                         3 );
                }
                else
                {
                    $aMatches = $aMatches[ 0 ];
                }

                // bind all the variables if possible
                foreach( $aBindVariables as $sVariable => $vValue )
                {
                    // check if a string was provided
                    if( !is_string( $sVariable ) )
                    {
                        throw new Exception( 'String not provided for bind variable name: ' . print_r( $sVariable, true ), 3);
                    }

                    // For PDO, we have to pass in the colon, so if it's not there, add it.
                    if( substr( $sVariable, 0, 1 ) != ':' )
                    {
                        $sVariable = ':' . $sVariable;
                    }

                    // check if this variable is bound in the query
                    if( !in_array( $sVariable, $aMatches ) )
                    {
                        throw new Exception( "Parameter '$sVariable' not bound in query: "
                                             . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                             . print_r( $oResults->queryString, true ),
                                             3);
                    }

                    // Determine the type of variable and use the appropriate PDO parameter value.
                    // The standard default for when no parameter is specified
                    $iParam = PDO::PARAM_STR;
                    if( is_int( $vValue ) )
                    {
                        $iParam = PDO::PARAM_INT;
                    }
                    elseif( is_bool( $vValue ) )
                    {
                        $iParam = PDO::PARAM_BOOL;
                    }
                    elseif( is_null( $vValue ) )
                    {
                        $iParam = PDO::PARAM_NULL;
                    }

                    // try to bind the variable
                    if( !$oResults->bindValue( $sVariable, $vValue, $iParam ) )
                    {
                        throw new Exception( 'Could not bind variable ' . $sVariable . ' to : ' . print_r( $vValue, true ) );
                    }
                }
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Constructor for the MySQL adapter.
         *
         * @param array $aSpecConf      Array of database settings, taken from the db config
         *                              file and parsed by the cBaseConfig class
         * @throws Exception
         */
        public function __construct( array $aSpecConf = array() )
        {
            try
            {
                // Verify there is a valid database name and set it if there is
                if ( !isset( $aSpecConf[ 'sDbName' ] ) || empty( $aSpecConf[ 'sDbName' ] ) )
                {
                    throw new Exception( 'Missing sDbName setting.' );
                }
                $this->sDbName = $aSpecConf[ 'sDbName' ];

                // Verify there is a valid username and set it if there is
                if ( !isset( $aSpecConf[ 'sUsername' ] ) || empty( $aSpecConf[ 'sUsername' ] ) )
                {
                    throw new Exception( 'Missing sUsername setting.' );
                }
                $this->sUsername = $aSpecConf[ 'sUsername' ];

                // If a password is set, use it, otherwise leave it as empty string
                if ( isset( $aSpecConf[ 'sPassword' ] ) && !empty( $aSpecConf[ 'sPassword' ] ) )
                {
                    $this->sPassword = $aSpecConf[ 'sPassword' ];
                }

                // Verify there is a valid host and set it if there is
                if ( !isset( $aSpecConf[ 'sHost' ] ) || empty( $aSpecConf[ 'sHost' ] ) )
                {
                    throw new Exception( 'Missing sHost setting.' );
                }
                $this->sHost = $aSpecConf[ 'sHost' ];

                // If a port is set, use it, otherwise use the default
                if ( isset( $aSpecConf[ 'iPort' ] ) && !empty( $aSpecConf[ 'iPort' ] ) )
                {
                    $this->iPort = $aSpecConf[ 'iPort' ];
                }

                // If a character set is set, use it, otherwise use the default
                if ( isset( $aSpecConf[ 'sCharset' ] ) && !empty( $aSpecConf[ 'sCharset' ] ) )
                {
                    $this->sCharset = $aSpecConf[ 'sCharset' ];
                }

                // If there is a number of retries set, use it (and increment by 1 because the
                //  first try doesn't count). Else, leave it at the default 1.
                if ( isset( $aSpecConf[ 'iRetries' ] ) && !empty( $aSpecConf[ 'iRetries' ] ) && $aSpecConf[ 'iRetries' ] > 0 )
                {
                    $this->iRetries = $aSpecConf[ 'iRetries' ] + 1;
                }

                // Set the PDO error reporting level. Expected options are: 'EXCEPTION' 'WARNING' 'SILENT'
                if ( isset( $aSpecConf[ 'sErrorMode' ] ) && $aSpecConf[ 'sErrorMode' ] != '' )
                {
                    switch ( strtoupper( $aSpecConf[ 'sErrorMode' ] ) )
                    {
                        case "EXCEPTION":
                            $this->iError = PDO::ERRMODE_EXCEPTION;
                            break;
                        case "WARNING":
                            $this->iError = PDO::ERRMODE_WARNING;
                            break;
                        case "SILENT":
                        default:
                            $this->iError = PDO::ERRMODE_SILENT;
                            break;
                    }
                }
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Ensures that the query provided is a string
         * and returns the query trimmed of preceding
         * or trailing whitespace.
         *
         * @param  string    $sQuery  Query to validate and trim.
         *
         * @throws Exception Thrown if query provided is not a string or is empty.
         *
         * @return string    Query trimmed of whitespace.
         */
        public function CleanQuery( $sQuery )
        {
            try
            {
                // check if the query was supplied correctly
                if( !is_string( $sQuery ) )
                {
                    throw new Exception( 'Query provided is not a string', 3 );
                }

                // trim the query
                return trim( $sQuery );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Sets a connection to the database. If the first attempt does not succeed, it
         * will try a number of times equal to iRetries.
         * Sets the error mode after connection is made.
         *
         * @return null
         */
        public function GetConnection()
        {
            try
            {
                // check if a connection has been created
                if ( !( $this->oConnection instanceof PDO ) )
                {
                    // try to connect
                    $iRetryCount = 0;
                    $sError = '';
                    while ( $iRetryCount < $this->iRetries )
                    {
                        try
                        {
                            $this->oConnection = new PDO( 'mysql:host=' . $this->sHost . ';dbname=' . $this->sDbName, $this->sUsername, $this->sPassword );
                        }
                        catch ( PDOException $eError )
                        {
                            $sError = $eError->getMessage();
                        }
                        ++$iRetryCount;
                    }

                    // check if a connection was created
                    if ( !( $this->oConnection instanceof PDO ) )
                    {
                        throw new Exception( 'Could not connect to database host '
                                             . $this->sHost . ' using username '
                                             . $this->sUsername . '. MySQL Error Message: ' . $sError, 3 );
                    }
                    else
                    {
                        // Determine database engine to disallow transactions if neccessary.
                        $sQuery = 'SELECT TABLE_NAME,
                                          ENGINE
                                   FROM   information_schema.TABLES
                                   WHERE  TABLE_SCHEMA = :tableName
                                          AND ENGINE = \'MyISAM\'
                                   ORDER BY TABLE_NAME';
                        $aMyISAMTables = $this->GetQueryResults( $sQuery, array( 'tableName' => $this->sDbName ) );
                        $iTableCount = count ( $aMyISAMTables );
                        for( $i = 0; $i < $iTableCount; ++$i )
                        {
                            $this->sMyISAMTables .= $aMyISAMTables[ $i ][ 'TABLE_NAME' ] . ', ';
                        }

                        // trim off trailing comma
                        if( !empty( $this->sMyISAMTables ) )
                        {
                            $this->sMyISAMTables = substr( $this->sMyISAMTables, 0, -2 );
                        }
                    }

                    // set the error mode and the fetch mode
                    if( !$this->oConnection->setAttribute( PDO::ATTR_ERRMODE, $this->iError ) )
                    {
                        throw new Exception( 'Could not set error mode.', 3 );
                    }
                    if( !$this->oConnection->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC ) )
                    {
                        throw new Exception( 'Could not set fetch mode.', 3 );
                    }

                    // Set the character set
                    if( $this->oConnection->exec( 'SET CHARACTER SET ' . $this->sCharset ) === false )
                    {
                        throw new Exception( 'Could not set character set.', 3 );
                    }
                }

                return $this->oConnection;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Runs a SQL statement and returns true if the query was successful.
         *
         * @param   string      $sQuery           query to run
         * @param   array       $aBindVariables   array of bind variables, key => value
         *                                        Can also be used as an array of arrays of binds:
         *                                            key => ( value1, value2, value3 )
         *
         * @throws  Exception
         *
         * @return  boolean
         *
         * Examples:
         *   RunQuery( 'insert into `names` VALUES (null, :firstname, :lastname) ',
         *              array( 'firstname' => 'John', 'lastname' => 'Smith' ) );
         *   RunQuery( 'insert into `names` VALUES (null, :firstname, :lastname) ',
         *              array(
         *                  'firstname' => array( 'John', 'Clark', 'Bruce' ),
         *                  'lastname'  => array( 'Smith', 'Kent', 'Wayne' )
         *              )
         *            );
         *
         *
         */
        public function RunQuery( $sQuery, array $aBindVariables = array() )
        {
            try
            {
                // cleanup the query
                $sQuery = $this->CleanQuery( $sQuery );

                // ensure we have a connection and prepare the results
                $oPrepared = $this->GetConnection()->prepare( $sQuery );

                // check if the statement could be prepared
                if( $oPrepared === false )
                {
                    throw new Exception( 'Could not prepare statement: ' . $sQuery . ' with bind variables: ' . print_r( $aBindVariables, true ), 3 );
                }
                // prepare the bind variables, if any
                if( !empty( $aBindVariables ) )
                {
                    // initialize array of bind arrays
                    $aBindKeyValues = array();

                    // setup bind arrays appropriately
                    foreach( $aBindVariables as $sVariable => $vValue )
                    {
                        // If this is just a single set of bind variables,
                        // convert it to array so the format is the same.
                        if( !is_array( $vValue ) )
                        {
                            $vValue = array( $vValue );
                        }

                        // ensure we have a colon for the bind variable
                        if( substr( $sVariable, 0, 1 ) != ':' )
                        {
                            $sVariable = ':' . $sVariable;
                        }

                        // Check sub-array length and if it's different set the check count.
                        $iVarCount  = count( $vValue );
                        $iNextCount = count( next( $aBindVariables ) );
                        if ( $iNextCount > $iVarCount || $iNextCount < $iVarCount )
                        {
                            $iCheckCnt = $iNextCount > $iVarCount ? $iNextCount : $iVarCount;
                        }

                        // If we have a checkcount set and it differs from new count.
                        if ( isset( $iCheckCnt ) && $iCheckCnt !== $iVarCount )
                        {
                            // Make sure only one value is passed to replicate.
                            if ( $iVarCount === 1 )
                            {
                                // Propagate the array with default value.
                                $vValue    = array_fill( 0, $iCheckCnt, $vValue[ 0 ] );
                                $iVarCount = $iCheckCnt;
                            }
                        }
                        // For reach row, make a key/value pair
                        for( $i = 0; $i < $iVarCount; ++$i )
                        {
                            $aBindKeyValues[ $i ][ $sVariable ] = $vValue[ $i ];
                        }
                    }

                    // Now loop through each (or the only one) of the bind arrays, binding the variables and running
                    // the same query for each one.
                    $iVarCount = count( $aBindKeyValues );
                    for( $i = 0; $i < $iVarCount; ++$i )
                    {
                        // set the single bind array
                        $aSingleBind = array();

                        // Loop through each variable and bind it
                        foreach( $aBindKeyValues[ $i ] as $sBindName => $vValue )
                        {
                            // Bind the variable. Note that the way OCI works, we can't just use $sBindName.
                            $aSingleBind[ $sBindName ] = $vValue;
                        }

                        // prepare the bind variables, if any
                        if ( !empty( $aSingleBind ) )
                        {
                            // bind all the variables
                            $this->BindVariables( $oPrepared, $aSingleBind );
                        }

                        // try to execute the query
                        if( $oPrepared->execute() === FALSE )
                        {
                            throw new Exception( 'Could not execute query: ' . $sQuery
                                                 . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                                 . 'Bind variables: '
                                                 . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                                 . print_r( $aSingleBind, true )
                                                 . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                                 . 'PDO Error information:'
                                                 . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                                 . print_r( $this->GetConnection()->errorInfo(), true ), 3 );
                        }
                    }
                }
                else
                {
                    // try to execute the query
                    if( $oPrepared->execute() === FALSE )
                    {
                        throw new Exception( 'Could not execute query: ' . $sQuery
                                             . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                             . 'PDO Error information:'
                                             . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                             . print_r( $this->GetConnection()->errorInfo(), true ), 3 );
                    }
                }

                return true;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Runs a SQL query, and the results in an array.
         * Only works with 'SELECT' statements.
         *
         * @param   string      $sQuery           select statement to run
         * @param   array       $aBindVariables   array of bind variables, key => value
         *
         * @throws  Exception
         *
         * @return  array
         *
         * Example:
         *   GetQueryResults( 'select * from `names` WHERE Name_First = :firstname',
         *                     array( 'firstname' => 'John' ) );
         */
        public function GetQueryResults( $sQuery, array $aBindVariables = array() )
        {
            try
            {
                // cleanup the query
                $sQuery = $this->CleanQuery( $sQuery );

                // Make sure this is a select statement
                if ( strtoupper( substr( $sQuery, 0, 6) ) != 'SELECT' )
                {
                    throw new Exception( 'GetQueryResults called without a SELECT statement.', 3 );
                }

                // ensure we have a connection and prepare the query
                $oResults = $this->GetConnection()->prepare( $sQuery );

                // check if the statement could be prepared
                if( $oResults === false )
                {
                    throw new Exception( 'Could not prepare statement: ' . $sQuery . ' with bind variables: ' . print_r( $aBindVariables, true ), 3 );
                }

                // prepare the bind variables, if any
                if ( !empty( $aBindVariables ) )
                {
                    // bind all the variables
                    $this->BindVariables( $oResults, $aBindVariables );
                }

                // execute query
                if( $oResults->execute() === FALSE )
                {
                    throw new Exception( 'Could not execute query: ' . $sQuery
                                         . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                         . 'Bind variables: '
                                         . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                         . print_r( $aBindVariables, true )
                                         . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                         . 'PDO Error information:'
                                         . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                         . print_r( $this->GetConnection()->errorInfo(), true ), 3 );
                }

                return $oResults->fetchAll( PDO::FETCH_ASSOC );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Runs a SQL query, and the results in an array.
         * Only works with 'SELECT' statements expecting one row.
         *
         * @param   string      $sQuery           select statement to run
         * @param   array       $aBindVariables   array of bind variables, key => value
         *
         * @throws  Exception
         *
         * @return  array
         *
         * Example:
         *   GetSingleQueryResults( 'select * from `names` WHERE Name_First = :firstname',
         *                           array( 'firstname' => 'John' ) );
         */
        public function GetSingleQueryResults( $sQuery, array $aBindVariables = array() )
        {
            try
            {
                // get the results
                $aResults = $this->GetQueryResults( $sQuery, $aBindVariables );

                // check for results
                if( !empty( $aResults ) )
                {
                    $aResults = $aResults[ 0 ];
                }

                return $aResults;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Runs a SQL query, and returns the number of columns.
         *
         * @param   string      $sQuery           select statement to run
         * @param   array       $aBindVariables   array of bind variables, key => value
         *
         * @throws  Exception
         *
         * @return  integer
         *
         * Example:
         *   ReturnColCount( 'select * from `names` WHERE Name_First = :firstname',
         *                    array( 'firstname' => 'John' ) );
         */
        public function ReturnColCount( $sQuery, array $aBindVariables = array() )
        {
            try
            {
                // cleanup the query
                $sQuery = $this->CleanQuery( $sQuery );

                // ensure we have a connection
                $this->GetConnection();

                // make sure this is a select statement
                if ( strtoupper( substr( $sQuery, 0, 6) ) != 'SELECT' )
                {
                    throw new Exception( 'ReturnColCount called without a SELECT statement.', 3 );
                }

                // prepare the query
                $oResults = $this->oConnection->prepare( $sQuery );

                // check if the statement could be prepared
                if( $oResults === false )
                {
                    throw new Exception( 'Could not prepare statement: ' . $sQuery . ' with bind variables: ' . print_r( $aBindVariables, true ), 3 );
                }

                // prepare the bind variables, if any
                if ( !empty( $aBindVariables ) )
                {
                    // bind all the variables
                    $this->BindVariables( $oResults, $aBindVariables );
                }

                // Execute query
                if( $oResults->execute() === FALSE )
                {
                    throw new Exception( 'Could not execute query: ' . $sQuery
                                         . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                         . 'Bind variables: '
                                         . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                         . print_r( $aBindVariables, true )
                                         . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                         . 'PDO Error information:'
                                         . ( ( bIS_CLI ) ? "\n" : '</br>' )
                                         . print_r( $this->GetConnection()->errorInfo(), true ), 3 );
                }

                return $oResults->columnCount();
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Returns the number of rows affected by the last statement executed or a given select statement.
         * If $sQuery is empty, then the number of rows affected by the last execute statement is given.
         * If $sQuery is a SELECT query, then the number of rows found is given.
         *
         * @param string $sQuery           not used
         *
         * @throws Exception
         *
         * @return integer
         */
        public function ReturnRowCount( $sQuery = '' )
        {
            try
            {
                // cleanup the query
                $sQuery = $this->CleanQuery( $sQuery );

                // ensure we have a connection
                $this->GetConnection();

                // check if a query was provided
                if ( $sQuery == '' )
                {
                    return $this->oConnection->rowCount();
                }
                else
                {
                    // ensure that the query is a SELECT statement
                    $sRegex = '/^SELECT\s+(?:ALL\s+|DISTINCT\s+)?(?:.*?)\s+FROM\s+(.*)$/i';
                    if ( preg_match( $sRegex, $sQuery, $aOutput) > 0 )
                    {
                        $oStatement = $this->oConnection->query( "SELECT COUNT(*) FROM {$aOutput[ 1 ]}", PDO::FETCH_NUM );

                        // check if the statement could be prepared
                        if( $oStatement === false )
                        {
                            throw new Exception( 'Could not execute query: ' . "SELECT COUNT(*) FROM {$aOutput[ 1 ]}", 3 );
                        }

                        return $oStatement->fetchColumn();
                    }

                    throw new Exception( 'ReturnRowCount called without a SELECT statement.' );
                }
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Returns the auto incremented id from the last insert made
         *
         * @param   string      $sQuery           not used
         *
         * @throws  Exception
         *
         * @return  integer
         */
        public function GetLastSequenceId( $sQuery = '' )
        {
            try
            {
                // cleanup the query
                $sQuery = $this->CleanQuery( $sQuery );

                // ensure we have a connection
                return $this->GetConnection()->lastInsertId();
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Function not used with MySQL
         */
        public function GetNextSequenceId( $sQuery = '' )
        {
            try
            {
                throw new Exception( 'GetNextSequenceId not supported with MySQL.', 3 );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * RunProcedure would run a procedure than a query to get the output of the procedure
         *  ... if we had rights to actually write procedures, that is.
         *
         * @param string $sProcedure
         */
        public function RunProcedure( $sProcedure, array $aBindVariables = array() )
        {
            throw BubbleException( new Exception( 'RunProcedure not enabled.', 3 ) );
        }

        /**
         * Frees up the connection, but does not close it.
         *
         * @param   string  $sQuery     not used
         *
         * @return  boolean
         */
        public function FreeResults( $sQuery = '' ) {}

        /**
         * Starts a new transaction.
         *
         * @return boolean
         */
        public function StartTransaction()
        {
            try
            {
                // ensure we have a connection
                $this->GetConnection();

                // check if there are any tables that do not support transactions
                if ( !empty( $this->sMyISAMTables ) )
                {
                    throw new Exception( 'The tables ' . $this->sMyISAMTables . ' do not allow transactions. StartTransaction cancelled.', 3 );
                }

                return $this->oConnection->beginTransaction();
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Commits any changes made since a new transaction was started.
         *
         * @return boolean
         */
        public function Commit()
        {
            try
            {
                // ensure we have a connection and commit the transaction
                return $this->GetConnection()->commit();
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Rolls back any changes made since a new transaction was started.
         *
         * @return boolean
         */
        public function Rollback()
        {
            try
            {
                // ensure we have a connection and rollback the transaction
                return $this->GetConnection()->rollBack();
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
            $this->oConnection = null;

            // garbage collect if possible
            if( function_exists( 'gc_collect_cycles' ) )
            {
                gc_collect_cycles();
            }
        }
    }
?>