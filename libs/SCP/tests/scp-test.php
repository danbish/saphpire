<?php
    require_once( '../../SSH2/SSH2.php' );
    require_once( '../SCP.php' );

    class SCPTest
    {
        private $sUsername;
        private $sPassword;

        public function __construct( $sUser = '', $sPass = '' )
        {
            $this->sUsername = $sUser;
            $this->sPassword = $sPass;
        }

        /**
         * test 1, null ssh object.
         * test 2, object of invalid class should return null.
         * test 3, You must supply a remote file destination!
         * test 4, Create a blank file, 'xyz'
         * test 5, send 1MB data.
         */
        public function test_put()
        {
            $oSSH = null;
            $oSCP = new SCP( $oSSH );

            // test 1, null ssh object.
            $bReturn1 = $this->equals( $oSCP->put( 's', 'xx' ), false );

            $oSSH = new stdClass();
            $oSCP = new SCP( $oSSH );

            // test 2, object of invalid class should return null.
            $bReturn2 = $this->equals( $oSCP->put( 's', 'xx' ), false );

            $oSSH = new SSH2( 'devsislin01.clemson.edu' );
            if ( !$oSSH->Login( $this->sUsername, $this->sPassword ) )
            {
                exit( 'Login failed.' );
            }
            $oSCP = new SCP( $oSSH );

            // test 3, You must supply a remote file destination!
            $bReturn3 = $this->equals( $oSCP->put( '', 'x' ), false );

            // test 4, Create a blank file, 'xyz'
            $bReturn4 = $this->equals( $oSCP->put( 'xyz', '' ), true );

            // test 5, send 1MB data.
            $bReturn5 = $this->equals( $oSCP->put( 'xxx', str_repeat( 'x', 1024 * 1024 ) ), true );

            // clean up.
            // $oSSH->Cmd( 'rm xyz' );
            // $oSSH->Cmd( 'rm xxx' );

            $sOutput =
            'Test 1: ' . $this->bool( $bReturn1 ) . "\r\n" .
            'Test 2: ' . $this->bool( $bReturn2 ) . "\r\n" .
            'Test 3: ' . $this->bool( $bReturn3 ) . "\r\n" .
            'Test 4: ' . $this->bool( $bReturn4 ) . "\r\n" .
            'Test 5: ' . $this->bool( $bReturn5 ) . "\r\n";

            echo "<pre>" . print_r( $sOutput, 1 ) . "</pre>";
        }

        /**
         * test 1, null ssh object.
         * test 2, object of invalid class should return null.
         * test 3, get file without specifying target
         * test 4, get file, 'xyz' to blank destination.
         * test 5, get 1MB data.
         */
        public function test_get()
        {
            $oSSH = null;
            $oSCP = new SCP( $oSSH );

            // test 1, null ssh object.
            $bReturn1 = $this->equals( $oSCP->get( 's', 'xx' ), false );

            $oSSH = new stdClass();
            $oSCP = new SCP( $oSSH );

            // test 2, object of invalid class should return null.
            $bReturn2 = $this->equals( $oSCP->get( 's', 'xx' ), false );

            $oSSH = new SSH2( 'devsislin01.clemson.edu' );
            if ( !$oSSH->Login( $this->sUsername, $this->sPassword ) )
            {
                exit( 'Login failed.' );
            }
            $oSCP = new SCP( $oSSH );

            // test 3, get file without specifying target
            $bReturn3 = $this->equals( $oSCP->get( '', 'x' ), false );

            // test 4, get file, 'xyz' to blank destination.
            $bReturn4 = $this->equals( $oSCP->get( 'xyz', '' ), false );

            // test 5, get 1MB data
            $bReturn5 = $this->equals( $oSCP->get( 'xxx', 'local.txt' ), true );

            // clean up.
            // unlink( 'local.txt' );

            $sOutput =
            'Test 1: ' . $this->bool( $bReturn1 ) . "\r\n" .
            'Test 2: ' . $this->bool( $bReturn2 ) . "\r\n" .
            'Test 3: ' . $this->bool( $bReturn3 ) . "\r\n" .
            'Test 4: ' . $this->bool( $bReturn4 ) . "\r\n" .
            'Test 5: ' . $this->bool( $bReturn5 ) . "\r\n";

            echo "<pre>" . print_r( $sOutput, 1 ) . "</pre>";
        }

        public function test__send()
        {

        }

        public function test__receive()
        {

        }

        public function test__close()
        {

        }

        public function equals( $bBool1, $bBool2 )
        {
            return $bBool1 === $bBool2;
        }

        public function bool( $bBool )
        {
            return $bBool ? 'true' : 'false';
        }

        public function cleanup()
        {
            $oSSH = new SSH2( 'devsislin01.clemson.edu' );
            if ( !$oSSH->Login( $this->sUsername, $this->sPassword ) )
            {
                exit( 'Cleanup Login failed. All good.' );
            }
            $oSSH->Cmd( 'rm xyz' );
            $oSSH->Cmd( 'rm xxx' );
            $oSSH->Disconnect();
            unlink( 'local.txt' );
        }
    }
?>