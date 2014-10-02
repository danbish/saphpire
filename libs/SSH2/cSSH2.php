<?php
    // load definitions
    require_once( 'includes/ssh2-defined.php' );

    //
    /**
     * Pure-PHP implementation of SSHv2.
     * @link http://www.ietf.org/rfc/rfc4253.txt
     *
     * PHP version 5
     *
     * PHP4 Incompatibility:
     *     microtime(true)
     *
     * Juggernaut standardized version
     *
     * usage:
     *
     * login
     *     $oSSH2 = new SSH2( 'devsislin01.clemson.edu' );
     *     if ( !$oSSH2->Login( $sUser, $sPass ) )
     *     {
     *         exit( 'Login Failed' );
     *     }
     *
     * send commands thru the shell.
     *
     *     $oSSH2->Cmd( 'uptime' );
     *
     * read/write directly to the command prompt
     *
     *     $oSSH2->Write( "uptime\n" );
     *     echo $oSSH2->Read( );
     *
     * @author Academic Services::Team Ra
     * @version 0.2.0
     *
     * @todo Refactor multiple returns
     * @todo Split class based on dependecies.
     * @todo optimize specific pieces of code:
     *           KeyExchange.
     *           Matching algorithm loops.
     */
    class cSSH2
    {
        /**
         * The SSH identifier
         *
         * @var String
         */
        private $sIndentifier = 'SSH-2.0-jds-1.0.0';

        /**
         * The Socket Object
         *
         * @var Object
         */
        public $oSocket;

        /**
         * Execution Bitmap
         *
         * The bits that are set represent functions that have been called already.  This is used to determine
         * if a requisite function has been successfully executed.  If not, an error should be thrown.
         *
         * @var Integer
         */
        public $iBitmap = 0;

        /**
         * Error information
         *
         * @see SSH2::GetErrors()
         * @see SSH2::GetLastError()
         * @var Array
         */
        private $aErrors = array();

        /**
         * Server Identifier
         *
         * @see SSH2::GetServerIdentification()
         * @var String
         */
        private $sServerIdentifier = '';

        /**
         * Key Exchange Algorithms
         *
         * @see SSH2::getKexAlgorithims()
         * @var Array
         */
        private $aKexAlgorithms;

        /**
         * Server Host Key Algorithms
         *
         * @see SSH2::GetServerHostKeyAlgorithms()
         * @var Array
         */
        private $aServerHostKeyAlgorithms;

        /**
         * Encryption Algorithms: Client to Server
         *
         * @see SSH2::GetEncryptionAlgorithmsClient2Server()
         * @var Array
         */
        private $aEncryptionAlgorithmsClientToServer;

        /**
         * Encryption Algorithms: Server to Client
         *
         * @see SSH2::GetEncryptionAlgorithmsServer2Client()
         * @var Array
         */
        private $aEncryptionAlgorithmsServerToClient;

        /**
         * MAC Algorithms: Client to Server
         *
         * @see SSH2::GetMACAlgorithmsClient2Server()
         * @var Array
         */
        private $aMacAlgorithmsClientToServer;

        /**
         * MAC Algorithms: Server to Client
         *
         * @see SSH2::GetMACAlgorithmsServer2Client()
         * @var Array
         */
        private $aMacAlgorithmsServerToClient;

        /**
         * Compression Algorithms: Client to Server
         *
         * @see SSH2::GetCompressionAlgorithmsClient2Server()
         * @var Array
         */
        private $aCompressionAlgorithmsClientToServer;

        /**
         * Compression Algorithms: Server to Client
         *
         * @see SSH2::GetCompressionAlgorithmsServer2Client()
         * @var Array
         */
        private $aCompressionAlgorithmsServerToClient;

        /**
         * Languages: Server to Client
         *
         * @see SSH2::GetLanguagesServer2Client()
         * @var Array
         */
        private $aLanguagesServerToClient;

        /**
         * Languages: Client to Server
         *
         * @see SSH2::GetLanguagesClient2Server()
         * @var Array
         */
        private $aLanguagesClientToServer;

        /**
         * Block Size for Server to Client Encryption
         *
         * "Note that the length of the concatenation of 'packet_length',
         *  'padding_length', 'payload', and 'random padding' MUST be a multiple
         *  of the cipher block size or 8, whichever is larger.  This constraint
         *  MUST be enforced, even when using stream ciphers."
         *
         *  -- http://tools.ietf.org/html/rfc4253#section-6
         *
         * @see SSH2::__construct()
         * @see SSH2::SendBinaryPacket()
         * @var Integer
         */
        private $iEncryptBlockSize = 8;

        /**
         * Block Size for Client to Server Encryption
         *
         * @see SSH2::__construct()
         * @see SSH2::GetBinaryPacket()
         * @var Integer
         */
        private $iDecryptBlockSize = 8;

        /**
         * Server to Client Encryption Object
         *
         * @see SSH2::GetBinaryPacket()
         * @var Object
         */
        private $oDecrypt = false;

        /**
         * Client to Server Encryption Object
         *
         * @see SSH2::SendBinaryPacket()
         * @var Object
         */
        private $oEncrypt = false;

        /**
         * Client to Server HMAC Object
         *
         * @see SSH2::SendBinaryPacket()
         * @var Object
         */
        private $oHmacCreate = false;

        /**
         * Server to Client HMAC Object
         *
         * @see SSH2::GetBinaryPacket()
         * @var Object
         */
        private $oHmacCheck = false;

        /**
         * Size of server to client HMAC
         *
         * We need to know how big the HMAC will be for the server to client
         * direction so that we know how many bytes to read.
         * For the client to server side, the HMAC object will make the HMAC
         * as long as it needs to be.  All we need to do is append it.
         *
         * @see SSH2::GetBinaryPacket()
         * @var Integer
         */
        private $iHmacSize = false;

        /**
         * Server Public Host Key
         *
         * @see SSH2::GetServerPublicHostKey()
         * @var String
         */
        private $sServerPublicHostKey;

        /**
         * Session identifer
         *
         * "The exchange hash H from the first key exchange is additionally
         *  used as the session identifier, which is a unique identifier for
         *  this connection."
         *
         *  -- http://tools.ietf.org/html/rfc4253#section-7.2
         *
         * @see SSH2::KeyExchange()
         * @var String
         */
        private $sSessionId = false;

        /**
         * Exchange hash
         *
         * The current exchange hash
         *
         * @see SSH2::KeyExchange()
         * @var String
         */
        private $sExchangeHash = false;

        /**
         * Message Numbers
         *
         * @see SSH2::__construct()
         * @var Array
         */
        private $aMessageNumbers = array();

        /**
         * Disconnection Message 'reason codes' defined in RFC4253
         *
         * @see SSH2::__construct()
         * @var Array
         */
        private $aDisconnectReasons = array();

        /**
         * SSH_MSG_CHANNEL_OPEN_FAILURE 'reason codes', defined in RFC4254
         *
         * @see SSH2::__construct()
         * @var Array
         */
        private $aChannelOpenFailureReasons = array();

        /**
         * Terminal Modes
         *
         * @link http://tools.ietf.org/html/rfc4254#section-8
         * @see SSH2::__construct()
         * @var Array
         */
        private $aTerminalModes = array();

        /**
         * SSH_MSG_CHANNEL_EXTENDED_DATA's data_type_codes
         *
         * @link http://tools.ietf.org/html/rfc4254#section-5.2
         * @see SSH2::__construct()
         * @var Array
         */
        private $aChannelExtendedDataTypeCodes = array();

        /**
         * Send Sequence Number
         *
         * See 'Section 6.4.  Data Integrity' of rfc4253 for more info.
         *
         * @see SSH2::SendBinaryPacket()
         * @var Integer
         */
        private $iSendSeqNo = 0;

        /**
         * Get Sequence Number
         *
         * See 'Section 6.4.  Data Integrity' of rfc4253 for more info.
         *
         * @see SSH2::GetBinaryPacket()
         * @var Integer
         */
        private $iGetSeqNo = 0;

        /**
         * Server Channels
         *
         * Maps client channels to server channels
         *
         * @see SSH2::GetChannelPacket()
         * @see SSH2::Cmd()
         * @var Array
         */
        public $aServerChannels = array();

        /**
         * Channel Buffers
         *
         * If a client requests a packet from one channel but receives two packets from another those packets should
         * be placed in a buffer
         *
         * @see SSH2::GetChannelPacket()
         * @see SSH2::Cmd()
         * @var Array
         */
        private $aChannelBuffers = array();

        /**
         * Channel Status
         *
         * Contains the type of the last sent message
         *
         * @see SSH2::GetChannelPacket()
         * @var Array
         */
        public $aChannelStatus = array();

        /**
         * Packet Size
         *
         * Maximum packet size indexed by channel
         *
         * @see SSH2::SendChannelPacket()
         * @var Array
         */
        public $aPacketSizeClientToServer = array();

        /**
         * Message Number Log
         *
         * @see SSH2::GetLog()
         * @var Array
         */
        private $aMessageNumberLog = array();

        /**
         * Message Log
         *
         * @see SSH2::GetLog()
         * @var Array
         */
        private $aMessageLog = array();

        /**
         * The Window Size
         *
         * Bytes the other party can send before it must wait for the window to be adjusted (0x7FFFFFFF = 2GB)
         *
         * @var Integer
         * @see SSH2::SendChannelPacket()
         * @see SSH2::Cmd()
         */
        public $iWindowSize = 0x7FFFFFFF;

        /**
         * Window size, server to client
         *
         * Window size indexed by channel
         *
         * @see SSH2::SendChannelPacket()
         * @var Array
         */
        public $aWindowSizeServerToClient = array();

        /**
         * Window size, client to server
         *
         * Window size indexed by channel
         *
         * @see SSH2::SendChannelPacket()
         * @var Array
         */
        public $aWindowSizeClientToServer = array();

        /**
         * Server signature
         *
         * Verified against $this->sSessionId
         *
         * @see SSH2::GetServerPublicHostKey()
         * @var String
         */
        private $sSignature = '';

        /**
         * Server signature format
         *
         * ssh-rsa or ssh-dss.
         *
         * @see SSH2::GetServerPublicHostKey()
         * @var String
         */
        private $sSignatureFormat = '';

        /**
         * Interactive Buffer
         *
         * @see SSH2::Read()
         * @var string
         */
        private $sInteractiveBuffer = '';

        /**
         * Current log size
         *
         * Should never exceed NET_SSH2_LOG_MAX_SIZE
         *
         * @see SSH2::SendBinaryPacket()
         * @see SSH2::GetBinaryPacket()
         * @var Integer
         */
        private $iLogSize;

        /**
         * Timeout
         *
         * @see SSH2::SetTimeout()
         * @var integer
         */
        private $iTimeout;

        /**
         * Current Timeout
         *
         * @see SSH2::GetChannelPacket()
         * @var integer
         */
        public $iCurTimeout;

        /**
         * Real-time log file pointer
         *
         * @see SSH2::AppendLog()
         * @var Resource
         */
        private $rhRealtimeLogFile;

        /**
         * Real-time log file size
         *
         * @see SSH2::AppendLog()
         * @var Integer
         */
        private $iRealtimeLogSize;

        /**
         * Has the signature been validated?
         *
         * @see SSH2::GetServerPublicHostKey()
         * @var Boolean
         */
        private $bSignatureValidated = false;

        /**
         * Real-time log file wrap boolean
         *
         * @see SSH2::AppendLog()
         * @var boolean
         */
        private $bRealtimeLogWrap;

        /**
         * Flag to suppress stderr from output
         *
         * @see SSH2::EnableQuietMode()
         * @var boolean
         */
        private $bQuietMode = false;

        /**
         * Time of first network activity
         *
         * @var float
         */
        private $fLastPacket;

        /**
         * Exit status returned from ssh if any
         *
         * @var Integer
         */
        private $iExitStatus;

        /**
         * Flag to request a PTY when using Cmd()
         *
         * @see SSH2::EnablePTY()
         * @var boolean
         */
        private $bRequestPTY = false;

        /**
         * Flag set while Cmd() is running when using EnablePTY()
         *
         * @var boolean
         */
        private $bInRequestPtyExec = false;

        /**
         * Flag set after StartSubsystem() is called
         */
        private $bInSubsystem;

        /**
         * Contents of stdError
         *
         * @var string
         */
        private $sStdErrorLog;

        /**
         * The Last Interactive Response
         *
         * @see SSH2::KeyboardInteractiveProcess()
         * @var string
         */
        private $sLastInteractiveResponse = '';

        /**
         * Keyboard Interactive Request / Responses
         *
         * @see SSH2::KeyboardInteractiveProcess()
         * @var array
         */
        private $aKeyboardRequestsResponses = array();

        /**
         * Banner Message
         *
         * Quoting from the RFC, "in some jurisdictions, sending a warning message before
         * authentication may be relevant for getting legal protection."
         *
         * @see SSH2::FilterPackets()
         * @see SSH2::GetBannerMessage()
         * @var string
         */
        private $sBannerMessage = '';

        /**
         * Did Read() timeout or return normally?
         *
         * @see SSH2::isTimeout
         * @var boolean
         */
        private $bIsTimeout = false;

        /**
         * SSH2 Class Constructor.
         *
         * Connects to an SSHv2 server
         *
         * @uses    Math_BigInteger
         * @uses    Crypt_Random
         * @uses    Crypt_Hash
         *
         * @param   String  $sHost
         * @param   Integer $iPort
         * @param   Integer $iTimeout in seconds
         *
         * @return  void
         */
        public function __construct( $sHost, $iPort = 22, $iTimeout = 10 )
        {
            // Include Math_BigInteger
            // Used to do Diffie-Hellman key exchange and DSA/RSA signature verification.
            if ( !class_exists( 'Math_BigInteger' ) )
            {
                require_once( 'includes/Math/BigInteger.php' );
            }
            if ( !function_exists( 'crypt_random_string' ) )
            {
                require_once( 'includes/Crypt/Random.php' );
            }
            if ( !class_exists( 'Crypt_Hash' ) )
            {
                require_once( 'includes/Crypt/Hash.php' );
            }
            // record last packet time.
            $this->fLastPacket = microtime( true );

            // Add space to prevent allocation of unnecessary indices.
            // lookup table for messages.
            $this->aMessageNumbers = array(
                " " . NET_SSH2_MSG_DISCONNECT                => 'NET_SSH2_MSG_DISCONNECT',
                " " . NET_SSH2_MSG_IGNORE                    => 'NET_SSH2_MSG_IGNORE',
                " " . NET_SSH2_MSG_UNIMPLEMENTED             => 'NET_SSH2_MSG_UNIMPLEMENTED',
                " " . NET_SSH2_MSG_DEBUG                     => 'NET_SSH2_MSG_DEBUG',
                " " . NET_SSH2_MSG_SERVICE_REQUEST           => 'NET_SSH2_MSG_SERVICE_REQUEST',
                " " . NET_SSH2_MSG_SERVICE_ACCEPT            => 'NET_SSH2_MSG_SERVICE_ACCEPT',
                " " . NET_SSH2_MSG_KEXINIT                   => 'NET_SSH2_MSG_KEXINIT',
                " " . NET_SSH2_MSG_NEWKEYS                   => 'NET_SSH2_MSG_NEWKEYS',
                " " . NET_SSH2_MSG_KEXDH_INIT                => 'NET_SSH2_MSG_KEXDH_INIT',
                " " . NET_SSH2_MSG_KEXDH_REPLY               => 'NET_SSH2_MSG_KEXDH_REPLY',
                " " . NET_SSH2_MSG_USERAUTH_REQUEST          => 'NET_SSH2_MSG_USERAUTH_REQUEST',
                " " . NET_SSH2_MSG_USERAUTH_FAILURE          => 'NET_SSH2_MSG_USERAUTH_FAILURE',
                " " . NET_SSH2_MSG_USERAUTH_SUCCESS          => 'NET_SSH2_MSG_USERAUTH_SUCCESS',
                " " . NET_SSH2_MSG_USERAUTH_BANNER           => 'NET_SSH2_MSG_USERAUTH_BANNER',
                " " . NET_SSH2_MSG_GLOBAL_REQUEST            => 'NET_SSH2_MSG_GLOBAL_REQUEST',
                " " . NET_SSH2_MSG_REQUEST_SUCCESS           => 'NET_SSH2_MSG_REQUEST_SUCCESS',
                " " . NET_SSH2_MSG_REQUEST_FAILURE           => 'NET_SSH2_MSG_REQUEST_FAILURE',
                " " . NET_SSH2_MSG_CHANNEL_OPEN              => 'NET_SSH2_MSG_CHANNEL_OPEN',
                " " . NET_SSH2_MSG_CHANNEL_OPEN_CONFIRMATION => 'NET_SSH2_MSG_CHANNEL_OPEN_CONFIRMATION',
                " " . NET_SSH2_MSG_CHANNEL_OPEN_FAILURE      => 'NET_SSH2_MSG_CHANNEL_OPEN_FAILURE',
                " " . NET_SSH2_MSG_CHANNEL_WINDOW_ADJUST     => 'NET_SSH2_MSG_CHANNEL_WINDOW_ADJUST',
                " " . NET_SSH2_MSG_CHANNEL_DATA              => 'NET_SSH2_MSG_CHANNEL_DATA',
                " " . NET_SSH2_MSG_CHANNEL_EXTENDED_DATA     => 'NET_SSH2_MSG_CHANNEL_EXTENDED_DATA',
                " " . NET_SSH2_MSG_CHANNEL_EOF               => 'NET_SSH2_MSG_CHANNEL_EOF',
                " " . NET_SSH2_MSG_CHANNEL_CLOSE             => 'NET_SSH2_MSG_CHANNEL_CLOSE',
                " " . NET_SSH2_MSG_CHANNEL_REQUEST           => 'NET_SSH2_MSG_CHANNEL_REQUEST',
                " " . NET_SSH2_MSG_CHANNEL_SUCCESS           => 'NET_SSH2_MSG_CHANNEL_SUCCESS',
                " " . NET_SSH2_MSG_CHANNEL_FAILURE           => 'NET_SSH2_MSG_CHANNEL_FAILURE'
            );
            // lookup table for disconnect reasons.
            $this->aDisconnectReasons = array(
                " " . NET_SSH2_DISCONNECT_HOST_NOT_ALLOWED_TO_CONNECT    => 'NET_SSH2_DISCONNECT_HOST_NOT_ALLOWED_TO_CONNECT',
                " " . NET_SSH2_DISCONNECT_PROTOCOL_ERROR                 => 'NET_SSH2_DISCONNECT_PROTOCOL_ERROR',
                " " . NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED            => 'NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED',
                " " . NET_SSH2_DISCONNECT_RESERVED                       => 'NET_SSH2_DISCONNECT_RESERVED',
                " " . NET_SSH2_DISCONNECT_MAC_ERROR                      => 'NET_SSH2_DISCONNECT_MAC_ERROR',
                " " . NET_SSH2_DISCONNECT_COMPRESSION_ERROR              => 'NET_SSH2_DISCONNECT_COMPRESSION_ERROR',
                " " . NET_SSH2_DISCONNECT_SERVICE_NOT_AVAILABLE          => 'NET_SSH2_DISCONNECT_SERVICE_NOT_AVAILABLE',
                " " . NET_SSH2_DISCONNECT_PROTOCOL_VERSION_NOT_SUPPORTED => 'NET_SSH2_DISCONNECT_PROTOCOL_VERSION_NOT_SUPPORTED',
                " " . NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE        => 'NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE',
                " " . NET_SSH2_DISCONNECT_CONNECTION_LOST                => 'NET_SSH2_DISCONNECT_CONNECTION_LOST',
                " " . NET_SSH2_DISCONNECT_BY_APPLICATION                 => 'NET_SSH2_DISCONNECT_BY_APPLICATION',
                " " . NET_SSH2_DISCONNECT_TOO_MANY_CONNECTIONS           => 'NET_SSH2_DISCONNECT_TOO_MANY_CONNECTIONS',
                " " . NET_SSH2_DISCONNECT_AUTH_CANCELLED_BY_USER         => 'NET_SSH2_DISCONNECT_AUTH_CANCELLED_BY_USER',
                " " . NET_SSH2_DISCONNECT_NO_MORE_AUTH_METHODS_AVAILABLE => 'NET_SSH2_DISCONNECT_NO_MORE_AUTH_METHODS_AVAILABLE',
                " " . NET_SSH2_DISCONNECT_ILLEGAL_USER_NAME              => 'NET_SSH2_DISCONNECT_ILLEGAL_USER_NAME'
            );

            // record start time.
            $fStart = microtime( true );

            // attempt socket connection
            $this->oSocket = @fsockopen( $sHost, $iPort, $iErrNo, $sError, $iTimeout );
            if ( !$this->oSocket )
            {
                user_error( rtrim( "Cannot connect to $sHost. Error $iErrNo. $sError" ) );
            }
            else
            {
                // record elapsed time
                $fElapsed = microtime( true ) - $fStart;

                // check for timeout
                $iTimeout -= $fElapsed;
                if ( $iTimeout <= 0 )
                {
                    user_error( rtrim( "Cannot connect to $sHost. Timeout error" ) );
                }
                else
                {
                    // contain our open socket in read streams array. initialize write stream array
                    $aRead  = array( $this->oSocket );
                    $aWrite = $aExcept = NULL;

                    // iTvSec + iTvUsec = timeout parameter
                    $iTvSec  = floor( $iTimeout );
                    $iTvUsec = 1000000 * ( $iTimeout - $iTvSec );

                    // on windows this returns a "Warning: Invalid CRT parameters detected" error
                    // the !count() is done as a workaround for <https://bugs.php.net/42682>
                    if ( !@stream_select( $aRead, $aWrite, $aExcept, $iTvSec, $iTvUsec ) && !count( $aRead ) )
                    {
                        user_error( rtrim( "Cannot connect to $sHost. Banner timeout" ) );
                    }
                    else
                    {
                        /* According to the SSH2 specs,
                          "The server MAY send other lines of data before sending the version
                           string.  Each line SHOULD be terminated by a Carriage Return and Line
                           Feed.  Such lines MUST NOT begin with "SSH-", and SHOULD be encoded
                           in ISO-10646 UTF-8 [RFC3629] (language is not specified).  Clients
                           MUST be able to process such lines." */
                        $sTempStr = '';
                        $sExtra   = '';
                        while ( !feof( $this->oSocket ) && !preg_match( '#^SSH-(\d\.\d+)#', $sTempStr, $aMatches ) )
                        {
                            if ( substr( $sTempStr, -2 ) == "\r\n" )
                            {
                                $sExtra   .= $sTempStr;
                                $sTempStr  = '';
                            }
                            $sTempStr .= fgets( $this->oSocket, 255 );
                        }

                        if ( feof( $this->oSocket ) )
                        {
                            user_error( 'Connection closed by server' );
                        }
                        else
                        {
                            $aExtensions = array();
                            if ( extension_loaded( 'mcrypt' ) )
                            {
                                $aExtensions[] = 'mcrypt';
                            }
                            if ( extension_loaded( 'gmp' ) )
                            {
                                $aExtensions[] = 'gmp';
                            }
                            else if ( extension_loaded( 'bcmath' ) )
                            {
                                $aExtensions[] = 'bcmath';
                            }
                            // attached extensions to identifier
                            if ( !empty( $aExtensions ) )
                            {
                                $this->sIndentifier .= ' (' . implode(', ', $aExtensions) . ')';
                            }
                            // start logging.
                            if ( defined( 'NET_SSH2_LOGGING' ) )
                            {
                                $this->AppendLog( '<-', $sExtra . $sTempStr );
                                $this->AppendLog( '->', $this->sIndentifier . "\r\n" );
                            }

                            $this->sServerIdentifier = trim( $sTempStr, "\r\n" );
                            if ( strlen( $sExtra ) )
                            {
                                $this->aErrors[] = utf8_decode( $sExtra );
                            }

                            if ( $aMatches[ 1 ] != '1.99' && $aMatches[ 1 ] != '2.0' )
                            {
                                user_error( 'Cannot connect to SSH ' . $aMatches[ 1 ] . 'servers' );
                            }
                            else
                            {
                                // identify yourself.
                                fputs( $this->oSocket, $this->sIndentifier . "\r\n" );

                                // get the response.
                                $sResponse = $this->GetBinaryPacket();

                                // check for errors.
                                if ( $sResponse === false )
                                {
                                    user_error( 'Connection closed by server' );
                                }
                                else
                                {
                                    if ( ord( $sResponse[ 0 ] ) != NET_SSH2_MSG_KEXINIT )
                                    {
                                        user_error( 'Expected SSH_MSG_KEXINIT' );
                                    }
                                    else if ( $this->KeyExchange( $sResponse ) )
                                    {
                                        $this->iBitmap = NET_SSH2_MASK_CONSTRUCTOR;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ( isset( $iTimeout ) )
            {
                $this->SetTimeout( $iTimeout );
            }
        }

        /**
         * Key Exchange
         *
         * @param   String  $sKexinitPayloadServer
         *
         * @return  boolean
         */
        private function KeyExchange( $sKexinitPayloadServer )
        {
            // initialize return variable.
            $bReturn = true;

            static $aKexAlgorithms = array(
                'diffie-hellman-group1-sha1', // REQUIRED
                'diffie-hellman-group14-sha1' // REQUIRED
            );

            static $aServerHostKeyAlgorithms = array(
                'ssh-rsa', // RECOMMENDED  sign   Raw RSA Key
                'ssh-dss'  // REQUIRED     sign   Raw DSS Key
            );
            static $aEncryptionAlgorithms = false;

            if ( $aEncryptionAlgorithms === false )
            {
                $aEncryptionAlgorithms = array(
                    // from <http://tools.ietf.org/html/rfc4345#section-4>:
                    'arcfour256',
                    'arcfour128',

                    'arcfour',        // OPTIONAL          the ARCFOUR stream cipher with a 128-bit key

                    // CTR modes from <http://tools.ietf.org/html/rfc4344#section-4>:
                    'aes128-ctr',     // RECOMMENDED       AES (Rijndael) in SDCTR mode, with 128-bit key
                    'aes192-ctr',     // RECOMMENDED       AES with 192-bit key
                    'aes256-ctr',     // RECOMMENDED       AES with 256-bit key

                    'twofish128-ctr', // OPTIONAL          Twofish in SDCTR mode, with 128-bit key
                    'twofish192-ctr', // OPTIONAL          Twofish with 192-bit key
                    'twofish256-ctr', // OPTIONAL          Twofish with 256-bit key

                    'aes128-cbc',     // RECOMMENDED       AES with a 128-bit key
                    'aes192-cbc',     // OPTIONAL          AES with a 192-bit key
                    'aes256-cbc',     // OPTIONAL          AES in CBC mode, with a 256-bit key


                    'twofish128-cbc', // OPTIONAL          Twofish with a 128-bit key
                    'twofish192-cbc', // OPTIONAL          Twofish with a 192-bit key
                    'twofish256-cbc',
                    'twofish-cbc',    // OPTIONAL          alias for "twofish256-cbc" (this is being retained for historical reasons)

                    'blowfish-ctr',   // OPTIONAL          Blowfish in SDCTR mode
                    'blowfish-cbc',   // OPTIONAL          Blowfish in CBC mode

                    '3des-ctr',       // RECOMMENDED       Three-key 3DES in SDCTR mode

                    '3des-cbc',       // REQUIRED          three-key 3DES in CBC mode
                    'none'            // OPTIONAL          no encryption; NOT RECOMMENDED
                );
                // remove unsupported encryption algorithms if we
                // can't load the class file.
                if ( !$this->IsIncludable( 'includes/Crypt/Rijndael.php' ) )
                {
                    $aEncryptionAlgorithms = array_diff(
                        $aEncryptionAlgorithms,
                        array( 'aes128-ctr', 'aes192-ctr', 'aes256-ctr', 'aes128-cbc', 'aes192-cbc', 'aes256-cbc' )
                    );
                }
                if ( !$this->IsIncludable( 'includes/Crypt/Twofish.php' ) )
                {
                    $aEncryptionAlgorithms = array_diff(
                        $aEncryptionAlgorithms,
                        array( 'twofish128-ctr', 'twofish192-ctr', 'twofish256-ctr', 'twofish128-cbc', 'twofish192-cbc', 'twofish256-cbc', 'twofish-cbc' )
                    );
                }
                if ( !$this->IsIncludable( 'includes/Crypt/Blowfish.php' ) )
                {
                    $aEncryptionAlgorithms = array_diff(
                        $aEncryptionAlgorithms,
                        array( 'blowfish-ctr', 'blowfish-cbc' )
                    );
                }
                if ( !$this->IsIncludable( 'includes/Crypt/TripleDES.php' ) )
                {
                    $aEncryptionAlgorithms = array_diff(
                        $aEncryptionAlgorithms,
                        array( '3des-ctr', '3des-cbc' )
                    );
                }
                $aEncryptionAlgorithms = array_values($aEncryptionAlgorithms);
            }

            static $aMacAlgorithms = array(
                'hmac-sha1-96', // RECOMMENDED     first 96 bits of HMAC-SHA1 (digest length = 12, key length = 20)
                'hmac-sha1',    // REQUIRED        HMAC-SHA1 (digest length = key length = 20)
                'hmac-md5-96',  // OPTIONAL        first 96 bits of HMAC-MD5 (digest length = 12, key length = 16)
                'hmac-md5',     // OPTIONAL        HMAC-MD5 (digest length = key length = 16)
                'none'          // OPTIONAL        no MAC; NOT RECOMMENDED
            );

            static $aCompressionAlgorithms = array(
                'none'   // REQUIRED        no compression
                //'zlib' // OPTIONAL        ZLIB (LZ77) compression
            );

            // some SSH servers have buggy implementations of some of the above algorithms
            switch ( $this->sServerIdentifier )
            {
                case 'SSH-2.0-SSHD' :
                    $aMacAlgorithms = array_values( array_diff(
                        $aMacAlgorithms,
                        array( 'hmac-sha1-96', 'hmac-md5-96' )
                    ) );
                    break;

                default :
                    // do nothing.
                    break;
            }

            static  $sKexAlgorithms,
                    $sServerHostKeyAlgorithms,
                    $aEncryptionAlgorithmsServerToClient,
                    $aMacAlgorithmsServerToClient,
                    $aCompressionAlgorithmsServerToClient,
                    $aEncryptionAlgorithmsClientToServer,
                    $aMacAlgorithmsClientToServer,
                    $aCompressionAlgorithmsClientToServer;

            if ( empty( $sKexAlgorithms ) )
            {
                $sKexAlgorithms                       = implode( ',', $aKexAlgorithms );
                $sServerHostKeyAlgorithms             = implode( ',', $aServerHostKeyAlgorithms );
                $aEncryptionAlgorithmsServerToClient  = $aEncryptionAlgorithmsClientToServer  = implode( ',', $aEncryptionAlgorithms );
                $aMacAlgorithmsServerToClient      = $aMacAlgorithmsClientToServer         = implode( ',', $aMacAlgorithms );
                $aCompressionAlgorithmsServerToClient = $aCompressionAlgorithmsClientToServer = implode( ',', $aCompressionAlgorithms );
            }
            // generate client cookie.
            $sClientCookie = crypt_random_string( 16 );

            // set response for processing.
            $sResponse = $sKexinitPayloadServer;

            // skip past the message number (it should be SSH_MSG_KEXINIT)
            $this->StringShift( $sResponse, 1 );
            $sServerCookie = $this->StringShift( $sResponse, 16 );

            // process all key exchange algorithm messages.
            $aTempArr = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
            $this->aKexAlgorithms = explode( ',', $this->StringShift( $sResponse, $aTempArr[ 'length' ] ) );

            $aTempArr = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
            $this->aServerHostKeyAlgorithms = explode( ',', $this->StringShift( $sResponse, $aTempArr[ 'length' ] ) );

            $aTempArr = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
            $this->aEncryptionAlgorithmsClientToServer = explode( ',', $this->StringShift( $sResponse, $aTempArr[ 'length' ] ) );

            $aTempArr = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
            $this->aEncryptionAlgorithmsServerToClient = explode( ',', $this->StringShift( $sResponse, $aTempArr[ 'length' ] ) );

            $aTempArr = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
            $this->aMacAlgorithmsClientToServer = explode( ',', $this->StringShift( $sResponse, $aTempArr[ 'length' ] ) );

            $aTempArr = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
            $this->aMacAlgorithmsServerToClient = explode( ',', $this->StringShift( $sResponse, $aTempArr[ 'length' ] ) );

            $aTempArr = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
            $this->aCompressionAlgorithmsClientToServer = explode( ',', $this->StringShift( $sResponse, $aTempArr[ 'length' ] ) );

            $aTempArr = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
            $this->aCompressionAlgorithmsServerToClient = explode( ',', $this->StringShift( $sResponse, $aTempArr[ 'length' ] ) );

            $aTempArr = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
            $this->aLanguagesClientToServer = explode( ',', $this->StringShift( $sResponse, $aTempArr[ 'length' ] ) );

            $aTempArr = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
            $this->aLanguagesServerToClient = explode( ',', $this->StringShift( $sResponse, $aTempArr[ 'length' ] ) );

            $aTempArr = unpack( 'CbKexFollows', $this->StringShift( $sResponse, 1 ) ) ;
            $bFirstKexPacketFollows = $aTempArr[ 'bKexFollows' ] != 0;

            // the sending of SSH2_MSG_KEXINIT could go in one of two places.  this is the second place.
            $sKexinitPayloadClient = pack( 'Ca*Na*Na*Na*Na*Na*Na*Na*Na*Na*Na*CN',
                NET_SSH2_MSG_KEXINIT,
                $sClientCookie,
                strlen( $sKexAlgorithms ),
                $sKexAlgorithms,
                strlen( $sServerHostKeyAlgorithms ),
                $sServerHostKeyAlgorithms,
                strlen( $aEncryptionAlgorithmsClientToServer ),
                $aEncryptionAlgorithmsClientToServer,
                strlen( $aEncryptionAlgorithmsServerToClient ),
                $aEncryptionAlgorithmsServerToClient,
                strlen( $aMacAlgorithmsClientToServer ),
                $aMacAlgorithmsClientToServer,
                strlen( $aMacAlgorithmsServerToClient ),
                $aMacAlgorithmsServerToClient,
                strlen( $aCompressionAlgorithmsClientToServer ),
                $aCompressionAlgorithmsClientToServer,
                strlen( $aCompressionAlgorithmsServerToClient ),
                $aCompressionAlgorithmsServerToClient,
                0,
                '',
                0,
                '',
                0,
                0
            );

            if ( !$this->SendBinaryPacket( $sKexinitPayloadClient ) )
            {
                $bReturn = false;
            }
            else
            {
                // we need to decide upon the symmetric encryption algorithms before we do the diffie-hellman key exchange
                // loops all known algorithms and checks against server algorithm to see if it is supported.
                for ( $iEncryptIdx = 0; $iEncryptIdx < count( $aEncryptionAlgorithms ); $iEncryptIdx++ )
                {
                    if ( in_array( $aEncryptionAlgorithms[ $iEncryptIdx ], $this->aEncryptionAlgorithmsServerToClient ) )
                    {
                        break;
                    }
                }
                if ( $iEncryptIdx == count( $aEncryptionAlgorithms ) )
                {
                    user_error( 'No compatible server to client encryption algorithms found' );
                    $bReturn = $this->Disconnect( NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED );
                }
                else
                {
                    // we don't initialize any crypto-objects, yet - we do that, later. for now, we need the lengths to make the
                    // diffie-hellman key exchange as fast as possible
                    $sDecrypt = $aEncryptionAlgorithms[ $iEncryptIdx ];
                    switch ( $sDecrypt )
                    {
                        case '3des-cbc' :
                        case '3des-ctr' :
                            $iDecryptKeyLength = 24; // eg. 192 / 8
                            break;

                        case 'aes256-cbc' :
                        case 'aes256-ctr' :
                        case 'twofish-cbc' :
                        case 'twofish256-cbc' :
                        case 'twofish256-ctr' :
                            $iDecryptKeyLength = 32; // eg. 256 / 8
                            break;

                        case 'aes192-cbc' :
                        case 'aes192-ctr' :
                        case 'twofish192-cbc' :
                        case 'twofish192-ctr' :
                            $iDecryptKeyLength = 24; // eg. 192 / 8
                            break;

                        case 'aes128-cbc' :
                        case 'aes128-ctr' :
                        case 'twofish128-cbc' :
                        case 'twofish128-ctr' :
                        case 'blowfish-cbc' :
                        case 'blowfish-ctr' :
                            $iDecryptKeyLength = 16; // eg. 128 / 8
                            break;

                        case 'arcfour' :
                        case 'arcfour128' :
                            $iDecryptKeyLength = 16; // eg. 128 / 8
                            break;

                        case 'arcfour256' :
                            $iDecryptKeyLength = 32; // eg. 128 / 8
                            break;

                        case 'none' :
                            $iDecryptKeyLength = 0;
                            break;

                        default:
                            // nothing
                            break;
                    }
                    // check for compatible client-to-server encryption algorithm.
                    for ( $iEncryptIdx = 0; $iEncryptIdx < count( $aEncryptionAlgorithms ); $iEncryptIdx++ )
                    {
                        if ( in_array( $aEncryptionAlgorithms[ $iEncryptIdx ], $this->aEncryptionAlgorithmsClientToServer ) )
                        {
                            break;
                        }
                    }
                    if ( $iEncryptIdx == count( $aEncryptionAlgorithms ) )
                    {
                        user_error( 'No compatible client to server encryption algorithms found' );
                        $bReturn = $this->Disconnect( NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED );
                    }
                    else
                    {
                        $sEncrypt = $aEncryptionAlgorithms[ $iEncryptIdx ];
                        switch ( $sEncrypt )
                        {
                            case '3des-cbc' :
                            case '3des-ctr' :
                                $iEncryptKeyLength = 24;
                                break;

                            case 'aes256-cbc' :
                            case 'aes256-ctr' :
                            case 'twofish-cbc' :
                            case 'twofish256-cbc' :
                            case 'twofish256-ctr' :
                                $iEncryptKeyLength = 32;
                                break;

                            case 'aes192-cbc' :
                            case 'aes192-ctr' :
                            case 'twofish192-cbc' :
                            case 'twofish192-ctr' :
                                $iEncryptKeyLength = 24;
                                break;

                            case 'aes128-cbc' :
                            case 'aes128-ctr' :
                            case 'twofish128-cbc' :
                            case 'twofish128-ctr' :
                            case 'blowfish-cbc' :
                            case 'blowfish-ctr' :
                                $iEncryptKeyLength = 16;
                                break;

                            case 'arcfour' :
                            case 'arcfour128' :
                                $iEncryptKeyLength = 16;
                                break;

                            case 'arcfour256' :
                                $iEncryptKeyLength = 32;
                                break;

                            case 'none' :
                                $iEncryptKeyLength = 0;
                                break;

                            default :
                                break;
                        }

                        $iKeyLength = $iDecryptKeyLength > $iEncryptKeyLength ? $iDecryptKeyLength : $iEncryptKeyLength;

                        // check for compatible key exchange algorithms.
                        for ( $iKexIdx = 0; $iKexIdx < count( $aKexAlgorithms ); $iKexIdx++ )
                        {
                            if ( in_array( $aKexAlgorithms[ $iKexIdx ], $this->aKexAlgorithms ) )
                            {
                                break;
                            }
                        }
                        if ( $iKexIdx == count( $aKexAlgorithms ) )
                        {
                            user_error( 'No compatible key exchange algorithms found' );
                            $bReturn = $this->Disconnect( NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED );
                        }
                        else
                        {
                            // through diffie-hellman key exchange a symmetric key is obtained
                            switch ( $aKexAlgorithms[ $iKexIdx ] )
                            {
                                // see http://tools.ietf.org/html/rfc2409#section-6.2 and
                                // http://tools.ietf.org/html/rfc2412, appendex E
                                case 'diffie-hellman-group1-sha1' :
                                    $oPrime = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74' .
                                              '020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F1437' .
                                              '4FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED' .
                                              'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE65381FFFFFFFFFFFFFFFF';
                                    break;

                                // see http://tools.ietf.org/html/rfc3526#section-3
                                case 'diffie-hellman-group14-sha1' :
                                    $oPrime = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74' .
                                              '020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F1437' .
                                              '4FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED' .
                                              'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF05' .
                                              '98DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB' .
                                              '9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3B' .
                                              'E39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF695581718' .
                                              '3995497CEA956AE515D2261898FA051015728E5A8AACAA68FFFFFFFFFFFFFFFF';
                                    break;
                            }

                            // For both diffie-hellman-group1-sha1 and diffie-hellman-group14-sha1
                            // the generator field element is 2 (decimal) and the hash function is sha1.
                            $oGenerator = new Math_BigInteger( 2 );
                            $oPrime     = new Math_BigInteger( $oPrime, 16 );
                            $oKexHash   = new Crypt_Hash( 'sha1' );
                            //$q = $p->bitwise_rightShift(1);

                            /* To increase the speed of the key exchange, both client and server may
                               reduce the size of their private exponents.  It should be at least
                               twice as long as the key material that is generated from the shared
                               secret.  For more details, see the paper by van Oorschot and Wiener
                               [VAN-OORSCHOT].

                               -- http://tools.ietf.org/html/rfc4419#section-6.2 */
                            $oOne       = new Math_BigInteger( 1 );
                            $iKeyLength = min( $iKeyLength, $oKexHash->getLength() );
                            $iMax       = $oOne->bitwise_leftShift( 16 * $iKeyLength )->subtract( $oOne ); // 2 * 8 * $iKeyLength

                            $iRand      = $oOne->random( $oOne, $iMax );
                            $iExponent  = $oGenerator->modPow( $iRand, $oPrime );

                            $fEbytes    = $iExponent->toBytes( true );
                            $sData      = pack( 'CNa*', NET_SSH2_MSG_KEXDH_INIT, strlen( $fEbytes ), $fEbytes );

                            // send negotiation packet
                            if ( !$this->SendBinaryPacket( $sData ) )
                            {
                                user_error( 'Connection closed by server' );
                                $bReturn = false;
                            }
                            else
                            {
                                // get response.
                                $sResponse = $this->GetBinaryPacket();

                                // check response for errors.
                                if ( $sResponse === false )
                                {
                                    user_error( 'Connection closed by server' );
                                    $bReturn = false;
                                }
                                else
                                {
                                    $aTempArr = unpack( 'Ctype', $this->StringShift( $sResponse, 1 ) );
                                    if ( $aTempArr[ 'type' ] != NET_SSH2_MSG_KEXDH_REPLY )
                                    {
                                        user_error( 'Expected SSH_MSG_KEXDH_REPLY' );
                                        $bReturn = false;
                                    }
                                    else
                                    {
                                        // build information for exchange hash.
                                        $aTempArr                   = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
                                        $this->sServerPublicHostKey = $sServerPublicHostKey = $this->StringShift( $sResponse, $aTempArr[ 'length' ] );

                                        $aTempArr                   = unpack( 'Nlength', $this->StringShift( $sServerPublicHostKey, 4 ) );
                                        $sPublicKeyFormat           = $this->StringShift( $sServerPublicHostKey, $aTempArr[ 'length' ] );

                                        $aTempArr                   = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
                                        $fBytes                     = $this->StringShift( $sResponse, $aTempArr[ 'length' ] );
                                        $oFloatBytes                = new Math_BigInteger( $fBytes, -256 );

                                        $aTempArr                   = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
                                        $this->sSignature           = $this->StringShift( $sResponse, $aTempArr[ 'length' ] );

                                        $aTempArr                   = unpack( 'Nlength', $this->StringShift( $this->sSignature, 4 ) );
                                        $this->sSignatureFormat     = $this->StringShift( $this->sSignature, $aTempArr[ 'length' ] );

                                        $oKey      = $oFloatBytes->modPow( $iRand, $oPrime );
                                        $sKeyBytes = $oKey->toBytes( true );

                                        $this->sExchangeHash = pack('Na*Na*Na*Na*Na*Na*Na*Na*',
                                            strlen( $this->sIndentifier ),
                                            $this->sIndentifier,
                                            strlen( $this->sServerIdentifier ),
                                            $this->sServerIdentifier,
                                            strlen( $sKexinitPayloadClient ),
                                            $sKexinitPayloadClient,
                                            strlen( $sKexinitPayloadServer ),
                                            $sKexinitPayloadServer,
                                            strlen( $this->sServerPublicHostKey ),
                                            $this->sServerPublicHostKey,
                                            strlen( $fEbytes ),
                                            $fEbytes,
                                            strlen( $fBytes ),
                                            $fBytes,
                                            strlen( $sKeyBytes ),
                                            $sKeyBytes
                                        );
                                        // create hash
                                        $this->sExchangeHash = $oKexHash->hash( $this->sExchangeHash );

                                        if ( $this->sSessionId === false )
                                        {
                                            $this->sSessionId = $this->sExchangeHash;
                                        }

                                        // check for compatible server host key algorithms
                                        for ( $iShKeyIdx = 0;   $iShKeyIdx < count( $aServerHostKeyAlgorithms ); $iShKeyIdx++ )
                                        {
                                            if ( in_array( $aServerHostKeyAlgorithms[ $iShKeyIdx ], $this->aServerHostKeyAlgorithms ) )
                                            {
                                                break;
                                            }
                                        }
                                        if ( $iShKeyIdx == count( $aServerHostKeyAlgorithms ) )
                                        {
                                            user_error( 'No compatible server host key algorithms found' );
                                            $bReturn = $this->Disconnect( NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED );
                                        }
                                        else
                                        {
                                            // check formats against algorithm.
                                            if (    $sPublicKeyFormat != $aServerHostKeyAlgorithms[ $iShKeyIdx ]
                                                ||  $this->sSignatureFormat != $aServerHostKeyAlgorithms[ $iShKeyIdx ] )
                                            {
                                                user_error( 'Server Host Key Algorithm Mismatch' );
                                                $bReturn = $this->Disconnect( NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED );
                                            }
                                            else
                                            {
                                                $sPacket = pack( 'C', NET_SSH2_MSG_NEWKEYS );

                                                // send key request packet.
                                                if ( !$this->SendBinaryPacket( $sPacket ) )
                                                {
                                                    $bReturn = false;
                                                }
                                                else
                                                {
                                                    $sResponse = $this->GetBinaryPacket();

                                                    if ( $sResponse === false )
                                                    {
                                                        user_error( 'Connection closed by server' );
                                                        $bReturn = false;
                                                    }
                                                    else
                                                    {
                                                        // get message type
                                                        $aTemp = unpack( 'Ctype', $this->StringShift( $sResponse, 1 ) );

                                                        if ( $aTemp[ 'type' ] != NET_SSH2_MSG_NEWKEYS )
                                                        {
                                                            // clear memory
                                                            unset( $aTemp );

                                                            user_error( 'Expected SSH_MSG_NEWKEYS' );
                                                            $bReturn = false;
                                                        }
                                                        else
                                                        {
                                                            switch ( $sEncrypt )
                                                            {
                                                                case '3des-cbc' :
                                                                    if ( !class_exists( 'Crypt_TripleDES' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/TripleDES.php' );
                                                                    }
                                                                    $this->oEncrypt = new Crypt_TripleDES();
                                                                    // $this->iEncryptBlockSize = 64 / 8 == the default
                                                                    break;

                                                                case '3des-ctr' :
                                                                    if ( !class_exists( 'Crypt_TripleDES' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/TripleDES.php' );
                                                                    }
                                                                    $this->oEncrypt = new Crypt_TripleDES( CRYPT_DES_MODE_CTR );
                                                                    // $this->iEncryptBlockSize = 64 / 8 == the default
                                                                    break;

                                                                case 'aes256-cbc' :
                                                                case 'aes192-cbc' :
                                                                case 'aes128-cbc' :
                                                                    if ( !class_exists( 'Crypt_Rijndael' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/Rijndael.php' );
                                                                    }
                                                                    $this->oEncrypt          = new Crypt_Rijndael();
                                                                    $this->iEncryptBlockSize = 16; // eg. 128 / 8
                                                                    break;

                                                                case 'aes256-ctr' :
                                                                case 'aes192-ctr' :
                                                                case 'aes128-ctr' :
                                                                    if ( !class_exists( 'Crypt_Rijndael' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/Rijndael.php' );
                                                                    }
                                                                    $this->oEncrypt          = new Crypt_Rijndael( CRYPT_RIJNDAEL_MODE_CTR );
                                                                    $this->iEncryptBlockSize = 16; // eg. 128 / 8
                                                                    break;

                                                                case 'blowfish-cbc' :
                                                                    if ( !class_exists( 'Crypt_Blowfish' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/Blowfish.php' );
                                                                    }
                                                                    $this->oEncrypt          = new Crypt_Blowfish();
                                                                    $this->iEncryptBlockSize = 8;
                                                                    break;

                                                                case 'blowfish-ctr' :
                                                                    if ( !class_exists( 'Crypt_Blowfish' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/Blowfish.php' );
                                                                    }
                                                                    $this->oEncrypt          = new Crypt_Blowfish( CRYPT_BLOWFISH_MODE_CTR );
                                                                    $this->iEncryptBlockSize = 8;
                                                                    break;

                                                                case 'twofish128-cbc' :
                                                                case 'twofish192-cbc' :
                                                                case 'twofish256-cbc' :
                                                                case 'twofish-cbc' :
                                                                    if ( !class_exists( 'Crypt_Twofish' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/Twofish.php' );
                                                                    }
                                                                    $this->oEncrypt          = new Crypt_Twofish();
                                                                    $this->iEncryptBlockSize = 16;
                                                                    break;

                                                                case 'twofish128-ctr' :
                                                                case 'twofish192-ctr' :
                                                                case 'twofish256-ctr' :
                                                                    if ( !class_exists( 'Crypt_Twofish' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/Twofish.php' );
                                                                    }
                                                                    $this->oEncrypt          = new Crypt_Twofish( CRYPT_TWOFISH_MODE_CTR );
                                                                    $this->iEncryptBlockSize = 16;
                                                                    break;

                                                                case 'arcfour' :
                                                                case 'arcfour128' :
                                                                case 'arcfour256' :
                                                                    if ( !class_exists( 'Crypt_RC4' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/RC4.php' );
                                                                    }
                                                                    $this->oEncrypt = new Crypt_RC4();
                                                                    break;

                                                                case 'none' :
                                                                    //$this->oEncrypt = new Crypt_Null();
                                                                    break;

                                                                default :
                                                                    // nothing
                                                                    break;
                                                            }

                                                            switch ( $sDecrypt )
                                                            {
                                                                case '3des-cbc' :
                                                                    if ( !class_exists( 'Crypt_TripleDES' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/TripleDES.php' );
                                                                    }
                                                                    $this->oDecrypt = new Crypt_TripleDES();
                                                                    break;

                                                                case '3des-ctr' :
                                                                    if ( !class_exists( 'Crypt_TripleDES' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/TripleDES.php' );
                                                                    }
                                                                    $this->oDecrypt = new Crypt_TripleDES( CRYPT_DES_MODE_CTR );
                                                                    break;

                                                                case 'aes256-cbc' :
                                                                case 'aes192-cbc' :
                                                                case 'aes128-cbc' :
                                                                    if ( !class_exists( 'Crypt_Rijndael' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/Rijndael.php' );
                                                                    }
                                                                    $this->oDecrypt = new Crypt_Rijndael();
                                                                    $this->iDecryptBlockSize = 16;
                                                                    break;

                                                                case 'aes256-ctr' :
                                                                case 'aes192-ctr' :
                                                                case 'aes128-ctr' :
                                                                    if ( !class_exists( 'Crypt_Rijndael' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/Rijndael.php' );
                                                                    }
                                                                    $this->oDecrypt = new Crypt_Rijndael( CRYPT_RIJNDAEL_MODE_CTR );
                                                                    $this->iDecryptBlockSize = 16;
                                                                    break;

                                                                case 'blowfish-cbc' :
                                                                    if ( !class_exists( 'Crypt_Blowfish' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/Blowfish.php' );
                                                                    }
                                                                    $this->oDecrypt = new Crypt_Blowfish();
                                                                    $this->iDecryptBlockSize = 8;
                                                                    break;

                                                                case 'blowfish-ctr' :
                                                                    if ( !class_exists( 'Crypt_Blowfish' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/Blowfish.php' );
                                                                    }
                                                                    $this->oDecrypt = new Crypt_Blowfish( CRYPT_BLOWFISH_MODE_CTR );
                                                                    $this->iDecryptBlockSize = 8;
                                                                    break;

                                                                case 'twofish128-cbc' :
                                                                case 'twofish192-cbc' :
                                                                case 'twofish256-cbc' :
                                                                case 'twofish-cbc' :
                                                                    if ( !class_exists( 'Crypt_Twofish' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/Twofish.php' );
                                                                    }
                                                                    $this->oDecrypt = new Crypt_Twofish();
                                                                    $this->iDecryptBlockSize = 16;
                                                                    break;

                                                                case 'twofish128-ctr' :
                                                                case 'twofish192-ctr' :
                                                                case 'twofish256-ctr' :
                                                                    if ( !class_exists( 'Crypt_Twofish' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/Twofish.php' );
                                                                    }
                                                                    $this->oDecrypt = new Crypt_Twofish( CRYPT_TWOFISH_MODE_CTR );
                                                                    $this->iDecryptBlockSize = 16;
                                                                    break;

                                                                case 'arcfour' :
                                                                case 'arcfour128' :
                                                                case 'arcfour256' :
                                                                    if ( !class_exists( 'Crypt_RC4' ) )
                                                                    {
                                                                        require_once( 'includes/Crypt/RC4.php' );
                                                                    }
                                                                    $this->oDecrypt = new Crypt_RC4();
                                                                    break;

                                                                case 'none' :
                                                                    //$this->oDecrypt = new Crypt_Null();
                                                                    break;

                                                                default :
                                                                    break;
                                                            }

                                                            $sKeyBytes = pack( 'Na*', strlen( $sKeyBytes ), $sKeyBytes );

                                                            if ( $this->oEncrypt )
                                                            {
                                                                $this->oEncrypt->enableContinuousBuffer();
                                                                $this->oEncrypt->disablePadding();

                                                                // generate IV (initialization vector)
                                                                $sInitVector = $oKexHash->hash( $sKeyBytes . $this->sExchangeHash . 'A' . $this->sSessionId );
                                                                while ( $this->iEncryptBlockSize > strlen( $sInitVector ) )
                                                                {
                                                                    $sInitVector .= $oKexHash->hash( $sKeyBytes . $this->sExchangeHash . $sInitVector );
                                                                }
                                                                $this->oEncrypt->setIV( substr( $sInitVector, 0, $this->iEncryptBlockSize ) );

                                                                // generate Key
                                                                $sKey = $oKexHash->hash( $sKeyBytes . $this->sExchangeHash . 'C' . $this->sSessionId );
                                                                while ( $iEncryptKeyLength > strlen( $sKey ) )
                                                                {
                                                                    $sKey .= $oKexHash->hash( $sKeyBytes . $this->sExchangeHash . $sKey );
                                                                }
                                                                $this->oEncrypt->setKey( substr( $sKey, 0, $iEncryptKeyLength ) );
                                                            }

                                                            if ( $this->oDecrypt )
                                                            {
                                                                $this->oDecrypt->enableContinuousBuffer();
                                                                $this->oDecrypt->disablePadding();

                                                                // generate IV (initialization vector)
                                                                $sInitVector = $oKexHash->hash( $sKeyBytes . $this->sExchangeHash . 'B' . $this->sSessionId );
                                                                while ( $this->iDecryptBlockSize > strlen( $sInitVector ) )
                                                                {
                                                                    $sInitVector .= $oKexHash->hash( $sKeyBytes . $this->sExchangeHash . $sInitVector );
                                                                }
                                                                $this->oDecrypt->setIV( substr( $sInitVector, 0, $this->iDecryptBlockSize ) );

                                                                // generate Key
                                                                $sKey = $oKexHash->hash( $sKeyBytes . $this->sExchangeHash . 'D' . $this->sSessionId );
                                                                while ( $iDecryptKeyLength > strlen( $sKey ) )
                                                                {
                                                                    $sKey .= $oKexHash->hash( $sKeyBytes . $this->sExchangeHash . $sKey );
                                                                }
                                                                $this->oDecrypt->setKey( substr( $sKey, 0, $iDecryptKeyLength ) );
                                                            }

                                                            /* The "arcfour128" algorithm is the RC4 cipher, as described in
                                                               [ SCHNEIER ], using a 128-bit key.  The first 1536 bytes of keystream
                                                               generated by the cipher MUST be discarded, and the first byte of the
                                                               first encrypted packet MUST be encrypted using the 1537th byte of
                                                               keystream.

                                                               -- http://tools.ietf.org/html/rfc4345#section-4 */
                                                            if ( $sEncrypt == 'arcfour128' || $sEncrypt == 'arcfour256' )
                                                            {
                                                                $this->oEncrypt->encrypt( str_repeat( "\0", 1536 ) );
                                                            }
                                                            if ( $sDecrypt == 'arcfour128' || $sDecrypt == 'arcfour256' )
                                                            {
                                                                $this->oDecrypt->decrypt( str_repeat( "\0", 1536 ) );
                                                            }
                                                            // check for compatible MAC algorithm for client to server
                                                            for ( $iMacIdx = 0;   $iMacIdx < count( $aMacAlgorithms ); $iMacIdx++ )
                                                            {
                                                                if ( in_array( $aMacAlgorithms[ $iMacIdx ], $this->aMacAlgorithmsClientToServer ) )
                                                                {
                                                                    break;
                                                                }
                                                            }
                                                            if ( $iMacIdx == count( $aMacAlgorithms ) )
                                                            {
                                                                user_error( 'No compatible client to server message authentication algorithms found' );
                                                                $bReturn = $this->Disconnect( NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED );
                                                            }
                                                            else
                                                            {
                                                                // determine key length based on algorithm.
                                                                $iCreateKeyLength = 0; // ie. $aMacAlgorithms[ $iMacIdx ] == 'none'
                                                                switch ( $aMacAlgorithms[ $iMacIdx ] )
                                                                {
                                                                    case 'hmac-sha1' :
                                                                        $this->oHmacCreate = new Crypt_Hash( 'sha1' );
                                                                        $iCreateKeyLength  = 20;
                                                                        break;

                                                                    case 'hmac-sha1-96' :
                                                                        $this->oHmacCreate = new Crypt_Hash( 'sha1-96' );
                                                                        $iCreateKeyLength  = 20;
                                                                        break;

                                                                    case 'hmac-md5' :
                                                                        $this->oHmacCreate = new Crypt_Hash( 'md5' );
                                                                        $iCreateKeyLength  = 16;
                                                                        break;

                                                                    case 'hmac-md5-96' :
                                                                        $this->oHmacCreate = new Crypt_Hash( 'md5-96' );
                                                                        $iCreateKeyLength  = 16;
                                                                        break;

                                                                    case 'none' :
                                                                        $iCreateKeyLength = 0;
                                                                        break;

                                                                    default :
                                                                        // nothing.
                                                                        break;
                                                                }

                                                                // check for compatible MAC algorithm for server to client
                                                                for ( $iMacIdx = 0;   $iMacIdx < count( $aMacAlgorithms ); $iMacIdx++ )
                                                                {
                                                                    if ( in_array( $aMacAlgorithms[ $iMacIdx ], $this->aMacAlgorithmsServerToClient ) )
                                                                    {
                                                                        break;
                                                                    }
                                                                }
                                                                if ( $iMacIdx == count( $aMacAlgorithms ) )
                                                                {
                                                                    user_error( 'No compatible server to client message authentication algorithms found' );
                                                                    $bReturn = $this->Disconnect( NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED );
                                                                }
                                                                else
                                                                {
                                                                    // determine key length and hmac size based on algorithm.
                                                                    $iCheckKeyLength = 0;
                                                                    $this->iHmacSize = 0;
                                                                    switch ( $aMacAlgorithms[ $iMacIdx ] )
                                                                    {
                                                                        case 'hmac-sha1' :
                                                                            $this->oHmacCheck = new Crypt_Hash( 'sha1' );
                                                                            $iCheckKeyLength = 20;
                                                                            $this->iHmacSize = 20;
                                                                            break;

                                                                        case 'hmac-sha1-96' :
                                                                            $this->oHmacCheck = new Crypt_Hash( 'sha1-96' );
                                                                            $iCheckKeyLength = 20;
                                                                            $this->iHmacSize = 12;
                                                                            break;

                                                                        case 'hmac-md5' :
                                                                            $this->oHmacCheck = new Crypt_Hash( 'md5' );
                                                                            $iCheckKeyLength = 16;
                                                                            $this->iHmacSize = 16;
                                                                            break;

                                                                        case 'hmac-md5-96' :
                                                                            $this->oHmacCheck = new Crypt_Hash( 'md5-96' );
                                                                            $iCheckKeyLength = 16;
                                                                            $this->iHmacSize = 12;
                                                                            break;

                                                                        case 'none' :
                                                                            $iCheckKeyLength = 0;
                                                                            break;

                                                                        default :
                                                                            // nothing.
                                                                            break;
                                                                    }

                                                                    // generate MAC keys
                                                                    $sCreateKey = $oKexHash->hash( $sKeyBytes . $this->sExchangeHash . 'E' . $this->sSessionId );
                                                                    while ( $iCreateKeyLength > strlen( $sCreateKey ) )
                                                                    {
                                                                        $sCreateKey .= $oKexHash->hash( $sKeyBytes . $this->sExchangeHash . $sCreateKey );
                                                                    }
                                                                    $this->oHmacCreate->setKey( substr( $sCreateKey, 0, $iCreateKeyLength ) );

                                                                    $sCheckKey = $oKexHash->hash( $sKeyBytes . $this->sExchangeHash . 'F' . $this->sSessionId );
                                                                    while ( $iCheckKeyLength > strlen( $sCheckKey ) )
                                                                    {
                                                                        $sCheckKey .= $oKexHash->hash( $sKeyBytes . $this->sExchangeHash . $sCheckKey );
                                                                    }
                                                                    $this->oHmacCheck->setKey( substr( $sCheckKey, 0, $iCheckKeyLength ) );

                                                                    for ( $iCmprsnIdx = 0; $iCmprsnIdx < count( $aCompressionAlgorithms ); $iCmprsnIdx++ )
                                                                    {
                                                                        if ( in_array( $aCompressionAlgorithms[ $iCmprsnIdx ], $this->aCompressionAlgorithmsServerToClient ) )
                                                                        {
                                                                            break;
                                                                        }
                                                                    }
                                                                    if ( $iCmprsnIdx == count( $aCompressionAlgorithms ) )
                                                                    {
                                                                        user_error( 'No compatible server to client compression algorithms found' );
                                                                        $bReturn = $this->Disconnect( NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED );
                                                                    }
                                                                    else
                                                                    {
                                                                        // $this->decompress = $aCompressionAlgorithms[ $iCmprsnIdx ] == 'zlib';

                                                                        for ( $iCmprsnIdx = 0; $iCmprsnIdx < count( $aCompressionAlgorithms ); $iCmprsnIdx++ )
                                                                        {
                                                                            if ( in_array( $aCompressionAlgorithms[ $iCmprsnIdx ], $this->aCompressionAlgorithmsClientToServer ) )
                                                                            {
                                                                                break;
                                                                            }
                                                                        }
                                                                        if ( $iCmprsnIdx == count( $aCompressionAlgorithms ) )
                                                                        {
                                                                            user_error( 'No compatible client to server compression algorithms found' );
                                                                            $bReturn = $this->Disconnect( NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED );
                                                                        }
                                                                        // $this->compress = $aCompressionAlgorithms[ $iCmprsnIdx ] == 'zlib';
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return $bReturn;
        }

        /**
         * Login Helper
         *
         * @param   String  $sUsername
         * @param   String  $sPassword  Password, can also accept:
         *                                  RSA-Encrypted private key
         *                                  Array
         *
         * @return  Boolean
         *
         * @todo    It might be worthwhile, at some point, to protect against
         *          {@link http://tools.ietf.org/html/rfc4251#section-9.3.9 traffic analysis}
         *          by sending dummy SSH_MSG_IGNORE messages.
         */
        private function LoginHelper( $sUsername, $sPassword = null )
        {
            // initialize return variable
            $bReturn = null;

            if ( !( $this->iBitmap & NET_SSH2_MASK_CONSTRUCTOR ) )
            {
                $bReturn = false;
            }
            else
            {
                if ( !( $this->iBitmap & NET_SSH2_MASK_LOGIN_REQ ) )
                {
                    $sPacket = pack( 'CNa*', NET_SSH2_MSG_SERVICE_REQUEST, strlen( 'ssh-userauth' ), 'ssh-userauth' );
                    if ( !$this->SendBinaryPacket( $sPacket ) )
                    {
                        $bReturn = false;
                    }
                    else
                    {
                        $sResponse = $this->GetBinaryPacket();
                        if ( $sResponse === false )
                        {
                            user_error( 'Connection closed by server' );
                            $bReturn = false;
                        }
                        else
                        {
                            // get message type.
                            $aTemp = unpack( 'Ctype', $this->StringShift( $sResponse, 1 ) );

                            if ( $aTemp[ 'type' ] != NET_SSH2_MSG_SERVICE_ACCEPT )
                            {
                                // clear memory
                                unset( $aTemp );

                                user_error( 'Expected SSH_MSG_SERVICE_ACCEPT' );
                                $bReturn = false;
                            }
                            else
                            {
                                $this->iBitmap |= NET_SSH2_MASK_LOGIN_REQ;
                            }
                        }
                    }
                }
                if ( $bReturn === NULL )
                {
                    if ( strlen( $this->sLastInteractiveResponse ) )
                    {
                        $bReturn = !is_string( $sPassword ) && !is_array( $sPassword ) ? false : $this->KeyboardInteractiveProcess( $sPassword );
                    }
                    else
                    {
                        // although PHP5's get_class() preserves the case, PHP4's does not
                        if ( is_object( $sPassword ) && strtolower( get_class( $sPassword ) ) == 'crypt_rsa' )
                        {
                            $bReturn = $this->PrivatekeyLogin( $sUsername, $sPassword );
                        }
                        else
                        {
                            if ( is_array( $sPassword ) )
                            {
                                if ( $this->KeyboardInteractiveLogin( $sUsername, $sPassword ) )
                                {
                                    $this->iBitmap |= NET_SSH2_MASK_LOGIN;
                                    $bReturn = true;
                                }
                                $bReturn = false;
                            }
                            if ( $bReturn === NULL )
                            {
                                // no authentication, no password provided.
                                if ( !isset( $sPassword ) )
                                {
                                    $sPacket = pack( 'CNa*Na*Na*',
                                        NET_SSH2_MSG_USERAUTH_REQUEST,
                                        strlen( $sUsername ),
                                        $sUsername,
                                        strlen( 'ssh-connection' ),
                                        'ssh-connection',
                                        strlen( 'none' ),
                                        'none'
                                     );

                                    if ( !$this->SendBinaryPacket( $sPacket ) )
                                    {
                                        $bReturn = false;
                                    }
                                    else
                                    {
                                        $sResponse = $this->GetBinaryPacket();
                                        if ( $sResponse === false )
                                        {
                                            user_error( 'Connection closed by server' );
                                            $bReturn = false;
                                        }
                                        else
                                        {
                                            $aTemp = unpack( 'Ctype', $this->StringShift( $sResponse, 1 ) );

                                            switch ( $aTemp[ 'type' ] )
                                            {
                                                case NET_SSH2_MSG_USERAUTH_SUCCESS :
                                                    $this->iBitmap |= NET_SSH2_MASK_LOGIN;
                                                    $bReturn = true;
                                                    break;

                                                //case NET_SSH2_MSG_USERAUTH_FAILURE :
                                                default :
                                                    $bReturn = false;
                                                    break;
                                            }
                                        }
                                    }
                                }
                                if ( $bReturn === NULL )
                                {
                                    $sPacket = pack( 'CNa*Na*Na*CNa*',
                                        NET_SSH2_MSG_USERAUTH_REQUEST,
                                        strlen( $sUsername ),
                                        $sUsername,
                                        strlen( 'ssh-connection' ),
                                        'ssh-connection',
                                        strlen( 'password' ),
                                        'password',
                                        0,
                                        strlen( $sPassword ),
                                        $sPassword
                                     );

                                    // remove the username and password from the logged packet
                                    if ( !defined( 'NET_SSH2_LOGGING' ) )
                                    {
                                        $sLogged = NULL;
                                    }
                                    else
                                    {
                                        $sLogged = pack('CNa*Na*Na*CNa*',
                                            NET_SSH2_MSG_USERAUTH_REQUEST,
                                            strlen( 'username' ), 'username',
                                            strlen( 'ssh-connection' ),
                                            'ssh-connection',
                                            strlen( 'password' ),
                                            'password',
                                            0,
                                            strlen( 'password' ),
                                            'password'
                                         );
                                        $this->aMessageLog[ count( $this->aMessageLog ) - 1 ] = $sPacket;
                                    }

                                    if ( !$this->SendBinaryPacket( $sPacket, $sLogged ) )
                                    {
                                        $bReturn = false;
                                    }
                                    else
                                    {
                                        $sResponse = $this->GetBinaryPacket();
                                        if ( $sResponse === false )
                                        {
                                            user_error( 'Connection closed by server' );
                                            $bReturn = false;
                                        }
                                        else
                                        {
                                            $aTemp = unpack( 'Ctype', $this->StringShift( $sResponse, 1 ) );
                                            switch ( $aTemp[ 'type' ] )
                                            {
                                                case NET_SSH2_MSG_USERAUTH_PASSWD_CHANGEREQ :
                                                    // in theory, the password can be changed
                                                    if ( defined( 'NET_SSH2_LOGGING' ) )
                                                    {
                                                        $this->aMessageNumberLog[ count( $this->aMessageNumberLog ) - 1 ] = 'NET_SSH2_MSG_USERAUTH_PASSWD_CHANGEREQ';
                                                    }
                                                    $aTemp           = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
                                                    $this->aErrors[] = 'SSH_MSG_USERAUTH_PASSWD_CHANGEREQ: ' . utf8_decode( $this->StringShift( $sResponse, $aTemp[ 'length' ] ) );

                                                    $bReturn = $this->Disconnect( NET_SSH2_DISCONNECT_AUTH_CANCELLED_BY_USER );
                                                    break;

                                                case NET_SSH2_MSG_USERAUTH_FAILURE :
                                                    // can we use keyboard-interactive authentication?  if not then either the login is
                                                    // bad or the server employs multi-factor authentication
                                                    $aTemp        = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
                                                    $aAuthMethods = explode( ',', $this->StringShift( $sResponse, $aTemp[ 'length' ] ) );

                                                    $aTemp           = unpack( 'Cpartial_success', $this->StringShift( $sResponse, 1 ) );
                                                    $bPartialSuccess = $aTemp[ 'partial_success' ] != 0;

                                                    if ( !$bPartialSuccess && in_array( 'keyboard-interactive', $aAuthMethods ) )
                                                    {
                                                        if ( $this->KeyboardInteractiveLogin( $sUsername, $sPassword ) )
                                                        {
                                                            $this->iBitmap |= NET_SSH2_MASK_LOGIN;
                                                            $bReturn = true;
                                                            break;
                                                        }
                                                        $bReturn = false;
                                                        break;
                                                    }
                                                    $bReturn = false;
                                                    break;

                                                case NET_SSH2_MSG_USERAUTH_SUCCESS :
                                                    $this->iBitmap |= NET_SSH2_MASK_LOGIN;
                                                    $bReturn = true;
                                                    break;

                                                default :
                                                    // nothing.
                                                    break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                else
                {
                    $bReturn = false;
                }
            }

            return $bReturn;
        }

        /**
         * Login via keyboard-interactive authentication
         *
         * See {@link http://tools.ietf.org/html/rfc4256 RFC4256} for details.
         * This is not a full-featured keyboard-interactive authenticator.
         *
         * @param   String  $sUsername
         * @param   String  $sPassword
         *
         * @return  Boolean
         */
        private function KeyboardInteractiveLogin( $sUsername, $sPassword )
        {
            // initialize return variable
            $bReturn = false;

            $sPacket = pack( 'CNa*Na*Na*Na*Na*',
                NET_SSH2_MSG_USERAUTH_REQUEST,
                strlen( $sUsername ),
                $sUsername,
                strlen( 'ssh-connection' ),
                'ssh-connection',
                strlen( 'keyboard-interactive' ),
                'keyboard-interactive',
                0,
                '',
                0,
                ''
             );

            if ( !$this->SendBinaryPacket( $sPacket ) )
            {
                $bReturn = false;
            }
            else
            {
                $bReturn = $this->KeyboardInteractiveProcess( $sPassword );
            }

            return $bReturn;
        }

        /**
         * Handle the keyboard-interactive requests / responses.
         *
         * @param   string  $sResponses...
         * @param   mixed   ...
         *
         * @return  Boolean
         *
         * @todo maybe phpseclib should force close the connection after x request / responses?
         *       unless something like that is done there could be an infinite loop of request / responses.
         */
        private function KeyboardInteractiveProcess()
        {
            // get arguments
            $aResponses = func_get_args();

            // initialize return variable.
            $bReturn = null;

            // get the original response
            if ( strlen( $this->sLastInteractiveResponse ) )
            {
                $sResponse = $this->sLastInteractiveResponse;
            }
            else
            {
                // get response after request.
                $sOrigResponse = $sResponse = $this->GetBinaryPacket();
                if ( $sResponse === false )
                {
                    user_error( 'Connection closed by server' );
                    $bReturn = false;
                }
            }

            if ( $bReturn === NULL )
            {
                // check for failure or request.
                $aTemp = unpack( 'Ctype', $this->StringShift( $sResponse, 1 ) );
                switch ( $aTemp[ 'type' ] )
                {
                    case NET_SSH2_MSG_USERAUTH_INFO_REQUEST :
                        // unpack response fields
                        $aTemp = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
                        $this->StringShift( $sResponse, $aTemp[ 'length' ] ); // name; may be empty

                        $aTemp = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
                        $this->StringShift( $sResponse, $aTemp[ 'length' ] ); // instruction; may be empty

                        $aTemp = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
                        $this->StringShift( $sResponse, $aTemp[ 'length' ] ); // language tag; may be empty

                        $aTemp = unpack( 'Nnum_prompts', $this->StringShift( $sResponse, 4 ) );
                        $iNumPrompts = $aTemp[ 'num_prompts' ];

                        // prepare responses for keyboard interaction.
                        for ( $iRspIdx = 0; $iRspIdx < count( $aResponses ); $iRspIdx++ )
                        {
                            if ( is_array( $aResponses[ $iRspIdx ] ) )
                            {
                                foreach ( $aResponses[ $iRspIdx ] as $sKey => $sValue )
                                {
                                    $this->aKeyboardRequestsResponses[ $sKey ] = $sValue;
                                }
                                unset( $aResponses[ $iRspIdx ] );
                            }
                        }
                        $aResponses = array_values( $aResponses );

                        if ( isset( $this->aKeyboardRequestsResponses ) )
                        {
                            for ( $iNumResponses = 0; $iNumResponses < $iNumPrompts; $iNumResponses++ )
                            {
                                $aTemp = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
                                // prompt - ie. "Password: "; must not be empty
                                $sPrompt = $this->StringShift( $sResponse, $aTemp[ 'length' ] );

                                foreach ( $this->aKeyboardRequestsResponses as $sKey => $sValue )
                                {
                                    if ( substr( $sPrompt, 0, strlen( $sKey ) ) == $sKey )
                                    {
                                        $aResponses[] = $sValue;
                                        break;
                                    }
                                }
                            }
                        }

                        // see http://tools.ietf.org/html/rfc4256#section-3.2
                        if ( strlen( $this->sLastInteractiveResponse ) )
                        {
                            $this->sLastInteractiveResponse = '';
                        }
                        else if ( defined( 'NET_SSH2_LOGGING' ) )
                        {
                            $this->aMessageNumberLog[ count( $this->aMessageNumberLog ) - 1 ] = str_replace(
                                'UNKNOWN',
                                'NET_SSH2_MSG_USERAUTH_INFO_REQUEST',
                                $this->aMessageNumberLog[ count( $this->aMessageNumberLog ) - 1 ]
                             );
                        }

                        // we ran out of responses...
                        if ( !count( $aResponses ) && $iNumPrompts )
                        {
                            $this->sLastInteractiveResponse = $sOrigResponse;
                            $this->iBitmap |= NET_SSH_MASK_LOGIN_INTERACTIVE;
                            $bReturn = false;
                        }
                        else
                        {

                            /*
                               After obtaining the requested information from the user, the client
                               MUST respond with an SSH_MSG_USERAUTH_INFO_RESPONSE message.
                            */
                            // see http://tools.ietf.org/html/rfc4256#section-3.4
                            $sPacket = $sLogged = pack( 'CN', NET_SSH2_MSG_USERAUTH_INFO_RESPONSE, count( $aResponses ) );
                            for ( $iIndex = 0; $iIndex < count( $aResponses ); $iIndex++ )
                            {
                                $sPacket .= pack( 'Na*', strlen( $aResponses[ $iIndex ] ), $aResponses[ $iIndex ] );
                                $sLogged .= pack( 'Na*', strlen( 'dummy-answer' ), 'dummy-answer' );
                            }

                            if ( !$this->SendBinaryPacket( $sPacket, $sLogged ) )
                            {
                                $bReturn = false;
                            }
                            else
                            {
                                if ( defined( 'NET_SSH2_LOGGING' ) && NET_SSH2_LOGGING == NET_SSH2_LOG_COMPLEX )
                                {
                                    $this->aMessageNumberLog[ count( $this->aMessageNumberLog ) - 1 ] = str_replace(
                                        'UNKNOWN',
                                        'NET_SSH2_MSG_USERAUTH_INFO_RESPONSE',
                                        $this->aMessageNumberLog[ count( $this->aMessageNumberLog ) - 1 ]
                                     );
                                }

                                /*
                                   After receiving the response, the server MUST send either an
                                   SSH_MSG_USERAUTH_SUCCESS, SSH_MSG_USERAUTH_FAILURE, or another
                                   SSH_MSG_USERAUTH_INFO_REQUEST message.
                                */
                                // see @todo about this..
                                $bReturn = $this->KeyboardInteractiveProcess();
                            }
                        }
                        break;

                    case NET_SSH2_MSG_USERAUTH_SUCCESS :
                        $bReturn = true;
                        break;

                    case NET_SSH2_MSG_USERAUTH_FAILURE :
                        $bReturn = false;
                        break;

                    default :
                        $bReturn = false;
                        break;
                }
            }

            return $bReturn;
        }

        /**
         * Login with an RSA private key
         *
         * @param   String      $sUsername
         * @param   Crypt_RSA   $oPrivateKey
         *
         * @return  Boolean
         *
         * @todo    It might be worthwhile, at some point, to protect against
         *          {@link http://tools.ietf.org/html/rfc4251#section-9.3.9 traffic analysis}
         *          by sending dummy SSH_MSG_IGNORE messages.
         */
        private function PrivatekeyLogin( $sUsername, $oPrivateKey )
        {
            // initialize return variable.
            $bReturn = false;

            // see http://tools.ietf.org/html/rfc4253#page-15
            $sPublicKey = $oPrivateKey->getPublicKey( CRYPT_RSA_PUBLIC_FORMAT_RAW );
            if ( $sPublicKey === false )
            {
                $bReturn = false;
            }
            else
            {
                $sPublicKey = array(
                    'e' => $sPublicKey[ 'e' ]->toBytes( true ),
                    'n' => $sPublicKey[ 'n' ]->toBytes( true )
                 );
                $sPublicKey = pack( 'Na*Na*Na*',
                                    strlen( 'ssh-rsa' ),
                                    'ssh-rsa',
                                    strlen( $sPublicKey[ 'e' ] ),
                                    $sPublicKey[ 'e' ],
                                    strlen( $sPublicKey[ 'n' ] ),
                                    $sPublicKey[ 'n' ]
                                );

                $sPart1 = pack( 'CNa*Na*Na*',
                                NET_SSH2_MSG_USERAUTH_REQUEST,
                                strlen( $sUsername ),
                                $sUsername,
                                strlen( 'ssh-connection' ),
                                'ssh-connection',
                                strlen( 'publickey' ),
                                'publickey'
                             );
                $sPart2 = pack( 'Na*Na*',
                                strlen( 'ssh-rsa' ),
                                'ssh-rsa',
                                strlen( $sPublicKey ),
                                $sPublicKey
                            );

                $sPacket = $sPart1 . chr( 0 ) . $sPart2;
                if ( !$this->SendBinaryPacket( $sPacket ) )
                {
                    $bReturn = false;
                }
                else
                {
                    $sResponse = $this->GetBinaryPacket();
                    if ( $sResponse === false )
                    {
                        user_error( 'Connection closed by server' );
                        $bReturn = false;
                    }
                    else
                    {
                        $aTemp = unpack( 'Ctype', $this->StringShift( $sResponse, 1 ) );
                        switch ( $aTemp[ 'type' ] )
                        {
                            case NET_SSH2_MSG_USERAUTH_FAILURE :
                                $aTemp           = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
                                $this->aErrors[] = 'SSH_MSG_USERAUTH_FAILURE: ' . $this->StringShift( $sResponse, $aTemp[ 'length' ] );
                                $bReturn         = false;
                                break;

                            case NET_SSH2_MSG_USERAUTH_PK_OK :
                                // we'll just take it on faith that the public key blob
                                // and the public key algorithm name are as they should be
                                if ( defined( 'NET_SSH2_LOGGING' ) && NET_SSH2_LOGGING == NET_SSH2_LOG_COMPLEX )
                                {
                                    $this->aMessageNumberLog[ count( $this->aMessageNumberLog ) - 1 ] = str_replace(
                                        'UNKNOWN',
                                        'NET_SSH2_MSG_USERAUTH_PK_OK',
                                        $this->aMessageNumberLog[ count( $this->aMessageNumberLog ) - 1 ]
                                     );
                                }
                                break;

                            default :
                                // nothing.
                                break;
                        }
                        // make sure auth didn't fail.
                        if ( $bReturn === NULL )
                        {
                            $sPacket    = $sPart1 . chr( 1 ) . $sPart2;
                            $oPrivateKey->setSignatureMode( CRYPT_RSA_SIGNATURE_PKCS1 );
                            $sSignature = $oPrivateKey->sign( pack( 'Na*a*', strlen( $this->sSessionId ), $this->sSessionId, $sPacket ) );
                            $sSignature = pack( 'Na*Na*', strlen( 'ssh-rsa' ), 'ssh-rsa', strlen( $sSignature ), $sSignature );
                            $sPacket   .= pack( 'Na*', strlen( $sSignature ), $sSignature );

                            if ( !$this->SendBinaryPacket( $sPacket ) )
                            {
                                $bReturn = false;
                            }
                            else
                            {
                                $sResponse = $this->GetBinaryPacket();
                                if ( $sResponse === false )
                                {
                                    user_error( 'Connection closed by server' );
                                    $bReturn = false;
                                }
                                else
                                {
                                    $aTemp = unpack( 'Ctype', $this->StringShift( $sResponse, 1 ) );
                                    switch ( $aTemp[ 'type' ] )
                                    {
                                        case NET_SSH2_MSG_USERAUTH_FAILURE :
                                            // either the login is bad or the server employs multi-factor authentication
                                            $bReturn = false;
                                            break;

                                        case NET_SSH2_MSG_USERAUTH_SUCCESS :
                                            $this->iBitmap |= NET_SSH2_MASK_LOGIN;
                                            $bReturn = true;
                                            break;

                                        default :
                                            $bReturn = false;
                                            break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return $bReturn;
        }

        /**
         * Creates an interactive shell
         *
         * @see SSH2::Read()
         * @see SSH2::Write()
         *
         * @return Boolean
         */
        private function InitShell()
        {
            if ( $this->bInRequestPtyExec === true )
            {
                return true;
            }

            $this->aWindowSizeServerToClient[ NET_SSH2_CHANNEL_SHELL ] = $this->iWindowSize;
            $iPacketSize = 0x4000;

            $sPacket = pack( 'CNa*N3',
                             NET_SSH2_MSG_CHANNEL_OPEN,
                             strlen( 'session' ),
                             'session',
                             NET_SSH2_CHANNEL_SHELL,
                             $this->aWindowSizeServerToClient[ NET_SSH2_CHANNEL_SHELL ],
                             $iPacketSize
                        );

            if ( !$this->SendBinaryPacket( $sPacket ) )
            {
                return false;
            }

            $this->aChannelStatus[ NET_SSH2_CHANNEL_SHELL ] = NET_SSH2_MSG_CHANNEL_OPEN;

            $sResponse = $this->GetChannelPacket( NET_SSH2_CHANNEL_SHELL );
            if ( $sResponse === false )
            {
                return false;
            }

            // request shell channel
            $sTerminalModes = pack( 'C', NET_SSH2_TTY_OP_END );
            $sPacket = pack( 'CNNa*CNa*N5a*',
                            NET_SSH2_MSG_CHANNEL_REQUEST,
                            $this->aServerChannels[ NET_SSH2_CHANNEL_SHELL ],
                            strlen( 'pty-req' ),
                            'pty-req',
                            1,
                            strlen( 'vt100' ),
                            'vt100',
                            80,
                            24,
                            0,
                            0,
                            strlen( $sTerminalModes ),
                            $sTerminalModes
                        );
            if ( !$this->SendBinaryPacket( $sPacket ) )
            {
                return false;
            }
            // get response.
            $sResponse = $this->GetBinaryPacket();
            if ( $sResponse === false )
            {
                user_error( 'Connection closed by server' );
                return false;
            }

            // check response packet type.
            $aTemp = unpack( 'Ctype', $this->StringShift( $sResponse, 1 ) );
            switch ( $aTemp[ 'type' ] )
            {
                case NET_SSH2_MSG_CHANNEL_SUCCESS :
                // if a pty can't be opened maybe commands can still be executed
                case NET_SSH2_MSG_CHANNEL_FAILURE :
                    break;

                default :
                    user_error( 'Unable to request pseudo-terminal' );
                    return $this->Disconnect( NET_SSH2_DISCONNECT_BY_APPLICATION );
            }

            // build channel request packet
            $sPacket = pack( 'CNNa*C',
                             NET_SSH2_MSG_CHANNEL_REQUEST,
                             $this->aServerChannels[ NET_SSH2_CHANNEL_SHELL ],
                             strlen( 'shell' ),
                             'shell',
                             1
                        );
            if ( !$this->SendBinaryPacket( $sPacket ) )
            {
                return false;
            }
            // set channel status
            $this->aChannelStatus[ NET_SSH2_CHANNEL_SHELL ] = NET_SSH2_MSG_CHANNEL_REQUEST;

            $sResponse = $this->GetChannelPacket( NET_SSH2_CHANNEL_SHELL );
            if ( $sResponse === false )
            {
                // channel request failed.
                return false;
            }
            // set channel status and bitmap flag
            $this->aChannelStatus[ NET_SSH2_CHANNEL_SHELL ] = NET_SSH2_MSG_CHANNEL_DATA;
            $this->iBitmap |= NET_SSH2_MASK_SHELL;

            return true;
        }

        /**
         * Gets Binary Packets
         *
         * See '6. Binary Packet Protocol' of rfc4253 for more info.
         *
         * @see SSH2::SendBinaryPacket()
         *
         * @return  String
         */
        public function GetBinaryPacket()
        {
            if ( !is_resource( $this->oSocket ) || feof( $this->oSocket ) )
            {
                user_error( 'Connection closed prematurely' );
                $this->iBitmap = 0; // bitmask
                return false;
            }

            $fStart     = microtime( true );
            $sRawPacket = fread( $this->oSocket, $this->iDecryptBlockSize );

            if ( !strlen( $sRawPacket ) )
            {
                return '';
            }

            if ( $this->oDecrypt !== false )
            {
                $sRawPacket = $this->oDecrypt->decrypt( $sRawPacket );
            }
            if ( $sRawPacket === false )
            {
                user_error( 'Unable to decrypt content' );
                return false;
            }

            $aTemp          = unpack( 'Npacket_length/Cpadding_length', $this->StringShift( $sRawPacket, 5 ) );
            $iPacketLength  = $aTemp[ 'packet_length' ];
            $iPaddingLength = $aTemp[ 'padding_length' ];

            $iRemainingLength = $iPacketLength + 4 - $this->iDecryptBlockSize;

            // quoting <http://tools.ietf.org/html/rfc4253#section-6.1>,
            // "implementations SHOULD check that the packet length is reasonable"
            // PuTTY uses 0x9000 as the actual max packet size and so to shall we
            if (   $iRemainingLength < -$this->iDecryptBlockSize
                || $iRemainingLength > 0x9000
                || $iRemainingLength % $this->iDecryptBlockSize != 0 )
            {
                user_error( 'Invalid size' );
                return false;
            }

            $sBuffer = '';
            while ( $iRemainingLength > 0 )
            {
                $sTemp             = fread( $this->oSocket, $iRemainingLength );
                $sBuffer          .= $sTemp;
                $iRemainingLength -= strlen( $sTemp );
            }
            $fStop = microtime( true );
            if ( strlen( $sBuffer ) )
            {
                $sRawPacket .= $this->oDecrypt !== false ? $this->oDecrypt->decrypt( $sBuffer ) : $sBuffer;
            }

            $sPayload = $this->StringShift( $sRawPacket, $iPacketLength - $iPaddingLength - 1 );
            $sPadding = $this->StringShift( $sRawPacket, $iPaddingLength ); // should leave $sRawPacket empty

            if ( $this->oHmacCheck !== false )
            {
                // calculate MAC hash and compare.
                $sHmac      = fread( $this->oSocket, $this->iHmacSize );
                $sMacStr    = pack( 'NNCa*',
                                    $this->iGetSeqNo,
                                    $iPacketLength,
                                    $iPaddingLength,
                                    $sPayload . $sPadding
                                );
                $sHmacStr   = $this->oHmacCheck->hash( $sMacStr );
                if ( $sHmac != $sHmacStr )
                {
                    user_error( 'Invalid HMAC' );
                    return false;
                }
            }

            //if ( $this->decompress )
            //{
            //    $sPayload = gzinflate( substr( $sPayload, 2 ) );
            //}

            $this->iGetSeqNo++;

            if ( defined( 'NET_SSH2_LOGGING' ) )
            {
                // log.
                $fCurrent       = microtime( true );
                $sMessageNumber = isset( $this->aMessageNumbers[ " " . ord( $sPayload[ 0 ] ) ] )
                                    ? $this->aMessageNumbers[ " " . ord( $sPayload[ 0 ] ) ]
                                    : 'UNKNOWN ( ' . ord( $sPayload[ 0 ] ) . ' )';
                $sMessageNumber = '<- ' . $sMessageNumber .
                                  ' ( since last: ' . round( $fCurrent - $this->fLastPacket, 4 ) . ', network: ' . round( $fStop - $fStart, 4 ) . 's )';
                $this->AppendLog( $sMessageNumber, $sPayload );
                $this->fLastPacket = $fCurrent;
            }

            return $this->FilterPackets( $sPayload );
        }

        /**
         * Filter Binary Packets
         *
         * Because some binary packets need to be ignored...
         *
         * @see SSH2::GetBinaryPacket()
         *
         * @param   string  $sPayload   Packet payload data string.
         *
         * @return  string  $sPayload   payload.
         */
        private function FilterPackets( $sPayload )
        {
            // initialize identifying bit for reuse.
            $sIdBit = ord( $sPayload[ 0 ] );
            switch ( $sIdBit )
            {
                case NET_SSH2_MSG_DISCONNECT :
                    $this->StringShift( $sPayload, 1 );
                    $aTemp           = unpack( 'Nreason_code/Nlength', $this->StringShift( $sPayload, 8 ) );
                    $this->aErrors[] = 'SSH_MSG_DISCONNECT: ' . $this->aDisconnectReasons[ " " . $aTemp[ 'reason_code' ] ] . "\r\n" . utf8_decode( $this->StringShift( $sPayload, $aTemp[ 'length' ] ) );
                    $this->iBitmap   = 0; // bitmask
                    return false;

                case NET_SSH2_MSG_IGNORE :
                    $sPayload = $this->GetBinaryPacket();
                    break;

                case NET_SSH2_MSG_DEBUG :
                    $this->StringShift( $sPayload, 2 );
                    $aTemp           = unpack( 'Nlength', $this->StringShift( $sPayload, 4 ) );
                    $this->aErrors[] = 'SSH_MSG_DEBUG: ' . utf8_decode( $this->StringShift( $sPayload, $aTemp[ 'length' ] ) );
                    $sPayload        = $this->GetBinaryPacket();
                    break;

                case NET_SSH2_MSG_UNIMPLEMENTED :
                    return false;

                case NET_SSH2_MSG_KEXINIT :
                    if ( $this->sSessionId !== false )
                    {
                        if ( !$this->KeyExchange( $sPayload ) )
                        {
                            $this->iBitmap = 0; // bitmask
                            return false;
                        }
                        $sPayload = $this->GetBinaryPacket();
                    }
                    break;

                default :
                    // nothing.
                    break;
            }

            // see http://tools.ietf.org/html/rfc4252#section-5.4; only called when the encryption
            // has been activated and when we haven't already logged in
            if ( ( $this->iBitmap & NET_SSH2_MASK_CONSTRUCTOR )
                 && !( $this->iBitmap & NET_SSH2_MASK_LOGIN )
                 && $sIdBit == NET_SSH2_MSG_USERAUTH_BANNER )
            {
                $this->StringShift( $sPayload, 1 );
                $aTemp                = unpack( 'Nlength', $this->StringShift( $sPayload, 4 ) );
                $this->sBannerMessage = utf8_decode( $this->StringShift( $sPayload, $aTemp[ 'length' ] ) );
                $sPayload             = $this->GetBinaryPacket();
            }

            // only called when we've already logged in
            if ( ( $this->iBitmap & NET_SSH2_MASK_CONSTRUCTOR ) && ( $this->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                switch ( $sIdBit )
                {
                    case NET_SSH2_MSG_GLOBAL_REQUEST : // see http://tools.ietf.org/html/rfc4254#section-4
                        $this->StringShift( $sPayload, 1 );
                        $aTemp           = unpack( 'Nlength', $this->StringShift( $sPayload ) );
                        $this->aErrors[] = 'SSH_MSG_GLOBAL_REQUEST: ' . utf8_decode( $this->StringShift( $sPayload, $aTemp[ 'length' ] ) );

                        if ( !$this->SendBinaryPacket( pack( 'C', NET_SSH2_MSG_REQUEST_FAILURE ) ) )
                        {
                            return $this->Disconnect( NET_SSH2_DISCONNECT_BY_APPLICATION );
                        }

                        $sPayload = $this->GetBinaryPacket();
                        break;

                    case NET_SSH2_MSG_CHANNEL_OPEN : // see http://tools.ietf.org/html/rfc4254#section-5.1
                        $this->StringShift( $sPayload, 1 );
                        $aTemp           = unpack( 'Nlength', $this->StringShift( $sPayload, 4 ) );
                        $this->aErrors[] = 'SSH_MSG_CHANNEL_OPEN: ' . utf8_decode( $this->StringShift( $sPayload, $aTemp[ 'length' ] ) );

                        // skip over client channel
                        $this->StringShift( $sPayload, 4 );

                        // get server channel
                        $aTemp   = unpack( 'Nserver_channel', $this->StringShift( $sPayload, 4 ) );

                        $sPacket = pack( 'CN3a*Na*',
                                         NET_SSH2_MSG_REQUEST_FAILURE,
                                         $aTemp[ 'server_channel' ],
                                         NET_SSH2_OPEN_ADMINISTRATIVELY_PROHIBITED,
                                         0,
                                         '',
                                         0,
                                         ''
                                        );

                        if ( !$this->SendBinaryPacket( $sPacket ) )
                        {
                            return $this->Disconnect( NET_SSH2_DISCONNECT_BY_APPLICATION );
                        }

                        $sPayload = $this->GetBinaryPacket();
                        break;

                    case NET_SSH2_MSG_CHANNEL_WINDOW_ADJUST :
                        $this->StringShift( $sPayload, 1 );
                        $aTemp    = unpack( 'Nchannel/Nwindow_size', $this->StringShift( $sPayload, 8 ) );

                        // adjust window size.
                        $this->aWindowSizeClientToServer[ $aTemp[ 'channel' ] ] += $aTemp[ 'window_size' ];

                        $sPayload = ( $this->iBitmap & NET_SSH2_MASK_WINDOW_ADJUST ) ? true : $this->GetBinaryPacket();
                        break;

                    default :
                        // nothing.
                        break;
                }
            }

            return $sPayload;
        }

        /**
         * Gets channel data
         *
         * Returns the data as a string if it's available and false if not.
         *
         * @param   integer $client_channel Channel
         * @param   boolean $bSkipExtended  default=false, skips over extended data.
         *
         * @return  Mixed
         */
        public function GetChannelPacket( $iClientChannel, $bSkipExtended = false )
        {
            if ( !empty( $this->aChannelBuffers[ $iClientChannel ] ) )
            {
                return array_shift( $this->aChannelBuffers[ $iClientChannel ] );
            }

            while ( true )
            {
                if ( $this->iCurTimeout )
                {
                    if ( $this->iCurTimeout < 0 )
                    {
                        $this->bIsTimeout = true;
                        return true;
                    }

                    $aReadStreams  = array( $this->oSocket );
                    $aWriteStreams = $aExceptStreams = NULL;

                    $fStart  = microtime( true );
                    $iTvSec  = floor( $this->iCurTimeout );
                    $iTvUsec = 1000000 * ( $this->iCurTimeout - $iTvSec );
                    // on windows this returns a "Warning: Invalid CRT parameters detected" error
                    if ( !@stream_select( $aReadStreams, $aWriteStreams, $aExceptStreams, $iTvSec, $iTvUsec ) && !count( $aReadStreams ) )
                    {
                        $this->bIsTimeout = true;
                        return true;
                    }
                    $fElapsed          = microtime( true ) - $fStart;
                    $this->iCurTimeout -= $fElapsed;
                }

                $sResponse = $this->GetBinaryPacket();
                if ( $sResponse === false )
                {
                    user_error( 'Connection closed by server' );
                    return false;
                }
                if ( $iClientChannel == -1 && $sResponse === true )
                {
                    return true;
                }
                if ( !strlen( $sResponse ) )
                {
                    return '';
                }

                $aTemp    = unpack( 'Ctype/Nchannel', $this->StringShift( $sResponse, 5 ) );
                $iMsgType = $aTemp[ 'type' ];
                $iChannel = $aTemp[ 'channel' ];

                // clear memory
                unset( $aTemp );

                // resize the window, if appropriate
                $this->aWindowSizeServerToClient[ $iChannel ] -= strlen( $sResponse ) + 4;
                if ( $this->aWindowSizeServerToClient[ $iChannel ] < 0 )
                {
                    $sPacket = pack( 'CNN', NET_SSH2_MSG_CHANNEL_WINDOW_ADJUST, $this->aServerChannels[ $iChannel ], $this->iWindowSize );
                    if ( !$this->SendBinaryPacket( $sPacket ) )
                    {
                        return false;
                    }
                    $this->aWindowSizeServerToClient[ $iChannel ] += $this->iWindowSize;
                }

                switch ( $this->aChannelStatus[ $iChannel ] )
                {
                    case NET_SSH2_MSG_CHANNEL_OPEN :
                        switch ( $iMsgType )
                        {
                            case NET_SSH2_MSG_CHANNEL_OPEN_CONFIRMATION :
                                $aTemp = unpack( 'Nserver_channel', $this->StringShift( $sResponse, 4 ) );
                                $this->aServerChannels[ $iChannel ] = $aTemp[ 'server_channel' ];

                                // $this->StringShift( $sResponse, 4 ); // skip over ( server ) window size
                                $aTemp = unpack( 'Nwindow_size', $this->StringShift( $sResponse, 4 ) );
                                $this->aWindowSizeClientToServer[ $iChannel ] = $aTemp[ 'window_size' ];

                                $aTemp = unpack( 'Npacket_size_client_to_server', $this->StringShift( $sResponse, 4 ) );
                                $this->aPacketSizeClientToServer[ $iChannel ] = $aTemp[ 'packet_size_client_to_server' ];

                                return $iClientChannel == $iChannel ? true : $this->GetChannelPacket( $iClientChannel, $bSkipExtended );

                            //case NET_SSH2_MSG_CHANNEL_OPEN_FAILURE:
                            default :
                                user_error( 'Unable to open channel' );
                                return $this->Disconnect( NET_SSH2_DISCONNECT_BY_APPLICATION );
                        }
                        break;

                    case NET_SSH2_MSG_CHANNEL_REQUEST :
                        switch ( $iMsgType )
                        {
                            case NET_SSH2_MSG_CHANNEL_SUCCESS :
                                return true;

                            case NET_SSH2_MSG_CHANNEL_FAILURE :
                                return false;

                            default :
                                user_error( 'Unable to fulfill channel request' );
                                return $this->Disconnect( NET_SSH2_DISCONNECT_BY_APPLICATION );
                        }
                        break;

                    case NET_SSH2_MSG_CHANNEL_CLOSE :
                        return $iMsgType == NET_SSH2_MSG_CHANNEL_CLOSE ? true : $this->GetChannelPacket( $iClientChannel, $bSkipExtended );

                    default :
                        // nothing.
                        break;
                }

                // ie. $this->channel_status[$iChannel] == NET_SSH2_MSG_CHANNEL_DATA

                switch ( $iMsgType )
                {
                    case NET_SSH2_MSG_CHANNEL_DATA :
                        $aTemp = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
                        $sData = $this->StringShift( $sResponse, $aTemp[ 'length' ] );
                        if ( $iClientChannel == $iChannel )
                        {
                            return $sData;
                        }
                        if ( !isset( $this->aChannelBuffers[ $iChannel ] ) )
                        {
                            $this->aChannelBuffers[ $iChannel ] = array();
                        }
                        $this->aChannelBuffers[ $iChannel ][] = $sData;
                        break;

                    case NET_SSH2_MSG_CHANNEL_EXTENDED_DATA :
                        // skip extended data?
                        if ( $bSkipExtended || $this->bQuietMode )
                        {
                            break;
                        }

                        // currently, there's only one possible value for $data_type_code: NET_SSH2_EXTENDED_DATA_STDERR
                        $aTemp               = unpack( 'Ndata_type_code/Nlength', $this->StringShift( $sResponse, 8 ) );
                        $sData               = $this->StringShift( $sResponse, $aTemp[ 'length' ] );
                        $this->sStdErrorLog .= $sData;
                        if ( $iClientChannel == $iChannel )
                        {
                            return $sData;
                        }
                        if ( !isset( $this->aChannelBuffers[ $iChannel ] ) )
                        {
                            $this->aChannelBuffers[ $iChannel ] = array();
                        }
                        $this->aChannelBuffers[ $iChannel ][] = $sData;
                        break;

                    case NET_SSH2_MSG_CHANNEL_REQUEST :
                        $aTemp  = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
                        $sValue = $this->StringShift( $sResponse, $aTemp[ 'length' ] );
                        switch ( $sValue )
                        {
                            case 'exit-signal' :
                                $this->StringShift( $sResponse, 1 );

                                $aTemp           = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
                                $this->aErrors[] = 'SSH_MSG_CHANNEL_REQUEST ( exit-signal ): ' . $this->StringShift( $sResponse, $aTemp[ 'length' ] );

                                $this->StringShift( $sResponse, 1 );

                                $aTemp = unpack( 'Nlength', $this->StringShift( $sResponse, 4 ) );
                                if ( $aTemp[ 'length' ] )
                                {
                                    $this->aErrors[ count( $this->aErrors ) ].= "\r\n" . $this->StringShift( $sResponse, $aTemp[ 'length' ] );
                                }
                                $this->SendBinaryPacket( pack( 'CN', NET_SSH2_MSG_CHANNEL_EOF, $this->aServerChannels[ $iClientChannel ] ) );
                                $this->SendBinaryPacket( pack( 'CN', NET_SSH2_MSG_CHANNEL_CLOSE, $this->aServerChannels[ $iChannel ] ) );

                                $this->aServerChannels[ $iChannel ] = NET_SSH2_MSG_CHANNEL_EOF;
                                break;

                            case 'exit-status' :
                                $aTemp             = unpack( 'Cfalse/Nexit_status', $this->StringShift( $sResponse, 5 ) );
                                $this->iExitStatus = $aTemp[ 'exit_status' ];

                                // "The channel needs to be closed with SSH_MSG_CHANNEL_CLOSE after this message."
                                // -- http://tools.ietf.org/html/rfc4254#section-6.10
                                $this->SendBinaryPacket( pack( 'CN', NET_SSH2_MSG_CHANNEL_EOF, $this->aServerChannels[ $iClientChannel ] ) );
                                $this->SendBinaryPacket( pack( 'CN', NET_SSH2_MSG_CHANNEL_CLOSE, $this->aServerChannels[ $iChannel ] ) );

                                $this->aChannelStatus[ $iChannel ] = NET_SSH2_MSG_CHANNEL_EOF;
                                break;

                            default :
                                // "Some systems may not implement signals, in which case they SHOULD ignore this message."
                                //  -- http://tools.ietf.org/html/rfc4254#section-6.9
                                break;
                        }
                        break;

                    case NET_SSH2_MSG_CHANNEL_CLOSE :
                        $this->iCurTimeout = 0;

                        if ( $this->iBitmap & NET_SSH2_MASK_SHELL )
                        {
                            $this->iBitmap &= ~NET_SSH2_MASK_SHELL;
                        }
                        if ( $this->aChannelStatus[ $iChannel ] != NET_SSH2_MSG_CHANNEL_EOF )
                        {
                            $this->SendBinaryPacket( pack( 'CN', NET_SSH2_MSG_CHANNEL_CLOSE, $this->aServerChannels[ $iChannel ] ) );
                        }

                        $this->aChannelStatus[ $iChannel ] = NET_SSH2_MSG_CHANNEL_CLOSE;
                        return true;

                    case NET_SSH2_MSG_CHANNEL_EOF :
                        break;

                    default :
                        user_error( 'Error reading channel data' );
                        return $this->Disconnect( NET_SSH2_DISCONNECT_BY_APPLICATION );
                }
            }
        }

        /**
         * Sends Binary Packets
         *
         * See '6. Binary Packet Protocol' of rfc4253 for more info.
         *
         * @param   string  $sData   binary string data being sent in packet.
         * @param   string  $sLogged default=NULL, contains data for logging.
         *
         * @see     SSH2::GetBinaryPacket()
         *
         * @return  boolean
         */
        public function SendBinaryPacket( $sData, $sLogged = NULL )
        {
            if ( !is_resource( $this->oSocket ) || feof( $this->oSocket ) )
            {
                user_error( 'Connection closed prematurely' );
                $this->iBitmap = 0; // bitmask
                return false;
            }

            //if ( $this->compress )
            //{
            //    // the -4 removes the checksum:
            //    // http://php.net/function.gzcompress#57710
            //    $sData = substr( gzcompress( $sData ), 0, -4 );
            //}

            // 4 ( packet length ) + 1 ( padding length ) + 4 ( minimal padding amount ) == 9
            $iPacketLength  = strlen( $sData ) + 9;

            // round up to the nearest $this->iEncryptBlockSize
            $iPacketLength += ( ( $this->iEncryptBlockSize - 1 ) * $iPacketLength ) % $this->iEncryptBlockSize;

            // subtracting strlen( $sData ) is obvious - subtracting 5 is necessary because of packet_length and padding_length
            $iPaddingLength = $iPacketLength - strlen( $sData ) - 5;
            $sPadding = crypt_random_string( $iPaddingLength );

            // we subtract 4 from packet_length because the packet_length field isn't supposed to include itself
            $sPacket = pack( 'NCa*', $iPacketLength - 4, $iPaddingLength, $sData . $sPadding );

            // create MAC hash
            $sHmac = $this->oHmacCreate !== false ? $this->oHmacCreate->hash( pack( 'Na*', $this->iSendSeqNo, $sPacket ) ) : '';
            $this->iSendSeqNo++;

            // encrypt packet?
            if ( $this->oEncrypt !== false )
            {
                $sPacket = $this->oEncrypt->encrypt( $sPacket );
            }
            // append MAC hash
            $sPacket .= $sHmac;

            // send packet with profiling.
            $fStart  = microtime( true );
            $bResult = strlen( $sPacket ) == fputs( $this->oSocket, $sPacket );
            $fStop   = microtime( true );

            if ( defined( 'NET_SSH2_LOGGING' ) )
            {
                $fCurrent       = microtime( true );
                $sMessageNumber = isset( $this->aMessageNumbers[ " " . ord( $sData[ 0 ] ) ] ) ? $this->aMessageNumbers[ " " . ord( $sData[ 0 ] ) ] : 'UNKNOWN ( ' . ord( $sData[ 0 ] ) . ' )';
                $sMessageNumber = '-> ' . $sMessageNumber .
                                  ' ( since last: ' . round( $fCurrent - $this->fLastPacket, 4 ) . ', network: ' . round( $fStop - $fStart, 4 ) . 's )';
                $this->AppendLog( $sMessageNumber, isset( $sLogged ) ? $sLogged : $sData );
                $this->fLastPacket = $fCurrent;
            }

            return $bResult;
        }

        /**
         * Logs data packets
         *
         * Makes sure that only the last 1MB worth of packets will be logged
         *
         * @param   string  $sMessgageNumber
         * @param   string  $sMessage
         *
         * @return  void.
         */
        private function AppendLog( $sMessageNumber, $sMessage )
        {
            // remove the byte identifying the message type from all but the first two messages (ie. the identification strings)
            if ( strlen( $sMessageNumber ) > 2 )
            {
                $this->StringShift( $sMessage );
            }
            switch ( NET_SSH2_LOGGING )
            {
                // useful for benchmarks
                case NET_SSH2_LOG_SIMPLE :
                    $this->aMessageNumberLog[] = $sMessageNumber;
                    break;

                // the most useful log for SSH2
                case NET_SSH2_LOG_COMPLEX :
                    $this->aMessageNumberLog[] = $sMessageNumber;
                    $this->iLogSize           += strlen( $sMessage );
                    $this->aMessageLog[]       = $sMessage;

                    while ( $this->iLogSize > NET_SSH2_LOG_MAX_SIZE )
                    {
                        $this->iLogSize-= strlen( array_shift( $this->aMessageLog ) );
                        array_shift(  $this->aMessageNumberLog );
                    }
                    break;

                // dump the output out realtime; packets may be interspersed with non packets,
                // passwords won't be filtered out and select other packets may not be correctly
                // identified
                case NET_SSH2_LOG_REALTIME :
                    switch ( PHP_SAPI )
                    {
                        case 'cli' :
                            $sStart = $sStop = "\r\n";
                            break;

                        default :
                            $sStart = '<pre>';
                            $sStop  = '</pre>';
                            break;
                    }
                    echo $sStart . $this->FormatLog( array( $sMessage ), array( $sMessageNumber ) ) . $sStop;
                    @flush();
                    @ob_flush();
                    break;

                // basically the same thing as NET_SSH2_LOG_REALTIME with the caveat that NET_SSH2_LOG_REALTIME_FILE
                // needs to be defined and that the resultant log file will be capped out at NET_SSH2_LOG_MAX_SIZE.
                // the earliest part of the log file is denoted by the first <<< START >>> and is not going to necessarily
                // at the beginning of the file
                case NET_SSH2_LOG_REALTIME_FILE :
                    if ( !isset( $this->rhRealtimeLogFile ) )
                    {
                        // PHP doesn't seem to like using constants in fopen()
                        $sFilename = NET_SSH2_LOG_REALTIME_FILENAME;
                        $rhLogFile = fopen( $sFilename, 'w' );

                        // capture resource.
                        $this->rhRealtimeLogFile = $rhLogFile;
                    }
                    if ( !is_resource( $this->rhRealtimeLogFile ) )
                    {
                        // fail.
                        break;
                    }
                    $sLogEntry = $this->FormatLog( array( $sMessage ), array( $sMessageNumber ) );
                    if ( $this->bRealtimeLogWrap )
                    {
                        $sTemp      = "<<< START >>>\r\n";
                        $sLogEntry .= $sTemp;
                        fseek( $this->rhRealtimeLogFile, ftell( $this->rhRealtimeLogFile ) - strlen( $sTemp ) );
                    }
                    $this->iRealtimeLogSize += strlen( $sLogEntry );
                    if ( $this->iRealtimeLogSize > NET_SSH2_LOG_MAX_SIZE )
                    {
                        fseek( $this->rhRealtimeLogFile, 0 );
                        $this->iRealtimeLogSize = strlen( $sLogEntry );
                        $this->bRealtimeLogWrap = true;
                    }
                    fputs( $this->rhRealtimeLogFile, $sLogEntry );
                    break;

                default :
                    // nothing.
                    break;
            }
        }

        /**
         * Sends channel data
         *
         * Spans multiple SSH_MSG_CHANNEL_DATAs if appropriate
         *
         * @param   Integer $iClientChannel
         * @param   String  $sData
         *
         * @return  Boolean
         */
        public function SendChannelPacket( $iClientChannel, $sData )
        {
            /* The maximum amount of data allowed is determined by the maximum
               packet size for the channel, and the current window size, whichever is smaller.
               -- http://tools.ietf.org/html/rfc4254#section-5.2
            */
            $iMaxSize = min(
                $this->aPacketSizeClientToServer[ $iClientChannel ],
                $this->aWindowSizeClientToServer[ $iClientChannel ]
            ) - 4;
            while ( strlen( $sData ) > $iMaxSize )
            {
                if ( !$this->aWindowSizeClientToServer[ $iClientChannel ] )
                {
                    $this->iBitmap ^= NET_SSH2_MASK_WINDOW_ADJUST;

                    // using an invalid channel will let the buffers be built up for the valid channels
                    $sOutput = $this->GetChannelPacket( -1 );

                    $this->iBitmap ^= NET_SSH2_MASK_WINDOW_ADJUST;

                    $iMaxSize = min(
                        $this->aPacketSizeClientToServer[ $iClientChannel ],
                        $this->aWindowSizeClientToServer[ $iClientChannel ]
                    ) - 4;
                }

                $sPacket = pack( 'CN2a*',
                    NET_SSH2_MSG_CHANNEL_DATA,
                    $this->aServerChannels[ $iClientChannel ],
                    $iMaxSize,
                    $this->StringShift( $sData, $iMaxSize )
                 );

                $this->aWindowSizeClientToServer[ $iClientChannel ] -= $iMaxSize + 4;

                if ( !$this->SendBinaryPacket( $sPacket ) )
                {
                    return false;
                }
            }

            if ( strlen( $sData ) >= $this->aWindowSizeClientToServer[ $iClientChannel ] - 4 )
            {
                $this->iBitmap ^= NET_SSH2_MASK_WINDOW_ADJUST;
                $this->GetChannelPacket(-1);
                $this->iBitmap ^= NET_SSH2_MASK_WINDOW_ADJUST;
            }

            $this->aWindowSizeClientToServer[ $iClientChannel ] -= strlen( $sData ) + 4;

            // prepare final packet.
            $sPacket = pack( 'CN2a*',
                             NET_SSH2_MSG_CHANNEL_DATA,
                             $this->aServerChannels[ $iClientChannel ],
                             strlen( $sData ),
                             $sData
                        );
            // continue sending/receiving data.
            return $this->SendBinaryPacket( $sPacket );
        }

        /**
         * Closes and flushes a channel
         *
         * SSH2 doesn't properly close most channels.  For Cmd() channels are normally closed by the server
         * and for SFTP channels are presumably closed when the client disconnects.  This functions is intended
         * for SCP more than anything.
         *
         * @param   Integer $iClientChannel
         *
         * @return  Boolean
         */
        public function CloseChannel( $iClientChannel )
        {
            // see http://tools.ietf.org/html/rfc4254#section-5.3

            // close channel
            $this->SendBinaryPacket( pack( 'CN', NET_SSH2_MSG_CHANNEL_EOF, $this->aServerChannels[ $iClientChannel ] ) );
            $this->SendBinaryPacket( pack( 'CN', NET_SSH2_MSG_CHANNEL_CLOSE, $this->aServerChannels[ $iClientChannel ] ) );

            // reset state.
            $this->aChannelStatus[ $iClientChannel ] = NET_SSH2_MSG_CHANNEL_CLOSE;
            $this->iCurTimeout = 0;

            // wait until we get a packet.
            $bTempBool = null;
            while ( !is_bool( $bTempBool ) )
            {
                $bTempBool = $this->GetChannelPacket( $iClientChannel );
            }

            if ( $this->iBitmap & NET_SSH2_MASK_SHELL )
            {
                $this->iBitmap &= ~NET_SSH2_MASK_SHELL;
            }
        }

        /**
         * Disconnect
         *
         * @param   Integer $iReason    default = NET_SSH2_DISCONNECT_BY_APPLICATION
         *
         * @return  Boolean
         */
        public function Disconnect( $iReason = NET_SSH2_DISCONNECT_BY_APPLICATION )
        {
            if ( $this->iBitmap )
            {
                $sData = pack( 'CNNa*Na*', NET_SSH2_MSG_DISCONNECT, $iReason, 0, '', 0, '' );
                $this->SendBinaryPacket( $sData );
                $this->iBitmap = 0;
                fclose( $this->oSocket );
                return false;
            }
        }

        /**
         * String Shift
         *
         * Inspired by array_shift
         *
         * @param String $string
         * @param optional Integer $index
         * @return String
         * @access private
         */
        public function StringShift( &$sString, $iIndex = 1 )
        {
            $sSubStr = substr( $sString, 0, $iIndex );
            $sString = substr( $sString, $iIndex );
            return $sSubStr;
        }

        /**
         * Formats a log for printing
         *
         * @param Array $aMessageLog
         * @param Array $aMessageNumberLog
         *
         * @return String
         */
        public function FormatLog( $aMessageLog, $aMessageNumberLog )
        {
            static $sBoundary = ':', $iLongWidth = 65, $iShortWidth = 16;

            $sOutput = '';
            for ( $iLogIdx = 0; $iLogIdx < count( $aMessageLog ); $iLogIdx++ )
            {
                $sOutput    .= $aMessageNumberLog[ $iLogIdx ] . "\r\n";
                $sCurrentLog = $aMessageLog[ $iLogIdx ];
                $iPadCounter = 0;
                do
                {
                    if ( strlen( $sCurrentLog ) )
                    {
                        $sOutput.= str_pad( dechex( $iPadCounter ), 7, '0', STR_PAD_LEFT ) . '0  ';
                    }
                    $sFragment = $this->StringShift( $sCurrentLog, $iShortWidth );
                    $sHex = substr(
                               preg_replace(
                                   '#( . )#es',
                                   '"' . $sBoundary . '" . str_pad( dechex( ord( substr( "\\1", -1 ) ) ), 2, "0", STR_PAD_LEFT )',
                                   $sFragment ),
                               strlen( $sBoundary )
                            );
                    // replace non ASCII printable characters with dots
                    // http://en.wikipedia.org/wiki/ASCII#ASCII_printable_characters
                    // also replace < with a . since < messes up the output on web browsers
                    $sRaw     = preg_replace( '#[^\x20-\x7E]|<#', '.', $sFragment );
                    $sOutput .= str_pad( $sHex, $iLongWidth - $iShortWidth, ' ' ) . $sRaw . "\r\n";
                    $iPadCounter++;
                }
                while ( strlen( $sCurrentLog ) );
                $sOutput.= "\r\n";
            }

            return $sOutput;
        }

        /**
         * Is a path includable?
         *
         * @return Boolean
         */
        private function IsIncludable( $sSuffix )
        {
            // initialize return variable
            $bReturn = false;

            // search all include paths.
            foreach ( explode( PATH_SEPARATOR, get_include_path() ) as $sPrefix )
            {
                $sDirSep = substr( $sPrefix, -1 ) == DIRECTORY_SEPARATOR ? '' : DIRECTORY_SEPARATOR;
                $sFile   = $sPrefix . $sDirSep . $sSuffix;

                if ( file_exists( $sFile ) )
                {
                    $bReturn = true;
                    break;
                }
            }

            return $bReturn;
        }

        /**
         * Login
         *
         * The $password parameter can be a plaintext password, a Crypt_RSA object or an array
         *
         * @param   String  $username
         * @param   Mixed   $password
         * @param   Mixed   $...
         *
         * @return  Boolean
         *
         * @see     LoginHelper
         */
        public function Login( $sUsername )
        {
            // remove username param
            $aArgs = array_slice( func_get_args(), 1 );
            if ( empty( $aArgs ) )
            {
                return $this->LoginHelper( $sUsername );
            }
            // attempt login with all available arguments as password.
            foreach ( $aArgs as $vArg )
            {
                if ( $this->LoginHelper( $sUsername, $vArg ) )
                {
                    return true;
                }
            }
            return false;
        }

        /**
         * Set Timeout
         *
         * $ssh->Cmd( 'ping 127.0.0.1' ); on a Linux host will never return and will run indefinitely.
         * SetTimeout() makes it so it'll timeout.
         * Setting $iTimeout to false or 0 will mean there is no timeout.
         *
         * @param   Mixed   $iTimeout
         */
        public function SetTimeout( $iTimeout )
        {
            $this->iTimeout = $this->iCurTimeout = $iTimeout;
        }

        /**
         * Get the output from stdError
         *
         * @return  string  stderr log.
         */
        public function GetStdErrror()
        {
            return $this->sStdErrorLog;
        }

        /**
         * Execute Command
         *
         * If $block is set to false then SSH2::GetChannelPacket( NET_SSH2_CHANNEL_EXEC ) will need to be called manually.
         * In all likelihood, this is not a feature you want to be taking advantage of.
         *
         * @param   string      $sCommand
         * @param   callable    $oCallback
         *
         * @return String
         */
        public function Cmd( $sCommand, $oCallback = NULL )
        {
            $this->iCurTimeout   = $this->iTimeout;
            $this->bIsTimeout   = false;
            $this->sStdErrorLog = '';

            if ( !( $this->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                return false;
            }

            // RFC4254 defines the ( client ) window size as "bytes the other party can send before it must wait for the window to
            // be adjusted".  0x7FFFFFFF is, at 2GB, the max size.  technically, it should probably be decremented, but,
            // honestly, if you're transfering more than 2GB, you probably shouldn't be using phpseclib, anyway.
            // see http://tools.ietf.org/html/rfc4254#section-5.2 for more info
            $this->aWindowSizeServerToClient[ NET_SSH2_CHANNEL_EXEC ] = $this->iWindowSize;
            // 0x8000 is the maximum max packet size, per http://tools.ietf.org/html/rfc4253#section-6.1, although since PuTTy
            // uses 0x4000, that's what will be used here, as well.
            $iPacketSize = 0x4000;

            $sPacket = pack( 'CNa*N3',
                NET_SSH2_MSG_CHANNEL_OPEN,
                strlen( 'session' ),
                'session',
                NET_SSH2_CHANNEL_EXEC,
                $this->aWindowSizeServerToClient[ NET_SSH2_CHANNEL_EXEC ],
                $iPacketSize
            );

            if ( !$this->SendBinaryPacket( $sPacket ) )
            {
                return false;
            }

            $this->aChannelStatus[ NET_SSH2_CHANNEL_EXEC ] = NET_SSH2_MSG_CHANNEL_OPEN;

            $sResponse = $this->GetChannelPacket( NET_SSH2_CHANNEL_EXEC );
            if ( $sResponse === false )
            {
                return false;
            }
            // request pseudo terminal...
            if ( $this->bRequestPTY === true )
            {
                $sTerminalModes = pack( 'C', NET_SSH2_TTY_OP_END );
                $sPacket = pack( 'CNNa*CNa*N5a*',
                                 NET_SSH2_MSG_CHANNEL_REQUEST,
                                 $this->aServerChannels[ NET_SSH2_CHANNEL_EXEC ],
                                 strlen( 'pty-req' ),
                                 'pty-req',
                                 1,
                                 strlen( 'vt100' ),
                                 'vt100',
                                 80,
                                 24,
                                 0,
                                 0,
                                 strlen( $sTerminalModes ),
                                 $sTerminalModes
                            );

                if ( !$this->SendBinaryPacket( $sPacket ) )
                {
                    return false;
                }
                $sResponse = $this->GetBinaryPacket();
                if ( $sResponse === false )
                {
                    user_error( 'Connection closed by server' );
                    return false;
                }

                $aTemp = unpack( 'Ctype', $this->StringShift( $sResponse, 1 ) );
                switch ( $aTemp[ 'type' ] )
                {
                    case NET_SSH2_MSG_CHANNEL_SUCCESS:
                        break;

                    case NET_SSH2_MSG_CHANNEL_FAILURE:
                    default:
                        user_error( 'Unable to request pseudo-terminal' );
                        return $this->Disconnect( NET_SSH2_DISCONNECT_BY_APPLICATION );
                }
                $this->bInRequestPtyExec = true;
            }

            // sending a pty-req SSH_MSG_CHANNEL_REQUEST message is unnecessary and, in fact, in most cases, slows things
            // down.  the one place where it might be desirable is if you're doing something like SSH2::Cmd( 'ping localhost &' ).
            // with a pty-req SSH_MSG_CHANNEL_REQUEST, Cmd() will return immediately and the ping process will then
            // then immediately terminate.  without such a request Cmd() will loop indefinitely.  the ping process won't end but
            // neither will your script.

            // although, in theory, the size of SSH_MSG_CHANNEL_REQUEST could exceed the maximum packet size established by
            // SSH_MSG_CHANNEL_OPEN_CONFIRMATION, RFC4254#section-5.1 states that the "maximum packet size" refers to the
            // "maximum size of an individual data packet". ie. SSH_MSG_CHANNEL_DATA.  RFC4254#section-5.2 corroborates.
            $sPacket = pack( 'CNNa*CNa*',
                NET_SSH2_MSG_CHANNEL_REQUEST,
                $this->aServerChannels[ NET_SSH2_CHANNEL_EXEC ],
                strlen( 'exec' ),
                'exec',
                1,
                strlen( $sCommand ),
                $sCommand
            );
            if ( !$this->SendBinaryPacket( $sPacket ) )
            {
                return false;
            }

            $this->aChannelStatus[ NET_SSH2_CHANNEL_EXEC ] = NET_SSH2_MSG_CHANNEL_REQUEST;

            $sResponse = $this->GetChannelPacket( NET_SSH2_CHANNEL_EXEC );
            if ( $sResponse === false )
            {
                return false;
            }

            $this->aChannelStatus[ NET_SSH2_CHANNEL_EXEC ] = NET_SSH2_MSG_CHANNEL_DATA;

            if ( $oCallback === false || $this->bInRequestPtyExec )
            {
                return true;
            }

            $sOutput = '';
            while ( true )
            {
                $sTemp = $this->GetChannelPacket( NET_SSH2_CHANNEL_EXEC );
                switch ( true )
                {
                    case $sTemp === true:
                        return is_callable( $oCallback ) ? true : $sOutput;

                    case $sTemp === false:
                        return false;

                    default:
                        if ( is_callable( $oCallback ) )
                        {
                            $oCallback( $sTemp );
                        }
                        else
                        {
                            $sOutput.= $sTemp;
                        }
                        break;
                }
            }
        }

        /**
         * Return the channel to be used with read() / write()
         *
         * @see     SSH2::read()
         * @see     SSH2::write()
         *
         * @return  Integer
         */
        public function GetInteractiveChannel()
        {
            $iChannel = NET_SSH2_CHANNEL_SHELL;

            if ( $this->bInSubsystem )
            {
                $iChannel = NET_SSH2_CHANNEL_SUBSYSTEM;
            }
            else if ( $this->bInRequestPtyExec )
            {
                $iChannel = NET_SSH2_CHANNEL_EXEC;
            }
            return $iChannel;
        }

        /**
         * Returns the output of an interactive shell
         *
         * Returns when there's a match for $sExpect, which can take the form of a string literal or,
         * if $iMode == NET_SSH2_READ_REGEX, a regular expression.
         *
         * @see     SSH2:Write()
         *
         * @param   String  $sExpect
         * @param   Integer $iMode
         *
         * @return  String
         */
        public function Read( $sExpect = '', $iMode = NET_SSH2_READ_SIMPLE )
        {
            $this->iCurTimeout = $this->iTimeout;
            $this->bIsTimeout = false;

            if ( !( $this->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                user_error( 'Operation disallowed prior to Login()' );
                return false;
            }

            if ( !( $this->iBitmap & NET_SSH2_MASK_SHELL ) && !$this->InitShell() )
            {
                user_error( 'Unable to initiate an interactive shell session' );
                return false;
            }

            $iChannel = $this->GetInteractiveChannel();

            $sMatch = $sExpect;
            while ( true )
            {
                // get regex match
                if ( $iMode == NET_SSH2_READ_REGEX )
                {
                    preg_match( $sExpect, $this->sInteractiveBuffer, $aMatches );
                    $sMatch = isset( $aMatches[ 0 ] ) ? $aMatches[ 0 ] : '';
                }
                // get string position
                $iPos = strlen( $sMatch ) ? strpos( $this->sInteractiveBuffer, $sMatch ) : false;
                if ( $iPos !== false )
                {
                    return $this->StringShift( $this->sInteractiveBuffer, $iPos + strlen( $sMatch ) );
                }

                $sResponse = $this->GetChannelPacket( $iChannel );
                if ( is_bool( $sResponse ) )
                {
                    $this->bInRequestPtyExec = false;
                    return $sResponse ? $this->StringShift( $this->sInteractiveBuffer, strlen( $this->sInteractiveBuffer ) ) : false;
                }

                $this->sInteractiveBuffer .= $sResponse;
            }
        }

        /**
         * Inputs a command into an interactive shell.
         *
         * @see     SSH2::Read()
         *
         * @param   String  $sCmd
         * @return  Boolean
         */
        public function Write( $sCmd )
        {
            if ( !( $this->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                user_error( 'Operation disallowed prior to Login()' );
                return false;
            }

            if ( !( $this->iBitmap & NET_SSH2_MASK_SHELL ) && !$this->InitShell() )
            {
                user_error( 'Unable to initiate an interactive shell session' );
                return false;
            }

            $iChannel = $this->GetInteractiveChannel();

            return $this->SendChannelPacket( $iChannel, $sCmd );
        }

        /**
         * Start a subsystem.
         *
         * Right now only one subsystem at a time is supported.
         * To support multiple subsystem's StopSubsystem() could accept
         * a string that contained the name of the subsystem, but at that
         * point, only one subsystem of each type could be opened.
         *
         * To support multiple subsystem's of the same name maybe it'd be
         * best if StartSubsystem() generated a new channel id and
         * returns that and then that that was passed into StopSubsystem()
         * but that'll be saved for a future date and implemented
         * if there's sufficient demand for such a feature.
         *
         * @see     SSH2::StopSubsystem()
         *
         * @param   String $sSubsystem
         *
         * @return  Boolean
         */
        public function StartSubsystem( $sSubsystem )
        {
            $this->aWindowSizeServerToClient[ NET_SSH2_CHANNEL_SUBSYSTEM ] = $this->iWindowSize;
            $iPacketSize = 0x4000;
            $sPacket = pack( 'CNa*N3',
                            NET_SSH2_MSG_CHANNEL_OPEN,
                            strlen( 'session' ),
                            'session',
                            NET_SSH2_CHANNEL_SUBSYSTEM,
                            $this->iWindowSize,
                            $iPacketSize
                         );

            if ( !$this->SendBinaryPacket( $sPacket ) )
            {
                return false;
            }

            $this->aChannelStatus[ NET_SSH2_CHANNEL_SUBSYSTEM ] = NET_SSH2_MSG_CHANNEL_OPEN;

            $sResponse = $this->GetChannelPacket( NET_SSH2_CHANNEL_SUBSYSTEM );
            if ( $sResponse === false )
            {
                return false;
            }

            $sPacket = pack( 'CNNa*CNa*',
                            NET_SSH2_MSG_CHANNEL_REQUEST,
                            $this->aServerChannels[ NET_SSH2_CHANNEL_SUBSYSTEM ],
                            strlen( 'subsystem' ),
                            'subsystem',
                            1,
                            strlen( $sSubsystem ),
                            $sSubsystem
                         );
            if ( !$this->SendBinaryPacket( $sPacket ) )
            {
                return false;
            }

            $this->aChannelStatus[ NET_SSH2_CHANNEL_SUBSYSTEM ] = NET_SSH2_MSG_CHANNEL_REQUEST;

            $sResponse = $this->GetChannelPacket( NET_SSH2_CHANNEL_SUBSYSTEM );

            if ( $sResponse === false )
            {
               return false;
            }

            $this->aChannelStatus[ NET_SSH2_CHANNEL_SUBSYSTEM ] = NET_SSH2_MSG_CHANNEL_DATA;

            $this->iBitmap |= NET_SSH2_MASK_SHELL;
            $this->bInSubsystem = true;

            return true;
        }

        /**
         * Stops a subsystem.
         *
         * @see     SSH2::StartSubsystem()
         *
         * @return  Boolean
         */
        public function StopSubsystem()
        {
            $this->bInSubsystem = false;
            $this->CloseChannel( NET_SSH2_CHANNEL_SUBSYSTEM );

            return true;
        }

        /**
         * Closes a channel
         *
         * If Read() timed out you might want to just close the channel
         * and have it auto-restart on the next Read() call
         */
        public function Reset()
        {
            $iChannel = $this->GetInteractiveChannel();
            $this->CloseChannel( $iChannel );
        }

        /**
         * Is timeout?
         *
         * Did Cmd() or Read() return because they timed out or because they encountered the end?
         */
        public function IsTimeout()
        {
            return $this->bIsTimeout;
        }

        /**
         * Disconnect
         */
        public function Logout()
        {
            $this->Disconnect( NET_SSH2_DISCONNECT_BY_APPLICATION );
            if ( isset( $this->rhRealtimeLogFile ) && is_resource( $this->rhRealtimeLogFile ) )
            {
                fclose( $this->rhRealtimeLogFile );
            }
        }

        /**
         * Is the connection still active?
         */
        public function IsConnected()
        {
            return $this->iBitmap & NET_SSH2_MASK_LOGIN;
        }

        /**
         * Enable Quiet Mode
         *
         * Suppress stderr from output
         */
        public function EnableQuietMode()
        {
            $this->bQuietMode = true;
        }

        /**
         * Disable Quiet Mode
         *
         * Show stderr in output
         */
        public function DisableQuietMode()
        {
            $this->bQuietMode = false;
        }

        /**
         * Enable request-pty when using Cmd()
         */
        public function EnablePTY()
        {
            $this->bRequestPTY = true;
        }

        /**
         * Disable request-pty when using Cmd()
         */
        public function DisablePTY()
        {
            $this->bRequestPTY = false;
        }

        /**
         * Returns a log of the packets that have been sent and received.
         *
         * Returns a string if NET_SSH2_LOGGING == NET_SSH2_LOG_COMPLEX,
         * an array if NET_SSH2_LOGGING == NET_SSH2_LOG_SIMPLE
         * and false if !defined( 'NET_SSH2_LOGGING' )
         *
         * @return String or Array
         */
        public function GetLog()
        {
            if ( !defined( 'NET_SSH2_LOGGING' ) )
            {
                return false;
            }

            switch ( NET_SSH2_LOGGING )
            {
                case NET_SSH2_LOG_SIMPLE :
                    return $this->aMessageNumberLog;
                    break;

                case NET_SSH2_LOG_COMPLEX :
                    return $this->FormatLog( $this->aMessageLog, $this->aMessageNumberLog );
                    break;

                default :
                    // nothing.
                    return false;
            }
        }

        /**
         * Returns all errors
         *
         * @return String
         */
        public function GetErrors()
        {
            return $this->aErrors;
        }

        /**
         * Returns the last error
         *
         * @return String
         */
        public function GetLastError()
        {
            return !empty( $this->aErrors ) ? $this->aErrors[ count( $this->aErrors ) - 1 ] : '';
        }

        /**
         * Return the server identification.
         *
         * @return String
         */
        public function GetServerIdentification()
        {
            return $this->sServerIdentifier;
        }

        /**
         * Return a list of the key exchange algorithms the server supports.
         *
         * @return Array
         */
        public function GetKexAlgorithms()
        {
            return $this->aKexAlgorithms;
        }

        /**
         * Return a list of the host key ( public key ) algorithms the server supports.
         *
         * @return Array
         */
        public function GetServerHostKeyAlgorithms()
        {
            return $this->aServerHostKeyAlgorithms;
        }

        /**
         * Return a list of the ( symmetric key ) encryption algorithms the server supports, when receiving stuff from the client.
         *
         * @return Array
         */
        public function GetEncryptionAlgorithmsClient2Server()
        {
            return $this->aEncryptionAlgorithmsClientToServer;
        }

        /**
         * Return a list of the ( symmetric key ) encryption algorithms the server supports, when sending stuff to the client.
         *
         * @return Array
         */
        public function GetEncryptionAlgorithmsServer2Client()
        {
            return $this->aEncryptionAlgorithmsServerToClient;
        }

        /**
         * Return a list of the MAC algorithms the server supports, when receiving stuff from the client.
         *
         * @return Array
         */
        public function GetMACAlgorithmsClient2Server()
        {
            return $this->aMacAlgorithmsClientToServer;
        }

        /**
         * Return a list of the MAC algorithms the server supports, when sending stuff to the client.
         *
         * @return Array
         */
        public function GetMACAlgorithmsServer2Client()
        {
            return $this->aMacAlgorithmsServerToClient;
        }

        /**
         * Return a list of the compression algorithms the server supports, when receiving stuff from the client.
         *
         * @return Array
         */
        public function GetCompressionAlgorithmsClient2Server()
        {
            return $this->aCompressionAlgorithmsClientToServer;
        }

        /**
         * Return a list of the compression algorithms the server supports, when sending stuff to the client.
         *
         * @return Array
         */
        public function GetCompressionAlgorithmsServer2Client()
        {
            return $this->aCompressionAlgorithmsServerToClient;
        }

        /**
         * Return a list of the languages the server supports, when sending stuff to the client.
         *
         * @return Array
         */
        public function GetLanguagesServer2Client()
        {
            return $this->aLanguagesServerToClient;
        }

        /**
         * Return a list of the languages the server supports, when receiving stuff from the client.
         *
         * @return Array
         */
        public function GetLanguagesClient2Server()
        {
            return $this->aLanguagesClientToServer;
        }

        /**
         * Returns the banner message.
         *
         * Quoting from the RFC, "in some jurisdictions, sending a warning message before
         * authentication may be relevant for getting legal protection."
         *
         * @return String
         */
        public function GetBannerMessage()
        {
            return $this->sBannerMessage;
        }

        /**
         * Returns the server public host key.
         *
         * Caching this the first time you connect to a server and checking the result on subsequent connections
         * is recommended.  Returns false if the server signature is not signed correctly with the public host key.
         *
         * @return Mixed
         */
        public function GetServerPublicHostKey()
        {
            $sSignature           = $this->sSignature;
            $sServerPublicHostKey = $this->sServerPublicHostKey;

            $aTemp = unpack( 'Nlength', $this->StringShift( $sServerPublicHostKey, 4 ) );
            $this->StringShift( $sServerPublicHostKey, $aTemp[ 'length' ] );

            if ( $this->bSignatureValidated )
            {
                return $this->iBitmap ?
                    $this->sSignatureFormat . ' ' . base64_encode( $this->sServerPublicHostKey ) :
                    false;
            }

            $this->bSignatureValidated = true;

            switch ( $this->sSignatureFormat )
            {
                case 'ssh-dss' :
                    $oZero = new Math_BigInteger();

                    $aTemp  = unpack( 'Nlength', $this->StringShift( $sServerPublicHostKey, 4 ) );
                    $oPhk1 = new Math_BigInteger( $this->StringShift( $sServerPublicHostKey, $aTemp[ 'length' ] ), -256 );

                    $aTemp = unpack( 'Nlength', $this->StringShift( $sServerPublicHostKey, 4 ) );
                    $oPhk2     = new Math_BigInteger( $this->StringShift( $sServerPublicHostKey, $aTemp[ 'length' ] ), -256 );

                    $aTemp = unpack( 'Nlength', $this->StringShift( $sServerPublicHostKey, 4 ) );
                    $oPhk3     = new Math_BigInteger( $this->StringShift( $sServerPublicHostKey, $aTemp[ 'length' ] ), -256 );

                    $aTemp = unpack( 'Nlength', $this->StringShift( $sServerPublicHostKey, 4 ) );
                    $oPhk4     = new Math_BigInteger( $this->StringShift( $sServerPublicHostKey, $aTemp[ 'length' ] ), -256 );

                    /* The value for 'dss_signature_blob' is encoded as a string containing
                       r, followed by s ( which are 160-bit integers, without lengths or
                       padding, unsigned, and in network byte order ). */
                    $aTemp = unpack( 'Nlength', $this->StringShift( $sSignature, 4 ) );
                    if ( $aTemp[ 'length' ] != 40 )
                    {
                        user_error( 'Invalid signature' );
                        return $this->Disconnect( NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED );
                    }

                    $oSig1 = new Math_BigInteger( $this->StringShift( $sSignature, 20 ), 256 );
                    $oSig2 = new Math_BigInteger( $this->StringShift( $sSignature, 20 ), 256 );

                    // if ( $oSig1->compare( $oPhk2 ) >= 0 || $oSig2->compare( $oPhk2 ) >= 0 )
                    // {
                    //     user_error( 'Invalid signature' );
                    //     return $this->Disconnect( NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED );
                    // }
                    switch ( true )
                    {
                        case $oSig1->equals( $oZero ) :
                        case $oSig1->compare( $oPhk2 ) >= 0 :
                        case $oSig2->equals( $oZero ) :
                        case $oSig2->compare( $oPhk2 ) >= 0 :
                            user_error( 'Invalid signature' );
                            return $this->Disconnect( NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED );

                        default :
                            // nothing.
                            break;
                    }
                    // recalculate signature, compare to given.
                    $oModInverse = $oSig2->modInverse( $oPhk2 );

                    $oExponent1 = $oModInverse->multiply( new Math_BigInteger( sha1( $this->sExchangeHash ), 16 ) );
                    list( , $oExponent1 ) = $oExponent1->divide( $oPhk2 );

                    $oExponent2 = $oModInverse->multiply( $oSig1 );
                    list( , $oExponent2 ) = $oExponent2->divide( $oPhk2 );

                    $oPhk3 = $oPhk3->modPow( $oExponent1, $oPhk1 );
                    $oPhk4 = $oPhk4->modPow( $oExponent2, $oPhk1 );

                    $oSignature = $oPhk3->multiply( $oPhk4 );
                    list( , $oSignature ) = $oSignature->divide( $oPhk1 );
                    list( , $oSignature ) = $oSignature->divide( $oPhk2 );

                    if ( !$oSignature->equals( $oSig1 ) )
                    {
                        user_error( 'Bad server signature' );
                        return $this->Disconnect( NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE );
                    }

                    break;

                case 'ssh-rsa' :
                    $aTemp      = unpack( 'Nlength', $this->StringShift( $sServerPublicHostKey, 4 ) );
                    $oExponent  = new Math_BigInteger( $this->StringShift( $sServerPublicHostKey, $aTemp[ 'length' ] ), -256 );

                    $aTemp      = unpack( 'Nlength', $this->StringShift( $sServerPublicHostKey, 4 ) );
                    $oKeyNumber = new Math_BigInteger( $this->StringShift( $sServerPublicHostKey, $aTemp[ 'length' ] ), -256 );
                    $iNumLength = $aTemp[ 'length' ];

                    $aTemp      = unpack( 'Nlength', $this->StringShift( $sSignature, 4 ) );
                    $oSignature = new Math_BigInteger( $this->StringShift( $sSignature, $aTemp[ 'length' ] ), 256 );

                    // validate an RSA signature per "8.2 RSASSA-PKCS1-v1_5", "5.2.2 RSAVP1", and "9.1 EMSA-PSS" in the
                    // following URL:
                    // ftp://ftp.rsasecurity.com/pub/pkcs/pkcs-1/pkcs-1v2-1.pdf

                    // also, see SSHRSA.c ( rsa2_verifysig ) in PuTTy's source.

                    if ( $oSignature->compare( new Math_BigInteger() ) < 0 || $oSignature->compare( $oKeyNumber->subtract( new Math_BigInteger( 1 ) ) ) > 0 )
                    {
                        user_error( 'Invalid signature' );
                        return $this->Disconnect( NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED );
                    }

                    $oSignature = $oSignature->modPow( $oExponent, $oKeyNumber );
                    $oSignature = $oSignature->toBytes();

                    $sCompareSig = pack( 'N4H*', 0x00302130, 0x0906052B, 0x0E03021A, 0x05000414, sha1( $this->sExchangeHash ) );
                    $sCompareSig = chr( 0x01 ) . str_repeat( chr( 0xFF ), $iNumLength - 3 - strlen( $sCompareSig ) ) . $sCompareSig;

                    if ( $oSignature != $sCompareSig )
                    {
                        user_error( 'Bad server signature' );
                        return $this->Disconnect( NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE );
                    }
                    break;

                default :
                    user_error( 'Unsupported signature format' );
                    return $this->Disconnect( NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE );
            }

            return $this->sSignatureFormat . ' ' . base64_encode( $this->sServerPublicHostKey );
        }

        /**
         * Returns the exit status of an SSH command or false.
         *
         * @return Integer or false
         */
        public function GetExitStatus()
        {
            if ( is_null( $this->iExitStatus ) )
            {
                return false;
            }
            return $this->iExitStatus;
        }

        /**
         * Destructor.
         *
         * Will be called, automatically, if you're supporting just PHP5.
         * If you're supporting PHP4, you'll need to call Logout().
         */
        public function __destruct()
        {
            $this->Logout();
            if ( function_exists( 'gc_collect_cycles' ) )
            {
                gc_collect_cycles();
            }
        }
    }
?>