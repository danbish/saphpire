<?php
    require_once '../../../../config.php';
    require_once '../../classes/cBasePresentation.php';

    /**
    * Test class for cBasePresentation
    *
    * @author Dylan Pruitt
    */
    class cBasePresentationTest extends PHPUnit_Framework_TestCase
    {
        protected $oBasePresentation;

        public function setUp()
        {
            $this->oBasePresentation = new cBasePresentation();
        }

        public function tearDown()
        {
            unset( $this->oBasePresentation );
        }

        public function testBuildOptions()
        {
            $aOptions   = array( 1, 2, 3 );
            $sActual    = $this->oBasePresentation->BuildOptions( $aOptions );
            $sExpected  = '<option value="0" _:_ATTRS_:_>1</option><option value="1" _:_ATTRS_:_>2</option><option value="2" _:_ATTRS_:_>3</option>';

            $this->assertEquals( $sActual, $sExpected );
        }

        public function testBuildElementErrors()
        {
            $aErrors    = array( 'error1' =>
                                    array( 'humanized' => 'error1',
                                           'errors' =>
                                                array( 0 => 'Error 1',
                                                       1 => 'Error 2')));

            $sActual    = $this->oBasePresentation->BuildElementErrors( $aErrors );
            $sExpected  = 'error1:<br />&nbsp;&nbsp;&nbsp;Error 1<br />&nbsp;&nbsp;&nbsp;Error 2<br /><br />';

            $this->assertEquals( $sActual, $sExpected );
        }

        public function testGetForm()
        {
            $aData      = array();
            $aErrors    = array();

            $sActual    = $this->oBasePresentation->GetForm( 'form.html', $aData, $aErrors );

            // strip all white space from string
            $sActual    = preg_replace( '/\s+/', '', $sActual );
            $sExpected  = '<div><div>_:_ERRORS_:__:_FORM_:_</div></div>';

            $this->assertEquals( $sActual, $sExpected );
        }

        public function testPopulateLayout()
        {
            $sActual = $this->oBasePresentation->PopulateLayout();

            // strip all white space from string
            $sActual    = preg_replace( '/\s+/', '', $sActual );
            $sExpected  = '<html></html>';

            $this->assertEquals( $sActual, $sExpected );
        }

        public function testGetErrorPage()
        {
            $sActual    = $this->oBasePresentation->GetErrorPage();

            // remove all whitespace from around html tags
            $sActual    = preg_replace( '/\s+/', '', $sActual );

            $sExpected  = '<html><body>ErrorAnunexpectederrorhasoccurred.</body></html>';

            $this->assertEquals( $sActual, $sExpected );
        }
    }
?>