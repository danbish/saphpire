<?php
    require_once '../../../../config.php';
    require_once '../../classes/cBaseAuth.php';

    /**
    * Test class for cBaseAuth
    *
    * @author Jassiem Moore
    * @author DylanPruitt
    */
    class cBaseAuthTest extends PHPUnit_Framework_TestCase
    {
        protected $oBaseAuth;

        public function setUp()
        {
            $_SERVER['HTTP_HOST'] = 'capp.clemson.edu';
            $this->oBaseAuth = new cBaseAuth();
            $sUser = 1;
        }
        
        public function testIsAuthenticated()
        {
            $this->assertFalse($this->oBaseAuth->IsAuthenticated());
            $_SESSION['sUser'] = 'jassie2';
            $this->assertTrue($this->oBaseAuth->IsAuthenticated());
        }

        public function testGetUser()
        {
            $sUser = $this->oBaseAuth->GetUser();
            $this->assertEquals( $sUser, null );

            $_SESSION[ 'sUser' ] = 'dpruit2';
            $sUser = $this->oBaseAuth->GetUser();
            $this->assertEquals( $sUser, 'dpruit2' );
        }
        
        public function testSetUser()
        {
            try
            {
                $this->oBaseAuth->SetUser($sUser);
            }
            catch(Exception $e)
            {
                $sUser = 'jassie2';
                $this->oBaseAuth->SetUser($sUser);
                $this->assertEquals($_SESSION['sUser'], $sUser);
                
                return;
            }
            
            $this->fail('Expected exception was not thrown.');
        }
        
        public function testIsSecure()
        {
            $_SERVER['HTTPS'] = 'yes';
            $this->assertTrue($this->oBaseAuth->IsSecure());
            unset($_SERVER['HTTPS']);
            $this->assertFalse($this->oBaseAuth->IsSecure());
        }
        
        public function testGetProtocol()
        {
            $sProtocol;
            $sProtocol = $this->oBaseAuth->GetProtocol();
            $this->assertEquals($sProtocol, 'http');
            
            $_SERVER['HTTPS'] = 'yes';
            $this->oBaseAuth->IsSecure();
            $sProtocol = $this->oBaseAuth->GetProtocol();
            $this->assertEquals($sProtocol, 'https');
        }
    }
?>