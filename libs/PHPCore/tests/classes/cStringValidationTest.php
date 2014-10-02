<?php 
    require_once '../../../../config.php';
    require_once '../../classes/cStringValidation.php';

    /**
    * Test class for cStringValidation
    *
    * @author Dylan Pruitt
    */
    class cStringValidationTest extends PHPUnit_Framework_TestCase
    {
        protected $oStringValidation;
         
        public function setUp()
        {
            $this->oStringValidation = new cStringValidation();
        }
        
        public function tearDown()
        {
            unset( $this->oStringValidation );
        }

        public function testValidateAlnum()
        {
            $bReturn = $this->oStringValidation->ValidateAlnum( "2sldfjdk" );
            $this->assertTrue( $bReturn );
        }
        
        public function testValidateAlpha()
        {
            $bReturn = $this->oStringValidation->ValidateAlpha( "adfjdk" );
            $this->assertTrue( $bReturn );
        }
        
        public function testValidateEqualsLength()
        {
            $bReturn = $this->oStringValidation->ValidateEqualsLength( "adfjdk" , 6 );
            $this->assertTrue( $bReturn );
        }
        
        public function testValidateHex()
        {
            $bReturn = $this->oStringValidation->ValidateHex( "#000" );
            $this->assertTrue( $bReturn );
        }
        
        public function testValidateLower()
        {
            $bReturn = $this->oStringValidation->ValidateLower( "adfjdk" );
            $this->assertTrue( $bReturn );
        }
        
        public function testValidateMaxLength()
        {
            $bReturn = $this->oStringValidation->ValidateMaxLength( "adfjdk" , 9 );
            $this->assertTrue( $bReturn );
        }
        
        public function testValidateMinLength()
        {
            $bReturn = $this->oStringValidation->ValidateMinLength( "adfjdk" , 3 );
            $this->assertTrue( $bReturn );
        }
        
        public function testValidatePrintable()
        {
            $bReturn = $this->oStringValidation->ValidatePrintable( "adfjdk" );
            $this->assertTrue( $bReturn );
        }
        
        public function testValidateRegex()
        {
            $bReturn = $this->oStringValidation->ValidateRegex( "adfjdk" , '([a-z0-9])' );
            $this->assertTrue( $bReturn );
        }
        
        public function testValidateString()
        {
            $bReturn = $this->oStringValidation->ValidateString( "adfjdk" );
            $this->assertTrue( $bReturn );
        }
        
        public function testValidateUpper()
        {
            $bReturn = $this->oStringValidation->ValidateUpper( "AFLKAS" );
            $this->assertTrue( $bReturn );
        }
    }
?>