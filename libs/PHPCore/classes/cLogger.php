<?php
    // get access to the file utilities
    require_once( sCORE_INC_PATH . '/classes/cFileUtilities.php' );

    // get access to the xml utilities
    require_once( sCORE_INC_PATH . '/classes/cXmlUtilities.php' );

    // get access to the base auth class
    require_once( sCORE_INC_PATH . '/classes/cBaseAuth.php' );

    // get access to the template engine
    require_once( sCORE_INC_PATH . '/classes/cTemplate.php' );

    /**
     * Logging class.
     *
     * Supports logging in XML or delimited formats.
     * XML is preferred because it is more flexible
     * and as such, it is the default log format.
     *
     * To turn off XML logging, set $bXmlFormat to false.
     *
     * Provides three ways of calling:
     *  - cLogger::Log( 'message' )                             // type defaults to info
     *  - cLogger::Log( 'type', 'message' )
     *  - cLogger::Log( 'type', 'message', [, mixed $... ]  )   // any amount of extra values to save
     *
     * Logs the following information:
     *  - current user
     *  - user's IP address ( or server IP if run from CLI )
     *  - type ( error, exception, info, etc. )
     *  - message
     *  - optional extra info
     *  - time that this was called in sTIMESTAMP_FORMAT format as well as microseconds
     *  - path to file this was called from
     *  - file this was called from
     *  - function that called this
     *  - arguments that were supplied to the calling function
     *  - line this was called from
     *
     * Important Note: The output to and XML log file is escaped with CDATA as necessary
     *                 so that the characters will not interfere with XML structure.
     *
     * @author  Ryan Masters
     *
     * @package Core_0
     * @version 0.4
     *
     * @todo
     *    - turn this class into a wrapper like cDbAbs so that we can have multiple logging types
     *    - use templates for logging
     *    - remove manual CDATA handling. use the xml utilities for this.
     *    - break error and exception single log files into 2
     *        - one log contains timestamp, who did it, ip address, file, line, error code, and message
     *        - one log contains full stack trace, function arguments, and timestamp for a cross reference
     */
    class cLogger
    {
        /**
         * Directory to store log files. Relative to sCORE_INC_PATH.
         *
         * @var string
         */
        protected static $sLogDirectory = 'logs';

        /**
         * Delimiter to separate logged information.
         *
         * Will not be used if $bXmlFormat is true.
         *
         * @var string
         */
        protected static $sDelimiter = '|';

        /**
         * If true, saves in xml format.
         *
         * Otherwise, uses $sDelimiter for delimited format.
         *
         * @var boolean
         */
        protected static $bXmlFormat = true;

        /**
         * List of exception error codes and their associated messages.
         *
         * @var array
         */
        protected static $aCodesAndMessages = array();

        /**
         * Returns the user's IP address.
         *
         * Tries to pull the REMOTE_ADDR or SERVER_ADDR
         * from $_SERVER and if neither are found, pulls
         * the hostname of the server.
         *
         * @return  string
         */
        protected static function GetUserIP()
        {
            // check if this was an HTTP request
            if( isset( $_SERVER[ 'REMOTE_ADDR' ] ) )
            {
                $sUserIP = $_SERVER[ 'REMOTE_ADDR' ];
            }
            // check if this was run from CLI
            elseif( isset( $_SERVER[ 'SERVER_ADDR' ] ) )
            {
                $sUserIP = $_SERVER[ 'SERVER_ADDR' ];
            }
            // server is misconfigured, so just get the hostname
            else
            {
                $sUserIP = gethostbyname( gethostname() );
            }

            return $sUserIP;
        }

        /**
         * Generic logging function.
         *
         * Provides three ways of calling:
         *  - cLogger::Log( 'message' )                             // type defaults to info
         *  - cLogger::Log( 'type', 'message' )
         *  - cLogger::Log( 'type', 'message', [, mixed $... ]  )   // any amount of extra values to save
         *
         * Logs the following information:
         *  - current user
         *  - type
         *  - message
         *  - optional extra info
         *  - time that this was called in sTIMESTAMP_FORMAT format as well as microseconds
         *  - path to file this was called from
         *  - file this was called from
         *  - function that called this
         *  - arguments that were supplied to the calling function
         *  - line this was called from
         *
         * @throws Exception
         */
        public static function Log()
        {
            // get the time
            $aTime         = explode( ' ', microtime() );
            $sDateTime     = date( sTIMESTAMP_FORMAT, $aTime[ 1 ] );
            $sMicroSeconds = substr( $aTime[ 0 ], 2, 8 );

            // get the args and count of args
            $aArgs     = func_get_args();
            $iArgCount = count( $aArgs );

            // initialize  data to save
            $sType    = 'info';
            $sMessage = '';
            $sExtra   = '';

            // get the current user
            $sUser = '';
            $vUser = cBaseAuth::GetUser();
            if( $vUser !== null )
            {
                $sUser = $vUser;
            }

            // get user's IP address
            $sUserIP = self::GetUserIP();

            // check if this was a quick log or a full log
            if( $iArgCount === 1 ) // message
            {
                // set the message
                if( !empty( $aArgs[ 0 ] ) )
                {
                    $sMessage = print_r( $aArgs[ 0 ], true );
                }
                else
                {
                    $sMessage = var_dump( $aArgs[ 0 ] );
                }
            }
            // type and message
            elseif( $iArgCount === 2 )
            {
                // set the type
                $sType = $aArgs[ 0 ];

                // set the message
                if( isset( $aArgs[ 1 ] ) )
                {
                    $sMessage = print_r( $aArgs[ 1 ], true );
                }
            }
            // type, message, extra info
            else
            {
                // set the type
                $sType = $aArgs[ 0 ];

                // set the message
                if( isset( $aArgs[ 1 ] ) )
                {
                    $sMessage = print_r( $aArgs[ 1 ], true );
                }

                // set the extra info
                for( $i = 2; $i < $iArgCount; ++$i )
                {
                    if( isset( $aArgs[ $i ] ) )
                    {
                        if( $aArgs[ $i ] instanceof Exception )
                        {
                            $sExtra .= 'Exception: ' . $aArgs[ $i ]->getMessage();
                        }
                        else
                        {
                            $sExtra .= print_r( $aArgs[ $i ], true );
                        }
                    }
                }

                // check if we need to wrap the value with CDATA
                if( is_string( $sExtra ) &&
                    ( strpos( $sExtra, '&' ) !== false
                      || strpos( $sExtra, '<' ) !== false
                      || strpos( $sExtra, '>' ) !== false ) )
                {
                    // check if the characters not allowed in CDATA are present
                    if( strpos( $sExtra, ']]>' ) )
                    {
                        throw new Exception( 'Invalid characters found in section that will be wrapped in CDATA.' );
                    }

                    // wrap with CDATA
                    $sExtra = "<![CDATA[$sExtra]]>";
                }
            }

            // check if a type was provided correctly
            if( !is_string( $sType ) )
            {
                throw new Exception( 'Type provided is not a string.' );
            }
            if( empty( $sType ) )
            {
                throw new Exception( 'Type was not provided.' );
            }

            // get info about the function that called this
            $aCallers = debug_backtrace();
            if( isset( $aCallers[ 1 ] ) )
            {
                $aCaller = $aCallers[ 1 ];
            }
            else
            {
                $aCaller = $aCallers[ 0 ];

                // we can't use the class, function, or args
                // because they refer to this class, function, and args
                $aCaller[ 'class' ]    = '';
                $aCaller[ 'function' ] = '';
                $aCaller[ 'args' ]     = '';
            }

            // separate caller info
            $iLine     = isset( $aCaller[ 'line' ] )     ? $aCaller[ 'line' ]     : '';
            $sFunction = isset( $aCaller[ 'function' ] ) ? $aCaller[ 'function' ] : '';
            $aArgs     = isset( $aCaller[ 'args' ] )     ? $aCaller[ 'args' ]     : array();
            $sClass    = isset( $aCaller[ 'class' ] )    ? $aCaller[ 'class' ]    : '';
            $sArgs = '';
            if( !empty( $aArgs ) )
            {
                $iArgCount = count( $aArgs );
                for( $i = 0; $i < $iArgCount; ++$i )
                {
                    if( $aArgs[ $i ] instanceof Exception )
                    {
                        $sArgs .= 'Exception: ' . $aArgs[ $i ]->getMessage();
                    }
                    else
                    {
                        $sArgs .= print_r( $aArgs[ $i ], true );
                    }
                }

                // check if we need to wrap the value with CDATA
                if( is_string( $sArgs ) &&
                    ( strpos( $sArgs, '&' ) !== false
                      || strpos( $sArgs, '<' ) !== false
                      || strpos( $sArgs, '>' ) !== false ) )
                {
                    // check if the characters not allowed in CDATA are present
                    if( strpos( $sArgs, ']]>' ) )
                    {
                        throw new Exception( 'Invalid characters found in section that will be wrapped in CDATA.' );
                    }

                    // wrap with CDATA
                    $sArgs = "<![CDATA[$sArgs]]>";
                }
            }
            $aPathInfo = isset( $aCaller[ 'file' ] ) ? pathinfo( $aCaller[ 'file' ] ) : array();
            $sFile     = isset( $aPathInfo[ 'basename' ] ) ? $aPathInfo[ 'basename' ] : '';
            $sPath     = isset( $aPathInfo[ 'dirname' ] )  ? $aPathInfo[ 'dirname' ]  : '';

            // set the path to the log file
            $sLogFile = sBASE_INC_PATH . '/' . self::$sLogDirectory . '/' . $sType;

            // save to type file
            if( self::$bXmlFormat )
            {
                // ensure that it's logging to an xml file
                if( substr( $sLogFile, -4 ) !== '.xml' )
                {
                    $sLogFile .= '.xml';
                }

                // get log contents
                if( file_exists( $sLogFile ) && filesize( $sLogFile ) !== 0 )
                {
                    // load the file
                    $sContents = file_get_contents( $sLogFile );

                    // create a new element
                    $oLog = new SimpleXMLElement( $sContents, LIBXML_NOCDATA );

                    // add the new child
                    $oLogNode = $oLog->addChild( 'lognode' );

                    // add info nodes to the log node
                    if( !empty( $sUser ) )
                    {
                        $oLogNode->addChild( 'user', $sUser );
                    }
                    if( !empty( $sUserIP ) )
                    {
                        $oLogNode->addChild( 'user-IP', $sUserIP );
                    }
                    $oLogNode->addChild( 'message', $sMessage );
                    $oLogNode->addChild( 'extra', $sExtra );
                    $oLogNode->addChild( 'path', $sPath );
                    $oLogNode->addChild( 'file', $sFile );
                    if( !empty( $sClass ) )
                    {
                        $oLogNode->addChild( 'class', $sClass );
                    }
                    if( !empty( $sFunction ) )
                    {
                        $oLogNode->addChild( 'function', $sFunction );
                    }
                    if( !empty( $sArgs ) )
                    {
                        $oLogNode->addChild( 'args', $sArgs );
                    }
                    $oLogNode->addChild( 'line', $iLine );
                    $oLogNode->addChild( 'date', $sDateTime );
                    $oLogNode->addChild( 'microseconds', $sMicroSeconds );

                    // append the new log information to the file
                    $bSuccess = file_put_contents(
                        // file to save to
                        $sLogFile,

                        // new contents
                        $oLog->asXml()
                    );
                }
                else
                {
                    // create the initial entry
                    $sContents = '<log><lognode>';
                    if( !empty( $sUser ) )
                    {
                        $sContents .= "<user>$sUser</user>";
                    }
                    if( !empty( $sUserIP ) )
                    {
                        $sContents .= "<user-IP>$sUserIP</user-IP>";
                    }
                    $sContents .= "<message>$sMessage</message>";
                    $sContents .= "<extra>$sExtra</extra>";
                    $sContents .= "<path>$sPath</path>";
                    $sContents .= "<file>$sFile</file>";
                    if( !empty( $sClass ) )
                    {
                        $sContents .= "<class>$sClass</class>";
                    }
                    if( !empty( $sFunction ) )
                    {
                        $sContents .= "<function>$sFunction</function>";
                    }
                    if( !empty( $sArgs ) )
                    {
                        $sContents .= "<args>$sArgs</args>";
                    }
                    $sContents .= "<line>$iLine</line>";
                    $sContents .= "<date>$sDateTime</date>";
                    $sContents .= "<microseconds>$sMicroSeconds</microseconds>";
                    $sContents .= '</lognode></log>';

                    // append the new log information to the file
                    $bSuccess = file_put_contents(
                        // file to save to
                        $sLogFile,

                        // new contents
                        $sContents
                    );
                }
            }
            else
            {
                // append the new log information to the file
                $bSuccess = file_put_contents(
                    // file to save to
                    $sLogFile,

                    // new log entry
                    "$sUser {$this->sDelimiter} "     .
                    "$sUserIP {$this->sDelimiter} "     .
                    "$sMessage {$this->sDelimiter} "  .
                    "$sExtra {$this->sDelimiter} "    .
                    "$sPath {$this->sDelimiter} "     .
                    "$sFile {$this->sDelimiter} "     .
                    "$sClass {$this->sDelimiter} "    .
                    "$sFunction {$this->sDelimiter} " .
                    "$sArgs {$this->sDelimiter} "     .
                    "$iLine {$this->sDelimiter} "     .
                    "$sDateTime {$this->sDelimiter} " .
                    "$sMicroSeconds",

                    // flag to append to file if it has been created
                    FILE_APPEND
                );
            }

            // check if log was successful
            if( !$bSuccess )
            {
                throw new Exception( 'Could not log info.' );
            }
        }

        /**
         * Logs an exception with a full stack trace as well as
         * who caused the error and when.
         *
         * @param  Exception  $oException  The exception to log.
         */
        public static function LogException( Exception $oException )
        {
            // get the time
            $aTime         = explode( ' ', microtime() );
            $sDateTime     = date( sTIMESTAMP_FORMAT, $aTime[ 1 ] );
            $sMicroSeconds = substr( $aTime[ 0 ], 2, 8 );

            // get the current user
            $sUser = '';
            $vUser = cBaseAuth::GetUser();
            if( $vUser !== null )
            {
                $sUser = $vUser;
            }

            // get user's IP address
            $sUserIP = self::GetUserIP();

            // get an instance of the template class
            $oTemplate = new cTemplate( sCORE_INC_PATH . '/templates' );

            // set the path to the log file
            $sLogFile = sBASE_INC_PATH . '/' . self::$sLogDirectory . '/exception.xml';

            // get the stack trace
            $aTrace = $oException->getTrace();
            if( empty( $aTrace ) )
            {
                $aTrace = debug_backtrace();
            }

            // initialize  the tracenodes string
            $sTraceNodes = '';

            // log each function call
            $iTraceCount = count( $aTrace );
            for( $i = 0; $i < $iTraceCount; ++$i )
            {
                // only log useful information
                if( $aTrace[ $i ][ 'function' ] == 'BubbleException'
                    || ( isset( $aTrace[ $i ][ 'class' ] )  && $aTrace[ $i ][ 'class' ] == 'cLogger' ) )
                {
                    continue;
                }

                // remove exception handling information
                if( $aTrace[ $i ][ 'function' ] == 'ExceptionHandler' )
                {
                    unset( $aTrace[ $i ][ 'function' ] );
                    unset( $aTrace[ $i ][ 'args' ] );
                }

                // initialize  the tracenode template information
                $aTraceTemplate = array(
                    'template' => 'tracenode.xml'
                );

                // get the path info
                $aPathInfo = pathinfo( $aTrace[ $i ][ 'file' ] );
                $sFile     = $aPathInfo[ 'basename' ];
                $sPath     = $aPathInfo[ 'dirname' ];

                // add file and path nodes to the tracenode
                $aTraceTemplate[ '_:_PATH_:_' ] = $sPath;
                $aTraceTemplate[ '_:_FILE_:_' ] = $sFile;

                // add a function node if it exists
                if( !empty( $aTrace[ $i ][ 'function' ] ) )
                {
                    $aTraceTemplate[ 'template' ] = 'tracenode-function.xml';
                    $aTraceTemplate[ '_:_FUNCTION_:_' ] = $aTrace[ $i ][ 'function' ];
                }

                // add a class node if it exists
                if( !empty( $aTrace[ $i ][ 'class' ] ) )
                {
                    $aTraceTemplate[ 'template' ] = 'tracenode-class.xml';
                    $aTraceTemplate[ '_:_CLASS_:_' ] = $aTrace[ $i ][ 'class' ];
                }

                // add an arguments node if it exists
                if( !empty( $aTrace[ $i ][ 'args' ] ) )
                {
                    // initialize the trace template
                    $aTraceTemplate[ '_:_ARGS_:_' ] = array(
                        'template' => 'tracenode-args.xml',
                        '_:_ARG-LIST_:_' => array(
                            'template' => 'tracenode-arg.xml'
                        )
                    );

                    // add each arg to the args node
                    $iArgCount = count( $aTrace[ $i ][ 'args' ] );
                    for( $j = 0; $j < $iArgCount; ++$j )
                    {
                        // exceptions are gigantic, so don't log them, only their message
                        if( $aTrace[ $i ][ 'args' ][ $j ] instanceof Exception )
                        {
                            $sArg = 'Exception: ' . $aTrace[ $i ][ 'args' ][ $j ]->getMessage();
                        }
                        // convert argument to formatted string
                        else
                        {
                            $sArg = print_r( $aTrace[ $i ][ 'args' ][ $j ], true );
                        }

                        // check if we need to wrap the value with CDATA
                        if( is_string( $sArg ) &&
                            ( strpos( $sArg, '&' ) !== false
                              || strpos( $sArg, '<' ) !== false
                              || strpos( $sArg, '>' ) !== false ) )
                        {
                            // check if the characters not allowed in CDATA are present
                            if( strpos( $sArg, ']]>' ) )
                            {
                                throw new Exception( 'Invalid characters found in section that will be wrapped in CDATA.' );
                            }

                            // wrap with CDATA
                            $sArg = "<![CDATA[$sArg]]>";
                        }

                        // add arg to arg list template
                        $aTraceTemplate[ '_:_ARGS_:_' ][ '_:_ARG-LIST_:_' ][] = array(
                            '_:_ARG_:_' => $sArg
                        );
                    }
                }
                else
                {
                    // set arg list to be empty
                    $aTraceTemplate[ '_:_ARGS_:_' ] = '';
                }

                // add a line node and build the trace node
                $aTraceTemplate[ '_:_LINE_:_' ] = $aTrace[ $i ][ 'line' ];
                $sTraceNodes .= $oTemplate->replace( $aTraceTemplate );
            }

            // get the list of error codes and messages
            $iMessageCount = count( self::$aCodesAndMessages );

            // initialize the code and message tample
            $aCodesAndMessages = array(
                'template' => 'code-and-message.xml'
            );

            // build the code and message nodes
            for( $i = 0; $i < $iMessageCount; ++$i )
            {
                // get the code and message
                list( $vCode, $sMessage ) = self::$aCodesAndMessages[ $i ];

                // check if we need to wrap the value with CDATA
                if( is_string( $sMessage ) &&
                    ( strpos( $sMessage, '&' ) !== false
                      || strpos( $sMessage, '<' ) !== false
                      || strpos( $sMessage, '>' ) !== false ) )
                {
                    // check if the characters not allowed in CDATA are present
                    if( strpos( $sMessage, ']]>' ) )
                    {
                        throw new Exception( 'Invalid characters found in section that will be wrapped in CDATA.' );
                    }

                    // wrap with CDATA
                    $sMessage = "<![CDATA[$sMessage]]>";
                }

                // add code and message
                $aCodesAndMessages[] = array(
                    '_:_CODE_:_'    => $vCode,
                    '_:_MESSAGE_:_' => $sMessage
                );
            }

            // build exception template tag values
            $aException = array(
                'template'                 => 'exception.xml',
                '_:_USER_:_'               => $sUser,
                '_:_USER-IP_:_'            => $sUserIP,
                '_:_DATE_:_'               => $sDateTime,
                '_:_MICROSECONDS_:_'       => $sMicroSeconds,
                '_:_CODES-AND-MESSAGES_:_' => $aCodesAndMessages,
                '_:_TRACE-NODES_:_'        => $sTraceNodes
            );

            // set the path to the log file
            $sLogFile = sBASE_INC_PATH . '/' . self::$sLogDirectory . '/exception.xml';

            // get log contents
            if( file_exists( $sLogFile ) && filesize( $sLogFile ) !== 0 )
            {
                // load the file
                $sContents = substr( file_get_contents( $sLogFile ), 0, -6 ) . $oTemplate->replace( $aException ) . '</log>';
            }
            else
            {
                // start with the initial exception template
                $aNewContents = array(
                    'template' => 'initial-exception.xml',
                    '_:_EXCEPTION_:_' => $aException
                );
                $sContents = $oTemplate->replace( $aNewContents );
            }

            // append the new log information to the file
            $bSuccess = file_put_contents(
                // file to save to
                $sLogFile,

                // new contents
                $sContents
            );

            // check if log was successful
            if( !$bSuccess )
            {
                throw new Exception( 'Could not log info.' );
            }
        }

        /**
         * Returns the directory that log files are saved to.
         *
         * @return string
         */
        public static function GetLogDirectory()
        {
            return self::$sLogDirectory;
        }

        /**
         * Returns an array of all log files in the log directory.
         *
         * @return array
         */
        public static function GetLogFiles()
        {
            return cFileUtilities::GetDirectoryContents( sBASE_INC_PATH . '/' . self::$sLogDirectory );
        }

        /**
         * Returns the contents of a log file.
         *
         * @param   string      $sFile  The name of the log file to use.
         *
         * @throws  Exception   Thrown if file provided is not a string or file provided does not exist.
         *
         * @return  string      Contents of file provided.
         */
        public static function GetLogContents( $sFile )
        {
            // make sure the file provided is a string
            if( !is_string( $sFile ) )
            {
                throw new Exception( 'File provided is not a string.' );
            }

            // initialize return value
            $sReturn = '';

            // get the contents if possible
            if( file_exists( sBASE_INC_PATH . '/' . self::$sLogDirectory . '/' . $sFile ) )
            {
                $sReturn = file_get_contents( sBASE_INC_PATH . '/' . self::$sLogDirectory . '/' . $sFile );
            }
            else
            {
                throw new Exception( 'Log file does not exist: ' . $sFile );
            }

            return $sReturn;
        }

        /**
         * Clears XML log file entries before the specified date in days.
         *
         * @param   string  $sFile  The log file used to clear old entries.
         * @param   int     $iDays  The number of days.
         *
         * @return  boolean         Whether or not the clear was successful.
         */
        public static function ClearXmlLogBefore( $sFile, $iDays )
        {
            // initialize  the string to return
            $bSuccess = false;

            // set the path to the file
            $sFile = sBASE_INC_PATH . '/' . self::$sLogDirectory . '/' . $sFile;

            // get the file contents
            $sContents = file_get_contents( $sFile );

            // only try to clear if there is data
            if( !empty( $sContents ) )
            {
                // create an instance of the xml utilities
                $oXmlUtilities = new cXmlUtilities();

                // convert the xml to the an array
                $aContents = $oXmlUtilities->ToArray( $sContents );

                if( isset( $aContents[ 'lognode' ] ) )
                {
                    // get when we should start clearing log entries
                    $iSeconds = time() - ( 60 * 60 * 24 * $iDays );

                    // check if there are multiple nodes
                    if( isset( $aContents[ 'lognode' ][ 0 ] ) )
                    {
                        // reverse log nodes so we see the newest ones first
                        $aContents[ 'lognode' ] = array_reverse( $aContents[ 'lognode' ] );

                        // format each node
                        $iNodeCount = count( $aContents[ 'lognode' ] );
                        for( $i = 0; $i < $iNodeCount; ++$i )
                        {
                            // get the date of the log entry
                            $iLogEntryDate = strtotime( $aContents[ 'lognode' ][ $i ][ 'date' ] );

                            // check if we need to clear it
                            if( $iLogEntryDate < $iSeconds )
                            {
                                unset( $aContents[ 'lognode' ][ $i ] );
                            }
                        }
                    }
                    else
                    {
                        // get the date of the log entry
                        $iLogEntryDate = strtotime( $aContents[ 'lognode' ][ 'date' ] );

                        // check if we need to clear it
                        if( $iLogEntryDate < $iSeconds )
                        {
                            unset( $aContents[ 'lognode' ] );
                        }
                    }

                    // if there are still entries left, save the new contents
                    if( !empty( $aContents[ 'lognode' ]) )
                    {
                        // if there are multiple entries, we have to save them
                        if( !empty( $aContents[ 'lognode' ][ 0 ] ) )
                        {
                            // reverse the array to set it back to normal
                            $aContents[ 'lognode' ] = array_reverse( $aContents[ 'lognode' ] );

                            // set the new contents
                            $oLog = new SimpleXMLElement( '<log></log>' );

                            // build new structure
                            $iNodeCount = count( $aContents[ 'lognode' ] );
                            for( $i = 0; $i < $iNodeCount; ++$i )
                            {
                                // add the new lognode
                                $oLogNode = $oLog->addChild( 'lognode' );

                                // populate the log node
                                foreach( $aContents[ 'lognode' ][ $i ] as $sNode => $sValue)
                                {
                                    // convert array to string
                                    if( is_array( $sValue ) )
                                    {
                                        $sValue = print_r( $sValue, true );
                                    }

                                    // add node to the log node
                                    $oLogNode->addChild( $sNode, $sValue );
                                }
                            }

                            // convert the xml object to a string
                            $sContents = $oLog->asXml();

                            // overwrite old contents with new clean contents
                            $bSuccess = file_put_contents( $sFile, $sContents );
                        }
                    }
                    else
                    {
                        // overwrite old contents with new clean contents
                        $bSuccess = file_put_contents( $sFile, '<log></log>' );
                    }
                }
            }

            return $bSuccess;
        }

        /**
         * Resets a log file.
         *
         * @param   string      $sFile  The file to clear.
         *
         * @throws  Exception   Thrown if there was a problem clearing the file.
         */
        public static function ClearLogFile( $sFile )
        {
            return file_put_contents( sBASE_INC_PATH . '/' . self::$sLogDirectory .  '/' . $sFile, '' );
        }

        /**
         * Adds the given code and message to a list of all error
         * codes and message for the current exception sequence.
         *
         * @param int | string  $vErrorCode The error code to associate with the given message.
         * @param string        $sMessage   The message to associate with the given error code.
         */
        public static function AddCodeAndMessage( $vErrorCode, $sMessage )
        {
            self::$aCodesAndMessages[] = array( $vErrorCode, $sMessage );
        }

        /**
         * Returns the list of error codes and messages.
         *
         * @return  array   Structure: array( array( code, message ), array( code, message ), ... )
         */
        public static function GetCodesAndMessages()
        {
            return self::$aCodesAndMessages;
        }
    }
?>