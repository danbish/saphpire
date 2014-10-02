<?php
    require_once '../../../../config.php';
    require_once '../../classes/cFileUtilities.php';

    /**
    * Test class for cFileUtilities
    *
    * @author Dylan Pruitt
    */
    class cFileUtilitiesTest extends PHPUnit_Framework_TestCase
    {
        protected $oFileUtil;

        public function setUp()
        {
            $this->oFileUtil = new cFileUtilities();
        }

        public function tearDown()
        {
            unset( $this->oFileUtil );
        }
        
        public function testGetDirectoryContents()
        {
            $aActual    = $this->oFileUtil->GetDirectoryContents( sBASE_INC_PATH . '/logs' );
            $aExpected  = array( 'error.xml', 'exception.xml' );
            
            $this->assertEquals( $aActual, $aExpected );
        }

        public function testSaveSerializedData()
        {
            $sData      = "Write this to a file.";
            $vActual    = $this->oFileUtil->SaveSerializedData( sCORE_INC_PATH . '/tests/classes/data.txt', $sData );

            $this->assertGreaterThan( 1, $vActual );
        }

        public function testGetUnserializedData()
        {
            $sActual    = $this->oFileUtil->GetUnserializedData( sCORE_INC_PATH . '/tests/classes/data.txt' );
            $sExpected  = "Write this to a file.";

            $this->assertEquals( $sExpected, $sActual );
        }
    }
?>
