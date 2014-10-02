<?php
    require_once '../../../../config.php';
    require_once '../../classes/cBaseBusiness.php';

    /**
    * Test class for cBaseBusiness
    *
    * @author Dylan Pruitt
    */
    class cBaseBusinessTest extends PHPUnit_Framework_TestCase
    {
        protected $oBaseBusiness;

        public function setUp()
        {
            $this->oBaseBusiness = new cBaseBusiness();
        }

        public function tearDown()
        {
            unset( $this->oBaseBusiness );
        }

        public function testHandleForm()
        {
            $_POST[ 'submit' ] = 1;
            $this->oBaseBusiness->HandleForm();
        }
    }
?>