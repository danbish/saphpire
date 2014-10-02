<?php
    require_once( sCORE_INC_PATH . '/classes/cRequest.php' );
    require_once( sCORE_INC_PATH . '/includes/HttpStatusCodes.php' );
    /**
     * Service Request Proxy
     *
     * This application acts as a middleman between the services that
     * the user intends to call and the user invoking this proxy.
     *
     * The proxy accepts only HTTP POST requests. Within the post data
     * you must specify the HTTP Verb, Service URL, and Arguments to be
     * sent along to the service URL.
     *
     * expecting:
     * verb         - GET, POST, PUT, or DELETE (OPTIONS|TRACE)
     * url          - URL of service to make request
     * args         - Args or payload (request body) can be:
     *                array ( PAYLOAD => '', FORMAT => 'binary,text,base64,json' )
     *                array ( token => xxxxxx, acctid => 4 )  -> becomes JSON
     *                base64_encoded string
     *
     *
     * See Examples in /docs/examples.txt
     *
     * If you are specifying a GET request, the arguments will be appended
     * to the Service URL and the GET request will be sent to the new URL+Args
     *
     * If you are specifying a POST request, the arguments will be supplied
     * as the post fields in the request to the Service URL
     *
     * If you are specifying a PUT request, you may specify a PAYLOAD argument that must
     * be accompanied with a FORMAT argument that declares the format of the PAYLOAD.
     *
     * If no PAYLOAD argument is supplied, and the args are not an array, we assume that
     * the entire args content is the payload for the request body in base64 encoding.
     *
     * If the args is an array from form, then we json_encode that array and send it as
     * the payload for the PUT request.
     *
     * With PHP+cURL the body of the PUT request must be read from php://input.
     *
     * If you are specifying a DELETE request, the arguments will be supplied
     * as the post fields in the request to the Service URL. With PHP+cURL the body
     * of the request must be read from php://input
     */
    class cBusProxy
    {
        /**
         * Instantiated request class object.
         *
         * @var    object
         */
        protected $oRequest;

        /**
         * Service proxy constructor.
         *
         * @throws  Exception rethrows anything it catches.
         */
        public function __construct()
        {
            try
            {
                // nothing to do.
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validates the request coming in to the proxy to
         * ensure that there is enough information to process
         * the request. Otherwise, it returns the http error
         * code and response body required.
         *
         * @throws  Exception rethrows anything it catches.
         *
         * @return  array( $bReturn, $iResponseCode, $sResponseBody )
         */
        public function ValidateRequest()
        {
            try
            {
                // initialize return variables.
                $bReturn       = true;
                $iResponseCode = 200;
                $sResponseBody = '';

                if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' )
                {
                    // set response code and header.
                    header( $aHttpStatusCodes[ 405 ], true, 405 );
                    header( 'Accept: POST' );

                    // stop execution.
                    exit( 0 );
                }
                else
                {
                    if ( empty( $_POST ) )
                    {
                        // bad request
                        $iResponseCode = 400;
                        $sResponseBody = 'POST Data is empty.';
                        $bReturn       = false;
                    }
                    else
                    {
                        if ( empty( $_POST[ 'url' ] ) )
                        {
                            // bad request
                            $iResponseCode = 400;
                            $sResponseBody = 'Service URL is empty.';
                            $bReturn       = false;
                        }
                        if ( empty( $_POST[ 'args' ] ) && $_POST[ 'verb' ] !== 'GET' )
                        {
                            // bad request
                            $iResponseCode = 400;
                            $sResponseBody = 'Arguments are empty.';
                            $bReturn       = false;
                        }
                    }
                }
                return array( $bReturn, $iResponseCode, $sResponseBody );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Handles the incoming request and forwards the request
         * thru the appropriate channel based on the HTTP Verb supplied
         * in the POST request.
         *
         * @throws  Exception rethrows anything it catches.
         *
         * @return  array( $iResponseCode, $sResponseBody )
         */
        public function ProxifyRequest()
        {
            try
            {
                global $aHttpStatusCodes;

                // validate the incoming request
                list( $bValid, $iResponseCode, $sResponseBody ) = $this->ValidateRequest();

                // initialize debug flag
                $bDebugOutput = false;

                if ( $bValid )
                {
                    // receive the client's request
                    $sVerb       = $_POST[ 'verb' ];
                    $sServiceURL = $_POST[ 'url' ];
                    $aArgs       = $_POST[ 'args' ];
                    $sArgs       = '';
                    if ( !is_array( $aArgs ) )
                    {
                        // use string variable instead.
                        $sArgs = $_POST[ 'args' ];
                    }

                    // relay the request thru the generic requestor object
                    $this->oRequest = new cRequest();

                    // get the debug flag from request class
                    $bDebugOutput = $this->oRequest->getDebugOutput();

                    // check the verb.
                    switch ( strtoupper( $sVerb ) )
                    {
                        case 'GET' :
                            // perform the request to the service.
                            list( $sResponseBody, $iCode ) = $this->ExecuteGet( $sServiceURL, $aArgs );
                            break;

                        case 'POST' :
                            // perform the request to the service.
                            list( $sResponseBody, $iCode ) = $this->ExecutePost( $sServiceURL, $aArgs );
                            break;

                        case 'PUT' :
                            // perform the request to the service.
                            list( $sResponseBody, $iCode ) = $this->ExecutePut( $sServiceURL, $aArgs, $sArgs );
                            break;

                        case 'DELETE' :
                            // perform the request to the service.
                            list( $sResponseBody, $iCode ) = $this->ExecuteDelete( $sServiceURL, $aArgs );
                            break;

                        default :
                            // bad request
                            $iResponseCode = 400;
                            $sResponseBody = 'Invalid verb supplied.';
                            break;
                    }
                }
                // add proxy response
                if ( isset( $iResponseCode ) && $bDebugOutput )
                {
                    // append proxy http code to body
                    $sResponseBody .=
                    "\r\n\r\n" . 'Proxy returns: HTTP/1.1 ' . $iResponseCode . ' ' . $aHttpStatusCodes[ $iResponseCode ];
                }
                // set service response
                if ( isset( $iCode ) )
                {
                    // set response code to actual response
                    $iResponseCode = $iCode;
                }

                return array( $iResponseCode, $sResponseBody );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Appends the arguments as a query string to the
         * service url and forwards the request to the
         * requestor class object that is initialized.
         *
         * @param   string      $sServiceURL    URL of service to request.
         * @param   array       $aArgs          Array of arguments for query string.
         *
         * @throws  Exception                   rethrows anything it catches.
         *
         * @return  array( $sResponse, $iCode )
         */
        public function ExecuteGet( $sServiceURL, array $aArgs )
        {
            try
            {
                if ( !empty( $aArgs ) )
                {
                    // check for existing query string
                    if ( strstr( $sServiceURL, '?' ) === false )
                    {
                        $sServiceURL = $sServiceURL . '?';
                    }
                    else
                    {
                        $sServiceURL = $sServiceURL . '&';
                    }
                    // append query string
                    $sServiceURL = $sServiceURL . http_build_query( $aArgs );
                }

                // perform the request to the service.
                list( $sResponse, $iCode ) = $this->oRequest->Get( $sServiceURL );

                return array( $sResponse, intval( $iCode ) );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Forwards the POST request to the requestor class
         * object and returns the responses from the request.
         *
         * @param   string      $sServiceURL    URL of service to request.
         * @param   array       $aArgs          Array of arguments for post fields.
         *
         * @throws  Exception                   rethrows anything it catches.
         *
         * @return  array( $sResponse, $iCode )
         */
        public function ExecutePost( $sServiceURL, array $aArgs )
        {
            try
            {
                // perform the request to the service.
                list( $sResponse, $iCode ) = $this->oRequest->Post( $sServiceURL, $aArgs );

                return array( $sResponse, intval( $iCode ) );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Forwards the PUT request to the requestor class
         * object and returns the responses from the request.
         *
         * @param   string      $sServiceURL    URL of service to request.
         * @param   array       $aArgs          Array of arguments containing
         *                                      PAYLOAD and FORMAT.
         * @param   string      $sArgs          Payload of PUT request
         *
         * @throws  Exception rethrows anything it catches.
         *
         * @return  array( $sResponse, $iCode )
         */
        public function ExecutePut( $sServiceURL, array $aArgs, $sArgs )
        {
            try
            {
                if ( !empty( $sArgs ) )
                {
                    // we have stricly payload, decode it.
                    $sArgs = base64_decode( $sArgs );
                    list( $sResponse, $iCode ) = $this->oRequest->Put( $sServiceURL, $sArgs );
                }
                else if ( !empty( $aArgs[ 'PAYLOAD' ] ) )
                {
                    // set payload and format variables.
                    $sPayload = $aArgs[ 'PAYLOAD' ];
                    $sFormat  = ( !empty( $aArgs[ 'FORMAT' ] ) ? $aArgs[ 'FORMAT' ] : '' );

                    if( $sFormat == 'binary' )
                    {
                        // don't do anything.
                        list( $sResponse, $iCode ) = $this->oRequest->Put( $sServiceURL, $sPayload );
                    }
                    else if ( $sFormat == 'text' )
                    {
                        // don't do anything.
                        list( $sResponse, $iCode ) = $this->oRequest->Put( $sServiceURL, $sPayload );
                    }
                    else if ( $sFormat == 'base64' )
                    {
                        // decode the payload and send the request
                        $sArgs = base64_decode( $sPayload );
                        list( $sResponse, $iCode ) = $this->oRequest->Put( $sServiceURL, $sArgs );
                    }
                    else if ( $sFormat == 'json' )
                    {
                        // decode the payload and send the request
                        $sArgs = json_decode( $sPayload );
                        if ( $sArgs !== NULL )
                        {
                            list( $sResponse, $iCode ) = $this->oRequest->Put( $sServiceURL, $sArgs, true );
                        }
                        else
                        {
                            // invalid json
                            $iCode     = 422;
                            $sResponse = 'Invalid JSON-encoded string provided.';
                        }
                    }
                    else
                    {
                        // we don't know.
                        $iCode     = 422;
                        $sResponse = 'Unknown PAYLOAD Format.';
                    }
                }
                else
                {
                    // assume query string
                    // json_encode $aArgs as payload.
                    $sPayload = json_encode( $aArgs );
                    list( $sResponse, $iCode ) = $this->oRequest->Put( $sServiceURL, $sPayload, true );
                }

                return array( $sResponse, intval( $iCode ) );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Forwards the DELETE request to the requestor class
         * object and returns the responses from the request.
         *
         * @throws  Exception rethrows anything it catches.
         *
         * @return  array( $sResponse, $iCode )
         */
        public function ExecuteDelete()
        {
            try
            {
                // perform the request to the service.
                list( $sResponse, $iCode ) = $this->oRequest->Delete( $sServiceURL, $aArgs );

                return array( $sResponse, intval( $iCode ) );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>