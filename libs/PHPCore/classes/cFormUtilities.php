<?php
    // get validation classes
    require_once( sCORE_INC_PATH . '/classes/cBaseValidation.php' );
    require_once( sCORE_INC_PATH . '/classes/cStringValidation.php' );
    require_once( sCORE_INC_PATH . '/classes/cWebValidation.php' );
    require_once( sCORE_INC_PATH . '/classes/cClemsonValidation.php' );

    // get string utility
    require_once( sCORE_INC_PATH . '/classes/cStringUtilities.php' );

    // get the logger
    require_once( sCORE_INC_PATH . '/classes/cLogger.php' );

    /**
     * Form management class.
     *
     * Provides convenience methods for:
     *  - checking if a form has been submitted
     *  - check if a elements are valid
     *  - get error messages for invalid elements
     *
     * @author  Ryan Masters
     * @package Core_0
     * @version 0.3
     *
     * @TODO: - implement custom error messages through form input
     *        - implement filters through form input
     *        - implement JS error message handling
     */
    class cFormUtilities
    {
        /**
         * Delimiter for separating elements in HTML validation rules.
         *
         * @var string
         */
        const sELEMENT_DELIM = ';';

        /**
         * Delimiter for separating validators in HTML validation rules.
         *
         * @var string
         */
        const sVALIDATOR_DELIM = ',';

        /**
         * Delimiter for separating validator params in HTML validation rules.
         *
         * @var string
         */
        const sVALIDATOR_PARAM_DELIM = '|';

        /**
         * Delimiter for separating elements from their validators in HTML validation rules.
         *
         * @var string
         */
        const sELEMENT_VALIDATOR_DELIM = ':';

        /**
         * Delimiter for separating validator name from value(s) in HTML validation rules.
         *
         * @var string
         */
        const sVALIDATOR_VALUE_DELIM = '=';

        /**
         * Flag for whether or not to auto trim all inputs.
         *
         * @var boolean
         */
        private $bAutoTrim = true;

        /**
         * Error messages for this form.
         *
         * @var array
         */
        private $aErrors = array();

        /**
         * Mapping between validator aliases and their
         * corresponding validation functions.
         *
         * @var array
         */
        private $aValidators = array(
            // basic validation
            'between'  => array( 'class' => 'cBaseValidation', 'function' => 'ValidateBetween' ),
            'date'     => array( 'class' => 'cBaseValidation', 'function' => 'ValidateDate' ),
            'decimal'  => array( 'class' => 'cBaseValidation', 'function' => 'ValidateDecimal' ),
            'equals'   => array( 'class' => 'cBaseValidation', 'function' => 'ValidateEquals' ),
            'greater'  => array( 'class' => 'cBaseValidation', 'function' => 'ValidateGreater' ),
            'int'      => array( 'class' => 'cBaseValidation', 'function' => 'ValidateInteger' ),
            'less'     => array( 'class' => 'cBaseValidation', 'function' => 'ValidateLess' ),
            'required' => array( 'class' => 'cBaseValidation', 'function' => 'ValidateRequired' ),
            'numeric'  => array( 'class' => 'cBaseValidation', 'function' => 'ValidateNumeric' ),

            // string validation
            'alpha'    => array( 'class' => 'cStringValidation', 'function' => 'ValidateAlpha' ),
            'alnum'    => array( 'class' => 'cStringValidation', 'function' => 'ValidateAlnum' ),
            'eqlen'    => array( 'class' => 'cStringValidation', 'function' => 'ValidateEqualsLength' ),
            'hex'      => array( 'class' => 'cStringValidation', 'function' => 'ValidateHex' ),
            'lower'    => array( 'class' => 'cStringValidation', 'function' => 'ValidateLower' ),
            'maxlen'   => array( 'class' => 'cStringValidation', 'function' => 'ValidateMaxLength' ),
            'minlen'   => array( 'class' => 'cStringValidation', 'function' => 'ValidateMinLength' ),
            'print'    => array( 'class' => 'cStringValidation', 'function' => 'ValidatePrintable' ),
            'regex'    => array( 'class' => 'cStringValidation', 'function' => 'ValidateRegex' ),
            'upper'    => array( 'class' => 'cStringValidation', 'function' => 'ValidateUpper' ),

            // web validation
            'email'    => array( 'class' => 'cWebValidation', 'function' => 'ValidateEmail' ),
            'url'      => array( 'class' => 'cWebValidation', 'function' => 'ValidateURL' ),
            'ipv4'     => array( 'class' => 'cWebValidation', 'function' => 'ValidateIPv4' ),
            'ipv6'     => array( 'class' => 'cWebValidation', 'function' => 'ValidateIPv6' ),

            // clemson validation
            'cuid'     => array( 'class' => 'cClemsonValidation', 'function' => 'ValidateCuid' ),
            'xid'      => array( 'class' => 'cClemsonValidation', 'function' => 'ValidateXid' ),
            'dept'     => array( 'class' => 'cClemsonValidation', 'function' => 'ValidateDeptCode' ),
            'emplid'   => array( 'class' => 'cClemsonValidation', 'function' => 'ValidateEmplid' ),
            'term'     => array( 'class' => 'cClemsonValidation', 'function' => 'ValidateTerm' ),
            'major'    => array( 'class' => 'cClemsonValidation', 'function' => 'ValidateMajor' ),
        );

        /**
         * Name of input element for multidimensional array of
         * elements and their associated validators.
         *
         * Ex. <input type="hidden" name="validators[element][validator]" value="validator-param">
         *
         * @var string
         */
        private $sMultiValidators = 'validators';

        /**
         * Name of input element for associative array of
         * elements and their associated validators.
         *
         * Ex. <input type="hidden" name="elementValidators[element]" value="validator, validator2=param">
         *
         * @var string
         */
        private $sElementValidators = 'elementValidators';

        /**
         * Name of input element for string of all
         * elements and their associated validators.
         *
         * Ex. <input type="hidden" name="validator" value="element:validator, validator2=param">
         *
         * @var string
         */
        private $sValidator = 'validator';

        /**
         * String to prepend to element name for multidimensional array of
         * elements and their associated validators.
         *
         * Ex. <input type="hidden" name="validators-element" value="validator, validator2=param">
         *
         * @var string
         */
        private $sPrependValidators = 'validators-';

        /**
         * String to append to element name for multidimensional array of
         * elements and their associated validators.
         *
         * Ex. <input type="hidden" name="element-validators" value="validator, validator2=param">
         *
         * @var string
         */
        private $sAppendValidators = '-validators';

        /**
         * Name of element input for array of elements and their associated validators.
         *
         * Ex. <input type="hidden" name="formValidators[]" value="element: validator, validator2 = 4006;">
         *
         * @var string
         */
        private $sFormValidators = 'formValidators';

        /**
         * Checks if a form has been submitted or not.
         *
         * @param   string      $sSubmitKey The name of the submit button element to check against.
         *                                  Optional and defaulted to 'submit'.
         *
         * @throws  Exception   Thrown if an exception was caught at a lower level.
         *
         * @return  boolean
         */
        public function IsFormSubmitted( $sSubmitKey = 'submit' )
        {
            try
            {
                // initialize the submitted flag to false
                $bFormSubmitted = false;

                // check if POST data has been submitted and the submit key exists
                if( !empty( $_POST ) && !empty( $_POST[ $sSubmitKey ] ) )
                {
                    $bFormSubmitted = true;
                }
                // check if GET data has been submitted and the submit key exists
                elseif( !empty( $_GET ) && !empty( $_GET[ $sSubmitKey ] ) )
                {
                    $bFormSubmitted = true;
                }

                return $bFormSubmitted;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Returns whether or not the form is valid.
         *
         * @throws  Exception   Thrown if an exception was caught at a lower level.
         *
         * @return boolean
         */
        public function IsValid()
        {
            try
            {
                // get the data that was submitted
                $aData = $this->GetFormData();

                // set the errors for this form
                $this->Validate( $aData );

                return empty( $this->aErrors );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Return the errors for this form.
         *
         * @throws  Exception   Thrown if an exception was caught at a lower level.
         *
         * @return array
         */
        public function GetErrors()
        {
            try
            {
                return array( 'elements' => $this->aErrors );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Returns all information submitted.
         *
         * @throws  Exception   Thrown if an exception was caught at a lower level.
         *
         * @return  array Returns $_POST + $_GET. POST information will override any GET values.
         */
        public function GetFormData()
        {
            try
            {
                // get the data
                return $_POST + $_GET;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Returns all submitted elements and their values with
         * validation rules stripped from element names.
         *
         * @param   array       Optional data to clean.
         *
         * @throws  Exception   Thrown if an exception was caught at a lower level.
         *
         * @return  array       Returns $_POST + $_GET. POST information will override any GET values.
         */
        public function GetCleanFormData( $aData = array() )
        {
            try
            {
                // get the data
                if( empty( $aData ) )
                {
                    $aData = $_POST + $_GET;
                }

                // remove all validators
                unset( $aData[ $this->sAppendValidators ] );
                unset( $aData[ $this->sElementValidators ] );
                unset( $aData[ $this->sFormValidators ] );
                unset( $aData[ $this->sMultiValidators ] );
                unset( $aData[ $this->sPrependValidators ] );
                unset( $aData[ $this->sValidator ] );

                // filter input names that include validator information
                // ex: contactInfo:required,maxlength=4000
                foreach( $aData as $sKey => $sValue )
                {
                    // check if we need to split this element name
                    if( strpos( $sKey, self::sELEMENT_VALIDATOR_DELIM) !== false )
                    {
                        // split element name
                        list( $sElementName, $sUnusued ) = explode( self::sELEMENT_VALIDATOR_DELIM, $sKey );

                        // save clean data
                        $aData[ trim( $sElementName ) ] = $sValue;

                        // remove old data
                        unset( $aData[ $sKey ] );
                    }
                }

                return $aData;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Convert validator string in array value
         * with array of validators and their params.
         *
         * Array structure:
         *  array(
         *      'element-name' => 'validator,validator2=param,validator3=param|param2'
         *  )
         *
         * @param   array       $aElementValidators Array of element and string of validators for that element.
         *
         * @throws  Exception   Thrown if an exception was caught at a lower level.
         *
         * @return  array       Original array with validator string converted to array.
         */
        public function GetElementValidators( $aElementValidators )
        {
            try
            {
                // remove all whitespace
                $sElementName = key( $aElementValidators );
                $sTrimmed = cStringUtilities::ReplaceWhitespace( $aElementValidators[ $sElementName ] );
                $aElementValidators[ $sElementName ] = array();

                // remove trailing comma
                if( substr( $sTrimmed, -1 ) == self::sVALIDATOR_DELIM )
                {
                    $sTrimmed = substr( $sTrimmed, 0, -1 );
                }

                // convert escaped apostrophes to be non-escaped and single quotes to double quotes
                $sNonEscaped = str_replace( "\\\\\\'", "'", $sTrimmed );
                $sNonEscaped = str_replace( "\'", '"', $sNonEscaped );

                // rip out strings and replace with tags so they won't interfere with delimiters
                $aStrings = array();
                preg_match( '/"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"/s', $sNonEscaped, $aMatches );
                if( !empty( $aMatches ) )
                {
                    $iMatchCount = count( $aMatches );
                    for( $j = 0; $j < $iMatchCount; ++$j )
                    {
                        // set strings to replace and their replacements
                        $aToReplace    = array( '_', '"' );
                        $aReplacements = array( ' ', '' );

                        // replace original with tag
                        $sNonEscaped   = str_replace( $aMatches[ $j ], "{{string-{$j}}}", $sNonEscaped );

                        // replace strings for readability add tag to list of strings
                        $aStrings[ "{{string-{$j}}}" ] = str_replace( $aToReplace, $aReplacements, $aMatches[ $j ] );
                    }
                }

                // split validators on commas
                $aTempValidators = explode( self::sVALIDATOR_DELIM, str_replace( '_', '', $sNonEscaped ) );

                // add validators to list of all validators for this element
                $iElementValidatorCount = count( $aTempValidators );
                for( $j = 0; $j < $iElementValidatorCount; ++$j )
                {
                    // set validator and its value
                    $sValidatorValue = null;
                    if( !stripos( $aTempValidators[ $j ], self::sVALIDATOR_VALUE_DELIM ) )
                    {
                        $sValidator = $aTempValidators[ $j ];
                    }
                    else
                    {
                        list( $sValidator, $sValidatorValue ) = explode( self::sVALIDATOR_VALUE_DELIM, $aTempValidators[ $j ] );
                    }

                    // make sure the validator is lowercase to work as an alias
                    $sValidator = strtolower( $sValidator );

                    // add validator and value to list for this element
                    $aElementValidators[ $sElementName ][ $sValidator ] = ( strpos( $sValidatorValue, self::sVALIDATOR_PARAM_DELIM ) ) ? explode( self::sVALIDATOR_PARAM_DELIM, $sValidatorValue ) : $sValidatorValue;

                    // replace strings as needed
                    if( !empty( $aStrings ) && is_array( $aElementValidators[ $sElementName ][ $sValidator ] ) )
                    {
                        $iParamCount = count( $aElementValidators[ $sElementName ][ $sValidator ] );
                        for( $k = 0; $k < $iParamCount; ++$k )
                        {
                            $aElementValidators[ $sElementName ][ $sValidator ][ $k ] = str_replace( array_keys( $aStrings ), $aStrings, $aElementValidators[ $sElementName ][ $sValidator ][ $k ] );
                        }
                    }
                }

                return $aElementValidators;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Convert validation string to array of elements
         * and their associated validators.
         *
         * @param   string      $sValidatorString   Ex. 'element:validator, validator2=param, validator3=param|param2'
         *
         * @throws  Exception   Thrown if an exception was caught at a lower level.
         *
         * @return  array       Array of elements and validators for each.
         */
        public function GetAllValidatorsFromString( $sValidatorString )
        {
            try
            {
                // initialize list of element validators
                $aElementValidators = array();

                // remove all whitespace
                $sTrimmed = cStringUtilities::ReplaceWhitespace( $sValidatorString );

                // remove trailing semicolon
                if( substr( $sTrimmed, -1 ) == self::sELEMENT_DELIM )
                {
                    $sTrimmed = substr( $sTrimmed, 0, -1 );
                }

                // remove trailing comma
                if( substr( $sTrimmed, -1 ) == self::sVALIDATOR_DELIM )
                {
                    $sTrimmed = substr( $sTrimmed, 0, -1 );
                }

                // convert escaped apostrophes to be non-escaped and single quotes to double quotes
                $sNonEscaped = str_replace( "\\\\\\'", "'", $sTrimmed );
                $sNonEscaped = str_replace( "\'", '"', $sNonEscaped );

                // rip out strings and replace with tags so they won't interfere with delimiters
                $aStrings = array();
                preg_match( '/"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"/s', $sNonEscaped, $aMatches );
                if( !empty( $aMatches ) )
                {
                    $iMatchCount = count( $aMatches );
                    for( $j = 0; $j < $iMatchCount; ++$j )
                    {
                        // set strings to replace and their replacements
                        $aToReplace    = array( '_', '"' );
                        $aReplacements = array( ' ', '' );

                        // replace original with tag
                        $sNonEscaped   = str_replace( $aMatches[ $j ], "{{string-{$j}}}", $sNonEscaped );

                        // replace strings for readability add tag to list of strings
                        $aStrings[ "{{string-{$j}}}" ] = str_replace( $aToReplace, $aReplacements, $aMatches[ $j ] );
                    }
                }

                // convert validator string to array
                $aValidators = explode( self::sELEMENT_DELIM , str_replace( '_', '', $sNonEscaped ) );

                // cycle through each element and add validators
                $iElementCount = count( $aValidators );
                for( $i = 0; $i < $iElementCount; ++$i )
                {
                    // split element name from validators
                    list( $sElementName, $sValidators ) = explode( self::sELEMENT_VALIDATOR_DELIM, $aValidators[ $i ] );

                    // split validators on commas
                    $aTempValidators = explode( self::sVALIDATOR_DELIM, $sValidators );

                    // add validators to list of all validators for this element
                    $iElementValidatorCount = count( $aTempValidators );
                    for( $j = 0; $j < $iElementValidatorCount; ++$j )
                    {
                        // get the validator and its value
                        $sValidatorValue = null;
                        if( !stripos( $aTempValidators[ $j ], self::sVALIDATOR_VALUE_DELIM ) )
                        {
                            $sValidator = $aTempValidators[ $j ];
                        }
                        else
                        {
                            list( $sValidator, $sValidatorValue ) = explode( self::sVALIDATOR_VALUE_DELIM, $aTempValidators[ $j ] );
                        }

                        // make sure the validator is lowercase to work as an alias
                        $sValidator = strtolower( $sValidator );

                        // add validator and value to list for this element
                        $aElementValidators[ $sElementName ][ $sValidator ] = ( strpos( $sValidatorValue, self::sVALIDATOR_PARAM_DELIM ) ) ? explode( self::sVALIDATOR_PARAM_DELIM, $sValidatorValue ) : $sValidatorValue;

                        // replace strings as needed
                        if( !empty( $aStrings ) && is_array( $aElementValidators[ $sElementName ][ $sValidator ] ) )
                        {
                            $iParamCount = count( $aElementValidators[ $sElementName ][ $sValidator ] );
                            for( $k = 0; $k < $iParamCount; ++$k )
                            {
                                $aElementValidators[ $sElementName ][ $sValidator ][ $k ] = str_replace( array_keys( $aStrings ), $aStrings, $aElementValidators[ $sElementName ][ $sValidator ][ $k ] );
                            }
                        }
                    }
                }

                return $aElementValidators;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Parses array of validation strings into an array
         * of elements and their associated validators.
         *
         * @param   array       $aInputValidators   Array of validation strings.
         *
         * @throws  Exception   Thrown if an exception was caught at a lower level.
         *
         * @return  array       Array of elements and validators for each.
         */
        public function GetFormValidators( $aInputValidators )
        {
            try
            {
                // initialize list of all elements and their validators
                $aAllElementValidators = array();

                // check if there is an array of elements
                if( !empty( $aInputValidators ) )
                {
                    // build list of all validators for each element
                    $iValidatorCount = count( $aInputValidators );
                    for( $i = 0; $i < $iValidatorCount; ++$i )
                    {
                        // if validators were supplied, add them to the list of validators
                        if( !empty( $aInputValidators[ $i ] ) )
                        {
                            // add validator and value to list for this element
                            $aAllElementValidators += $this->GetAllValidatorsFromString( $aInputValidators[ $i ] );
                        }
                    }
                }

                return $aAllElementValidators;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Parses all validators out of input data into an
         * array of elements and their associated validators.
         *
         * @param   array       $aData  Array of get submitted from a form.
         *
         * @throws  Exception   Thrown if an exception was caught at a lower level.
         *
         * @return  array       Array of elements and validators for each.
         */
        protected function GetValidators( $aData )
        {
            try
            {
                // initialize the list of validators
                $aAllElementValidators = array();

                // only try to validate if data is provided
                if( !empty( $aData ) )
                {
                    // validate all elements with validators supplied
                    foreach( $aData as $sInputName => $vInputValue )
                    {
                        // initialize  the list of parsed validators
                        $aParsedValidators = null;

                        // check for immutabe validator strings first
                        if( $sInputName == $this->sFormValidators )
                        {
                            $aParsedValidators = $this->GetFormValidators( $vInputValue );
                        }
                        elseif( $sInputName == $this->sValidator )
                        {
                            $aParsedValidators = $this->GetAllValidatorsFromString( $vInputValue );
                        }
                        elseif( $sInputName == $this->sMultiValidators )
                        {
                            $aParsedValidators = $vInputValue;
                        }
                        elseif( $sInputName == $this->sElementValidators )
                        {
                            $aParsedValidators = $this->GetElementValidators( $vInputValue );
                        }
                        elseif( strpos( $sInputName, self::sELEMENT_VALIDATOR_DELIM ) !== false )
                        {
                            $aParsedValidators = $this->GetAllValidatorsFromString( $sInputName );

                            // get element name
                            list( $sElementName, $sUnused ) = explode( self::sELEMENT_VALIDATOR_DELIM, $sInputName );

                            // add element if possible
                            if( isset( $aData[ $sElementName ] ) )
                            {
                                throw new Exception( "Element '$sInputName' is attempting to override element '$sElementName' " );
                            }
                            else
                            {
                                $aParsedValidators[ $sInputName ] = $aParsedValidators[ $sElementName ];
                                unset( $aParsedValidators[ $sElementName ] );
                            }
                        }
                        // check for mutable validator strings
                        else
                        {
                            $iPrependLength = strlen( $this->sPrependValidators );
                            $iAppendLength  = strlen( $this->sAppendValidators  );
                            if( substr( $sInputName, 0, $iPrependLength ) == $this->sPrependValidators )
                            {
                                $sElementName = substr( $sInputName, $iPrependLength );
                                $aParsedValidators = $this->GetElementValidators( array( $sElementName => $vInputValue ) );
                            }
                            elseif( substr( $sInputName, -$iAppendLength ) == $this->sAppendValidators )
                            {
                                $sElementName = substr( $sInputName, 0, $iAppendLength );
                                $aParsedValidators = $this->GetElementValidators( array( $sElementName => $vInputValue ) );
                            }
                        }

                        // add to list of all validators if possible
                        if( !empty( $aParsedValidators ) )
                        {
                            $aAllElementValidators = array_merge( $aAllElementValidators, $aParsedValidators );
                        }
                    }
                }

                return $aAllElementValidators;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Checks if the data provided builds a hash
         * that has been saved in the session.
         *
         * In other words, checks if the validation
         * rules have been tampered with.
         *
         * @param   array       $aData  Form data to validate.
         *
         * @throws  Exception   Rethrows anything that is caught.
         *
         * @return  boolean
         */
        public function CheckFormHash( array $aData )
        {
            try
            {
                // check if form hashes have been set
                if( !empty( $_SESSION[ 'form-hashes' ] ) )
                {
                    // initialize the concatenation string
                    $sConcat = '';

                    // build form hash from data that has been submitted
                    foreach( $aData as $sInput => $sValue )
                    {
                        $sConcat .= $sInput;
                    }

                    // check if the hash exists
                    if( !isset( $_SESSION[ 'form-hashes' ][ md5( $sConcat ) ] ) )
                    {
                        // log the hacking attempt
                        cLogger::Log(
                            'form-hacking',
                            'Received this data: ' . print_r( $aData, true ),
                            'Form hashes: ' . print_r( $_SESSION[ 'form-hashes' ], true )
                        );

                        return false;
                    }
                }

                return true;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validates all elements against each of their associated validators.
         *
         * @param   array       $aData
         * @param   array       $aAllElementValidators
         *
         * @throws  Exception   Thrown if an exception was caught at a lower level.
         *
         * @return  array       Array of elements and errors for each. If there
         *                      are no errors, then an empty array is returned.
         */
        protected function ValidateElements( $aData, $aAllElementValidators )
        {
            try
            {
                // check if the elements came from one of our forms
                if( !$this->CheckFormHash( $aData ) )
                {
                    return array( 'form' => 'Hacking attempt detected.' );
                }

                // initialize  list of validators
                $aErrors = array();

                // check if validators have been parsed
                if( !empty( $aAllElementValidators ) )
                {
                    // apply all parsed validators to each element
                    foreach( $aAllElementValidators as $sElementName => $aValidators )
                    {
                        // check if element exists for validation
                        if( isset( $aData[ $sElementName ] ) )
                        {
                            // get the element's value
                            $sElementValue = $aData[ $sElementName ];

                            // trim if possible
                            if( $this->bAutoTrim )
                            {
                                $sElementValue = trim( $sElementValue );
                            }

                            // if there are validators, apply them
                            if( !empty( $aValidators )
                                && ( array_key_exists( 'required', $aValidators ) || !empty( $sElementValue ) ) )
                            {
                                // apply each validator
                                foreach( $aValidators as $sValidator => $vValidatorValue )
                                {
                                    // get the validation class and function
                                    $aValidationInfo     = $this->aValidators[ $sValidator ];
                                    $sValidationClass    = $aValidationInfo[ 'class' ];
                                    $sValidationFunction = $aValidationInfo[ 'function' ];

                                    // create the validator class if it hasn't been made yet
                                    if( empty( $$sValidationClass ) )
                                    {
                                        if( class_exists( $sValidationClass ) )
                                        {
                                            $$sValidationClass = new $sValidationClass();
                                        }
                                        else
                                        {
                                            throw new Exception( 'Validation class "' . $sValidationClass . '" does not exist.' );
                                        }
                                    }

                                    // check if the validator has any values associated with it
                                    if( isset( $vValidatorValue ) )
                                    {
                                        // get the parameters
                                        $aValidationParams = is_array( $vValidatorValue ) ? $vValidatorValue : array( $vValidatorValue );
                                        array_unshift( $aValidationParams, $sElementValue );

                                        // set the validation class and function as a callback
                                        $aValidator = array( $$sValidationClass, $sValidationFunction );

                                        // call the validator with the params
                                        $vResult = call_user_func_array( $aValidator, $aValidationParams );
                                    }
                                    else
                                    {
                                        // evaluate the validator
                                        $vResult = $$sValidationClass->$sValidationFunction( $sElementValue );
                                    }

                                    // if validation failed, add to list of errors
                                    if( $vResult !== true )
                                    {
                                        if( !isset( $aErrors[ $sElementName ][ 'humanized' ] ) )
                                        {
                                            $sHumanized = $sElementName;
                                            if( strpos( $sHumanized, self::sELEMENT_VALIDATOR_DELIM ) !== false )
                                            {
                                                list( $sHumanized, $sUnused ) = explode( self::sELEMENT_VALIDATOR_DELIM, $sHumanized );
                                            }
                                            $sHumanized = trim( $sHumanized );
                                            $sHumanized = cStringUtilities::Humanize( $sHumanized );
                                        }

                                        $aErrors[ $sElementName ][ 'humanized' ] = $sHumanized;
                                        $aErrors[ $sElementName ][ 'errors' ][]  = $vResult;
                                    }
                                }
                            }
                        }
                        else
                        {
                            throw new Exception( 'Validation rules supplied for element that does not exist: ' . $sElementName . ' Rules: <pre>' . print_r( $aData, true ) . '</pre>' );
                        }
                    }
                }

                return $aErrors;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validates a list of elements.
         *
         * An element will not be validated if validation rules are not supplied for it.
         *
         * @param   array       $aData  Array of elements and their associated validators.
         *
         * @throws  Exception   Thrown if an exception was caught at a lower level.
         *
         * @return  array       Array of elements and their associated errors.
         */
        public function Validate( array $aData = array() )
        {
            try
            {
                // initialize the list of validators
                $aAllElementValidators = $this->GetValidators( $aData );

                // initialize the list of errors for all elements
                $this->aErrors = $this->ValidateElements( $aData, $aAllElementValidators );

                return $this->aErrors;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>