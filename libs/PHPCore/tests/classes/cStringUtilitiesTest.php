<?php 
    require_once '../../classes/cStringUtilities.php';

    /**
    * Test class for cStringUtilities
    *
    * @author Dylan Pruitt
    */
    class cStringUtilitiesTest extends PHPUnit_Framework_TestCase
    {
        protected $oStringUtilities;
         
        public function setUp()
        {
            $this->oStringUtilities = new cStringUtilities();
        }
        
        public function tearDown()
        {
            unset( $this->oStringUtilities );
        }
        
        public function testVerifyString()
        {  
            $sTest = 'test';
            $this->assertEquals( $this->oStringUtilities->VerifyString( $sTest ), true );
        }
        
        public function testUnderscore()
        {
            $sResult = $this->oStringUtilities->Underscore( "TestTwo" );
            $this->assertEquals( $sResult , "test_two" );
        }
        
        public function testHumanize()
        {
            $sResult = $this->oStringUtilities->Humanize( "test_two" );
            $this->assertEquals( $sResult , "Test Two" );
        }
        
        public function testReplaceWhitespace()
        {
            $sResult = $this->oStringUtilities->ReplaceWhitespace( "T est Two" , '' );
            $this->assertEquals( $sResult , "TestTwo" );
        }
        
        public function testParseSchemeFromUrl()
        {
            $sResult = $this->oStringUtilities->ParseSchemeFromUrl( "http://www.clemson.edu" );
            $this->assertEquals( $sResult , "http" );
        }
        
        public function testSplitUrl()
        {
            $aResult    = $this->oStringUtilities->SplitUrl( "http://www.clemson.edu/test/scheme/one" );
            $aExpected  = array( 'scheme' => 'http',
                                'host' => 'www.clemson.edu',
                                'path' => '/test/scheme/one');
            
            $this->assertEquals( $aResult , $aExpected );
        }
    }
?>