<?php
    require_once '../../../../config.php';
    require_once '../../classes/cLogger.php';

    /**
    * Test class for cLogger
    *
    * @author Dylan Pruitt
    */
    class cLoggerTest extends PHPUnit_Framework_TestCase
    {
        protected $oLogger;

        public function setUp()
        {
            $_SERVER['HTTP_HOST'] = 'capp.clemson.edu';
            $this->oLogger = new cLogger();
        }

        public function tearDown()
        {
            unset( $this->oLogger );
        }
        
        public function testLog()
        {
            $this->oLogger->Log( 'error on this line' );
            $this->assertFileExists(sBASE_INC_PATH . '/logs/error.xml');
        }

        public function testLogException()
        {
            $this->markTestIncomplete();
        }

        public function testGetLogDirectory()
        {
            $sDir = $this->oLogger->GetLogDirectory();
            $this->assertEquals('logs', $sDir);
        }
        
        public function testLogFiles()
        {
            $aLogs = $this->oLogger->GetLogFiles();
            $this->assertNotEmpty($aLogs);
        }

        public function testGetLogContents()
        {
            $sFile = 'info.xml';
            $sFirstStr = file_get_contents(sBASE_INC_PATH . '/logs/info.xml');
            $sSecStr = $this->oLogger->GetLogContents($sFile);
            $this->assertEquals($sFirstStr, $sSecStr);
        }
        
        public function testClearXmlLogBefore()
        {
            $sFile = 'info.xml';
            $iDays = 1;
            $bSuccess = $this->oLogger->ClearXmlLogBefore($sFile, $iDays);
            $this->assertTrue( $bSuccess );
        }

        public function testClearLogFile()
        {
            $sFile = 'info.xml';
            $this->oLogger->ClearLogFile($sFile);
            $sNewContents = file_get_contents(sBASE_INC_PATH . '/logs/' . $sFile );
            $this->assertEmpty( $sNewContents );
        }

        public function testAddCodeAndMessage()
        {
            $this->oLogger->AddCodeAndMessage( 479, 'Custom Error Message' );
            $aErrorMessages = $this->oLogger->GetCodesAndMessages();
            $aExpected      = array( array( 479, 'Custom Error Message' ) );
            $this->assertEquals( $aExpected, $aErrorMessages );
        }

        public function testGetCodesAndMessages()
        {
            $this->markTestIncomplete();
        }
    }
?>