<?php
    require_once( 'ifRequest.php' );

    /**
     * HttpRequest class
     *
     * @author Michael Alphonso
     *
     * @package Request
     *
     * @version 0.0.0.1
     *
     * Properties:
     *
     * sContentType     ( application/x-www-form-urlencoded, multipart/form-data )
     * sUserAgent       ( X-Juggernaut )
     * iMaxRedirects    ( 10 )
     * bAutoRedirect    ( true )
     * iConnectTimeout  ( 20s )
     * iResponseTimeout ( 20s )
     * iMaxRetries      ( 1 )
     * bKeepAlive       (Connection: keep-alive)
     * sReferer         ( www.clemson.edu )
     * sAcceptEncoding  ( gzip, deflate )
     * sAccept          ( text/html,application/xhtml+xml,application/xml;q=0.8,* /*;q=0.5 )
     * sAcceptLanguage  ( en-US,en )
     * sHost
     */
    class cHttpRequest implements ifRequest
    {
        /**
         * Content type used during POST method
         *
         * application/x-www-form-urlencoded, multipart/form-data
         *
         * @var    string
         */
        public $sContentType     = '';

        /**
         * User-agent string.
         *
         * @var    string
         */
        public $sUserAgent       = 'X-Juggernaut';

        /**
         * Maximum number of redirects for a single request
         * default=10
         * 0=disabled.
         *
         * @var    integer
         */
        public $iMaxRedirects    = 10;

        /**
         * Connection timeout, in seconds.
         *
         * @var    integer
         */
        public $iConnectTimeout  = 20;

        /**
         * Response timeout, in seconds.
         *
         * @var    integer
         */
        public $iResponseTimeout = 20;

        /**
         * Maximum number of times to re-attempt a failed request
         * default=1
         *
         * @var    integer
         */
        public $iMaxRetries      = 1;

        /**
         * Flag to set Connection: keep-alive header in request
         *
         * @var    boolean
         */
        public $bKeepAlive       = true;

        /**
         * Http Referer header
         *
         * @var    string
         */
        public $sReferer         = '';

        /**
         * Http header for default encoding
         *
         * @var    string
         */
        public $sAcceptEncoding  = 'gzip,deflate';

        /**
         * Http Accept header
         *
         * @var    string
         */
        public $sAccept          = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';

        /**
         * Accept-Language header
         *
         * @var    string
         */
        public $sAcceptLanguage  = 'en-US,en';

        /**
         * Host header.
         *
         * @var    string
         */
        public $sHost;

        /**
         * Array of cURL options
         *
         * @var    array
         */
        public $aHttpOpts;

        /**
         * Array of cURL options
         *
         * @var    array
         */
        protected $aPostFields;

        /**
         * Array of request headers
         *
         * @var    array
         */
        protected $aHeaders;

        /**
         * HttpRequest object
         *
         * @var    object
         */
        protected $oHttpReq;

        /**
         * Flag to turn of debug output of headers
         * in the response body.
         *
         * @var    object
         */
        protected $bDebugOutput = true;

        /**
         * HttpRequest class constructor.
         *
         * @param   boolean     $bDebugOutput   true|false turns on debug output
         *                                      of response headers in response body
         *
         * @throws  Exception rethrows anything it catches.
         */
        public function __construct( $bDebugOutput = null )
        {
            try
            {
                $this->sUserAgent .= '-' . phpversion();
                $this->aHeaders    = array();
                $this->aPostFields = array();
                $this->aHttpOpts   = array();
                $this->oHttpReq    = new HttpRequest();

                // wait for boolean value
                if ( !is_null( $bDebugOutput ) || is_bool( $bDebugOutput ) )
                {
                    $this->bDebugOutput = $bDebugOutput;
                }
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Sends a GET request from HttpRequest
         *
         * @param   string  $sUrl   URL to query
         *
         * @throws  Exception       rethrows anything it catches.
         *
         * @return  array( string, string )
         */
        public function Get( $sUrl )
        {
            try
            {
                return $this->Send( $sUrl );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Sends a POST request from HttpRequest
         *
         * @param   string  $sUrl       URL to query
         * @param   array   $aFields    Array of post data
         *
         * @throws  Exception           rethrows anything it catches.
         *
         * @return  array( string, string )
         */
        public function Post( $sUrl, array $aFields )
        {
            try
            {
                // set options
                $this->oHttpReq->setMethod( HTTP_METH_POST );
                $this->aPostFields = $aFields;
                $this->oHttpReq->setPostFields( $this->aPostFields );

                return $this->Send( $sUrl );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Sends a PUT request from HttpRequest
         *
         * @param   string  $sUrl           URL to query
         * @param   array   $vRequestBody   Request body
         * @param   boolean $bJsonEncode    json_encode the request body
         *                                  true | false
         *
         * @throws  Exception               rethrows anything it catches.
         *
         * @return  array( string, string )
         */
        public function Put( $sUrl, $vRequestBody, $bJsonEncode = false )
        {
            try
            {
               if ( $bJsonEncode )
                {
                    // json encode array, set json header
                    $jsonInput = json_encode( $vRequestBody );
                    $sInput    = $jsonInput;
                    $this->aHeaders[ 'Content-Type' ] = 'application/json';
                }
                else
                {
                    $sInput = $vRequestBody;
                }

                // set httprequest options for input file
                return $this->PutFile( $sUrl, $sInput );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Sends a PUT file request from HttpRequest. Requires a string
         * that contains the payload for the file that is being sent
         * via PUT.
         *
         * Returns code: 201 - Created on success.
         *
         * @param   string      $sUrl   URL to query
         * @param   string      $sInput String data of input
         *
         * @throws  Exception           rethrows anything it catches.
         *
         * @return  array( string, string )
         */
        public function PutFile( $sUrl, $sInput )
        {
            try
            {
                // set request method
                $this->oHttpReq->setMethod( HTTP_METH_PUT );

                if ( !empty( $sInput ) )
                {
                    // set put data for request
                    $this->oHttpReq->setPutData( $sInput );
                }
                return $this->Send( $sUrl );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Sends a DELETE request from HttpRequest. To receive the
         * payload for this request in PHP, you must
         * access it with 'php://input' stream.
         *
         * @param   string  $sUrl       URL to query
         * @param   array   $aFields    Array of post data
         *
         * @throws  Exception           rethrows anything it catches.
         *
         * @return  array( string, string )
         */
        public function Delete( $sUrl, array $aFields = array() )
        {
            try
            {
                // set options
                $this->oHttpReq->setMethod( HTTP_METH_DELETE );
                $this->aPostFields = $aFields;
                $this->oHttpReq->setPostFields( $this->aPostFields );

                return $this->Send( $sUrl );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Main method for sending HttpRequest requests.
         *
         * @param   string      $sUrl   URL to query
         *
         * @throws  Exception           rethrows anything it catches.
         *
         * @return  array( string, string )     Response body, HTTP status code.
         */
        public function Send( $sUrl )
        {
            try
            {
                // set up request headers
                $aHeaders = $this->aHeaders;

                // set static headers
                $aHeaders[ 'Accept-Language' ] = $this->sAcceptLanguage;

                // set content-type
                if ( !empty( $this->sContentType ) )
                {
                    $aHeaders[ 'Content-Type' ] = $this->sContentType;
                }
                // set keep-alive
                if ( $this->bKeepAlive )
                {
                    $aHeaders[ 'Connection' ] = 'keep-alive';
                }
                // set host header
                if ( !empty( $this->sHost ) )
                {
                    $aHeaders[ 'Host' ] = $this->sHost;
                }
                // set referer
                if ( !empty( $this->sReferer ) )
                {
                    $aHeaders[ 'Referer' ] = $this->sReferer;
                }
                // set accept encoding
                if ( !empty( $this->sAcceptEncoding ) )
                {
                    $aHeaders[ 'Accept-Encoding' ] = $this->sAcceptEncoding;
                }

                // set options array
                $this->aHttpOpts[ 'url' ]                 = $sUrl;                   // set request url
                $this->aHttpOpts[ 'redirect' ]            = $this->iMaxRedirects;    // follow redirects
                $this->aHttpOpts[ 'useragent' ]           = $this->sUserAgent;       // user-agent
                $this->aHttpOpts[ 'connecttimeout' ]      = $this->iConnectTimeout;  // timeout on connect
                $this->aHttpOpts[ 'timeout' ]             = $this->iResponseTimeout; // timeout on response
                $this->aHttpOpts[ 'ssl' ][ 'verifypeer' ] = false;                   // disable ssl cert verification
                $this->aHttpOpts[ 'headers' ]             = $aHeaders;               // set custom request headers

                // set options
                $this->oHttpReq->setOptions( $this->aHttpOpts );

                // send request, returns HttpMessage object
                $oHttpResp = $this->oHttpReq->send();

                // capture result body
                $sResult = $oHttpResp->getBody();

                // compile headers to look like cURL
                $aHeaders = $oHttpResp->getHeaders();
                $sHeaders = 'HTTP/' . $oHttpResp->getHttpVersion() . ' ' .
                            $oHttpResp->getResponseCode() . ' ' .
                            $oHttpResp->getResponseStatus();
                foreach ( $aHeaders as $sKey => $sHeader )
                {
                    $sHeaders .= ( $sHeaders !== '' ? "\n" : '' ) . $sKey . ': ' . $sHeader;
                }

                // add response headers to body if bDebugOutput=true
                $sResult = ( $this->bDebugOutput ? ( $sHeaders . "\n\n" ) : '' ) . $sResult;

                // get http status code
                $sCode   = $oHttpResp->getResponseCode();

                // return the body of the response and http response code
                return array( $sResult, $sCode );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Destructor.
         *
         * @throws  Exception rethrows anything it catches.
         */
        public function __destruct()
        {
            try
            {
                // garbage collect if possible
                if( function_exists( 'gc_collect_cycles' ) )
                {
                    gc_collect_cycles();
                }
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
         * @return  $this->$bDebugOutput
         */
        public function getDebugOutput()
        {
            try
            {
                return $this->bDebugOutput;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>