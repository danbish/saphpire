<?php
    require_once '../../config.php';
    require_once '../../classes/cBaseConfig.php';

    /**
    * Test class for cBaseConfig
    */
    class cBaseConfigTest extends PHPUnit_Framework_TestCase
    {
        protected $oBaseConfig;

        public function setUp()
        {
            $this->oBaseConfig = new cBaseConfig();
        }
        
        /**
         * Tests the ReadIni() function.
         */
        public function testReadFromIni()
        {
            $sFile = 'php.ini';
            $aIniVals = $this->oBaseConfig->ReadIni($sFile);
            $this->assertArrayHasKey('200', $aIniVals);
        }
        
        /**
         * Tests the Write() function
         * @depends testReadFromIni
         */
        public function testWrite()
        {
            $sFile = 'php.ini';
            $myFile = 'holdContents.txt';
            //get config settings
            $aContents = $this->oBaseConfig->ReadIni($sFile);
            
            $sContents = implode(" ", $aContents);
            $this->oBaseConfig->Write($myFile, $sContents);
            $myContents = file_get_contents(sBASE_INC_PATH . '/configs/holdContents.txt');
            
            $this->assertNotEmpty($myContents);   
        }   
    }
?>