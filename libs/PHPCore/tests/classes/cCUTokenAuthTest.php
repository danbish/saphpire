<?php
    require '../../config.php';
    require '../../classes/cCUTokenAuth.php';

    /**
    * Test class for cCUTokenAuth
    *
    * @author Jassiem Moore
    */
    class cCUTokenAuthTest extends PHPUnit_Framework_TestCase
    {
        protected $oCUTokenAuth;

        public function setUp()
        {
            $_SERVER['HTTP_HOST'] = 'capp.clemson.edu';
            $this->oCUTokenAuth = new cCUTokenAuth();
            //@TODO: need correct url
            $url = 'capp.clemson.edu';
            
            $_COOKIE['CUTOKENING'] = 'yes';
        }
        
        public function testAuthentication()
        {
            //if authentication failed then user is redirected
            $this->oCUTokenAuth->Authenticate();

            $ch = curl_init();
            
            //Set the URL
            curl_setopt($ch, CURLOPT_URL, $url);
            //Enable curl response
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $result = curl_exec($ch);

            //redirection leaves response body empty
            $this->assertEmpty($result);
            
            //test successful authentication
            //@TODO: need a valid token value
            $_COOKIE['CUTOKEN'] = 'proper-token';
            $this->oCUTokenAuth->Authenticate();
            $result = curl_exec($ch);
            
            //if successful there should be no redirection, response body isn't empty
            $this->assertNotEmpty($result);
            
            curl_close($ch);
        }
        
        public function testClearCookie()
        {
            $this->oCUTokenAuth->ClearCookie();
            $this->assertEmpty($_COOKIE['CUTOKENING']);
        }
    }
?>