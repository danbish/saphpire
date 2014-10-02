<?php
    require_once( 'includes/sftp-defined.php' );

    /**
     * Pure-PHP implementation of SFTP.
     *
     * PHP version 5
     *
     * PHP4 Incompatibility:
     *     microtime( true )
     *
     * Currently only supports SFTPv2 and v3, which, according to wikipedia.org,
     * "is the most widely used version, implemented by the popular OpenSSH SFTP server".
     *
     * If you want SFTPv4/5/6 support, provide me with access to an SFTPv4/5/6 server.
     *
     * The API for this library is modeled after the API from PHP's
     * @link http://php.net/book.ftp    FTP extension.
     *
     * Juggernaut standardized version
     *
     * usage:
     *
     * SFTP requires a valid/open SSH2 interactive shell object.
     * You need to require the SSH2 library.
     *
     *login
     *     $oSSH2 = new cSSH2( 'devsislin01.clemson.edu' );
     *     if ( !$oSSH2->Login( $sUser, $sPass ) )
     *     {
     *         exit( 'Login Failed' );
     *     }
     *
     *     $oSFTP = new cSFTP( $oSSH2 );
     *     $oSFTP->put()
     *     $oSFTP->get()
     *     $oSFTP->delete()
     *     $oSFTP->rename()
     *     $oSFTP->chmod()
     *     $oSFTP->pwd()
     *     $oSFTP->cwd()
     *     $oSFTP->chown()
     *     $oSFTP->chgrp()
     *     $oSFTP->chdir()
     *     $oSFTP->nlist()
     *     $oSFTP->size()
     *     $oSFTP->truncate()
     *     $oSFTP->touch()
     *     $oSFTP->mkdir()
     *     $oSFTP->rmdir()
     *     $oSFTP->getSFTPLog()
     *     $oSFTP->getSFTPErrors()
     *     $oSFTP->getLastSFTPError()
     *     $oSFTP->getSupportedVersions()
     *
     * @author Academic Services::Team Ra
     * @version 0.1.0
     *
     * @todo    test support of OpenSSHv4 server. This implementation may be lacking
     *          some of the SFTPv4 protocol's measures.
     */
    class cSFTP
    {
        /**
         * SSH Object
         *
         * @var Object
         */
        private $oSSH;

        /**
         * Packet Types
         *
         * @see SFTP::__construct()
         * @var Array
         */
        private $aPacketTypes = array();

        /**
         * Status Codes
         *
         * @see SFTP::__construct()
         * @var Array
         */
        private $aStatusCodes = array();

        /**
         * The Request ID
         *
         * The request ID exists in the off chance that a packet is
         * sent out-of-order.  Of course, this library doesn't support
         * concurrent actions, so it's somewhat academic, here.
         *
         * @var Integer
         * @see SFTP::_send_sftp_packet()
         */
        private $iRequestId = false;

        /**
         * The Packet Type
         *
         * The packet type identifies the packet we are receiving for
         * proper handling.
         *
         * @var Integer
         * @see SFTP::_get_sftp_packet()
         */
        private $iPacketType = -1;

        /**
         * Packet Buffer
         *
         * @var String
         * @see SFTP::_get_sftp_packet()
         */
        private $sPacketBuffer = '';

        /**
         * Extensions supported by the server
         *
         * @var Array
         * @see SFTP::Init()
         */
        private $aExtensions = array();

        /**
         * Server SFTP version
         *
         * @var Integer
         * @see SFTP::Init()
         */
        private $iVersion;

        /**
         * Current working directory
         *
         * @var String
         * @see SFTP::_realpath()
         * @see SFTP::chdir()
         */
        private $sPwd = false;

        /**
         * Packet Type Log
         *
         * @see SFTP::getLog()
         * @var Array
         */
        private $aPacketTypeLog = array();

        /**
         * Packet Log
         *
         * @see SFTP::getLog()
         * @var Array
         */
        private $aPacketLog = array();

        /**
         * Error information
         *
         * @see SFTP::getSFTPErrors()
         * @see SFTP::getLastSFTPError()
         * @var String
         */
        private $aSftpErrors = array();

        /**
         * Directory Cache
         *
         * Keeps tracks of the list of currently available
         * directories to prevent redundant processing.
         *
         * @see SFTP::_save_dir()
         * @see SFTP::_remove_dir()
         * @see SFTP::_is_dir()
         * @var Array
         */
        private $aDirs = array();

        /**
         * Max SFTP Packet Size
         *
         * @see SFTP::__construct()
         * @see SFTP::get()
         * @var Array
         */
        private $iMaxSftpPacketSize;

        /**
         * Determines whether the SFTP session
         * has been initialized or not.
         *
         * @var    boolean
         */
        private $bInitialized = false;

        /**
         * Default Constructor.
         *
         * Connects to an SFTP server
         *
         * @param   Object  $oSSH   SSH client object
         */
        public function __construct( $oSSH = null )
        {
            // check for valid ssh object.
            if ( !is_object( $this->oSSH ) && is_object( $oSSH ) )
            {
                // set version-specific settings.
                $sClass = strtolower( get_class( $oSSH ) );
                if ( $sClass === 'cssh2' )
                {
                    $this->oSSH = $oSSH;
                }
            }

            // set properties.
            $this->iMaxSftpPacketSize = 1 << 15;

            // set lookup tables for packet types, status codes, and attributes.
            $this->aPacketTypes = array(
                " " . NET_SFTP_INIT     => 'NET_SFTP_INIT',
                " " . NET_SFTP_VERSION  => 'NET_SFTP_VERSION',
                " " . NET_SFTP_OPEN     => 'NET_SFTP_OPEN',
                " " . NET_SFTP_CLOSE    => 'NET_SFTP_CLOSE',
                " " . NET_SFTP_READ     => 'NET_SFTP_READ',
                " " . NET_SFTP_WRITE    => 'NET_SFTP_WRITE',
                " " . NET_SFTP_LSTAT    => 'NET_SFTP_LSTAT',
                " " . NET_SFTP_SETSTAT  => 'NET_SFTP_SETSTAT',
                " " . NET_SFTP_OPENDIR  => 'NET_SFTP_OPENDIR',
                " " . NET_SFTP_READDIR  => 'NET_SFTP_READDIR',
                " " . NET_SFTP_REMOVE   => 'NET_SFTP_REMOVE',
                " " . NET_SFTP_MKDIR    => 'NET_SFTP_MKDIR',
                " " . NET_SFTP_RMDIR    => 'NET_SFTP_RMDIR',
                " " . NET_SFTP_REALPATH => 'NET_SFTP_REALPATH',
                " " . NET_SFTP_STAT     => 'NET_SFTP_STAT',
                " " . NET_SFTP_RENAME   => 'NET_SFTP_RENAME',
                " " . NET_SFTP_STATUS   => 'NET_SFTP_STATUS',
                " " . NET_SFTP_HANDLE   => 'NET_SFTP_HANDLE',
                " " . NET_SFTP_DATA     => 'NET_SFTP_DATA',
                " " . NET_SFTP_NAME     => 'NET_SFTP_NAME',
                " " . NET_SFTP_ATTRS    => 'NET_SFTP_ATTRS',
                " " . NET_SFTP_EXTENDED => 'NET_SFTP_EXTENDED'
            );
            $this->aStatusCodes = array(
                " " . NET_SFTP_STATUS_OK                          => 'NET_SFTP_STATUS_OK',
                " " . NET_SFTP_STATUS_EOF                         => 'NET_SFTP_STATUS_EOF',
                " " . NET_SFTP_STATUS_NO_SUCH_FILE                => 'NET_SFTP_STATUS_NO_SUCH_FILE',
                " " . NET_SFTP_STATUS_PERMISSION_DENIED           => 'NET_SFTP_STATUS_PERMISSION_DENIED',
                " " . NET_SFTP_STATUS_FAILURE                     => 'NET_SFTP_STATUS_FAILURE',
                " " . NET_SFTP_STATUS_BAD_MESSAGE                 => 'NET_SFTP_STATUS_BAD_MESSAGE',
                " " . NET_SFTP_STATUS_NO_CONNECTION               => 'NET_SFTP_STATUS_NO_CONNECTION',
                " " . NET_SFTP_STATUS_CONNECTION_LOST             => 'NET_SFTP_STATUS_CONNECTION_LOST',
                " " . NET_SFTP_STATUS_OP_UNSUPPORTED              => 'NET_SFTP_STATUS_OP_UNSUPPORTED',
                " " . NET_SFTP_STATUS_INVALID_HANDLE              => 'NET_SFTP_STATUS_INVALID_HANDLE',
                " " . NET_SFTP_STATUS_NO_SUCH_PATH                => 'NET_SFTP_STATUS_NO_SUCH_PATH',
                " " . NET_SFTP_STATUS_FILE_ALREADY_EXISTS         => 'NET_SFTP_STATUS_FILE_ALREADY_EXISTS',
                " " . NET_SFTP_STATUS_WRITE_PROTECT               => 'NET_SFTP_STATUS_WRITE_PROTECT',
                " " . NET_SFTP_STATUS_NO_MEDIA                    => 'NET_SFTP_STATUS_NO_MEDIA',
                " " . NET_SFTP_STATUS_NO_SPACE_ON_FILESYSTEM      => 'NET_SFTP_STATUS_NO_SPACE_ON_FILESYSTEM',
                " " . NET_SFTP_STATUS_QUOTA_EXCEEDED              => 'NET_SFTP_STATUS_QUOTA_EXCEEDED',
                " " . NET_SFTP_STATUS_UNKNOWN_PRINCIPAL           => 'NET_SFTP_STATUS_UNKNOWN_PRINCIPAL',
                " " . NET_SFTP_STATUS_LOCK_CONFLICT               => 'NET_SFTP_STATUS_LOCK_CONFLICT',
                " " . NET_SFTP_STATUS_DIR_NOT_EMPTY               => 'NET_SFTP_STATUS_DIR_NOT_EMPTY',
                " " . NET_SFTP_STATUS_NOT_A_DIRECTORY             => 'NET_SFTP_STATUS_NOT_A_DIRECTORY',
                " " . NET_SFTP_STATUS_INVALID_FILENAME            => 'NET_SFTP_STATUS_INVALID_FILENAME',
                " " . NET_SFTP_STATUS_LINK_LOOP                   => 'NET_SFTP_STATUS_LINK_LOOP',
                " " . NET_SFTP_STATUS_CANNOT_DELETE               => 'NET_SFTP_STATUS_CANNOT_DELETE',
                " " . NET_SFTP_STATUS_INVALID_PARAMETER           => 'NET_SFTP_STATUS_INVALID_PARAMETER',
                " " . NET_SFTP_STATUS_FILE_IS_A_DIRECTORY         => 'NET_SFTP_STATUS_FILE_IS_A_DIRECTORY',
                " " . NET_SFTP_STATUS_BYTE_RANGE_LOCK_CONFLICT    => 'NET_SFTP_STATUS_BYTE_RANGE_LOCK_CONFLICT',
                " " . NET_SFTP_STATUS_BYTE_RANGE_LOCK_REFUSED     => 'NET_SFTP_STATUS_BYTE_RANGE_LOCK_REFUSED',
                " " . NET_SFTP_STATUS_DELETE_PENDING              => 'NET_SFTP_STATUS_DELETE_PENDING',
                " " . NET_SFTP_STATUS_FILE_CORRUPT                => 'NET_SFTP_STATUS_FILE_CORRUPT',
                " " . NET_SFTP_STATUS_OWNER_INVALID               => 'NET_SFTP_STATUS_OWNER_INVALID',
                " " . NET_SFTP_STATUS_GROUP_INVALID               => 'NET_SFTP_STATUS_GROUP_INVALID',
                " " . NET_SFTP_STATUS_NO_MATCHING_BYTE_RANGE_LOCK => 'NET_SFTP_STATUS_NO_MATCHING_BYTE_RANGE_LOCK'
            );
            $this->aAttributes = array(
                    'NET_SFTP_ATTR_SIZE'        => 0x00000001,
                    'NET_SFTP_ATTR_UIDGID'      => 0x00000002,
                    'NET_SFTP_ATTR_PERMISSIONS' => 0x00000004,
                    'NET_SFTP_ATTR_ACCESSTIME'  => 0x00000008,
                    'NET_SFTP_ATTR_EXTENDED'    => -1 << 31
            );

            if ( empty( $this->oSSH ) )
            {
                throw new Exception( "Valid SSH client object required.", -1 );
            }
            else
            {
                $this->bInitialized = $this->Init();
            }
        }

        /**
         * Init
         *
         * Initializes the SFTP session on the server. Determines the
         * SFTP version that both client and server are utilizing. Gets
         * supported extensions and get's PWD
         *
         * We assume that we are already logged in to a valid SSH shell
         *
         * @return  Boolean
         *
         * @todo    make ssh version configurable.
         *
         * @access  private
         */
        public function Init()
        {
            // only want to do this once.
            if ( !$this->bInitialized )
            {
                $this->oSSH->aWindowSizeServerToClient[ NET_SFTP_CHANNEL ] = $this->oSSH->iWindowSize;

                // request an open channel session.
                $iPacketSize = 0x4000;
                $sPacket     = pack( 'CNa*N3',
                                     NET_SSH2_MSG_CHANNEL_OPEN,
                                     strlen( 'session' ),
                                     'session',
                                     NET_SFTP_CHANNEL,
                                     $this->oSSH->iWindowSize,
                                     $iPacketSize
                                );
                if ( !$this->oSSH->SendBinaryPacket( $sPacket ) )
                {
                    // sending failure.
                    return false;
                }
                // set our status.
                $this->oSSH->aChannelStatus[ NET_SFTP_CHANNEL ] = NET_SSH2_MSG_CHANNEL_OPEN;

                // check the response.
                $sResponse = $this->oSSH->GetChannelPacket( NET_SFTP_CHANNEL );
                if ( $sResponse === false )
                {
                    // retrieval failure.
                    return false;
                }

                // request sftp subsystem.
                $sPacket = pack( 'CNNa*CNa*',
                                 NET_SSH2_MSG_CHANNEL_REQUEST,
                                 $this->oSSH->aServerChannels[ NET_SFTP_CHANNEL ],
                                 strlen( 'subsystem' ),
                                 'subsystem',
                                 1,
                                 strlen( 'sftp' ),
                                 'sftp'
                            );
                if ( !$this->oSSH->SendBinaryPacket( $sPacket ) )
                {
                    return false;
                }
                // set our status.
                $this->oSSH->aChannelStatus[ NET_SFTP_CHANNEL ] = NET_SSH2_MSG_CHANNEL_REQUEST;

                $sResponse = $this->oSSH->GetChannelPacket( NET_SFTP_CHANNEL );
                if ( $sResponse === false )
                {
                    /**
                     * @todo    Check that this works correctly. On devsislin01.clemson.edu, there is
                     *          no symlink for /usr/lib/sftp-server, /usr/local/lib/sftp-server, or
                     *          sftp-server. The sftp binary is located at /usr/bin/sftp.
                     *
                     *          This may be just used to start the SFTP server if it is down. This will
                     *          not apply to us in the Enterprise environment.
                     */
                    exit( 'You need to check on something!! ' . __FILE__ . ' ' . __LINE__ . ' ' . print_r( $sResponse, 1 ) . ' ' . print_r( debug_backtrace(), 1 ) );
                    // from PuTTY's psftp.exe
                    $sCommand = "test -x /usr/lib/sftp-server && exec /usr/lib/sftp-server\n" .
                                "test -x /usr/local/lib/sftp-server && exec /usr/local/lib/sftp-server\n" .
                                "exec sftp-server";
                    // we don't do $this->exec( $sCommand, false ) because exec() operates on a different channel
                    // and plus the SSH_MSG_CHANNEL_OPEN that exec() does is redundant
                    $sPacket = pack('CNNa*CNa*',
                                    NET_SSH2_MSG_CHANNEL_REQUEST,
                                    $this->oSSH->aServerChannels[ NET_SFTP_CHANNEL ],
                                    strlen( 'exec' ),
                                    'exec',
                                    1,
                                    strlen( $sCommand ),
                                    $sCommand
                                );
                    if ( !$this->oSSH->SendBinaryPacket( $sPacket ) )
                    {
                        return false;
                    }

                    // set channel status
                    $this->oSSH->aChannelStatus[ NET_SFTP_CHANNEL ] = NET_SSH2_MSG_CHANNEL_REQUEST;
                    $sResponse = $this->oSSH->GetChannelPacket( NET_SFTP_CHANNEL );
                    if ( $sResponse === false )
                    {
                        return false;
                    }
                }

                $this->oSSH->aChannelStatus[ NET_SFTP_CHANNEL ] = NET_SSH2_MSG_CHANNEL_DATA;

                // send SFTP init packet with version identifier
                if ( !$this->_send_sftp_packet( NET_SFTP_INIT, "\0\0\0\3" ) )
                {
                    return false;
                }

                // get version response from server.
                $sResponse = $this->_get_sftp_packet();
                if ( $this->iPacketType != NET_SFTP_VERSION )
                {
                    user_error( 'Expected SSH_FXP_VERSION' );
                    return false;
                }

                // set SFTP version supported by server.
                $aTemp          = unpack( 'Nversion', $this->oSSH->StringShift( $sResponse, 4 ) );
                $this->iVersion = $aTemp[ 'version' ];

                while ( !empty( $sResponse ) )
                {
                    // get extensions from version response.
                    $aTemp  = unpack( 'Nlength', $this->oSSH->StringShift( $sResponse, 4 ) );
                    $sKey   = $this->oSSH->StringShift( $sResponse, $aTemp[ 'length' ] );
                    $aTemp  = unpack( 'Nlength', $this->oSSH->StringShift( $sResponse, 4 ) );
                    $sValue = $this->oSSH->StringShift( $sResponse, $aTemp[ 'length' ] );

                    $this->aExtensions[ $sKey ] = $sValue;
                }

                /**
                 * SFTPv4+ defines a 'newline' extension.  SFTPv3 seems to have unofficial support
                 * for it via 'newline@vandyke.com', however, I'm not sure what 'newline@vandyke.com'
                 * is supposed to do (the fact that it's unofficial means that it's not in the official
                 * SFTPv3 specs ) and 'newline@vandyke.com' / 'newline' are likely not drop-in substitutes
                 * for one another due to the fact that 'newline' comes with a SSH_FXF_TEXT bitmask whereas
                 * it seems unlikely that 'newline@vandyke.com' would.
                */
                // if ( isset( $this->aExtensions[ 'newline@vandyke.com' ] ) )
                // {
                //     $this->aExtensions[ 'newline' ] = $this->aExtensions[ 'newline@vandyke.com' ];
                //     unset( $this->aExtensions[ 'newline@vandyke.com' ] );
                // }

                $this->iRequestId = 1;

                /*
                 A Note on SFTPv4/5/6 support:
                 <http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-5.1> states the following:

                 "If the client wishes to interoperate with servers that support noncontiguous version
                  numbers it SHOULD send '3'"

                 Given that the server only sends its version number after the client has already done so, the above
                 seems to be suggesting that v3 should be the default version.  This makes sense given that v3 is the
                 most popular.

                 <http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-5.5> states the following;

                 "If the server did not send the "versions" extension, or the version-from-list was not included, the
                  server MAY send a status response describing the failure, but MUST then close the channel without
                  processing any further requests."

                 So what do you do if you have a client whose initial SSH_FXP_INIT packet says it implements v3 and
                 a server whose initial SSH_FXP_VERSION reply says it implements v4 and only v4?  If it only implements
                 v4, the "versions" extension is likely not going to have been sent so version re-negotiation as discussed
                 in draft-ietf-secsh-filexfer-13 would be quite impossible.  As such, what SFTP would do is close the
                 channel and reopen it with a new and updated SSH_FXP_INIT packet.
                */
                switch ( $this->iVersion )
                {
                    case 2 :
                        // we support this version.
                    case 3 :
                        // we definitely support this version.
                        break;

                    default :
                        // any other version is unlikely at this time.
                        return false;
                }

                // get pwd and cache it.
                $this->sPwd = $this->_realpath( '.' );
                $this->_save_dir( $this->sPwd );

                // we are initialized.
                $this->bInitialized = true;
            }
            return true;
        }

        /**
         * Returns the current directory name
         *
         * @return  boolean | string
         */
        public function pwd()
        {
            return $this->sPwd;
        }

        /**
         * Logs errors
         *
         * @param   String  $sResponse
         * @param   Integer $iStatus
         *
         * @access  private
         */
        public function LogError( $sResponse, $iStatus = -1 )
        {
            // initialize
            $sOrigResponse = $sResponse;
            if ( $iStatus == -1 )
            {
                // get status.
                $aTemp   = unpack( 'Nstatus', $this->oSSH->StringShift( $sResponse, 4 ) );
                $iStatus = $aTemp[ 'status' ];
            }

            if ( !empty( $this->aStatusCodes[ " " . $iStatus ] ) )
            {
                // get error from status code.
                $sError = $this->aStatusCodes[ " " . $iStatus ];
                if ( $this->iVersion > 2 )
                {
                    // log sftp error message
                    $aTemp               = unpack( 'Nlength', $this->oSSH->StringShift( $sResponse, 4 ) );
                    $this->aSftpErrors[] = $sError . ': ' . $this->oSSH->StringShift( $sResponse, $aTemp[ 'length' ] );
                }
                else
                {
                    // log status code.
                    $this->aSftpErrors[] = $sError;
                }
            }
            else
            {
                // log custom error message
                $sError              = "Custom error";
                $this->aSftpErrors[] = $sError . ': ' . $sOrigResponse;
            }
        }

        /**
         * Canonicalize the Server-Side Path Name
         *
         * SFTP doesn't provide a mechanism by which the current working
         * directory can be changed, so we'll emulate it. Returns the
         * absolute ( canonicalized ) path.
         *
         * @see     SFTP::chdir()
         *
         * @param   String  $sPath
         *
         * @return  boolean | string
         *
         * @access  private
         */
        public function _realpath( $sPath )
        {
            if ( empty( $sPath ) || !strlen( trim( $sPath ) ) )
            {
                // invalid path specified.
                return false;
            }
            if ( $this->sPwd === false )
            {
                // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.9
                if ( !$this->_send_sftp_packet( NET_SFTP_REALPATH, pack( 'Na*', strlen( $sPath ), $sPath ) ) )
                {
                    return false;
                }

                $sResponse = $this->_get_sftp_packet();
                switch ( $this->iPacketType )
                {
                    case NET_SFTP_NAME :
                        // although SSH_FXP_NAME is implemented differently in SFTPv3 than it is in SFTPv4+, the following
                        // should work on all SFTP versions since the only part of the SSH_FXP_NAME packet the following looks
                        // at is the first part and that part is defined the same in SFTP versions 3 through 6.
                        $this->oSSH->StringShift( $sResponse, 4 ); // skip over the count - it should be 1, anyway
                        $aTemp = unpack( 'Nlength', $this->oSSH->StringShift( $sResponse, 4 ) );
                        return $this->oSSH->StringShift( $sResponse, $aTemp[ 'length' ] );

                    case NET_SFTP_STATUS :
                        $this->LogError( $sResponse );
                        return false;

                    default :
                        user_error( 'Expected SSH_FXP_NAME or SSH_FXP_STATUS' );
                        return false;
                }
            }

            if ( $sPath[ 0 ] != '/' )
            {
                $sPath = $this->sPwd . '/' . $sPath;
            }

            // rebuild absolute path replacing .. and .
            $aPath = explode( '/', $sPath );
            $aNew  = array();
            foreach ( $aPath as $sDir )
            {
                if ( !strlen( $sDir ) )
                {
                    // skip empty
                    continue;
                }
                switch ( $sDir )
                {
                    case '..' :
                        array_pop( $aNew );

                    case '.' :
                        break;

                    default :
                        $aNew[] = $sDir;
                        break;
                }
            }

            return '/' . implode( '/', $aNew );
        }

        /**
         * Changes the current directory
         *
         * @param   String  $sDir
         *
         * @return  Boolean
         */
        public function chdir( $sDir )
        {
            // check for valid login
            if ( !( $this->oSSH->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                // failure.
                return false;
            }

            // assume current dir if $sDir is empty
            if ( $sDir === '' )
            {
                $sDir = './';
            }
            else if ( $sDir[ strlen( $sDir ) - 1 ] != '/' )
            {
                $sDir.= '/';
            }

            // get the absolute path of directory.
            $sDir = $this->_realpath( $sDir );

            // confirm that $sDir is, in fact, a valid directory
            if ( $this->_is_dir( $sDir ) )
            {
                // operation was successful.
                $this->sPwd = $sDir;
                return true;
            }

            // we could do a stat on the alleged $sDir to see if it's a directory but that doesn't tell us
            // the currently logged in user has the appropriate permissions or not. maybe you could see if
            // the file's uid / gid match the currently logged in user's uid / gid but how there's no easy
            // way to get those with SFTP

            // attempt to open the directory, $sDir.
            if ( !$this->_send_sftp_packet( NET_SFTP_OPENDIR, pack( 'Na*', strlen( $sDir ), $sDir ) ) )
            {
                // packet sending failed.
                return false;
            }

            // see SFTP::nlist() for a more thorough explanation of the following
            $sResponse = $this->_get_sftp_packet();
            switch ( $this->iPacketType )
            {
                case NET_SFTP_HANDLE :
                    // success! we have a handle.
                    $sHandle = substr( $sResponse, 4 );
                    break;

                case NET_SFTP_STATUS :
                    // we got an error message.
                    $this->LogError( $sResponse );
                    return false;

                default :
                    // packet retrieval failed.
                    user_error( 'Expected SSH_FXP_HANDLE or SSH_FXP_STATUS' );
                    return false;
            }

            // close handle.
            if ( !$this->_close_handle( $sHandle ) )
            {
                // failed to close handle.
                return false;
            }

            // get status.
            $sResponse = $this->_get_sftp_packet();
            if ( $this->iPacketType != NET_SFTP_STATUS )
            {
                // we didn't get a status.
                user_error( 'Expected SSH_FXP_STATUS' );
                return false;
            }
            // read status code from response.
            $aTemp   = unpack( 'Nstatus', $this->oSSH->StringShift( $sResponse, 4 ) );
            $iStatus = $aTemp[ 'status' ];
            if ( $iStatus != NET_SFTP_STATUS_OK )
            {
                // bad status
                $this->LogError( $sResponse, $iStatus );
                return false;
            }

            // update pwd and dir cache.
            $this->_save_dir( $sDir );
            $this->sPwd = $sDir;

            // operation was successful.
            return true;
        }

        /**
         * Returns a list of files in the given directory
         *
         * @param   String  $sDir
         *
         * @return  boolean | array
         */
        public function nlist( $sDir = '.' )
        {
            return $this->_list( $sDir, false );
        }

        /**
         * Returns a detailed list of files in the given directory
         *
         * @param   String  $sDir
         *
         * @return  boolean | array
         */
        public function rawlist( $sDir = '.' )
        {
            return $this->_list( $sDir, true );
        }

        /**
         * Reads a list, be it detailed or not, of files in the given directory
         *
         * $bRealpath exists because, in the case of the recursive deletes
         * and recursive chmod's $bRealpath has already been calculated.
         *
         * @param   String  $sDir
         * @param   Boolean $bRaw
         * @param   Boolean $bRealpath
         *
         * @return  boolean | array
         *
         * @access  private
         */
        public function _list( $sDir = '.', $bRaw = true, $bRealpath = true )
        {
            if ( !( $this->oSSH->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                return false;
            }
            if ( empty( $sDir ) )
            {
                $sDir = '.';
            }

            $sDir = $this->_realpath( $sDir . '/' );
            if ( $sDir === false )
            {
                return false;
            }

            // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.1.2
            if ( !$this->_send_sftp_packet( NET_SFTP_OPENDIR, pack( 'Na*', strlen( $sDir ), $sDir ) ) )
            {
                return false;
            }

            $sResponse = $this->_get_sftp_packet();
            switch ( $this->iPacketType )
            {
                case NET_SFTP_HANDLE :
                    // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-9.2
                    // since 'handle' is the last field in the SSH_FXP_HANDLE packet, we'll just remove the first four bytes that
                    // represent the length of the string and leave it at that
                    $sHandle = substr( $sResponse, 4 );
                    break;

                case NET_SFTP_STATUS :
                    // presumably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED
                    $this->LogError( $sResponse );
                    return false;

                default :
                    user_error( 'Expected SSH_FXP_HANDLE or SSH_FXP_STATUS' );
                    return false;
            }

            $this->_save_dir( $sDir );

            $aContents = array();
            while ( true )
            {
                // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.2.2
                // why multiple SSH_FXP_READDIR packets would be sent when the response to a single one can span arbitrarily many
                // SSH_MSG_CHANNEL_DATA messages is not known to me.
                if ( !$this->_send_sftp_packet( NET_SFTP_READDIR, pack( 'Na*', strlen( $sHandle ), $sHandle ) ) )
                {
                    return false;
                }

                $sResponse = $this->_get_sftp_packet();
                switch ( $this->iPacketType )
                {
                    case NET_SFTP_NAME :
                        $aTemp  = unpack( 'Ncount', $this->oSSH->StringShift( $sResponse, 4 ) );
                        $iCount = $aTemp[ 'count' ];
                        for ( $i = 0; $i < $iCount; $i++ )
                        {
                            // get short name and long name
                            $aTemp       = unpack( 'Nlength', $this->oSSH->StringShift( $sResponse, 4 ) );
                            $sShortname  = $this->oSSH->StringShift( $sResponse, $aTemp[ 'length' ] );
                            $aTemp       = unpack( 'Nlength', $this->oSSH->StringShift( $sResponse, 4 ) );
                            $sLongname   = $this->oSSH->StringShift( $sResponse, $aTemp[ 'length' ] );

                            // parse attributes.
                            $aAttributes = $this->_parseAttributes( $sResponse );
                            if ( !isset( $aAttributes[ 'type' ] ) )
                            {
                                $sFileType = $this->_parseLongname( $sLongname );
                                if ( $sFileType )
                                {
                                    $aAttributes[ 'type' ] = $sFileType;
                                }
                            }
                            if ( $bRaw )
                            {
                                // include attributes.
                                $aContents[ $sShortname ] = $aAttributes;
                            }
                            else
                            {
                                // basic output for printing.
                                $aContents[] = $sShortname;
                            }

                            if ( isset( $aAttributes[ 'type' ] )
                                && $aAttributes[ 'type' ] == NET_SFTP_TYPE_DIRECTORY
                                && ( $sShortname != '.' && $sShortname != '..' ) )
                            {
                                $this->_save_dir( $sDir . '/' . $sShortname );
                            }
                            // SFTPv6 has an optional boolean end-of-list field, but we'll ignore that, since the
                            // final SSH_FXP_STATUS packet should tell us that, already.
                        }
                        break;

                    case NET_SFTP_STATUS :
                        $aTemp = unpack( 'Nstatus', $this->oSSH->StringShift( $sResponse, 4 ) );
                        if ( $aTemp[ 'status' ] != NET_SFTP_STATUS_EOF )
                        {
                            // we didn't get an EOF response.
                            $this->LogError( $sResponse, $aTemp[ 'status' ] );
                            return false;
                        }
                        // break out of switch and while.
                        break 2;

                    default :
                        user_error( 'Expected SSH_FXP_NAME or SSH_FXP_STATUS' );
                        return false;
                }
            }

            if ( !$this->_close_handle( $sHandle ) )
            {
                return false;
            }

            return $aContents;
        }

        /**
         * Returns the file size, in bytes, or false, on failure
         *
         * Files larger than 4GB will show up as being exactly 4GB.
         *
         * @param   String  $sFilename
         *
         * @return  boolean | integer
         */
        public function size( $sFilename )
        {
            // initialize return variable.
            $vReturn = 0;

            // check we are logged in.
            if ( !( $this->oSSH->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                $vReturn = false;
            }
            else
            {
                // sanity check file name.
                if ( !empty( $sFilename ) )
                {
                    // get real path.
                    $sFilename = $this->_realpath( $sFilename );
                    if ( $sFilename === false )
                    {
                        $vReturn = false;
                    }
                    // get actual size of file.
                    $vReturn = $this->_size( $sFilename );
                }
            }
            return $vReturn;
        }

        /**
         * Save directories to cache
         *
         * @param   String  $sDir
         *
         * @todo    remove pass-by-reference.
         * @note    The last array will end up being a reference to
         *          $aTempDirs. Most likely causing memory leak...
         */
        private function _save_dir( $sDir )
        {
            // clean up the directory path.
            // preg_replace( '#^/|/( ?=/ )|/$#', '', $sDir ) == str_replace( '//', '/', trim( $sDir, '/' ) )
            $aDirs = explode( '/', preg_replace( '#^/|/( ?=/ )|/$#', '', $sDir ) );

            // make a copy so we ensure validity during processing.
            $aTempDirs = &$this->aDirs;
            foreach ( $aDirs as $sDir )
            {
                if ( !isset( $aTempDirs[ $sDir ] ) )
                {
                    // add new directory
                    $aTempDirs[ $sDir ] = array();
                }
                // begin nesting folders.
                $aTempDirs = &$aTempDirs[ $sDir ];
            }
        }

        /**
         * Remove directories from cache
         *
         * @param   String  $sDir
         */
        private function _remove_dir( $sDir )
        {
            // clean up the directory path.
            $aDirs     = explode( '/', preg_replace( '#^/|/( ?=/ )|/$#', '', $sDir ) );

            // make a copy so we ensure validity during processing.
            $aTempDirs = &$this->aDirs;
            foreach ( $aDirs as $sDir )
            {
                if ( $sDir == end( $aDirs ) )
                {
                    // remove last element, return true.
                    unset( $aTempDirs[ $sDir ] );
                    return true;
                }
                if ( !isset( $aTempDirs[ $sDir ] ) )
                {
                    // does not exist.
                    return false;
                }
                // follow the nest.
                $aTempDirs = &$aTempDirs[ $sDir ];
            }
        }

        /**
         * Checks cache for directory
         *
         * Mainly used by chdir, which is, in turn, also used for
         * determining whether or not an individual file is a directory
         * or not by stat() and lstat().
         *
         * @param   String  $sDir
         */
        private function _is_dir( $sDir )
        {
            // clean up the directory path.
            $aDirs     = explode( '/', preg_replace( '#^/|/( ?=/ )|/$#', '', $sDir ) );

            // make a copy so we ensure validity during processing.
            $aTempDirs = &$this->aDirs;
            foreach ( $aDirs as $sTempDir )
            {
                if ( !isset( $aTempDirs[ $sTempDir ] ) )
                {
                    // directory does not exist.
                    return false;
                }
                // explore the nest.
                $aTempDirs = &$aTempDirs[ $sTempDir ];
            }
            // it's a directory.
            return true;
        }

        /**
         * Returns general information about a file.
         *
         * Returns an array on success and false otherwise.
         *
         * @param   String  $sFilename
         *
         * @return  boolean | array
         */
        public function stat( $sFilename )
        {
            // are we logged in?
            if ( !( $this->oSSH->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                return false;
            }

            if ( !strlen( trim( $sFilename ) ) )
            {
                // invalid filename supplied.
                return false;
            }

            $sFilename = $this->_realpath( $sFilename );
            if ( $sFilename === false )
            {
                // path does not exist.
                return false;
            }

            $vStat = $this->_stat( $sFilename, NET_SFTP_STAT );
            if ( $vStat === false )
            {
                // stat command failed.
                return false;
            }
            if ( isset( $vStat[ 'type' ] ) )
            {
                return $vStat;
            }

            // manually check to see if $sFilename is a directory or file.
            $sPwd            = $this->sPwd;
            $vStat[ 'type' ] = $this->chdir( $sFilename ) ? NET_SFTP_TYPE_DIRECTORY : NET_SFTP_TYPE_REGULAR;
            $this->sPwd      = $sPwd;

            return $vStat;
        }

        /**
         * Returns general information about a file or symbolic link.
         *
         * Returns an array on success and false otherwise.
         *
         * @param   String  $sFilename
         *
         * @return  boolean | array
         */
        public function lstat( $sFilename )
        {
            // are we logged in?
            if ( !( $this->oSSH->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                return false;
            }

            if ( !strlen( trim( $sFilename ) ) )
            {
                // invalid filename supplied.
                return false;
            }

            $sFilename = $this->_realpath( $sFilename );
            if ( $sFilename === false )
            {
                // path does not exist.
                return false;
            }

            $vLstat = $this->_stat( $sFilename, NET_SFTP_LSTAT );
            if ( $vLstat === false )
            {
                // lstat command failed.
                return false;
            }
            if ( isset( $vLstat[ 'type' ] ) )
            {
                return $vLstat;
            }

            $vStat = $this->_stat( $sFilename, NET_SFTP_STAT );
            if ( $vLstat != $vStat )
            {
                // symlink.
                return array_merge( $vLstat, array( 'type' => NET_SFTP_TYPE_SYMLINK ) );
            }

            // remember our pwd before we attempt to chdir
            $sPwd             = $this->sPwd;
            $vLstat[ 'type' ] = $this->chdir( $sFilename ) ? NET_SFTP_TYPE_DIRECTORY : NET_SFTP_TYPE_REGULAR;
            $this->sPwd       = $sPwd;

            return $vLstat;
        }

        /**
         * Returns general information about a file or symbolic link
         *
         * Determines information without calling SFTP::_realpath().
         * The second parameter can be either NET_SFTP_STAT or NET_SFTP_LSTAT.
         *
         * @param   String  $sFilename
         * @param   Integer $iType
         *
         * @return  boolean | array
         *
         * @access  private
         */
        public function _stat( $sFilename, $iType )
        {
            // sanity checks.
            if ( !strlen( trim( $sFilename ) ) )
            {
                // invalid filename supplied.
                return false;
            }
            if ( !isset( $iType ) || $iType <= 0 )
            {
                // invalid packet type given.
                return false;
            }
            // are we logged in?
            if ( !( $this->oSSH->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                return false;
            }

            // SFTPv4+ adds an additional 32-bit integer field - flags - to the following:
            $sPacket = pack( 'Na*', strlen( $sFilename ), $sFilename );
            if ( !$this->_send_sftp_packet( $iType, $sPacket ) )
            {
                return false;
            }

            $sResponse = $this->_get_sftp_packet();
            if ( $sResponse !== false )
            {
                switch ( $this->iPacketType )
                {
                    case NET_SFTP_ATTRS :
                        return $this->_parseAttributes( $sResponse );

                    case NET_SFTP_STATUS :
                        $this->LogError( $sResponse );
                        return false;

                    default :
                        // nothing.
                        break;
                }
            }

            $this->LogError( 'Expected SSH_FXP_ATTRS or SSH_FXP_STATUS' );
            return false;
        }

        /**
         * Returns the file size, in bytes, or false, on failure. If
         * $sPath is a directory, the size will return 4096 (2KB), the
         * default file size.
         *
         * If we need the total size of a directory and it's contents,
         * recursively, we would have to calculate that ourselves.
         * @todo    Calculate size of directory contents. +/- Recursive
         *
         * Determines the size without calling SFTP::_realpath()
         *
         * @param   String  $sPath
         *
         * @return  boolean | integer
         *
         * @access  private
         */
        public function _size( $sPath )
        {
            // uses the stat command to retrieve array of info about given path.
            $vResult = $this->_stat( $sPath, NET_SFTP_STAT );
            if ( $vResult === false )
            {
                return false;
            }
            return isset( $vResult[ 'size' ] ) ? $vResult[ 'size' ] : -1;
        }

        /**
         * Truncates a file to a given length
         *
         * @param   String  $sFilename  Path to file to truncate.
         * @param   Integer $iNewSize   New file size in bytes.
         *
         * @return  Boolean
         */
        public function truncate( $sFilename, $iNewSize )
        {
            // create attribute field for setstat packet.
            $sAttr = pack( 'N3', NET_SFTP_ATTR_SIZE, $iNewSize / 0x100000000, $iNewSize );

            // send setstat.
            return $this->_setstat( $sFilename, $sAttr, false );
        }

        /**
         * Sets access and modification time of file.
         *
         * If the file does not exist, it will be created.
         *
         * @param   String  $sFilename  File to touch.
         * @param   Integer $iMtime     Modification time
         * @param   Integer $iAtime     Access time.
         *
         * @return  Boolean
         */
        public function touch( $sFilename, $iMtime = NULL, $iAtime = NULL )
        {
            // are we logged in?
            if ( !( $this->oSSH->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                return false;
            }

            $sFilename = $this->_realpath( $sFilename );
            if ( $sFilename === false )
            {
                // retrieving path name failed.
                return false;
            }

            // set modification/access time.
            if ( !isset( $iMtime ) )
            {
                $iMtime = time();
            }
            if ( !isset( $iAtime ) )
            {
                $iAtime = $iMtime;
            }

            // open file for writing or create if not exists.
            $iFlags  = NET_SFTP_OPEN_WRITE | NET_SFTP_OPEN_CREATE | NET_SFTP_OPEN_EXCL;
            $sAttr   = pack( 'N3', NET_SFTP_ATTR_ACCESSTIME, $iMtime, $iAtime );
            $sPacket = pack( 'Na*Na*', strlen( $sFilename ), $sFilename, $iFlags, $sAttr );
            if ( !$this->_send_sftp_packet( NET_SFTP_OPEN, $sPacket ) )
            {
                // packet failure. see logs.
                return false;
            }

            // check response packet.
            $sResponse = $this->_get_sftp_packet();
            switch ( $this->iPacketType )
            {
                case NET_SFTP_HANDLE :
                    // close the file handle.
                    return $this->_close_handle( substr( $sResponse, 4 ) );

                case NET_SFTP_STATUS :
                    // unknown error status message
                    $this->LogError( $sResponse );
                    break;

                default :
                    // something else failed..
                    user_error( 'Expected SSH_FXP_HANDLE or SSH_FXP_STATUS' );
                    return false;
            }

            // send the setstat for modification/access times.
            return $this->_setstat( $sFilename, $sAttr, false );
        }

        /**
         * Changes file or directory owner
         *
         * Returns TRUE on success or FALSE on error.
         *
         * @param   String  $sFilename  Path to chown.
         * @param   Integer $iUid       User-Id of user.
         * @param   Boolean $bRecursive
         *
         * @return  Boolean
         */
        public function chown( $sFilename, $iUid, $bRecursive = false )
        {
            // quoting from <http://www.kernel.org/doc/man-pages/online/pages/man2/chown.2.html>,
            // "if the owner or group is specified as -1, then that ID is not changed"
            $sAttr = pack( 'N3', NET_SFTP_ATTR_UIDGID, $iUid, -1 );

            return $this->_setstat( $sFilename, $sAttr, $bRecursive );
        }

        /**
         * Changes file or directory group
         *
         * Returns TRUE on success or FALSE on error.
         *
         * @param   String  $sFilename  Path to chgrp.
         * @param   Integer $iGid       Group-Id of group
         * @param   Boolean $bRecursive
         *
         * @return  Boolean
         */
        public function chgrp( $sFilename, $iGid, $bRecursive = false )
        {
            // quoting from <http://www.kernel.org/doc/man-pages/online/pages/man2/chown.2.html>,
            // "if the owner or group is specified as -1, then that ID is not changed"
            $sAttr = pack( 'N3', NET_SFTP_ATTR_UIDGID, -1, $iGid );

            return $this->_setstat( $sFilename, $sAttr, $bRecursive );
        }

        /**
         * Set permissions on a file.
         *
         * Returns the new file permissions on success or FALSE on error.
         * If $bRecursive is true than this just returns TRUE or FALSE.
         *
         * @param   Integer $iMode      File mode, 0777 | 0750 | 0755 | 0664 | etc.
         *                              leading zero is required.
         * @param   String  $sFilename  File name to chmod
         * @param   Boolean $bRecursive Recurse sub-directory's file/folders.
         *
         * @return  boolean | string
         */
        public function chmod( $iMode, $sFilename, $bRecursive = false )
        {
            /*
            // Backwards compatibility of chmod syntax is pointless..
            // syntactical flexibility could be achieved with overloading,
            // but PHP5 doesn't support method overloading.
            if ( is_string( $iMode ) && is_int( $sFilename ) )
            {
                $sTemp = $iMode;
                $iMode = $sFilename;
                $sFilename = $sTemp;
            }*/
            // handle the odd case where chmod( '777', $sFilename ) is called.
            // 777 is reevaluated differently than 0777 because one is an integer
            // the other is an octal notation.
            if ( intval( $iMode, 8 ) !== $iMode )
            {
                // create octal.
                $iMode = intval( "0" . $iMode, 8 );
            }

            $sAttr = pack( 'N2', NET_SFTP_ATTR_PERMISSIONS, $iMode & 07777 );
            if ( !$this->_setstat( $sFilename, $sAttr, $bRecursive ) )
            {
                return false;
            }
            if ( $bRecursive )
            {
                // skip returning the permission mode string.
                return true;
            }

            // rather than return what the permissions *should* be, we'll return what they actually are.  this will also
            // tell us if the file actually exists.
            // incidentally, SFTPv4+ adds an additional 32-bit integer field - flags - to the following:
            $sPacket = pack( 'Na*', strlen( $sFilename ), $sFilename );
            if ( !$this->_send_sftp_packet( NET_SFTP_STAT, $sPacket ) )
            {
                return false;
            }

            $sResponse = $this->_get_sftp_packet();
            switch ( $this->iPacketType )
            {
                case NET_SFTP_ATTRS :
                    $aAttrs = $this->_parseAttributes( $sResponse );
                    return $aAttrs[ 'permissions' ];

                case NET_SFTP_STATUS :
                    $this->LogError( $sResponse );
                    return false;

                default :
                    // nothing.
                    break;
            }

            user_error( 'Expected SSH_FXP_ATTRS or SSH_FXP_STATUS' );
            return false;
        }

        /**
         * Sets information about a file
         *
         * @param   String  $sFilename
         * @param   String  $sAttr
         * @param   Boolean $bRecursive
         *
         * @return  Boolean
         *
         * @access  private
         */
        private function _setstat( $sFilename, $sAttr, $bRecursive )
        {
            // are we logged in?
            if ( !( $this->oSSH->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                return false;
            }

            $sFilename = $this->_realpath( $sFilename );
            if ( $sFilename === false )
            {
                // path does not exist.
                return false;
            }

            if ( $bRecursive )
            {
                // recurse all sub folders/files.
                $iRespCount = 0;
                $bResult    = $this->_setstat_recursive( $sFilename, $sAttr, $iRespCount );
                $this->_read_put_responses( $iRespCount );
                return $bResult;
            }

            // SFTPv4+ has an additional byte field - type - that would need to be sent, as well. setting it to
            // SSH_FILEXFER_TYPE_UNKNOWN might work. if not, we'd have to do an SSH_FXP_STAT before doing an SSH_FXP_SETSTAT.
            if ( !$this->_send_sftp_packet( NET_SFTP_SETSTAT, pack( 'Na*a*', strlen( $sFilename ), $sFilename, $sAttr ) ) )
            {
                return false;
            }

            /*
             "Because some systems must use separate system calls to set various attributes, it is possible that a failure
              response will be returned, but yet some of the attributes may be have been successfully modified.  If possible,
              servers SHOULD avoid this situation; however, clients MUST be aware that this is possible."

              -- http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.6
            */
            $sResponse = $this->_get_sftp_packet();
            if ( $this->iPacketType != NET_SFTP_STATUS )
            {
                user_error( 'Expected SSH_FXP_STATUS' );
                return false;
            }

            // check status.
            $aTemp = unpack( 'Nstatus', $this->oSSH->StringShift( $sResponse, 4 ) );
            if ( $aTemp[ 'status' ] != NET_SFTP_STATUS_OK )
            {
                $this->LogError( $sResponse, $aTemp[ 'status' ] );
                return false;
            }

            // setstat a sucess!
            return true;
        }

        /**
         * Recursively sets information on directories on the SFTP server
         *
         * Minimizes directory lookups and SSH_FXP_STATUS requests for speed.
         *
         * Called from _setstat with $bRecursive = true;
         *
         * @param   String  $sPath
         * @param   String  $sAttr
         * @param   Integer $iRespCount
         *
         * @return  Boolean
         *
         * @access  private
         *
         * @todo    skip or return false for invalid file in entries list. ???
         */
        public function _setstat_recursive( $sPath, $sAttr, &$iRespCount )
        {
            // process pending responses.
            if ( !$this->_read_put_responses( $iRespCount ) )
            {
                return false;
            }

            // reset, list subfolders/files in $sPath.
            $iRespCount = 0;
            $aEntries   = $this->_list( $sPath, true, false );
            if ( $aEntries === false )
            {
                // most likely a file specified. setstat on the file.
                return $this->_setstat( $sPath, $sAttr, false );
            }

            // normally $aEntries would have at least . and .. but it might not
            // if the directories permissions didn't allow reading
            if ( empty( $aEntries ) )
            {
                return false;
            }

            // process all entries in the main folder.
            foreach ( $aEntries as $sFilename => $aProperties )
            {
                if ( $sFilename == '.' || $sFilename == '..' )
                {
                    // skip self and parent.
                    continue;
                }

                if ( !isset( $aProperties[ 'type' ] ) )
                {
                    // unknown type. skip? instead of returning false.
                    return false;
                }

                // get full path to file or folder..
                $sTempPath = $sPath . '/' . $sFilename;

                if ( $aProperties[ 'type' ] == NET_SFTP_TYPE_DIRECTORY )
                {
                    // recurse sub-directory
                    if ( !$this->_setstat_recursive( $sTempPath, $sAttr, $iRespCount ) )
                    {
                        return false;
                    }
                }
                else
                {
                    // setstat on files.
                    if ( !$this->_send_sftp_packet( NET_SFTP_SETSTAT, pack( 'Na*a*', strlen( $sTempPath ), $sTempPath, $sAttr ) ) )
                    {
                        return false;
                    }

                    // increment the number of expected responses.
                    $iRespCount++;

                    // is our queue overloaded?
                    if ( $iRespCount >= NET_SFTP_QUEUE_SIZE )
                    {
                        // process responses.
                        if ( !$this->_read_put_responses( $iRespCount ) )
                        {
                            return false;
                        }
                        $iRespCount = 0;
                    }
                }
            }

            // setstat for top-level directory.
            if ( !$this->_send_sftp_packet( NET_SFTP_SETSTAT, pack( 'Na*a*', strlen( $sPath ), $sPath, $sAttr ) ) )
            {
                return false;
            }

            // increment number of expected responses.
            $iRespCount++;

            // is our queue overloaded?
            if ( $iRespCount >= NET_SFTP_QUEUE_SIZE )
            {
                // process responses.
                if ( !$this->_read_put_responses( $iRespCount ) )
                {
                    return false;
                }
                $iRespCount = 0;
            }

            // sucess!
            return true;
        }

        /**
         * Creates a directory.
         *
         * @param   string  $sDir       Name of new directory.
         * @param   integer $iMode      Permission mode to be set.
         * @param   boolean $bRecursive Create all folders in the nested path.
         *
         * @return  boolean
         */
        public function mkdir( $sDir, $iMode = -1, $bRecursive = false )
        {
            if ( !( $this->oSSH->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                return false;
            }

            // handle the odd case where mkdir( $sDir, '777' ) is called.
            // 777 is reevaluated differently than 0777 because one is an integer
            // the other is an octal notation.
            if ( intval( $iMode, 8 ) !== $iMode )
            {
                // create octal.
                $iMode = intval( "0" . $iMode, 8 );
            }

            $sDir = $this->_realpath( $sDir );
            // by not providing any permissions, hopefully the server will use the
            // logged in users umask - their default permissions.
            $sAttr = $iMode == -1 ? "\0\0\0\0" : pack( 'N2', NET_SFTP_ATTR_PERMISSIONS, $iMode & 07777 );

            if ( $bRecursive )
            {
                // split path by directory separator.
                $aDirs = explode( '/', preg_replace( '#/( ?=/ )|/$#', '', $sDir ) );
                if ( empty( $aDirs[ 0 ] ) )
                {
                    // path was relative. or double //
                    array_shift(  $aDirs );
                    $aDirs[ 0 ] = '/' . $aDirs[ 0 ];
                }

                // create all directories leading up to $sDir.
                for ( $iNewDir = 0; $iNewDir < count( $aDirs ); $iNewDir++ )
                {
                    $aTemp   = array_slice( $aDirs, 0, $iNewDir + 1 );
                    $sTemp   = implode( '/', $aTemp );
                    $bResult = $this->_mkdir_helper( $sTemp, $sAttr );
                }
                return $bResult;
            }

            return $this->_mkdir_helper( $sDir, $sAttr );
        }

        /**
         * Helper function for directory creation
         *
         * @param   String  $sDir
         *
         * @return  Boolean
         *
         * @access  private
         */
        public function _mkdir_helper( $sDir, $sAttr )
        {
            if ( !$this->_send_sftp_packet( NET_SFTP_MKDIR, pack( 'Na*a*', strlen( $sDir ), $sDir, $sAttr ) ) )
            {
                return false;
            }

            $sResponse = $this->_get_sftp_packet();
            if ( $this->iPacketType != NET_SFTP_STATUS )
            {
                user_error( 'Expected SSH_FXP_STATUS' );
                return false;
            }

            $aTemp = unpack( 'Nstatus', $this->oSSH->StringShift( $sResponse, 4 ) );
            if ( $aTemp[ 'status' ] != NET_SFTP_STATUS_OK )
            {
                $this->LogError( $sResponse, $aTemp[ 'status' ] );
                return false;
            }

            $this->_save_dir( $sDir );

            return true;
        }

        /**
         * Removes a directory.
         *
         * @param   String  $sDir
         * @param   boolean $bRecursive Reserved for future implementation.
         *
         * @return  Boolean
         *
         * @todo    Add support for recursive removal of a directory's contents
         *          SFTP protocol does not support this if a directory is not empty...
         */
        public function rmdir( $sDir, $bRecursive = false )
        {
            if ( !( $this->oSSH->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                return false;
            }

            $sDir = $this->_realpath( $sDir );
            if ( $sDir === false )
            {
                return false;
            }

            if ( !$this->_send_sftp_packet( NET_SFTP_RMDIR, pack( 'Na*', strlen( $sDir ), $sDir ) ) )
            {
                return false;
            }

            $sResponse = $this->_get_sftp_packet();
            if ( $this->iPacketType != NET_SFTP_STATUS )
            {
                user_error( 'Expected SSH_FXP_STATUS' );
                return false;
            }

            $aTemp = unpack( 'Nstatus', $this->oSSH->StringShift( $sResponse, 4 ) );
            if ( $aTemp[ 'status' ] != NET_SFTP_STATUS_OK )
            {
                // presumably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED?
                $this->LogError( $sResponse, $aTemp[ 'status' ] );
                return false;
            }

            $this->_remove_dir( $sDir );

            return true;
        }

        /**
         * Uploads a file to the SFTP server.
         *
         * By default, SFTP::put() does not read from the local filesystem.  $sData is dumped directly into $sRemoteFile.
         * So, for example, if you set $sData to 'filename.ext' and then do SFTP::get(), you will get a file, twelve bytes
         * long, containing 'filename.ext' as its contents.
         *
         * Setting $iMode to NET_SFTP_LOCAL_FILE will change the above behavior.  With NET_SFTP_LOCAL_FILE, $sRemoteFile will
         * contain as many bytes as filename.ext does on your local filesystem.  If your filename.ext is 1MB then that is how
         * large $sRemoteFile will be, as well.
         *
         * Currently, only binary mode is supported.  As such, if the line endings need to be adjusted, you will need to take
         * care of that, yourself.
         *
         * $iMode can take an additional two parameters - NET_SFTP_RESUME and NET_SFTP_RESUME_START. These are bitwise AND'd with
         * $iMode. So if you want to resume upload of a 300mb file on the local file system you'd set $iMode to the following:
         *
         * NET_SFTP_LOCAL_FILE | NET_SFTP_RESUME
         *
         * If you wanted to simply append the full contents of a local file to the full contents of a remote file you'd replace
         * NET_SFTP_RESUME with NET_SFTP_RESUME_START.
         *
         * If $iMode & ( NET_SFTP_RESUME | NET_SFTP_RESUME_START ) then NET_SFTP_RESUME_START will be assumed.
         *
         * $iStart and $iLocalStart give you more fine grained control over this process and take precedent over NET_SFTP_RESUME
         * when they're non-negative. ie. $iStart could let you write at the end of a file ( like NET_SFTP_RESUME ) or in the middle
         * of one. $iLocalStart could let you start your reading from the end of a file ( like NET_SFTP_RESUME_START ) or in the
         * middle of one.
         *
         * Setting $iLocalStart to > 0 or $iMode | NET_SFTP_RESUME_START doesn't do anything unless $iMode | NET_SFTP_LOCAL_FILE.
         *
         * @param   String  $sRemoteFile
         * @param   String  $sData
         * @param   Integer $iMode
         * @param   Integer $iStart
         * @param   Integer $iLocalStart
         *
         * @return  Boolean
         *
         * @internal ASCII mode for SFTPv4/5/6 can be supported by adding a new function - SFTP::setMode().
         */
        public function put( $sRemoteFile, $sData, $iMode = NET_SFTP_STRING, $iStart = -1, $iLocalStart = -1 )
        {
            if ( !( $this->oSSH->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                return false;
            }

            $sRemoteFile = $this->_realpath( $sRemoteFile );
            if ( $sRemoteFile === false )
            {
                return false;
            }

            $iFlags = NET_SFTP_OPEN_WRITE | NET_SFTP_OPEN_CREATE;
            // according to the SFTP specs, NET_SFTP_OPEN_APPEND should "force all writes to append data at the end of the file."
            // in practice, it doesn't seem to do that.
            //$iFlags|= ( $iMode & NET_SFTP_RESUME ) ? NET_SFTP_OPEN_APPEND : NET_SFTP_OPEN_TRUNCATE;

            if ( $iStart >= 0 )
            {
                $iOffset = $iStart;
            }
            elseif ( $iMode & NET_SFTP_RESUME )
            {
                // if NET_SFTP_OPEN_APPEND worked as it should _size() wouldn't need to be called
                $iSize = $this->_size( $sRemoteFile );
                $iOffset = $iSize !== false ? $iSize : 0;
            }
            else
            {
                $iOffset = 0;
                $iFlags|= NET_SFTP_OPEN_TRUNCATE;
            }

            $sPacket = pack( 'Na*N2', strlen( $sRemoteFile ), $sRemoteFile, $iFlags, 0 );
            if ( !$this->_send_sftp_packet( NET_SFTP_OPEN, $sPacket ) )
            {
                return false;
            }

            $sResponse = $this->_get_sftp_packet();
            switch ( $this->iPacketType )
            {
                case NET_SFTP_HANDLE :
                    $sHandle = substr( $sResponse, 4 );
                    break;

                case NET_SFTP_STATUS :
                    $this->LogError( $sResponse );
                    return false;

                default :
                    user_error( 'Expected SSH_FXP_HANDLE or SSH_FXP_STATUS' );
                    return false;
            }

            // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.2.3
            if ( $iMode & NET_SFTP_LOCAL_FILE )
            {
                if ( !is_file( $sData ) )
                {
                    user_error( "$sData is not a valid file" );
                    return false;
                }
                $rhLocalFile = @fopen( $sData, 'rb' );
                if ( !$rhLocalFile )
                {
                    return false;
                }
                $iSize = filesize( $sData );

                if ( $iLocalStart >= 0 )
                {
                    fseek( $rhLocalFile, $iLocalStart );
                }
                elseif ( $iMode & NET_SFTP_RESUME_START )
                {
                    // do nothing
                }
                else
                {
                    fseek( $rhLocalFile, $iOffset );
                }
            }
            else
            {
                $iSize = strlen( $sData );
            }

            $iSentBytes = 0;
            $iSize = $iSize < 0 ? ( $iSize & 0x7FFFFFFF ) + 0x80000000 : $iSize;

            $iSftpPacketSize  = 4096; // PuTTY uses 4096
            $iSftpPacketSize -= strlen( $sHandle ) + 25;
            $iRespCount = 0;
            while ( $iSentBytes < $iSize )
            {
                $sTemp = $iMode & NET_SFTP_LOCAL_FILE ? fread( $rhLocalFile, $iSftpPacketSize ) : substr($sData, $iSentBytes, $iSftpPacketSize );
                $sSubTemp = $iOffset + $iSentBytes;
                $sPacket = pack( 'Na*N3a*', strlen( $sHandle ), $sHandle, $sSubTemp / 0x100000000, $sSubTemp, strlen( $sTemp ), $sTemp );
                if ( !$this->_send_sftp_packet( NET_SFTP_WRITE, $sPacket ) )
                {
                    fclose( $rhLocalFile );
                    return false;
                }
                $iSentBytes+= strlen( $sTemp );

                $iRespCount++;

                if ( $iRespCount == NET_SFTP_QUEUE_SIZE )
                {
                    if ( !$this->_read_put_responses( $iRespCount ) )
                    {
                        $iRespCount = 0;
                        break;
                    }
                    $iRespCount = 0;
                }
            }

            if ( !$this->_read_put_responses( $iRespCount ) )
            {
                if ( $iMode & NET_SFTP_LOCAL_FILE )
                {
                    fclose( $rhLocalFile );
                }
                $this->_close_handle( $sHandle );
                return false;
            }

            if ( $iMode & NET_SFTP_LOCAL_FILE )
            {
                fclose( $rhLocalFile );
            }

            return $this->_close_handle( $sHandle );
        }

        /**
         * Reads multiple successive SSH_FXP_WRITE responses
         *
         * Sending an SSH_FXP_WRITE packet and immediately reading its
         * response isn't as efficient as blindly sending out $iRespCount
         * SSH_FXP_WRITEs, in succession, and then reading $iRespCount responses.
         *
         * @param   Integer $iRespCount
         *
         * @return  Boolean
         *
         * @access  private
         */
        public function _read_put_responses( $iRespCount )
        {
            while ( $iRespCount-- )
            {
                $sResponse = $this->_get_sftp_packet();
                if ( $this->iPacketType != NET_SFTP_STATUS )
                {
                    user_error( 'Expected SSH_FXP_STATUS' );
                    return false;
                }

                $aTemp = unpack( 'Nstatus', $this->oSSH->StringShift( $sResponse, 4 ) );
                if ( $aTemp[ 'status' ] != NET_SFTP_STATUS_OK )
                {
                    $this->LogError( $sResponse, $aTemp[ 'status' ] );
                    break;
                }
            }

            return $iRespCount < 0;
        }

        /**
         * Close handle
         *
         * @param   String  $sHandle
         *
         * @return  Boolean
         *
         * @access  private
         */
        public function _close_handle( $sHandle )
        {
            if ( !$this->_send_sftp_packet( NET_SFTP_CLOSE, pack( 'Na*', strlen( $sHandle ), $sHandle ) ) )
            {
                return false;
            }

            // "The client MUST release all resources associated with the handle regardless of the status."
            //  -- http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.1.3
            $sResponse = $this->_get_sftp_packet();
            if ( $this->iPacketType != NET_SFTP_STATUS )
            {
                user_error( 'Expected SSH_FXP_STATUS' );
                return false;
            }

            $aTemp = unpack( 'Nstatus', $this->oSSH->StringShift( $sResponse, 4 ) );
            if ( $aTemp[ 'status' ] != NET_SFTP_STATUS_OK )
            {
                $this->LogError( $sResponse, $aTemp[ 'status' ] );
                return false;
            }

            return true;
        }

        /**
         * Downloads a file from the SFTP server.
         *
         * Returns a string containing the contents of $sRemoteFile if $sLocalFile is left undefined or a boolean false if
         * the operation was unsuccessful.  If $sLocalFile is defined, returns true or false depending on the success of the
         * operation.
         *
         * $iOffset and $iLength can be used to download files in chunks.
         *
         * @param   String  $sRemoteFile
         * @param   String  $sLocalFile
         * @param   Integer $iOffset
         * @param   Integer $iLength
         *
         * @return  boolean | string
         */
        public function get( $sRemoteFile, $sLocalFile = false, $iOffset = 0, $iLength = -1 )
        {
            if ( !( $this->oSSH->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                return false;
            }

            $sRemoteFile = $this->_realpath( $sRemoteFile );
            if ( $sRemoteFile === false )
            {
                return false;
            }

            $sPacket = pack( 'Na*N2', strlen( $sRemoteFile ), $sRemoteFile, NET_SFTP_OPEN_READ, 0 );
            if ( !$this->_send_sftp_packet( NET_SFTP_OPEN, $sPacket ) )
            {
                return false;
            }

            $sResponse = $this->_get_sftp_packet();
            switch ( $this->iPacketType )
            {
                case NET_SFTP_HANDLE :
                    $sHandle = substr( $sResponse, 4 );
                    break;

                case NET_SFTP_STATUS : // presumably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED
                    $this->LogError( $sResponse );
                    return false;

                default :
                    user_error( 'Expected SSH_FXP_HANDLE or SSH_FXP_STATUS' );
                    return false;
            }

            if ( $sLocalFile !== false )
            {
                $rhLocalFile = fopen( $sLocalFile, 'wb' );
                if ( !$rhLocalFile )
                {
                    return false;
                }
            }
            else
            {
                $sContent = '';
            }

            $iSize = $this->iMaxSftpPacketSize < $iLength || $iLength < 0 ? $this->iMaxSftpPacketSize : $iLength;
            while ( true )
            {
                $sPacket = pack( 'Na*N3', strlen( $sHandle ), $sHandle, $iOffset / 0x100000000, $iOffset, $iSize );
                if ( !$this->_send_sftp_packet( NET_SFTP_READ, $sPacket ) )
                {
                    if ( $sLocalFile !== false )
                    {
                        fclose( $rhLocalFile );
                    }
                    return false;
                }

                $sResponse = $this->_get_sftp_packet();
                switch ( $this->iPacketType )
                {
                    case NET_SFTP_DATA :
                        $sTemp = substr( $sResponse, 4 );
                        $iOffset+= strlen( $sTemp );
                        if ( $sLocalFile === false )
                        {
                            $sContent.= $sTemp;
                        }
                        else
                        {
                            fputs( $rhLocalFile, $sTemp );
                        }
                        break;

                    case NET_SFTP_STATUS :
                        // could, in theory, return false if !strlen( $sContent ) but we'll hold off for the time being
                        $this->LogError( $sResponse );
                        break 2;

                    default :
                        user_error( 'Expected SSH_FXP_DATA or SSH_FXP_STATUS' );
                        if ( $sLocalFile !== false )
                        {
                            fclose( $rhLocalFile );
                        }
                        return false;
                }

                if ( $iLength > 0 && $iLength <= $iOffset - $iSize )
                {
                    break;
                }
            }

            if ( $iLength > 0 && $iLength <= $iOffset - $iSize )
            {
                if ( $sLocalFile === false )
                {
                    $sContent = substr( $sContent, 0, $iLength );
                }
                else
                {
                    ftruncate( $rhLocalFile, $iLength );
                }
            }

            if ( $sLocalFile !== false )
            {
                fclose( $rhLocalFile );
            }

            if ( !$this->_close_handle( $sHandle ) )
            {
                return false;
            }

            // if $sContent isn't set that means a file was written to
            return isset( $sContent ) ? $sContent : true;
        }

        /**
         * Deletes a file on the SFTP server. Equivalent of SFTP `rm` command.
         *
         * This may support wild-cards, *, depending on the SFTP server.
         *
         * @param   String  $path
         * @param   Boolean $bRecursive
         *
         * @return  Boolean
         */
        public function delete( $sPath, $bRecursive = true )
        {
            if ( !( $this->oSSH->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                return false;
            }

            $sPath = $this->_realpath( $sPath );
            if ( $sPath === false )
            {
                return false;
            }

            // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.3
            if ( !$this->_send_sftp_packet( NET_SFTP_REMOVE, pack( 'Na*', strlen( $sPath ), $sPath ) ) )
            {
                return false;
            }

            $sResponse = $this->_get_sftp_packet();
            if ( $this->iPacketType != NET_SFTP_STATUS )
            {
                user_error( 'Expected SSH_FXP_STATUS' );
                return false;
            }

            // if $status isn't SSH_FX_OK it's probably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED
            $aTemp = unpack( 'Nstatus', $this->oSSH->StringShift( $sResponse, 4 ) );
            if ( $aTemp[ 'status' ] != NET_SFTP_STATUS_OK )
            {
                $this->LogError( $sResponse, $aTemp[ 'status' ] );
                if ( !$bRecursive )
                {
                    return false;
                }
                $iRespCount = 0;
                $bResult = $this->_delete_recursive( $sPath, $iRespCount );
                $this->_read_put_responses( $iRespCount );
                return $bResult;
            }

            return true;
        }

        /**
         * Recursively deletes directories on the SFTP server
         *
         * Minimizes directory lookups and SSH_FXP_STATUS requests for speed.
         *
         * @param   String  $sPath
         * @param   Integer $iRespCount
         *
         * @return  Boolean
         *
         * @access  private
         */
        public function _delete_recursive( $sPath, &$iRespCount )
        {
            if ( !$this->_read_put_responses( $iRespCount ) )
            {
                return false;
            }
            $iRespCount = 0;
            $aEntries = $this->_list( $sPath, true, false );

            // normally $aEntries would have at least . and .. but it might not if the directories
            // permissions didn't allow reading
            if ( empty( $aEntries ) )
            {
                return false;
            }

            foreach ( $aEntries as $sFilename=>$aProperties )
            {
                if ( $sFilename == '.' || $sFilename == '..' )
                {
                    continue;
                }

                if ( !isset( $aProperties[ 'type' ] ) )
                {
                    return false;
                }

                $sTempFile = $sPath . '/' . $sFilename;
                if ( $aProperties[ 'type' ] == NET_SFTP_TYPE_DIRECTORY )
                {
                    if ( !$this->_delete_recursive( $sTempFile, $iRespCount ) )
                    {
                        return false;
                    }
                }
                else
                {
                    if ( !$this->_send_sftp_packet( NET_SFTP_REMOVE, pack( 'Na*', strlen( $sTempFile ), $sTempFile ) ) )
                    {
                        return false;
                    }

                    $iRespCount++;

                    if ( $iRespCount >= NET_SFTP_QUEUE_SIZE )
                    {
                        if ( !$this->_read_put_responses( $iRespCount ) )
                        {
                            return false;
                        }
                        $iRespCount = 0;
                    }
                }
            }

            if ( !$this->_send_sftp_packet( NET_SFTP_RMDIR, pack( 'Na*', strlen( $sPath ), $sPath ) ) )
            {
                return false;
            }
            $this->_remove_dir( $sPath );

            $iRespCount++;

            if ( $iRespCount >= NET_SFTP_QUEUE_SIZE )
            {
                if ( !$this->_read_put_responses( $iRespCount ) )
                {
                    return false;
                }
                $iRespCount = 0;
            }

            return true;
        }

        /**
         * Renames a file or a directory on the SFTP server
         *
         * @param   String  $sOldName
         * @param   String  $sNewName
         *
         * @return  Boolean
         */
        public function rename( $sOldName, $sNewName )
        {
            if ( !( $this->oSSH->iBitmap & NET_SSH2_MASK_LOGIN ) )
            {
                return false;
            }

            $sOldName = $this->_realpath( $sOldName );
            $sNewName = $this->_realpath( $sNewName );
            if ( $sOldName === false || $sNewName === false )
            {
                return false;
            }

            // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.3
            $sPacket = pack( 'Na*Na*', strlen( $sOldName ), $sOldName, strlen( $sNewName ), $sNewName );
            if ( !$this->_send_sftp_packet( NET_SFTP_RENAME, $sPacket ) )
            {
                return false;
            }

            $sResponse = $this->_get_sftp_packet();
            if ( $this->iPacketType != NET_SFTP_STATUS )
            {
                user_error( 'Expected SSH_FXP_STATUS' );
                return false;
            }

            // if $status isn't SSH_FX_OK it's probably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED
            $aTemp = unpack( 'Nstatus', $this->oSSH->StringShift( $sResponse, 4 ) );
            if ( $aTemp[ 'status' ] != NET_SFTP_STATUS_OK )
            {
                $this->LogError( $sResponse, $aTemp[ 'status' ] );
                return false;
            }

            return true;
        }

        /**
         * Parse Attributes
         *
         * See '7.  File Attributes' of draft-ietf-secsh-filexfer-13 for more info.
         *
         * @param   String  $sResponse
         *
         * @return  Array
         *
         * @todo test NET_SFTP_ATTR_SIZE replacement with 64bit signed int.
         *
         * @access  private
         */
        public function _parseAttributes( &$sResponse )
        {
            // initialize return array
            $aAttr = array();

            // get flags
            $aTemp  = unpack( 'Nflags', $this->oSSH->StringShift( $sResponse, 4 ) );
            $iFlags = $aTemp[ 'flags' ];
            // SFTPv4+ have a type field ( a byte ) that follows the above flag field
            // $aTemp  = unpack( 'Ntype', $this->oSSH->StringShift( $sResponse, 4 ) );
            // $iType  = $aTemp[ 'type' ];
            // var_dump( $iType );
            // exit( __FILE__ . ' '. __LINE__ );
            foreach ( $this->aAttributes as $sValue => $iKey )
            {
                switch ( $iFlags & $iKey )
                {
                    case NET_SFTP_ATTR_SIZE : // 0x00000001
                        // size is represented by a 64-bit integer, so we perhaps ought to be doing the following:
                        // $aAttr[ 'size' ] = new Math_BigInteger( $this->oSSH->StringShift( $sResponse, 8 ), 256 );
                        // of course, you shouldn't be using Net_SFTP to transfer files that are in excess of 4GB
                        // ( 0xFFFFFFFF bytes ), anyway.  as such, we'll just represent all file sizes that are bigger than
                        // 4GB as being 4GB.
                        $aTemp  = unpack( 'Nupper/Nsize', $this->oSSH->StringShift( $sResponse, 8 ) );
                        $iUpper = $aTemp[ 'upper' ];
                        $iSize  = $aTemp[ 'size' ];

                        $aAttr[ 'size' ]  = $iUpper ? 0x100000000 * $iUpper : 0;
                        $aAttr[ 'size' ] += $iSize < 0 ? ( $iSize & 0x7FFFFFFF ) + 0x80000000 : $iSize;
                        break;

                    case NET_SFTP_ATTR_UIDGID : // 0x00000002 ( SFTPv3 only )
                        $aAttr += unpack( 'Nuid/Ngid', $this->oSSH->StringShift( $sResponse, 8 ) );
                        break;

                    case NET_SFTP_ATTR_PERMISSIONS : // 0x00000004
                        // mode == permissions; permissions was the original array key and is retained for bc purposes.
                        // mode was added because that's the more industry standard terminology
                        $aAttr                 += unpack( 'Npermissions', $this->oSSH->StringShift( $sResponse, 4 ) );
                        $aAttr                 += array( 'mode' => $aAttr[ 'permissions' ] );
                        $sFileType              = $this->_parseMode( $aAttr[ 'permissions' ] );
                        $aAttr[ 'permissions' ] = $this->ParsePermissions( $aAttr[ 'permissions' ] );
                        if ( $sFileType !== false )
                        {
                            $aAttr += array( 'type' => $sFileType );
                        }
                        break;

                    case NET_SFTP_ATTR_ACCESSTIME : // 0x00000008
                        $aAttr += unpack( 'Natime/Nmtime', $this->oSSH->StringShift( $sResponse, 8 ) );
                        break;

                    case NET_SFTP_ATTR_EXTENDED : // 0x80000000
                        $aTemp  = unpack( 'Ncount', $this->oSSH->StringShift( $sResponse, 4 ) );
                        $iCount = $aTemp[ 'count' ];
                        for ( $i = 0; $i < $iCount; $i++ )
                        {
                            // get attributes.
                            $aTemp          = unpack( 'Nlength', $this->oSSH->StringShift( $sResponse, 4 ) );
                            $iKey           = $this->oSSH->StringShift( $sResponse, $aTemp[ 'length' ] );
                            $aTemp          = unpack( 'Nlength', $this->oSSH->StringShift( $sResponse, 4 ) );
                            $aAttr[ $iKey ] = $this->oSSH->StringShift( $sResponse, $aTemp[ 'length' ] );
                        }
                        break;

                    default :
                        // nothing.
                        break;

                }
            }
            return $aAttr;
        }

        /**
         * Attempt to identify the file type
         *
         * Quoting the SFTP RFC, "Implementations MUST NOT send bits that are not defined" but they seem to anyway
         *
         * @param   Integer $iMode
         *
         * @return  Integer
         *
         * @access  private
         */
        public function _parseMode( $iMode )
        {
            // values come from http://lxr.free-electrons.com/source/include/uapi/linux/stat.h#L12
            // see, also, http://linux.die.net/man/2/stat
            switch ( $iMode & iTYPEMASK )
            {   // ie. 1111 0000 0000 0000
                case 0000000: // no file type specified - figure out the file type using alternative means
                    return false;

                case iDIRECTORY:
                    return NET_SFTP_TYPE_DIRECTORY;

                case iREGULARFILE:
                    return NET_SFTP_TYPE_REGULAR;

                case iSYMLINK:
                    return NET_SFTP_TYPE_SYMLINK;

                // new types introduced in SFTPv5+
                // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-05#section-5.2
                case iFIFO: // named pipe ( fifo )
                    return NET_SFTP_TYPE_FIFO;

                case iCHARACTER: // character special
                    return NET_SFTP_TYPE_CHAR_DEVICE;

                case iBLOCK: // block special
                    return NET_SFTP_BLOCK_DEVICE;

                case iSOCKET: // socket
                    return NET_SFTP_TYPE_SOCKET;

                case iSPECIAL: // whiteout
                    return NET_SFTP_TYPE_SPECIAL;

                default:
                    return NET_SFTP_TYPE_UNKNOWN;
            }
        }

        private function ParsePermissions( $iMode )
        {
            // initialize
            $sOwner  = '0';
            $sGroup  = '0';
            $sPublic = '0';

            // check to see that it is a file or folder..
            if ( ( $iMode & iTYPEMASK ) == iREGULARFILE
                || ( $iMode & iTYPEMASK ) == iDIRECTORY )
            {
                // get owner permissions
                $iOwnerBit   = 0;
                $iOwnerBit   = ( $iMode & iOWNERR ) ? $iOwnerBit + 4 : $iOwnerBit;
                $iOwnerBit   = ( $iMode & iOWNERW ) ? $iOwnerBit + 2 : $iOwnerBit;
                $iOwnerBit   = ( $iMode & iOWNERX ) ? $iOwnerBit + 1 : $iOwnerBit;

                // get group permissions
                $iGroupBit   = 0;
                $iGroupBit   = ( $iMode & iGROUPR ) ? $iGroupBit + 4 : $iGroupBit;
                $iGroupBit   = ( $iMode & iGROUPW ) ? $iGroupBit + 2 : $iGroupBit;
                $iGroupBit   = ( $iMode & iGROUPX ) ? $iGroupBit + 1 : $iGroupBit;

                // get public permissions.
                $iPublicBit  = 0;
                $iPublicBit  = ( $iMode & iPUBLICR ) ? $iPublicBit + 4 : $iPublicBit;
                $iPublicBit  = ( $iMode & iPUBLICW ) ? $iPublicBit + 2 : $iPublicBit;
                $iPublicBit  = ( $iMode & iPUBLICX ) ? $iPublicBit + 1 : $iPublicBit;

                $sOwner  = "$iOwnerBit";
                $sGroup  = "$iGroupBit";
                $sPublic = "$iPublicBit";
            }

            return $sOwner . $sGroup . $sPublic;
        }

        private function ModeToString( $iMode )
        {
            // this function will output string representing the
            // mode of the file or folder.
            //
            // ie. -rwsrw-r--
        }

        /**
         * Parse Longname
         *
         * SFTPv3 doesn't provide any easy way of identifying a file type.  You could try to open
         * a file as a directory and see if an error is returned or you could try to parse the
         * SFTPv3-specific longname field of the SSH_FXP_NAME packet.  That's what this function does.
         * The result is returned using the
         * {@link http://tools.ietf.org/html/draft-ietf-secsh-filexfer-04#section-5.2 SFTPv4 type constants}.
         *
         * If the longname is in an unrecognized format bool( false ) is returned.
         *
         * @param   String  $sLongname
         *
         * @return  boolean | string
         *
         * @access  private
         */
        public function _parseLongname( $sLongname )
        {
            // http://en.wikipedia.org/wiki/Unix_file_types
            // http://en.wikipedia.org/wiki/Filesystem_permissions#Notation_of_traditional_Unix_permissions
            if ( preg_match( '#^[ ^/ ]( [ r- ][ w- ][ xstST- ] ){3}#', $sLongname ) )
            {
                switch ( $sLongname[ 0 ] )
                {
                    case '-':
                        return NET_SFTP_TYPE_REGULAR;

                    case 'd':
                        return NET_SFTP_TYPE_DIRECTORY;

                    case 'l':
                        return NET_SFTP_TYPE_SYMLINK;

                    default:
                        return NET_SFTP_TYPE_SPECIAL;
                }
            }

            return false;
        }

        /**
         * Sends SFTP Packets
         *
         * See '6. General Packet Format' of draft-ietf-secsh-filexfer-13 for more info.
         *
         * @see     SFTP::_get_sftp_packet()
         * @see     SSH2::SendChannelPacket()
         *
         * @param   Integer $iType
         * @param   String  $sData
         *
         * @return  Boolean
         *
         * @access  private
         */
        public function _send_sftp_packet( $iType, $sData )
        {
            $sPacket = $this->iRequestId !== false ?
                pack( 'NCNa*', strlen( $sData ) + 5, $iType, $this->iRequestId, $sData ) :
                pack( 'NCa*',  strlen( $sData ) + 1, $iType, $sData );

            $fStart  = microtime( true );
            $bResult = $this->oSSH->SendChannelPacket( NET_SFTP_CHANNEL, $sPacket );
            $fStop   = microtime( true );

            if ( defined( 'NET_SFTP_LOGGING' ) )
            {
                $sPacketType = '-> ' . $this->aPacketTypes[ ' ' . $iType ] .
                               ' ( ' . round( $fStop - $fStart, 4 ) . 's )';
                if ( NET_SFTP_LOGGING == NET_SFTP_LOG_REALTIME )
                {
                    echo "<pre>\r\n" . $this->oSSH->FormatLog( array( $sData ), array( $sPacketType ) ) . "\r\n</pre>\r\n";
                    flush();
                    ob_flush();
                }
                else
                {
                    $this->aPacketTypeLog[] = $sPacketType;
                    if ( NET_SFTP_LOGGING == NET_SFTP_LOG_COMPLEX )
                    {
                        $this->aPacketLog[] = $sData;
                    }
                }
            }

            return $bResult;
        }

        /**
         * Receives SFTP Packets
         *
         * See '6. General Packet Format' of draft-ietf-secsh-filexfer-13 for more info.
         *
         * Incidentally, the number of SSH_MSG_CHANNEL_DATA messages has no bearing on the number of SFTP packets present.
         * There can be one SSH_MSG_CHANNEL_DATA messages containing two SFTP packets or there can be two SSH_MSG_CHANNEL_DATA
         * messages containing one SFTP packet.
         *
         * @see     SFTP::_send_sftp_packet()
         * @see     SSH2::GetChannelPacket()
         *
         * @return  String
         *
         * @access  private
         */
        public function _get_sftp_packet()
        {
            // refresh timeout flag
            $this->oSSH->iCurTimeout = false;

            // record start time.
            $fStart = microtime( true );

            // Wait for SFTP packet length
            while ( strlen( $this->sPacketBuffer ) < 4 )
            {
                $sTemp = $this->oSSH->GetChannelPacket( NET_SFTP_CHANNEL );
                if ( is_bool( $sTemp ) )
                {
                    $this->iPacketType   = false;
                    $this->sPacketBuffer = '';
                    return false;
                }
                $this->sPacketBuffer .= $sTemp;
            }
            $aTemp        = unpack( 'Nlength', $this->oSSH->StringShift( $this->sPacketBuffer, 4 ) );
            $iTempLength  = $aTemp[ 'length' ];
            $iTempLength -= strlen( $this->sPacketBuffer );

            // SFTP packet type and data payload
            while ( $iTempLength > 0 )
            {
                $sTemp = $this->oSSH->GetChannelPacket( NET_SFTP_CHANNEL );
                if ( is_bool( $sTemp ) )
                {
                    // empty packet buffer.
                    $this->iPacketType   = false;
                    $this->sPacketBuffer = '';
                    return false;
                }
                $this->sPacketBuffer .= $sTemp;
                $iTempLength         -= strlen( $sTemp );
            }

            // record stop time.
            $fStop = microtime( true );

            $this->iPacketType = ord( $this->oSSH->StringShift( $this->sPacketBuffer ) );

            if ( $this->iRequestId !== false )
            {
                // remove the request id
                $this->oSSH->StringShift( $this->sPacketBuffer, 4 );

                // account for the request id and the packet type
                $aTemp[ 'length' ]-= 5;
            }
            else
            {
                // account for the packet type
                $aTemp[ 'length' ]-= 1;
            }

            $sPacket = $this->oSSH->StringShift( $this->sPacketBuffer, $aTemp[ 'length' ] );

            if ( defined( 'NET_SFTP_LOGGING' ) )
            {
                $iPacketType = '<- ' . $this->aPacketTypes[ ' ' . $this->iPacketType ] .
                               ' ( ' . round( $fStop - $fStart, 4 ) . 's )';
                if ( NET_SFTP_LOGGING == NET_SFTP_LOG_REALTIME )
                {
                    echo "<pre>\r\n" . $this->oSSH->FormatLog( array( $sPacket ), array( $iPacketType ) ) . "\r\n</pre>\r\n";
                    flush();
                    ob_flush();
                }
                else
                {
                    $this->aPacketTypeLog[] = $iPacketType;
                    if ( NET_SFTP_LOGGING == NET_SFTP_LOG_COMPLEX )
                    {
                        $this->aPacketLog[] = $sPacket;
                    }
                }
            }

            return $sPacket;
        }

        /**
         * Returns a log of the packets that have been sent and received.
         *
         * Returns a string if NET_SFTP_LOGGING == NET_SFTP_LOG_COMPLEX,
         * an array if NET_SFTP_LOGGING == NET_SFTP_LOG_SIMPLE
         * and false if !defined( 'NET_SFTP_LOGGING' )
         *
         * @return  String | Array
         */
        public function getSFTPLog()
        {
            if ( !defined( 'NET_SFTP_LOGGING' ) )
            {
                return false;
            }

            switch ( NET_SFTP_LOGGING )
            {
                case NET_SFTP_LOG_COMPLEX :
                    return $this->oSSH->FormatLog( $this->aPacketLog, $this->aPacketTypeLog );
                    break;

                //case NET_SFTP_LOG_SIMPLE:
                default :
                    return $this->aPacketTypeLog;
            }
        }

        /**
         * Returns all errors
         *
         * @return String
         */
        public function getSFTPErrors()
        {
            return $this->aSftpErrors;
        }

        /**
         * Returns the last error
         *
         * @return String
         */
        public function getLastSFTPError()
        {
            return count( $this->aSftpErrors ) ? $this->aSftpErrors[ count($this->aSftpErrors ) - 1 ] : '';
        }

        /**
         * Get supported SFTP versions
         *
         * @return Array
         */
        public function getSupportedVersions()
        {
            $aTemp = array( 'version' => $this->iVersion );
            if ( isset( $this->aExtensions[ 'versions' ] ) )
            {
                $aTemp[ 'extensions' ] = $this->aExtensions[ 'versions' ];
            }
            return $aTemp;
        }

        /**
         * Disconnect
         *
         * @param   Integer $iReason
         *
         * @return  Boolean
         *
         * @access  private
         */
        public function Disconnect( $iReason = NET_SSH2_DISCONNECT_BY_APPLICATION )
        {
            $bReturn = $this->oSSH->Disconnect( $iReason );
            unset( $this->oSSH );

            return $bReturn;
        }

        /**
         * SFTP Class deconstructor.
         *
         * Cleans up allocated memory
         */
        public function __destruct()
        {
            if ( function_exists( 'gc_collect_cycles' ) )
            {
                gc_collect_cycles();
            }
            $this->bInitialized = false;
            $this->sPwd         = false;
        }
    }
?>