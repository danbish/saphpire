<?php
    require_once '../../../../config.php';
    require_once '../../classes/cRequest.php';

    /**
    * Test class for cRequest
    *
    * @author Dylan Pruitt
    */
    class cRequestTest extends PHPUnit_Framework_TestCase
    {
        protected $oRequest;

        public function setUp()
        {
            $this->oRequest = new cRequest();
        }

        public function tearDown()
        {
            unset( $this->oRequest );
        }

        public function testSendRequest()
        {
            $sReturn = $this->oRequest->SendRequest( 'GET', 'http://www.clemson.edu' );

            $this->assertNotNull( $sReturn );
        }
    }
?>