<?php
    require_once '../../../../config.php';
    require_once '../../classes/cArrayUtilities.php';

    /**
    * Test class for cArrayUtilities
    *
    * @author Dylan Pruitt
    */
    class cArrayUtilitiesTest extends PHPUnit_Framework_TestCase
    {
        protected $oArrayUtilities;
         
        public function setUp()
        {
            $this->oArrayUtilities = new cArrayUtilities();
        }
        
        public function tearDown()
        {
            unset( $this->oArrayUtilities );
        }

        /**
        * Function to use while testing ApplyFunctionRecursively function.
        */
        private function double( $a )
        {
            $a = $a * 2;
        }

        public function testApplyFunctionsRecursively()
        {

            $aFunctions = array( array( $this, 'double' ) );
            $aActual    = array( 1, 2, 3, 4, 5 );
            $aExpected  = array( 2, 4, 6, 8 , 10 );

            $aActual    = $this->oArrayUtilities->ApplyFunctionsRecursively( $aActual, $aFunctions );

            $this->assertEquals( $aExpected, $aActual );
        }

        public function testAssociativeArrayUnshift()
        {
            $aActual    = array( 2, 3 );
            $aExpected  = array( 'a' => 1, 2, 3 );

            $aActual    = $this->oArrayUtilities->AssociativeArrayUnshift( 'a', 1, $aActual );
            
            $this->assertEquals( $aExpected, $aActual );
        }
    }
?>