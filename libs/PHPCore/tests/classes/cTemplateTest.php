<?php
    require '../../../../config.php';
    require_once '../../classes/cTemplate.php';

    /**
    * Test class for cTemplate
    *
    * @author DylanPruitt
    */
    class cTemplateTest extends PHPUnit_Framework_TestCase
    {
        protected $oTemplate;
         
        public function setUp()
        {
            $this->oTemplate = new cTemplate();
        }
        
        public function tearDown()
        {
            unset( $this->oTemplate );
        }

        public function testAddTemplateDirectories()
        {
            $aParams = array( sCORE_INC_PATH . '/js', sCORE_INC_PATH . '/includes' );
            $this->oTemplate->AddTemplateDirectories( $aParams );
            
            $aActual    = $this->oTemplate->GetPath();
            $aExpected  = array( sCORE_INC_PATH . '/js/', sCORE_INC_PATH . '/includes/' );

            $this->assertEquals( $aActual, $aExpected );
        }

        /**
        * @depends testAddTemplateDirectories
        */
        public function testLoad()
        {
            $aParams = array( sCORE_INC_PATH . '/tests/classes' );
            $this->oTemplate->AddTemplateDirectories( $aParams );
            
            $sReturn = $this->oTemplate->load( 'test.html' );
            $bReturn = is_string( $sReturn );
            $this->assertTrue( $bReturn );
        }

        /**
        * @depends testAddTemplateDirectories
        * 
        */
        public function testReplace()
        {
            $aParams = array( sCORE_INC_PATH . '/tests/classes' );
            $this->oTemplate->AddTemplateDirectories( $aParams );

            $aReplace = array(  'tmpl' => 'test.html',
                                '_:_TAG_:_' => 'A tag was here.' );

            $sReturn = $this->oTemplate->replace( $aReplace );
            $bReturn = is_string( $sReturn );
            $this->assertTrue( $bReturn );
        }
    }
?>