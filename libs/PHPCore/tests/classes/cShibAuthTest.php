<?php
    require '../../../../config.php';
    require '../../classes/cShibAuth.php';

    /**
    * Test class for cShibAuth
    *
    * @author Jassiem Moore
    * @author Dylan Pruitt
    */
    class cShibAuthTest extends PHPUnit_Framework_TestCase
    {
        protected $oShibAuth;

        public function setUp()
        {
            $_SERVER['HTTP_HOST'] = 'capp.clemson.edu';
            $this->oShibAuth = new cShibAuth();
            //@TODO: need correct url
            $url = 'http://www.capp.clemson.edu';

            $_COOKIE['CUTOKENING'] = 'yes';
            $_SESSION['sUser'] = 'jassie2';
            $_SERVER['cn'] = 'jassiem';
        }
        
        public function testAuthenticate()
        {
            $this->oShibAuth->Authenticate();
            $this->assertEquals($_SESSION['sUser'], $_SERVER['cn']);
        }

        public function testIsAuthenticated()
        {
            $sReturn = $this->oShibAuth->IsAuthenticated();

            $this->assertTrue( $sReturn );
        }
    }
?>