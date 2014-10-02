<?php
    require '../../../../config.php';
    require_once '../../classes/cXmlUtilities.php';

    /**
    * Test class for cXmlUtilities
    *
    * @author DylanPruitt
    */
    class cXmlUtilitiesTest extends PHPUnit_Framework_TestCase
    {
        protected $oXmlUtilities;
         
        public function setUp()
        {
            $this->oXmlUtilities = new cXmlUtilities();
        }
        
        public function tearDown()
        {
            unset( $this->oXmlUtilities );
        }
        
        public function testToArray()
        {
            $sXML = 
            '<outer>
                <inner>something</inner>
            </outer>';

            $aActual = $this->oXmlUtilities->ToArray( $sXML );
            $aExpected = array( 'inner' => 'something' );

            $this->assertEquals( $aExpected, $aActual );
        }
        
        public function testReadArrayFromFile()
        {
            $aActual = $this->oXmlUtilities->ReadArrayFromFile( 'test.xml' );
            $aExpected = array( 'title' => 'Forty What?',
                                'from' => 'Joe',
                                'to' => 'Jane',
                                'body' => "I know that's the answer -- but what's the question?");

            $this->assertEquals( $aExpected, $aActual );
        }

        public function testArrayToXML()
        {
            $aInput = array( 'a' => 'A', 'b' => 'B' );
            $sActual = $this->oXmlUtilities->ArrayToXML( 'document', $aInput );
            $sActual = preg_replace('~>\s+<~', '><', $sActual);
            $sActual = trim( $sActual );
            $sExpected = '<?xml version="1.0"?><document><a>A</a><b>B</b></document>';

            $this->assertEquals( $sExpected, $sActual );
        }
    }
?>