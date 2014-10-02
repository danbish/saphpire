<?php
    // load definitions.
    require_once( 'includes/scp-defined.php' );

    /**
     * Pure-PHP implementation of SCP.
     * @link https://blogs.oracle.com/janp/entry/how_the_scp_protocol_works
     *
     * PHP versions 4 and 5
     *
     * The API for this library is modeled after the API from PHP's
     * @link http://php.net/book.ftp    FTP extension.
     *
     * Juggernaut standardized version.
     *
     * Example:
     *
     * SCP requires a valid/open SSH2 interactive shell object.
     * You need to require the SSH2 library.
     *
     *     $oSSH = new cSSH2( 'host' );
     *     if ( !$oSSH->Login( 'user', 'pass' ) )
     *     {
     *         exit( 'Login Failed' );
     *     }
     *     $oSCP = new cSCP( $oSSH );
     *
     *     $oSCP->Put( 'testfile.txt',
     *                 str_repeat( "\r\n", 1024 ),
     *                 (optional) '0750',
     *                 (optional) NET_SCP_STRING | NET_SCP_LOCAL_FILE
     *                 );
     *
     *     $oSCP->Get( 'testfile.txt', 'localtest.txt' );
     *     $sFile = $oSCP-Get( 'testfile.txt' );
     *
     *
     * @author Academic Services::Team Ra
     * @version 0.2.1
     *
     * @todo add support for fully-recursive directory transfers.
     *       scp -r | Dmmmm, E
     * @todo CRC checking or MD5 hash sum?
     *       Note: SSH/SSH2 has methods to ensure data integrity built in.
     *             See MAC/HMAC in SSH2
     */
    class cSCP
    {
        /**
         * SSH Object
         *
         * @var Object
         */
        private $oSSH;

        /**
         * Packet Size
         *
         * @var Integer
         */
        private $iPacketSize;

        /**
         * Mode
         *
         * @var Integer
         */
        private $iMode;

        /**
         * Default Constructor.
         *
         * Connects to an SSH server
         *
         * @param Object SSH object
         */
        public function __construct( $oSSH )
        {
            // check for valid ssh object.
            if ( is_object( $oSSH ) )
            {
                // set version-specific settings.
                $sClass = strtolower( get_class( $oSSH ) );
                if ( $sClass === 'cssh2' )
                {
                    $this->iMode = NET_SCP_SSH2;
                    $this->oSSH  = $oSSH;
                }
                else if ( $sClass === 'cssh1' )
                {
                    $this->iPacketSize = 50000;
                    $this->iMode       = NET_SCP_SSH1;
                    $this->oSSH        = $oSSH;
                }
            }
        }

        /**
         * Uploads a file to the SCP server.
         *
         * By default, SCP::Put() does not read from the local file system.
         * $sData is dumped directly into $sRemoteFile. So, for example, if you
         * set $sData to 'filename.ext' and then do SCP::Get(), you will get a
         * file, twelve bytes long, containing 'filename.ext' as its contents.
         *
         * Setting $iXferMode to NET_SCP_LOCAL_FILE will change the above behavior.
         * With NET_SCP_LOCAL_FILE, $sRemoteFile will contain as many bytes as
         * filename.ext does on your local file system.  If your filename.ext is
         * 1MB then that is how large $sRemoteFile will be, as well.
         *
         * Currently, only binary mode is supported.  As such, if the line
         * endings need to be adjusted, you will need to take care of that,
         * yourself.
         *
         * @param   String      $sRemoteFile    Remote file destination to PUT to.
         * @param   String      $sData          Binary data to send.
         * @param   String      $sFileMode      File permission mode
         * @param   Integer     $iXferMode      Transfer mode
         *
         * @return  Boolean
         *
         * @todo    make line-ending character configurable.
         */
        public function Put( $sRemoteFile, $sData, $sFileMode = '0644', $iXferMode = NET_SCP_STRING )
        {
            // initialize return variable.
            $bReturn = null;

            // trim the edges.
            $sRemoteFile = trim( $sRemoteFile );

            // check valid SSH object, validate destination, initialize SCP transfer.
            if( empty( $this->oSSH )
             || empty( $sRemoteFile )
             || !is_string( $sRemoteFile )
             || !$this->oSSH->Cmd( 'scp -t ' . $sRemoteFile, false ) ) // -t = to
            {
                $bReturn = false;
            }
            else
            {
                // check for null bit.
                $sTempStr = $this->Receive();
                if ( $sTempStr === chr( 0 ) )
                {
                    // adjust packet size
                    if ( $this->iMode == NET_SCP_SSH2 )
                    {
                        $this->iPacketSize = $this->oSSH->aPacketSizeClientToServer[ NET_SSH2_CHANNEL_EXEC ];
                    }

                    // initialize
                    $sRemoteFile = basename( $sRemoteFile );
                    $iSize       = 0;

                    // handle file transfer mode.
                    if ( $iXferMode == NET_SCP_STRING )
                    {
                        // read from string.
                        $iSize = strlen( $sData );
                    }
                    else if ( !is_file( $sData ) || !file_exists( $sData ) )
                    {
                        // file does not exist.
                        user_error( "$sData is not a valid file", E_USER_NOTICE );
                        $bReturn = false;
                    }
                    else
                    {
                        // attempt to read binary data from file.
                        $rhFile = @fopen( $sData, 'rb' );
                        if ( !$rhFile )
                        {
                            // close file handle.
                            fclose( $rhFile );
                            $bReturn = false;
                        }
                        else
                        {
                            // get file size
                            $iSize = filesize( $sData );
                        }
                    }

                    // make sure the return variable is still unset.
                    if ( $bReturn === null )
                    {
                        // send transfer command.
                        $this->Send( 'C' . $sFileMode . ' ' . strlen( $sData ) . ' ' . $sRemoteFile . "\n" );

                        // check for null bit.
                        $sTempStr = $this->Receive();
                        if ( $sTempStr === chr( 0 ) )
                        {
                            // process entire data string {iPacketSize} bytes at a time.
                            $iBytesSent = 0;
                            while ( $iBytesSent < $iSize )
                            {
                                if ( $iXferMode == NET_SCP_STRING )
                                {
                                    // read data string.
                                    $sTempStr = substr( $sData, $iBytesSent, $this->iPacketSize );
                                }
                                else
                                {
                                    // read file.
                                    $sTempStr = fread( $rhFile, $this->iPacketSize );
                                }

                                // send bytes.
                                $this->Send( $sTempStr );
                                $iBytesSent += strlen( $sTempStr );
                            }
                            // close file handle.
                            if ( $iXferMode != NET_SCP_STRING )
                            {
                                fclose( $rhFile );
                            }

                            // success!
                            $bReturn = true;
                        }
                        else
                        {
                            // unknown failure.
                            $bReturn = false;
                        }
                    }
                }
                else
                {
                    // unknown failure.
                    $bReturn = false;
                }

                // close connection.
                $this->Close();
            }

            return $bReturn;
        }

        /**
         * Downloads a file from the SCP server.
         *
         * Returns a string containing the contents of $sRemoteFile
         * if $vLocalFile is left undefined or a boolean false if the
         * operation was unsuccessful.  If $vLocalFile is defined,
         * returns true or false depending on the success of the operation
         *
         * @param   string          $sRemoteFile    Path to remote file for retrieving.
         * @param   string|boolean  $vLocalFile     default=false, if set as a string,
         *                                          the string will be used as a path
         *                                          to a local file.
         *
         * @return  string|boolean  Returns the content of $sRemoteFile or boolean success
         *                          flag if using a local file for output.
         *
         * @todo    add parameter to allow for control of file permission mode
         *          for the newly downloaded $vLocalFile (if it's a valid file path).
         */
        public function Get( $sRemoteFile, $vLocalFile = false )
        {
            // initialize variables
            $sContent  = '';
            $iFileSize = 0;
            $vReturn   = null;

            // trim the edges.
            $sRemoteFile = trim( $sRemoteFile );

            // check valid SSH object, validate origin file, initialize SCP transfer.
            if( empty( $this->oSSH )
             || empty( $sRemoteFile )
             || !is_string( $sRemoteFile )
             || !$this->oSSH->Cmd( 'scp -f ' . $sRemoteFile, false ) ) // -f = from
            {
                $vReturn = false;
            }
            else
            {
                // send null bit.
                $this->Send( "\0" );

                // get file info.
                $sFileInfoRaw = $this->Receive();
                if ( !preg_match( '#(?<perms>[^ ]+) (?<size>\d+) (?<name>.+)#', rtrim( $sFileInfoRaw ), $aFileInfo ) )
                {
                    $vReturn = false;
                }
                else
                {
                    // send null bit.
                    $this->Send( "\0" );

                    // validate $vLocalFile as file path.
                    if ( $vLocalFile !== false && is_string( $vLocalFile ) )
                    {
                        // open local file for writing.
                        $rhFile = @fopen( $vLocalFile, 'wb' );
                        if ( !$rhFile )
                        {
                            // failure.
                            $vReturn = false;
                        }
                    }
                    else
                    {
                        // we don't know where to write...
                        $vReturn = false;
                    }

                    if ( $vReturn === null )
                    {
                        // SCP usually seems to split stuff out into 16k chunks
                        while ( $iFileSize < $aFileInfo[ 'size' ] )
                        {
                            // get chunk.
                            $sData      = $this->Receive();
                            $iFileSize += strlen( $sData );

                            // save chunk.
                            if ( $vLocalFile === false )
                            {
                                // save to variable.
                                $sContent .= $sData;
                            }
                            else
                            {
                                // write to file.
                                fputs( $rhFile, $sData );
                            }
                        }

                        if ( $vLocalFile !== false )
                        {
                            // close our resource handle. return true boolean
                            fclose( $rhFile );
                            $vReturn = true;
                        }
                        else
                        {
                            // set file contents as return value.
                            $vReturn = $sContent;
                        }
                    }
                }

                // close connection.
                $this->Close();
            }

            return $vReturn;
        }

        /**
         * Sends a packet to an SSH server
         *
         * @param String $sData
         */
        private function Send( $sData )
        {
            // version-specific packet being sent.
            if ( $this->iMode == NET_SCP_SSH2 )
            {
                $this->oSSH->SendChannelPacket( NET_SSH2_CHANNEL_EXEC, $sData );
            }
            else if ( $this->iMode == NET_SCP_SSH1 )
            {
                $sData = pack( 'CNa*', NET_SSH1_CMSG_STDIN_DATA, strlen( $sData ), $sData );
                $this->oSSH->SendBinaryPacket( $sData );
            }
        }

        /**
         * Receives a packet from an SSH server
         *
         * @return  boolean | string
         *
         * @todo    add time-out to packet receiving loop for SSH1?
         */
        private function Receive()
        {
            // initialize return variable
            $vReturn = false;

            // version-specific packet reception.
            if ( $this->iMode == NET_SCP_SSH2 )
            {
                $vReturn = $this->oSSH->GetChannelPacket( NET_SSH2_CHANNEL_EXEC, true );
            }
            else if ( $this->iMode == NET_SCP_SSH1 && !empty( $this->oSSH->iBitmap ) )
            {
                // @todo: should we put a timeout on this??
                while ( true )
                {
                    // get response packet.
                    $sResponse = $this->oSSH->GetBinaryPacket();
                    $sIdBit    = $sResponse[ NET_SSH1_RESPONSE_TYPE ];

                    if ( $sIdBit == NET_SSH1_SMSG_STDOUT_DATA )
                    {
                        // get response data.
                        $aTemp   = unpack( 'Nlength', $sResponse[ NET_SSH1_RESPONSE_DATA ] );
                        $vReturn = $this->oSSH->StringShift( $sResponse[ NET_SSH1_RESPONSE_DATA ], $aTemp[ 'length' ] );
                        break;
                    }
                    else if ( $sIdBit == NET_SSH1_SMSG_EXITSTATUS )
                    {
                        // close out
                        $this->oSSH->SendBinaryPacket( chr( NET_SSH1_CMSG_EXIT_CONFIRMATION ) );
                        fclose( $this->oSSH->oSocket );

                        $this->oSSH->iBitmap = 0;
                        $vReturn             = false;
                        break;
                    }
                    else
                    {
                        // NET_SSH1_SMSG_STDERR_DATA
                        user_error( 'Unknown packet received', E_USER_NOTICE );
                        $vReturn = false;
                        break;
                    }
                }
            }

            return $vReturn;
        }

        /**
         * Closes the connection to an SSH server
         */
        private function Close()
        {
            // version-specific disconnection.
            if ( $this->iMode == NET_SCP_SSH2 )
            {
                $this->oSSH->CloseChannel( NET_SSH2_CHANNEL_EXEC );
            }
            else if ( $this->iMode == NET_SCP_SSH1 )
            {
                $this->oSSH->Disconnect();
            }
        }

        /**
         * Destructor
         */
        public function __destruct()
        {
            // leave for cleanup.

            if ( function_exists( 'gc_collect_cycles' ) )
            {
                gc_collect_cycles();
            }
        }
    }
?>