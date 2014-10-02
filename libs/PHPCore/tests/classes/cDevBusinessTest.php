<?php
    require_once '../../../../config.php';
    require_once '../../classes/cDevBusiness.php';

    /**
    * Test class for cDevBusiness
    *
    * @author Dylan Pruitt
    */
    class cDevBusinessTest extends PHPUnit_Framework_TestCase
    {
        protected $oDevBusiness;

        public function setUp()
        {
            $this->oDevBusiness = new cDevBusiness();
        }

        public function tearDown()
        {
            unset( $this->oDevBusiness );
        }

        public function testHandleLogForm()
        {
            //# $aActual = $this->oDevBusiness->HandleLogForm();

            // Function is attempting to get first log file in folder, but is trying to open /logs directory.
            // cLogger::GetLogFiles() is not working correctly.
        }

        public function testHandleConfigForm()
        {
           $aActual = $this->oDevBusiness->HandleConfigForm();
           $aExpected = array( 'app.xml', 'contacts.xml', 'errors.xml', 'hosts.xml' );

           $this->assertEquals( $aActual[ 2 ], $aExpected );
        }

        public function testGetBeacon()
        {
            $sActual = $this->oDevBusiness->GetBeacon();
            $bCheckHash = preg_match( '/^[a-f0-9]{32}$/', $sActual );

            $this->assertEquals( $bCheckHash, 1 );
        }

        public function testGetCoreBeancon()
        {
            $this->markTestIncomplete();

            // Function not yet implemented.
        }

        public function testCheckPort()
        {
            $bCheckPort = $this->oDevBusiness->CheckPort( 'www.clemson.edu', '80' );
            $this->assertTrue( $bCheckPort );
        }

        public function testGetDatabaseConnections()
        {
            $aActual = $this->oDevBusiness->GetDatabaseConnections();
            $this->assertEmpty( $aActual );

            // Find a way to connect to databases for testing.
        }
    }
?>