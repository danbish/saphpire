<?php 
    require_once '../../../../config.php';
    require_once '../../classes/cWebValidation.php';

    /**
    * Test class for cWebValidation
    *
    * @author Dylan Pruitt
    */
    class cWebValidationTest extends PHPUnit_Framework_TestCase
    {
        protected $oWebValidation;
         
        public function setUp()
        {
            $this->oWebValidation = new cWebValidation();
        }
        
        public function tearDown()
        {
            unset( $this->oWebValidation );
        }
        
        public function testValidateEmail()
        {
            $bResult = $this->oWebValidation->ValidateEmail( 'myemail@email.com' );
            $this->assertTrue( $bResult );
        }
        
        public function testValidateIPv4()
        {
            $bResult = $this->oWebValidation->ValidateIPv4( '100.0.10.1' );
            $this->assertTrue( $bResult );
        }
        
        public function testValidateIPv6()
        {
            $bResult = $this->oWebValidation->ValidateIPv6( '2001:0db8:85a3:0042:1000:8a2e:0370:7334' );
            $this->assertTrue( $bResult );
        }
        
        public function testValidateURL()
        {
            $bResult = $this->oWebValidation->ValidateURL( 'http://www.google.com' );
            $this->assertTrue( $bResult );
        }
    }
?>