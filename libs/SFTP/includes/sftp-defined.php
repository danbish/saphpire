<?php
    /**
     * @see SFTP::getLog()
     */
    // Returns the message numbers
    define( 'NET_SFTP_LOG_SIMPLE',  NET_SSH2_LOG_SIMPLE );
    // Returns the message content
    define( 'NET_SFTP_LOG_COMPLEX', NET_SSH2_LOG_COMPLEX );
    // Outputs the message content in real-time.
    define( 'NET_SFTP_LOG_REALTIME', 3 );
    define( 'NET_SFTP_LOGGING', NET_SFTP_LOG_COMPLEX );

    /**
     * SFTP channel constant
     *
     * SSH2::exec() uses 0
     * SSH2::read() / SSH2::write() use 1.
     * SFTP uses 2.
     *
     * @see SSH2::SendChannelPacket()
     * @see SSH2::GetChannelPacket()
     */
    define( 'NET_SFTP_CHANNEL', 0x100 );

    /**
     * @see SFTP::put()
     */
    // Reads data from a local file.
    define( 'NET_SFTP_LOCAL_FILE',   1 );
    // Reads data from a string.
    // this value isn't really used anymore but i'm keeping it reserved for historical reasons
    define( 'NET_SFTP_STRING',       2 );
    // Resumes an upload
    define( 'NET_SFTP_RESUME',       4 );
    // Append a local file to an already existing remote file
    define( 'NET_SFTP_RESUME_START', 8 );

    // SFTP status codes
    define( 'NET_SFTP_STATUS_OK',                          0 );
    define( 'NET_SFTP_STATUS_EOF',                         1 );
    define( 'NET_SFTP_STATUS_NO_SUCH_FILE',                2 );
    define( 'NET_SFTP_STATUS_PERMISSION_DENIED',           3 );
    define( 'NET_SFTP_STATUS_FAILURE',                     4 );
    define( 'NET_SFTP_STATUS_BAD_MESSAGE',                 5 );
    define( 'NET_SFTP_STATUS_NO_CONNECTION',               6 );
    define( 'NET_SFTP_STATUS_CONNECTION_LOST',             7 );
    define( 'NET_SFTP_STATUS_OP_UNSUPPORTED',              8 );
    define( 'NET_SFTP_STATUS_INVALID_HANDLE',              9 );
    define( 'NET_SFTP_STATUS_NO_SUCH_PATH',                10 );
    define( 'NET_SFTP_STATUS_FILE_ALREADY_EXISTS',         11 );
    define( 'NET_SFTP_STATUS_WRITE_PROTECT',               12 );
    define( 'NET_SFTP_STATUS_NO_MEDIA',                    13 );
    define( 'NET_SFTP_STATUS_NO_SPACE_ON_FILESYSTEM',      14 );
    define( 'NET_SFTP_STATUS_QUOTA_EXCEEDED',              15 );
    define( 'NET_SFTP_STATUS_UNKNOWN_PRINCIPAL',           16 );
    define( 'NET_SFTP_STATUS_LOCK_CONFLICT',               17 );
    define( 'NET_SFTP_STATUS_DIR_NOT_EMPTY',               18 );
    define( 'NET_SFTP_STATUS_NOT_A_DIRECTORY',             19 );
    define( 'NET_SFTP_STATUS_INVALID_FILENAME',            20 );
    define( 'NET_SFTP_STATUS_LINK_LOOP',                   21 );
    define( 'NET_SFTP_STATUS_CANNOT_DELETE',               22 );
    define( 'NET_SFTP_STATUS_INVALID_PARAMETER',           23 );
    define( 'NET_SFTP_STATUS_FILE_IS_A_DIRECTORY',         24 );
    define( 'NET_SFTP_STATUS_BYTE_RANGE_LOCK_CONFLICT',    25 );
    define( 'NET_SFTP_STATUS_BYTE_RANGE_LOCK_REFUSED',     26 );
    define( 'NET_SFTP_STATUS_DELETE_PENDING',              27 );
    define( 'NET_SFTP_STATUS_FILE_CORRUPT',                28 );
    define( 'NET_SFTP_STATUS_OWNER_INVALID',               29 );
    define( 'NET_SFTP_STATUS_GROUP_INVALID',               30 );
    define( 'NET_SFTP_STATUS_NO_MATCHING_BYTE_RANGE_LOCK', 31 );

    // SFTP Packet Types
    define( 'NET_SFTP_INIT',     1 );
    define( 'NET_SFTP_VERSION',  2 );
     /* the format of SSH_FXP_OPEN changed between SFTPv4 and SFTPv5+:
       SFTPv5+: http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.1.1
   pre-SFTPv5 : http://tools.ietf.org/html/draft-ietf-secsh-filexfer-04#section-6.3 */
    define( 'NET_SFTP_OPEN',     3 );
    define( 'NET_SFTP_CLOSE',    4 );
    define( 'NET_SFTP_READ',     5 );
    define( 'NET_SFTP_WRITE',    6 );
    define( 'NET_SFTP_LSTAT',    7 );
    define( 'NET_SFTP_SETSTAT',  9 );
    define( 'NET_SFTP_OPENDIR',  11 );
    define( 'NET_SFTP_READDIR',  12 );
    define( 'NET_SFTP_REMOVE',   13 );
    define( 'NET_SFTP_MKDIR',    14 );
    define( 'NET_SFTP_RMDIR',    15 );
    define( 'NET_SFTP_REALPATH', 16 );
    define( 'NET_SFTP_STAT',     17 );
    /* the format of SSH_FXP_RENAME changed between SFTPv4 and SFTPv5+:
           SFTPv5+: http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.3
       pre-SFTPv5 : http://tools.ietf.org/html/draft-ietf-secsh-filexfer-04#section-6.5 */
    define( 'NET_SFTP_RENAME',   18 );
    define( 'NET_SFTP_STATUS',   101 );
    define( 'NET_SFTP_HANDLE',   102 );
    define( 'NET_SFTP_DATA',     103 );
    /* the format of SSH_FXP_NAME changed between SFTPv3 and SFTPv4+:
           SFTPv4+: http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-9.4
       pre-SFTPv4 : http://tools.ietf.org/html/draft-ietf-secsh-filexfer-02#section-7 */
    define( 'NET_SFTP_NAME',     104 );
    define( 'NET_SFTP_ATTRS',    105 );
    define( 'NET_SFTP_EXTENDED', 200 );

    // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-04#section-6.3
    // the flag definitions change somewhat in SFTPv5+.
    // if SFTPv5+ support is added to this library, maybe name
    // the array for that $this->open5_flags and similarly alter the constant names.
    define( 'NET_SFTP_OPEN_READ',     0x00000001 );
    define( 'NET_SFTP_OPEN_WRITE',    0x00000002 );
    define( 'NET_SFTP_OPEN_APPEND',   0x00000004 );
    define( 'NET_SFTP_OPEN_CREATE',   0x00000008 );
    define( 'NET_SFTP_OPEN_TRUNCATE', 0x00000010 );
    define( 'NET_SFTP_OPEN_EXCL',     0x00000020 );

    // SFTP File types
    // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-04#section-5.2
    // see Net_SFTP::_parseLongname() for an explanation
    define( 'NET_SFTP_TYPE_REGULAR',      1 );
    define( 'NET_SFTP_TYPE_DIRECTORY',    2 );
    define( 'NET_SFTP_TYPE_SYMLINK',      3 );
    define( 'NET_SFTP_TYPE_SPECIAL',      4 );
    define( 'NET_SFTP_TYPE_UNKNOWN',      5 );
    // the following types were first defined for use in SFTPv5+
    // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-05#section-5.2
    define( 'NET_SFTP_TYPE_SOCKET',       6 );
    define( 'NET_SFTP_TYPE_CHAR_DEVICE',  7 );
    define( 'NET_SFTP_TYPE_BLOCK_DEVICE', 8 );
    define( 'NET_SFTP_TYPE_FIFO',         9 );

    // SFTP Attributes
    define( 'NET_SFTP_ATTR_SIZE', 0x00000001 );
    // defined in SFTPv3, removed in SFTPv4+
    define( 'NET_SFTP_ATTR_UIDGID', 0x00000002 );
    define( 'NET_SFTP_ATTR_PERMISSIONS', 0x00000004 );
    define( 'NET_SFTP_ATTR_ACCESSTIME', 0x00000008 );
    // 0x80000000 will yield a floating point on 32-bit systems and converting floating points to integers
    // yields inconsistent behavior depending on how php is compiled.  so we left shift -1 (which, in
    // two's compliment, consists of all 1 bits) by 31.  on 64-bit systems this'll yield 0xFFFFFFFF80000000.
    // that's not a problem, however, and 'anded' and a 32-bit number, as all the leading 1 bits are ignored.
    define( 'NET_SFTP_ATTR_EXTENDED', -1 << 31 );

    // modify this setting for benchmark testing.
    define( 'NET_SFTP_QUEUE_SIZE', 50 );

    // file permission constants (POSIX-compliant).
    define( 'iOWNERMASK',  00700 );
    define( 'iOWNERR',     00400 );
    define( 'iOWNERW',     00200 );
    define( 'iOWNERX',     00100 );
    define( 'iGROUPMASK',  00070 );
    define( 'iGROUPR',     00040 );
    define( 'iGROUPW',     00020 );
    define( 'iGROUPX',     00010 );
    define( 'iPUBLICMASK', 00007 );
    define( 'iPUBLICR',    00004 );
    define( 'iPUBLICW',    00002 );
    define( 'iPUBLICX',    00001 );

    // file type/mode constants
    define( 'iTYPEMASK',    0170000 );  // mask for all types
    // "SPECIAL should be used for files that are of
    //  a known type which cannot be expressed in the protocol"
    define( 'iSPECIAL',     0160000 );  // type: special
    define( 'iSOCKET',      0140000 );  // type: socket
    define( 'iSYMLINK',     0120000 );  // type: symbolic link
    define( 'iREGULARFILE', 0100000 );  // type: regular file
    define( 'iBLOCK',       0060000 );  // type: block device
    define( 'iDIRECTORY',   0040000 );  // type: directory
    define( 'iCHARACTER',   0020000 );  // type: character device
    define( 'iFIFO',        0010000 );  // type: fifo
    define( 'iUID',         0004000 );  // set-uid bit
    define( 'iGID',         0002000 );  // set-gid bit
    define( 'iSTICKY',      0001000 );  // sticky bit
?>