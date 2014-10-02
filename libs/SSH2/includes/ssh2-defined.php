<?php
    /**
     * Execution Bitmap Masks
     *
     * @see SSH2::bitmap
     */
    define( 'NET_SSH2_MASK_CONSTRUCTOR',  0x00000001 );
    define( 'NET_SSH2_MASK_LOGIN_REQ',    0x00000002 );
    define( 'NET_SSH2_MASK_LOGIN',        0x00000004 );
    define( 'NET_SSH2_MASK_SHELL',        0x00000008 );
    define( 'NET_SSH2_MASK_WINDOW_ADJUST', 0X00000010 );

    /**
     * Channel constants
     *
     * RFC4254 refers not to client and server channels but rather to sender and recipient channels.  we don't refer
     * to them in that way because RFC4254 toggles the meaning. the client sends a SSH_MSG_CHANNEL_OPEN message with
     * a sender channel and the server sends a SSH_MSG_CHANNEL_OPEN_CONFIRMATION in response, with a sender and a
     * recepient channel.  at first glance, you might conclude that SSH_MSG_CHANNEL_OPEN_CONFIRMATION's sender channel
     * would be the same thing as SSH_MSG_CHANNEL_OPEN's sender channel, but it's not, per this snipet:
     *     The 'recipient channel' is the channel number given in the original
     *     open request, and 'sender channel' is the channel number allocated by
     *     the other side.
     *
     * @see SSH2::_send_channel_packet()
     * @see SSH2::_get_channel_packet()
     */
    define( 'NET_SSH2_CHANNEL_EXEC',      0 ); // PuTTy uses 0x100
    define( 'NET_SSH2_CHANNEL_SHELL',     1 );
    define( 'NET_SSH2_CHANNEL_SUBSYSTEM', 2 );

    /**
     * Returns the message numbers
     *
     * @see SSH2::getLog()
     */
    define( 'NET_SSH2_LOG_SIMPLE', 1 );

    //Returns the message content
    define( 'NET_SSH2_LOG_COMPLEX', 2 );

    //Outputs the content real-time
    define( 'NET_SSH2_LOG_REALTIME', 3 );

    //Dumps the content real-time to a file
    define( 'NET_SSH2_LOG_REALTIME_FILE', 4 );

    // real-time log file relative path.
    define( 'NET_SSH2_LOG_REALTIME_FILENAME', 'log.realtime' );

    define( 'NET_SSH2_LOGGING', NET_SSH2_LOG_COMPLEX );

    /**
     * @see SSH2::read()
     */
    //Returns when a string matching $expect exactly is found
    define( 'NET_SSH2_READ_SIMPLE', 1 );

    //Returns when a string matching the regular expression $expect is found
    define( 'NET_SSH2_READ_REGEX', 2 );

    //Make sure that the log never gets larger than this
    define( 'NET_SSH2_LOG_MAX_SIZE', 1024 * 1024 );

    define( 'NET_SSH2_MSG_DISCONNECT',                            1   );
    define( 'NET_SSH2_MSG_IGNORE',                                2   );
    define( 'NET_SSH2_MSG_UNIMPLEMENTED',                         3   );
    define( 'NET_SSH2_MSG_DEBUG',                                 4   );
    define( 'NET_SSH2_MSG_SERVICE_REQUEST',                       5   );
    define( 'NET_SSH2_MSG_SERVICE_ACCEPT',                        6   );
    define( 'NET_SSH2_MSG_KEXINIT',                               20  );
    define( 'NET_SSH2_MSG_NEWKEYS',                               21  );
    define( 'NET_SSH2_MSG_KEXDH_INIT',                            30  );
    define( 'NET_SSH2_MSG_KEXDH_REPLY',                           31  );
    define( 'NET_SSH2_MSG_USERAUTH_REQUEST',                      50  );
    define( 'NET_SSH2_MSG_USERAUTH_FAILURE',                      51  );
    define( 'NET_SSH2_MSG_USERAUTH_SUCCESS',                      52  );
    define( 'NET_SSH2_MSG_USERAUTH_BANNER',                       53  );
    define( 'NET_SSH2_MSG_GLOBAL_REQUEST',                        80  );
    define( 'NET_SSH2_MSG_REQUEST_SUCCESS',                       81  );
    define( 'NET_SSH2_MSG_REQUEST_FAILURE',                       82  );
    define( 'NET_SSH2_MSG_CHANNEL_OPEN',                          90  );
    define( 'NET_SSH2_MSG_CHANNEL_OPEN_CONFIRMATION',             91  );
    define( 'NET_SSH2_MSG_CHANNEL_OPEN_FAILURE',                  92  );
    define( 'NET_SSH2_MSG_CHANNEL_WINDOW_ADJUST',                 93  );
    define( 'NET_SSH2_MSG_CHANNEL_DATA',                          94  );
    define( 'NET_SSH2_MSG_CHANNEL_EXTENDED_DATA',                 95  );
    define( 'NET_SSH2_MSG_CHANNEL_EOF',                           96  );
    define( 'NET_SSH2_MSG_CHANNEL_CLOSE',                         97  );
    define( 'NET_SSH2_MSG_CHANNEL_REQUEST',                       98  );
    define( 'NET_SSH2_MSG_CHANNEL_SUCCESS',                       99  );
    define( 'NET_SSH2_MSG_CHANNEL_FAILURE',                       100 );
    define( 'NET_SSH2_DISCONNECT_HOST_NOT_ALLOWED_TO_CONNECT',    1   );
    define( 'NET_SSH2_DISCONNECT_PROTOCOL_ERROR',                 2   );
    define( 'NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED',            3   );
    define( 'NET_SSH2_DISCONNECT_RESERVED',                       4   );
    define( 'NET_SSH2_DISCONNECT_MAC_ERROR',                      5   );
    define( 'NET_SSH2_DISCONNECT_COMPRESSION_ERROR',              6   );
    define( 'NET_SSH2_DISCONNECT_SERVICE_NOT_AVAILABLE',          7   );
    define( 'NET_SSH2_DISCONNECT_PROTOCOL_VERSION_NOT_SUPPORTED', 8   );
    define( 'NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE',        9   );
    define( 'NET_SSH2_DISCONNECT_CONNECTION_LOST',                10  );
    define( 'NET_SSH2_DISCONNECT_BY_APPLICATION',                 11  );
    define( 'NET_SSH2_DISCONNECT_TOO_MANY_CONNECTIONS',           12  );
    define( 'NET_SSH2_DISCONNECT_AUTH_CANCELLED_BY_USER',         13  );
    define( 'NET_SSH2_DISCONNECT_NO_MORE_AUTH_METHODS_AVAILABLE', 14  );
    define( 'NET_SSH2_DISCONNECT_ILLEGAL_USER_NAME',              15  );
    define( 'NET_SSH2_OPEN_ADMINISTRATIVELY_PROHIBITED',          1   );
    define( 'NET_SSH2_TTY_OP_END',                                0   );
    define( 'NET_SSH2_EXTENDED_DATA_STDERR',                      1   );
    define( 'NET_SSH2_MSG_USERAUTH_PASSWD_CHANGEREQ',             60  );
    define( 'NET_SSH2_MSG_USERAUTH_PK_OK',                        60  );
    define( 'NET_SSH2_MSG_USERAUTH_INFO_REQUEST',                 60  );
    define( 'NET_SSH2_MSG_USERAUTH_INFO_RESPONSE',                61  );
?>