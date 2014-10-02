<?php 
    require_once '../../../../config.php';
    require_once '../../classes/cBaseValidation.php';

    /**
    * Test class for cBaseValidation
    *
    * @author Dylan Pruitt
    */
    class cBaseValidationTest extends PHPUnit_Framework_TestCase
    {
        protected $oBaseValidation;
         
        public function setUp()
        {
            $this->oBaseValidation = new cBaseValidation();
        }
        
        public function tearDown()
        {
            unset( $this->oBaseValidation );
        }

        public function testGetErrorMessage()
        {
            $vError = $this->oBaseValidation->getErrorMessage( "Message" );
            $this->assertEquals( "Message" , $vError );
        }
        
        public function testGetValidators()
        {
            $aValidators = $this->oBaseValidation->GetValidators();
            $this->assertArrayHasKey( 'ValidateBetween' , $aValidators );   
        }
        
        public function testValidateBetween()
        {
            $vReturn = $this->oBaseValidation->ValidateBetween( '14' , '3' , '16' );
            $this->assertTrue( $vReturn );
        }
        
        public function testValidateDate()
        {
            $vReturn = $this->oBaseValidation->ValidateDate( '101112' , 'MM-DD-YY' );
            $this->assertTrue( $vReturn );
        }
        
        public function testValidateDateBetween()
        {
            $vReturn = $this->oBaseValidation->ValidateDateBetween( '14' , '3' , '16' );
            $this->assertTrue( $vReturn );
        }
        
        public function testValidateDecimal()
        {
            $vReturn = $this->oBaseValidation->ValidateDecimal( '14.5' );
            $this->assertTrue( $vReturn );
        }
        
        public function testValidateEquals()
        {
            $vReturn = $this->oBaseValidation->ValidateEquals( '14' , '14' );
            $this->assertTrue( $vReturn );
        }
        
        public function testValidateGreater()
        {
            $vReturn = $this->oBaseValidation->ValidateGreater( '16' , '11' );
            $this->assertTrue( $vReturn );
        }
        
        public function testValidateInteger()
        {
            $vReturn = $this->oBaseValidation->ValidateInteger( '14' );
            $this->assertTrue( $vReturn );
        }
        
        public function testValidateLess()
        {
            $vReturn = $this->oBaseValidation->ValidateLess( '14' , '16' );
            $this->assertTrue( $vReturn );
        }
        
        public function testValidateNotEmpty()
        {
            $vReturn = $this->oBaseValidation->ValidateNotEmpty( '14' );
            $this->assertTrue( $vReturn );
        }
        
        public function testValidateNumeric()
        {
            $vReturn = $this->oBaseValidation->ValidateNumeric( '14' );
            $this->assertTrue( $vReturn );
        }
    }
?>