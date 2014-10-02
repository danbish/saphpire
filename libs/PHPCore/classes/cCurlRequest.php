<?php
    require_once( 'ifRequest.php' );

    /**
     * cURL Request class
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
    class cCurlRequest implements ifRequest
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
         *
         * @var    integer
         */
        public $iMaxRedirects    = 10;

        /**
         * Flag to allow auto redirect
         * default=true
         *
         * @var    boolean
         */
        public $bAutoRedirect    = true;

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
        public $aCurlOpts;

        /**
         * Array of request headers
         *
         * @var    array
         */
        protected $aHeaders;

        /**
         * Flag to turn of debug output of headers
         * in the response body.
         *
         * @var    object
         */
        protected $bDebugOutput = true;

        /**
         * cURL class constructor
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
                $this->aCurlOpts   = array();

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
         * Sends a GET request from cURL
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
         * Sends a POST request from cURL
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
                $this->aCurlOpts[ CURLOPT_POST ]       = count( $aFields );
                $this->aCurlOpts[ CURLOPT_POSTFIELDS ] = http_build_query( $aFields );

                return $this->Send( $sUrl );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Sends a PUT request from cURL
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
                // allocate some memory
                $rhInput = fopen( 'php://temp', 'rw+' );

                if ( $bJsonEncode )
                {
                    // json encode array, set json header
                    $jsonInput = json_encode( $vRequestBody );
                    $sInput    = $jsonInput;
                    $this->aHeaders[] = 'Content-Type: application/json';
                }
                else
                {
                    $sInput = $vRequestBody;
                }
                // get filesize
                $iFileSize = strlen( $sInput );

                // write file to memory, reset the pointer
                fwrite( $rhInput, $sInput );
                rewind( $rhInput );

                // set curl options for input file
                return $this->PutFile( $sUrl, $rhInput, $iFileSize );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Sends a PUT file request from cURL. Requires a
         * resource handle and filesize for the file
         * that is being sent via PUT.
         *
         * Returns code: 201 - Created on success.
         *
         * @param   string      $sUrl       URL to query
         * @param   resource    $rhInput    Resource handle to file
         * @param   integer     $iFileSize  File size
         *
         * @throws  Exception               rethrows anything it catches.
         *
         * @return  array( string, string )
         */
        public function PutFile( $sUrl, $rhInput, $iFileSize )
        {
            try
            {
                // set put file options
                $this->aCurlOpts[ CURLOPT_PUT ] = true;

                if ( !empty( $rhInput ) )
                {
                    // set curl options for input file
                    $this->aCurlOpts[ CURLOPT_INFILE ]     = $rhInput;
                    $this->aCurlOpts[ CURLOPT_INFILESIZE ] = $iFileSize;
                }
                return $this->Send( $sUrl );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Sends a DELETE request from cURL. To receive the
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
                $this->aCurlOpts[ CURLOPT_CUSTOMREQUEST ] = 'DELETE';
                $this->aCurlOpts[ CURLOPT_POSTFIELDS ]    = http_build_query( $aFields );

                return $this->Send( $sUrl );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Main method for sending cURL requests.
         *
         * @param    string         $sUrl       URL to query
         * @param    string|array   $vFields    Query fields (key=>value)
         * @param    string         $sType      Request method:
         *                                      GET, POST, PUT or DELETE
         *
         * @throws  Exception rethrows anything it catches.
         *
         * @return  array( string, string )     Response body, HTTP status code.
         */
        public function Send( $sUrl )
        {
            try
            {
                // open the curl connection
                $rhCurl = curl_init();

                // set up request headers
                $aHeaders = $this->aHeaders;

                // set static headers
                $aHeaders[] = 'Accept-Language:' . $this->sAcceptLanguage;

                // set content-type
                if ( !empty( $this->sContentType ) )
                {
                    $aHeaders[] = 'Content-Type:' . $this->sContentType;
                }
                // set keep-alive
                if ( $this->bKeepAlive )
                {
                    $aHeaders[] = 'Connection:keep-alive';
                }
                // set host header
                if ( !empty( $this->sHost ) )
                {
                    $aHeaders[] = 'Host:' . $this->sHost;
                }
                // set referer
                if ( !empty( $this->sReferer ) )
                {
                    $aHeaders[] = 'Referer: ' . $this->sReferer;
                }

                // set options array
                $this->aCurlOpts[ CURLOPT_URL ]            = $sUrl;                   // set request url
                $this->aCurlOpts[ CURLOPT_RETURNTRANSFER ] = true;                    // return response to variable, not screen.
                $this->aCurlOpts[ CURLOPT_HEADER ]         = $this->bDebugOutput;     // true=debug on | false=debug off
                $this->aCurlOpts[ CURLOPT_FOLLOWLOCATION ] = $this->bAutoRedirect;    // follow redirects
                $this->aCurlOpts[ CURLOPT_ENCODING ]       = $this->sAcceptEncoding;  // handle all encodings
                $this->aCurlOpts[ CURLOPT_USERAGENT ]      = $this->sUserAgent;       // user-agent
                $this->aCurlOpts[ CURLOPT_AUTOREFERER ]    = true;                    // set referer on redirect
                $this->aCurlOpts[ CURLOPT_CONNECTTIMEOUT ] = $this->iConnectTimeout;  // timeout on connect
                $this->aCurlOpts[ CURLOPT_TIMEOUT ]        = $this->iResponseTimeout; // timeout on response
                $this->aCurlOpts[ CURLOPT_MAXREDIRS ]      = $this->iMaxRedirects;    // stop after x redirects
                $this->aCurlOpts[ CURLOPT_SSL_VERIFYPEER ] = false;                   // disable ssl cert verification
                $this->aCurlOpts[ CURLOPT_HTTPHEADER ]     = $aHeaders;               // set custom request headers

                // set options with array
                curl_setopt_array( $rhCurl, $this->aCurlOpts );

                // execute post, capture result body
                $sResult = curl_exec( $rhCurl );

                // get http status code
                $sCode   = curl_getinfo( $rhCurl, CURLINFO_HTTP_CODE );

                // close connection
                curl_close( $rhCurl );

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