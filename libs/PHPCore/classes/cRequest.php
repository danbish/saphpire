<?php
    require_once( 'cRequestAbs.php' );
    /**
     * Generic Request class
     *
     * Phase 2 - You will need to add a generic class that will allow the application to generically call the
     *             methods without specifying the type.
     *
     *             The default type will be whichever we find has a better benchmark.  When an application doesn't
     *             specify CURL or HTTP Req, this generic class will see if the default is available and if not,
     *             try the alternate method.  Whichever is available first gets used.
     *
     * @author Michael Alphonso
     *
     * @package Request
     *
     * @version 0.0.0.1
     *
     * usage:
     * $oRequest = new cRequest(); // default requestor selected.
     *
     * $oCurl = new cRequest( 'curl' );
     * $oCurl->Get( 'https://clemson.edu' );
     * $oCurl->Post( 'https://clemson.edu', array( 'search' => 1 ) );
     * $oCurl->Put( 'https://clemson.edu', array( 'file' => 'test' ) );
     * $oCurl->Delete( 'https://clemson.edu', array( 'id' => 1 ) );
     *
     * $oHttp = new cRequest( 'http' );
     * $oHttp->Get( 'https://clemson.edu' );
     * $oHttp->Post( 'https://clemson.edu', array( 'search' => 1 ) );
     * $oHttp->Put( 'https://clemson.edu', array( 'file' => 'test' ) );
     * $oHttp->Delete( 'https://clemson.edu', array( 'id' => 1 ) );
     */
    class cRequest
    {
        /**
         * Instantiated request class object to reference
         *
         * @var    object
         */
        protected $oRequestObj = null;

        /**
         * Constructor for generic request class. Uses a requestor parameter
         * if specified, otherwise reverts to default value set in property
         *
         * @param   string  $sRequestor Request class to use (curl | http)
         *
         * @throws  Exception rethrows anything it catches.
         */
        public function __construct( $sRequestor = '' )
        {
            try
            {
                $sRequestorToUse = strtolower( !empty( $sRequestor ) ? $sRequestor : cRequestAbs::$sDefault );
                if ( empty( $sRequestorToUse ) )
                {
                    throw new Exception( 'No default requestor specified. Must choose one.' );
                }
                else
                {
                    if ( $sRequestorToUse == 'curl' && extension_loaded( 'curl' ) )
                    {
                        $this->oRequestObj = cRequestAbs::GetObj( 'curl' );
                    }
                    else if (  ( $sRequestorToUse == 'http'      || $sRequestorToUse == 'httprequest' )
                            && ( extension_loaded( 'http' ) || extension_loaded( 'pecl_http' ) ) )
                    {
                        $this->oRequestObj = cRequestAbs::GetObj( 'http' );
                    }
                    else
                    {
                        // use default
                        $this->oRequestObj = cRequestAbs::GetObj( cRequestAbs::$sDefault );
                    }
                }
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Gets the request class object from the class properties
         *
         * @throws  Exception               rethrows anything it catches.
         *
         * @return  null|object  $oRequestObj    Request class object
         */
        public function GetObj()
        {
            try
            {
                return $this->oRequestObj;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Wrapper around the request objects GET request method.
         *
         * @param   string  $sUrl   URL to query
         *
         * @throws  Exception rethrows anything it catches.
         *
         * @return  array(string, string)   response body, response code
         */
        public function Get( $sUrl )
        {
            try
            {
                return $this->oRequestObj->Get( $sUrl );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Wrapper around the request objects POST request method.
         *
         * @param   string  $sUrl       URL to query
         * @param   array   $aFields    Array of key=>value parameters for
         *                              query string
         *
         * @throws  Exception rethrows anything it catches.
         *
         * @return  array(string, string)   response body, response code
         */
        public function Post( $sUrl, array $aFields )
        {
            try
            {
                return $this->oRequestObj->Post( $sUrl, $aFields );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Wrapper around the request objects PUT request method.
         *
         * @param   string  $sUrl       URL to query
         * @param   array   $aFields    Array of key=>value parameters for
         *                              query string
         *
         * @throws  Exception rethrows anything it catches.
         *
         * @return  array(string, string)   response body, response code
         */
        public function Put( $sUrl, $vRequestBody, $bJsonEncode = false )
        {
            try
            {
                return $this->oRequestObj->Put( $sUrl, $vRequestBody, $bJsonEncode );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Wrapper around the request objects DELETE request method.
         *
         * @param   string  $sUrl       URL to query
         * @param   array   $aFields    Array of key=>value parameters for
         *                              query string (optional)
         *
         * @throws  Exception rethrows anything it catches.
         *
         * @return  array(string, string)   response body, response code
         */
        public function Delete( $sUrl, array $aFields = array() )
        {
            try
            {
                return $this->oRequestObj->Delete( $sUrl, $aFields );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * getter for $bDebugOutput.
         *
         * @throws  Exception rethrows anything it catches.
         *
         * @return  $this->oRequestObj->$bDebugOutput
         */
        public function getDebugOutput()
        {
            try
            {
                return $this->oRequestObj->getDebugOutput();
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>