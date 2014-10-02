<?php
    require_once '../../../../config.php';
    require_once '../../classes/cClemsonValidation.php';

    /**
    * Test class for cClemsonValidation
    *
    * @author Dylan Pruitt
    */
    class cClemsonValidationTest extends PHPUnit_Framework_TestCase
    {
        protected $oClemsonValidation;

        public function setUp()
        {
            $this->oClemsonValidation = new cClemsonValidation();
        }

        public function tearDown()
        {
            unset( $this->oClemsonValidation );
        }

        public function testValidateCuid()
        {
            $sCuid      = '392048503';
            $bReturn    = $this->oClemsonValidation->ValidateCuid( $sCuid );

            $this->assertTrue( $bReturn );
        }

        public function testValidateXid()
        {
            $sXid      = 'c93820184';
            $bReturn   = $this->oClemsonValidation->ValidateXid( $sXid );

            $this->assertTrue( $bReturn );
        }

        public function testValidateDeptCode()
        {
            $sDeptCode  = '1234';
            $bReturn    = $this->oClemsonValidation->ValidateDeptCode( $sDeptCode );

            $this->assertTrue( $bReturn );
        }

        public function testValidateTerm()
        {
            $sTerm     = '101990';
            $bReturn   = $this->oClemsonValidation->ValidateTerm( $sTerm );

            $this->assertTrue( $bReturn );
        }

        public function testValidateEmplId()
        {
            $sEmplId   = '123456';
            $bReturn   = $this->oClemsonValidation->ValidateEmplId( $sEmplId );

            $this->assertTrue( $bReturn );
        }

        public function testValidateMajor()
        {
            $sMajor    = '123';
            $bReturn   = $this->oClemsonValidation->ValidateMajor( $sMajor );

            $this->assertTrue( $bReturn );
        }
    }
?>