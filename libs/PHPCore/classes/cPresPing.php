<?php
    // include the base class
    require_once( sCORE_INC_PATH . '/classes/cBasePresentation.php' );

    /**
     * Main presentation class for output.
     *
     * @author	Academic Services :: Team Ra
     * @version 0.0.0.1
     */
    class cPresPing extends cBasePresentation
    {
        /**
         * Get the correct template path.
         *
         * @throws  Exception   Thrown if an exception was caught at a lower level.
         */
        public function __construct()
        {
            try
            {
                // call to base presentation constructor -- sets template path
                parent::__construct( sBASE_INC_PATH . '/templates' );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Presents the response of the ping to the client
         *
         * @param   string  $sPingResponse  Ping response from business
         *
         * @throws  Exception               rethrows anything it catches.
         *
         * @return  void
         */
        public function ShowPingResponse( $sPingResponse = '' )
        {
            try
            {
                // load http status codes.
                require_once( sCORE_INC_PATH . '/includes/HttpStatusCodes.php' );

                if ( $sPingResponse == '405' )
                {
                    // bad request method
                    header( 'Method Not Allowed', true, 405 );
                    header( 'Accept: GET' );
                }
                else if ( $sPingResponse == '403' )
                {
                    // return access denied
                    $iResponseCode = 403;
                    header( $aHttpStatusCodes[ $iResponseCode ], true, $iResponseCode );
                }
                else
                {
                    if ( trim( $sPingResponse ) !== '' )
                    {
                        // OK response
                        $iResponseCode = 200;
                        $sResponseBody = '';
                    }
                    else
                    {
                        // bad request
                        $iResponseCode = 400;
                        $sResponseBody = 'Request Data is empty.';
                    }

                    // return http headers
                    header( $aHttpStatusCodes[ $iResponseCode ], true, $iResponseCode );
                    echo $sResponseBody;
                }

                // halt execution.
                die();
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>