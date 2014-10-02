<?php
    require '../../../../config.php';
    require '../../classes/cFormUtilities.php';

    /**
    * Test class for cFormUtilities
    *
    * @author Dylan Pruitt
    */
    class cFormUtilitiesTest extends PHPUnit_Framework_TestCase
    {
        protected $oFormUtil;
        
        public function setUp()
        {
            $this->oFormUtil = new cFormUtilities();    
            $_POST = array();
            $_GET = array();
        }
        
        /**
         * Checks if GetElementValidators function can handle incorrect validators
         */
        public function testRejectInvalidValidators()
        {
            $aValidators = array
            (
                "Chucky" => "Cheese",
                "Batman" => "Robin",
                "Run" => "Away"
            );
            
            $aVar = $this->oFormUtil->GetElementValidators($aValidators);
            //validators are invalid so it shouldn't return a filled array
            $this->assertEmpty($aVar);
            
            //test for valid validators
            $aValidators = array
            (
                'element1' => 'notempty, maxlen = 24',
                'element2' => 'int, greater = 10'
            );
            $aVar = $this->oFormUtil->GetElementValidators($aValidators);
            
            $this->assertNotEmpty($aVar);
        }
        
        /**
         * Checks if GetAllValidatorsFromString function can handle incorrect validators with valid element
         */
        public function testRejectInvalidValidators2()
        {
            $sValidators = 'textarea:chocolate, brightness=255, luminescence=12|13';
            $aElements = $this->oFormUtil->GetAllValidatorsFromString($sValidators);
            //element and validators are invalid so it shouldn't return a filled array
            $this->assertEmpty($aElements);
            
            //test for valid validators
            $sValidators = 'textarea:notempty, minlen=20, maxlen=50';
            $aElements = $this->oFormUtil->GetAllValidatorsFromString($sValidators);
            $this->assertNotEmpty($aElements);
            
        }
        
        /**
         * Checks if isFormSubmitted() function correctly detects submitted form
         */
        public function testForFormSubmission()
        {
            $_POST['submit'] = 'true';
            //test for post submission
            $this->assertTrue($this->oFormUtil->IsFormSubmitted());
            $_POST = array();
            $_GET['submit'] = 'true';
            //test for GET submission
            $this->assertTrue($this->oFormUtil->IsFormSubmitted());
            $_GET = array();
            //test for no form submission
            $this->assertFalse($this->oFormUtil->IsFormSubmitted());
        }
        
        /**
         * Checks if getFormData() correctly gets form data
         */
        public function testFormDataRetrieval()
        {
            $this->assertNotEmpty($this->oFormUtil->GetFormData());
        }
        
        /**
         * Checks if getCleanFormData() function removes validators properly
         */
        public function testFormCleaning()
        {
            $key = "textarea: notempty, maxlen=4000";
            $data = array($key => "Textarea info.");
            $data = $this->oFormUtil->getCleanFormData($data);
            $this->assertArrayNotHasKey($key, $data);
        }
        
        /**
         * Tests the GetErrors() function
         */
        public function testGetErrors()
        {
            $this->assertNotEmpty($this->oFormUtil->GetErrors());
        }
        
        /**
         * Tests the validate function.
         */
        public function testValidate()
        {
            $data = array 
            (
                "textarea" =>"notempty, maxlen=4000",
                "input" => "email"
            );
            
            $badData = array
            (
                "bunnies" => "noempty, maxlen=a",
                "input" => "maxlen=-2, lower"
            );
            
            $this->assertEmpty($this->oFormUtil->Validate($data));
            $this->assertNotEmpty($this->oFormUtil->Validate($badData));
        }
    }
?>