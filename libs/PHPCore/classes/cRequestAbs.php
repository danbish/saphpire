<?php
    /**
     *    Michael
     *
     *        I know we have a class in core that does some of this, but I want you to create a set of black box classes
     *        and an interface that will ultimately be used as a local processor or test processor for all REST methods.
     *
     *        We have some CURL stuff in place, but I want you to ignore that code and start from scratch.
     *
     *        You will need to create all of the methods that make calls in the classes so that they can use CURL and
     *        HTTP Requests (e.g. http://php.net/manual/en/httprequest.send.php).
     *
     *        This is to say that your interface should work like the db abstraction layer.  The applications that use
     *        it should be able to specify which call set (CURL or HTTP Req) they want to use and have the same methods.
     *
     *        Be sure to allow the call by type
     *
     *        Be sure to allow the content-type to be set in the request
     *
     *        Phase 2 - Benchmark the two methods and see what is more efficient in terms of speed.
     *        Phase 2 - You will need to add a generic class that will allow the application to generically call the
     *                  methods without specifying the type.
     *
     *                  The default type will be whichever we find has a better benchmark.  When an application doesn't
     *                  specify CURL or HTTP Req, this generic class will see if the default is available and if not,
     *                  try the alternate method.  Whichever is available first gets used.
     *
     */
    /**
     * Request class abstraction layer
     *
     * @author Michael Alphonso
     *
     * @package Request
     *
     * @version 0.0.0.1
     *
     * usage:
     * $oCurl = cRequestAbs::GetObj( 'curl' );
     * $oHttp = cRequestAbs::GetObj( 'http' ) | cRequestAbs::GetObj( 'httprequest' );
     */
    class cRequestAbs
    {
        /**
         * Static default requestor.
         *
         * @var    string
         */
        public static $sDefault = 'curl';

        /**
         * Flag to turn of debug output of headers
         * in the response body.
         *
         * @var    object
         */
        protected static $bDebugOutput = true;

        /**
         * Creates a request class object based on the requestor
         * you have chosen.
         *
         * @param   string  $sRequestor Requestor class (curl, http|httprequest)
         *
         * @throws  Exception rethrows anything it catches.
         *
         * @return  object  Request class object
         */
        public static function GetObj( $sRequestor = '' )
        {
            try
            {
                // initialize request object
                $oRequestObj = null;
                $sRequestorToUse = strtolower( !empty( $sRequestor ) ? $sRequestor : self::$sDefault );

                if ( empty( $sRequestorToUse ) )
                {
                    throw new Exception( 'No default requestor specified. Must choose one.' );
                }
                else
                {
                    if ( $sRequestorToUse == 'curl' && extension_loaded( 'curl' ) )
                    {
                        require_once( 'cCurlRequest.php' );
                        $oRequestObj = new cCurlRequest( self::$bDebugOutput );
                    }
                    else if (  ( $sRequestorToUse == 'http'      || $sRequestorToUse == 'httprequest' )
                            && ( extension_loaded( 'http' ) || extension_loaded( 'pecl_http' ) ) )
                    {
                        require_once( 'cHttpRequest.php' );
                        $oRequestObj = new cHttpRequest( self::$bDebugOutput );
                    }
                    else
                    {
                        // do nothing
                        throw new Exception( 'Invalid requestor selected.' );
                    }
                }
                return $oRequestObj;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>