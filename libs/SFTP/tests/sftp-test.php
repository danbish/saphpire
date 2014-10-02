<?php
    require_once( '../../SSH2/SSH2.php' );
    require_once( '../SFTP.php' );

    class SFTPTest
    {
        private $sUsername;
        private $sPassword;
        public $oSSH;

        public function __construct( $sUser = '', $sPass = '' )
        {
            $this->sUsername = $sUser;
            $this->sPassword = $sPass;

            if ( !empty( $sUser ) && !empty( $sPass ) )
            {
                $this->oSSH = new SSH2( 'devsislin01.clemson.edu' );
                if ( !$this->oSSH->Login( $sUser, $sPass ) )
                {
                    exit( 'Login Failed.' );
                }
            }
        }

        public function test___construct()
        {
            $bReturn1 = false;
            $bReturn2 = false;
            $bReturn3 = false;
            try
            {
                // should throw exception.
                $oSFTP = new SFTP();
                $oSFTP->put( 'test.x', str_repeat( '01', 512 ) );
            }
            catch( Exception $oException )
            {
                // validate our exception.
                $bReturn1 = $this->equals( $oException->getMessage() == 'Valid SSH client object required.' , true );
            }

            try
            {
                // should throw exception.
                $oSSH  = new stdClass();
                $oSFTP = new SFTP( $oSSH );
                $oSFTP->put( 'test.y', str_repeat( '10', 512 ) );
            }
            catch( Exception $oException )
            {
                // validate our exception.
                $bReturn2 = $this->equals( $oException->getMessage() == 'Valid SSH client object required.' , true );
            }

            try
            {
                // should work.
                $oSFTP = new SFTP( $this->oSSH );
                $oSFTP->put( 'test.z', str_repeat( '1010', 256 ) );
            }
            catch( Exception $oException )
            {
                var_dump( $oException );
                $bReturn3 = true;
            }
            $bReturn3 = $this->equals( $bReturn3, false );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 )
                );
        }

        public function test_init()
        {
            // Run the Init() function.
            $oSFTP   = new SFTP( $this->oSSH );
            $bReturn = $this->equals( $oSFTP->Init(), true );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn )
            );
        }

        public function test_pwd()
        {
            // check for string or false bool.
            $oSFTP   = new SFTP( $this->oSSH );
            $sPwd    = $oSFTP->pwd();
            $bReturn = $this->equals( is_string( $sPwd ) || $sPwd === FALSE, true );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn )
            );
        }

        public function test_LogError()
        {
            $oSFTP = new SFTP( $this->oSSH );
            $oSFTP->LogError( 'Testing 1...2...3!' );
            $sLastErr = $oSFTP->getLastSFTPError();
            $bReturn  = $this->equals( strpos( $sLastErr, 'Testing 1...2...3!' ) !== false, true );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn )
            );
        }

        public function test__realpath()
        {
            // test that realpath finds the correct path.
            $oSFTP   = new SFTP( $this->oSSH );
            $sPwd    = $oSFTP->pwd();
            $sPwd2   = $oSFTP->_realpath( '.' );
            $bReturn = $this->equals( $sPwd == $sPwd2, true );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn )
            );
        }

        public function test_chdir()
        {
            $oSFTP       = new SFTP( $this->oSSH );
            $iTempBitmap = $this->oSSH->iBitmap;

            // this should fail because of the bad Bitmap.
            $this->oSSH->iBitmap = false;
            $bReturn1 = $this->equals( $oSFTP->chdir( 'www' ), false );

            // this should change directory to the current working directory (pwd)
            $this->oSSH->iBitmap = $iTempBitmap;
            $bReturn2 = $this->equals( $oSFTP->chdir( '' ), true );

            // change to non-existent folder, should fail.
            $bReturn3 = $this->equals( $oSFTP->chdir( 'www2' ), false );

            // change to existing file, should fail.
            $bReturn4 = $this->equals( $oSFTP->chdir( 'test' ), false );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 ),
                'Test 4: ' . $this->bool( $bReturn4 )
            );
        }

        public function test_nlist()
        {
            $oSFTP = new SFTP( $this->oSSH );

            // no arguments, should default.
            $bReturn1 = $this->equals( is_array( $oSFTP->nlist( ) ), true );

            // get parent directory.
            $bReturn2 = $this->equals( is_array( $oSFTP->nlist( '../' ) ), true );

            // invalid directory/path.
            $bReturn3 = $this->equals( is_array( $oSFTP->nlist( 'wwwwwwwwwww' ) ), false );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 )
            );
        }

        public function test_rawlist()
        {
            $oSFTP = new SFTP( $this->oSSH );

            // no arguments, should default.
            $bReturn1 = $this->equals( is_array( $oSFTP->rawlist( ) ), true );

            // get parent directory.
            $bReturn2 = $this->equals( is_array( $oSFTP->rawlist( '../' ) ), true );

            // invalid directory/path.
            $bReturn3 = $this->equals( is_array( $oSFTP->rawlist( 'wwwwwwwwwww' ) ), false );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 )
            );
        }

        public function test_list()
        {
            $oSFTP = new SFTP( $this->oSSH );

            // no arguments, should default.
            $bReturn1 = $this->equals( is_array( $oSFTP->_list( '.' ) ), true );

            // get parent directory.
            $bReturn2 = $this->equals( is_array( $oSFTP->_list( '../' ) ), true );

            // invalid directory/path.
            $bReturn3 = $this->equals( is_array( $oSFTP->_list( 'wwwwwwwwwww' ) ), false );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 )
            );
        }

        public function test_size()
        {
            $oSFTP = new SFTP( $this->oSSH );
            $iTempBitmap = $this->oSSH->iBitmap;

            // this should fail because of the bad Bitmap.
            $this->oSSH->iBitmap = false;
            $bReturn1 = $this->equals( $oSFTP->size( 'test' ), false );

            // fix the bitmap, get size of existent file.
            $this->oSSH->iBitmap = $iTempBitmap;
            $bReturn2 = $this->equals( $oSFTP->size( 'test' ) > 0, true );

            // empty filename, should fail gracefully with 0 size.
            $bReturn3 = $this->equals( $oSFTP->size( '' ) > 0, false );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 )
            );
        }

        public function test_stat()
        {
            $oSFTP = new SFTP( $this->oSSH );
            $iTempBitmap = $this->oSSH->iBitmap;

            // this should fail due to invalid bitmap
            $this->oSSH->iBitmap = false;
            $bReturn1 = $this->equals( $oSFTP->stat( 'test' ), false );

            // this should work
            $this->oSSH->iBitmap = $iTempBitmap;
            $bReturn2 = $this->equals( is_array( $oSFTP->stat( 'test' ) ), true );

            // bad filename, should fail.
            $bReturn3 = $this->equals( $oSFTP->stat( '' ), false );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 )
            );
        }

        public function test_lstat()
        {
            $oSFTP = new SFTP( $this->oSSH );
            $iTempBitmap = $this->oSSH->iBitmap;

            // this should fail due to invalid bitmap
            $this->oSSH->iBitmap = false;
            $bReturn1 = $this->equals( $oSFTP->lstat( 'test' ), false );

            // this should work
            $this->oSSH->iBitmap = $iTempBitmap;
            $bReturn2 = $this->equals( is_array( $oSFTP->lstat( 'test' ) ), true );

            // bad filename, should fail.
            $bReturn3 = $this->equals( $oSFTP->lstat( '' ), false );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 )
            );
        }

        public function test__stat()
        {
            $oSFTP = new SFTP( $this->oSSH );
            $iTempBitmap = $this->oSSH->iBitmap;

            // this should fail due to invalid bitmap
            $this->oSSH->iBitmap = false;
            $bReturn1 = $this->equals( $oSFTP->_stat( 'test', NET_SFTP_STAT ), false );

            // this should work
            $this->oSSH->iBitmap = $iTempBitmap;
            $bReturn2 = $this->equals( is_array( $oSFTP->_stat( 'test', NET_SFTP_STAT ) ), true );

            // bad filename, should fail.
            $bReturn3 = $this->equals( $oSFTP->_stat( '', NET_SFTP_LSTAT ), false );

            // packet stat packet type.
            $bReturn3 = $this->equals( $oSFTP->_stat( 'test', 0 ), false );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 )
            );
        }

        public function test__size()
        {
            $oSFTP = new SFTP( $this->oSSH );
            $iTempBitmap = $this->oSSH->iBitmap;

            // this should fail because of the bad Bitmap.
            $this->oSSH->iBitmap = false;
            $bReturn1 = $this->equals( $oSFTP->_size( 'test' ), false );

            // fix the bitmap, get size of existent file.
            $this->oSSH->iBitmap = $iTempBitmap;
            $bReturn2 = $this->equals( $oSFTP->_size( 'test' ) > 0, true );

            // empty filename, should fail gracefully with 0 size.
            $bReturn3 = $this->equals( $oSFTP->_size( '' ) > 0, false );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 )
            );
        }

        public function test_truncate()
        {
            $oSFTP = new SFTP( $this->oSSH );
            $iTempBitmap = $this->oSSH->iBitmap;

            // this should fail because of the bad Bitmap.
            $this->oSSH->iBitmap = false;
            $bReturn1 = $this->equals( $oSFTP->truncate( 'test', 0 ), false );

            // fix the bitmap, get size of existent file.
            $this->oSSH->iBitmap = $iTempBitmap;
            $bReturn2 = $this->equals( $oSFTP->truncate( 'test.z', 1000 ), true );

            // invalid filename, should fail.
            $bReturn3 = $this->equals( $oSFTP->truncate( '', 1000 ), false );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 )
            );
        }

        public function test_touch()
        {
            $oSFTP = new SFTP( $this->oSSH );
            $iTempBitmap = $this->oSSH->iBitmap;

            // this should fail because of the bad Bitmap.
            $this->oSSH->iBitmap = false;
            $bReturn1 = $this->equals( $oSFTP->touch( 'testfile', 0 ), false );

            // fix the bitmap, touch non-existent path. create new file.
            $this->oSSH->iBitmap = $iTempBitmap;
            $bReturn2 = $this->equals( $oSFTP->touch( 'testfile' ), true );

            $bReturn3 = $this->equals( $oSFTP->touch( '' ), false );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 )
            );
        }

        public function test_chown()
        {
            $oSFTP = new SFTP( $this->oSSH );
            $iTempBitmap = $this->oSSH->iBitmap;

            // this should fail because of the bad Bitmap.
            $this->oSSH->iBitmap = false;
            $bReturn1 = $this->equals( $oSFTP->chown( 'testfile', 0 ), false );

            // fix the bitmap, take ownership
            $this->oSSH->iBitmap = $iTempBitmap;
            $bReturn2 = $this->equals( $oSFTP->chown( 'testfile', 242114 ), true );

            // attempt to set ownership to another uid. shouldn't have permissions.
            $bReturn3 = $this->equals( $oSFTP->chown( 'testfile', 244924 ), false );

            // failure, invalid path.
            $bReturn4 = $this->equals( $oSFTP->chown( '', 0 ), false );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 ),
                'Test 4: ' . $this->bool( $bReturn4 )
            );
        }

        public function test_chmod()
        {
            $oSFTP = new SFTP( $this->oSSH );
            $iTempBitmap = $this->oSSH->iBitmap;

            // this should fail because of the bad Bitmap.
            $this->oSSH->iBitmap = false;
            $bReturn1 = $this->equals( $oSFTP->chmod( 0777, 'test' ), false );

            // fix the bitmap, change mode
            $this->oSSH->iBitmap = $iTempBitmap;
            $bReturn2 = $this->equals( $oSFTP->chmod(  0777, 'test' ), "777" );
            $bReturn3 = $this->equals( $oSFTP->chmod(  0644, 'test' ), "644" );
            $bReturn4 = $this->equals( $oSFTP->chmod(  0, 'test' ), "000" );

            // test string mode.
            $bReturn5 = $this->equals( $oSFTP->chmod(  "777", 'test' ), "777" );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 ),
                'Test 4: ' . $this->bool( $bReturn4 ),
                'Test 5: ' . $this->bool( $bReturn5 )
            );
        }

        public function test_chgrp()
        {
            $oSFTP = new SFTP( $this->oSSH );
            $iTempBitmap = $this->oSSH->iBitmap;

            // this should fail because of the bad Bitmap.
            $this->oSSH->iBitmap = false;
            $bReturn1 = $this->equals( $oSFTP->chgrp( 'testfile', 0 ), false );

            // fix the bitmap, change group.
            $this->oSSH->iBitmap = $iTempBitmap;
            $bReturn2 = $this->equals( $oSFTP->chgrp( 'testfile', 10000 ), true );

            // attempt to set ownership to another gid.
            $bReturn3 = $this->equals( $oSFTP->chgrp( 'testfile', 500 ), true );

            // failure, invalid path.
            $bReturn4 = $this->equals( $oSFTP->chgrp( '', 0 ), false );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 ),
                'Test 4: ' . $this->bool( $bReturn4 )
            );
        }

        /**
         * @todo finish this test.
         */
        public function test_mkdir()
        {
            require_once('File/ANSI.php');
            $oSFTP = new SFTP( $this->oSSH );
            $iTempBitmap = $this->oSSH->iBitmap;

            // this should fail because of the bad Bitmap.
            $this->oSSH->iBitmap = false;
            $bReturn1 = $this->equals( $oSFTP->mkdir( 'testdir', 0 ), false );

            // fix the bitmap, change group.
            $this->oSSH->iBitmap = $iTempBitmap;

            $bReturn2 = $this->equals( $oSFTP->mkdir( 'testdir', 0664 ), true );

            $bReturn3 = $this->equals( $oSFTP->mkdir( 'testdir2', "777" ), true );

            $bReturn4 = $this->equals( $oSFTP->mkdir( 'testdir3' ), true );

            $bReturn5 = $this->equals( $oSFTP->mkdir( 'test1/test2/test3', -1, true ), true );

            $oSFTP->rmdir( 'testdir' );
            $oSFTP->rmdir( 'testdir2' );
            $oSFTP->rmdir( 'testdir3' );

            // remove test file.
            $this->oSSH->Disconnect();
            $this->oSSH = new SSH2( 'devsislin01.clemson.edu' );
            $this->oSSH->Login( $this->sUsername, $this->sPassword );
            $this->oSSH->SetTimeout( 1 );
            $this->oSSH->Cmd( 'rm -rf test1' );

            $this->pre(
                'Call to ' . __FUNCTION__,
                'Test 1: ' . $this->bool( $bReturn1 ),
                'Test 2: ' . $this->bool( $bReturn2 ),
                'Test 3: ' . $this->bool( $bReturn3 ),
                'Test 4: ' . $this->bool( $bReturn4 ),
                'Test 5: ' . $this->bool( $bReturn5 )
            );
        }

        public function pre( $sString )
        {
            $aArgs      = func_get_args();
            $aArgs[ 0 ] = '<strong>' . $aArgs[ 0 ] . '</strong>';
            $sArgs      = implode( '<br />', $aArgs );
            echo "<pre>" . $sArgs . "</pre>";
        }

        public function equals( $bBool1, $bBool2 )
        {
            return $bBool1 === $bBool2;
        }

        public function bool( $bBool )
        {
            return $bBool ? 'true' : '<strong><u><em>false</em></u></strong>';
        }
    }
?>