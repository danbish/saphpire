<?php
    require_once '../../classes/cDevPresentation.php';

    /**
    * Test class for cDevPresentation
    *
    * @author Dylan Pruitt
    */
    class cDevPresentationTest extends PHPUnit_Framework_TestCase
    {
        protected $oDevPresentation;

        public function setUp()
        {
            $this->oDevPresentation = new cDevPresentation();
        }

        public function tearDown()
        {
            unset( $this->oDevPresentation );
        }

        public function testFormatXmlLogNode()
        {
            $this->markTestIncomplete();
        }

        public function testFormatXmlLogContents()
        {
            $this->markTestIncomplete();
        }

        public function testGetLogViewPage()
        {
            $this->markTestIncomplete();
        }

        public function testGetConfigViewPage()
        {
            $this->markTestIncomplete();
        }

        public function testFormatXmlTraceNode()
        {
            $this->markTestIncomplete();
        }

        public function testFormatExceptionLogContents()
        {
            $this->markTestIncomplete();
        }

        public function testGetExceptionLogPage()
        {
            $this->markTestIncomplete();
        }

        public function testGetPulsePage()
        {
            $this->markTestIncomplete();
        }

        public function testGetDevConsolePage()
        {
            $this->markTestIncomplete();
        }
    }
?>