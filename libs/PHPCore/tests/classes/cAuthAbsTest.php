<?php
    require_once '../../../../config.php';
    require '../../classes/cAuthAbs.php';

    /**
    * Test class for cAuthAbs
    *
    * @author Dylan Pruitt
    */
    class cAuthTest extends PHPUnit_Framework_TestCase
    {
        protected $oAuthAbs;

        public function setUp()
        {
            $this->oAuthAbs = new cAuthAbs();
        }
        
        /**
        * @expectedException Exception
        */
        public function testGetAuthObj()
        {
            $oReturn = $this->oAuthAbs->GetAuthObj( 'CUTOKENAUTH' );
            $this->assertInstanceOf( 'cCUTokenAuth', $oReturn );

            $oReturn = $this->oAuthAbs->GetAuthObj( 'SHIB' );
            $this->assertInstanceOf( 'cShibAuth', $oReturn );

            // Exception should be thrown here.
            $oReturn = $this->oAuthAbs->GetAuthObj( 'AAA' );
        }
    }
?>    