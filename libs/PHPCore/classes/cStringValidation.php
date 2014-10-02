<?php
    // get the string utility class
    require_once( sCORE_INC_PATH . '/classes/cStringUtilities.php' );

    // get the base class
    require_once( sCORE_INC_PATH . '/classes/cBaseValidation.php' );

    /**
     * String validation class.
     *
     * All validation functions will return either a true on success
     * or an error message on failure. All error messages are defaulted
     * but can be overridden through changing the default or passing
     * an alternate message as the last parameter of the function.
     *
     * If a default error message is not provided and an error parameter
     * is not provided, then an exception will be thrown.
     *
     * Validation function prototype:
     *
     * public function ValidateFunction( $vValueToTest [, $...] [, $sErrorMessage] )
     * {
     *     try
     *     {
     *         $vReturnValue = $sErrorMessage;
     *
     *         if( <validation test passes> )
     *         {
     *             $vReturnValue = true;
     *         }
     *
     *         return $vReturnValue;
     *     }
     *     catch( Exception $oException )
     *     {
     *          throw BubbleException( $oException );
     *     }
     * }
     *
     * @uses   cStringUtilities
     *
     * @author  Ryan Masters
     *
     * @package Core_0
     * @version 0.1
     */
    class cStringValidation extends cBaseValidation
    {
        /**
         * Default error messages for validation functions.
         *
         * Structure:
         *  array(
         *      FunctionName => Error Message
         *  )
         *
         * Messages that have _:_TAGS_:_ will replace the tags with relevant data on error.
         *
         * @var array
         */
        protected $aNewErrorMessages = array(
            'ValidateAlnum'          => 'The value provided does not contain only alphanumeric characters.',
            'ValidateAlpha'          => 'The value provided does not contain only alphabetic characters.',
            'ValidateEqualsLength'   => 'The length of the value provided, _:_VALUE_:_, does not equal _:_TEST_:_.',
            'ValidateHex'            => 'The value provided is not a hexadecimal number.',
            'ValidateLower'          => 'The value provided contains uppercase letters.',
            'ValidateMaxLength'      => 'The value provided is _:_CHARS_:_ characters and can be no more than _:_MAX_:_ characters.',
            'ValidateMinLength'      => 'The value provided is _:_CHARS_:_ characters and can be no less than _:_MIN_:_ characters.',
            'ValidatePrintable'      => 'The value provided contains non-printable characters.',
            'ValidateRegex'          => 'The value provided does not contain any matches for the pattern: _:_PATTERN_:_',
            'ValidateString'         => 'The value provided is not a string.',
            'ValidateUpper'          => 'The value provided contains lowercase letters.'
        );

        public function __construct()
        {
            $this->aErrorMessages = $this->aErrorMessages + $this->aNewErrorMessages;
        }

        /**
         * Validates whether or not the value provided
         * contains only alphabetic and numeric characters.
         *
         * @param   string          $sValue         The value to test.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateAlnum( $sValue, $sErrorMessage = null )
        {
            try
            {
                // verify that a string was provided to test
                cStringUtilities::VerifyString( $sValue );

                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( preg_match( "/^([a-z0-9])+$/i", $sValue ) )
                {
                    $vReturn = true;
                }

                return $vReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validates whether or not the value provided
         * contains only alphabetic characters.
         *
         * @param   string          $sValue         The value to test.
         * @param   boolean         $bStrict        If true, does not allow whitespace.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateAlpha( $sValue, $bStrict = false, $sErrorMessage = null )
        {
            try
            {
                // verify that a string was provided to test
                cStringUtilities::VerifyString( $sValue );

                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( $bStrict && preg_match( "/^([a-z])+$/i", $sValue ) )
                {
                    $vReturn = true;
                }
                elseif( !$bStrict && preg_match( "/^([a-z\x20])+$/i", $sValue ) )
                {
                    $vReturn = true;
                }

                return $vReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validates whether or not the length of the value provided equals the test value.
         *
         * @param   string          $sValue         The value to test.
         * @param   int | string    $vTest          The value to test against.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateEqualsLength( $sValue, $vTest, $sErrorMessage = null )
        {
            try
            {
                // verify that a string was provided to test
                cStringUtilities::VerifyString( $sValue );

                // check if the test value is numeric
                if( !is_numeric( $vTest ) )
                {
                    throw new Exception( 'Value provided is not numeric: <pre>' . print_r( $vTest, true ) . '</pre>' );
                }
                elseif( is_string( $vTest ) )
                {
                    // get the value as a float if possible
                    if( is_float( $vTest ) )
                    {
                        $vTest = floatval( $vTest );
                    }
                    // get the integer value
                    else
                    {
                        $vTest = intval( $vTest );
                    }
                }

                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( strlen( $sValue ) === $vTest )
                {
                    $vReturn = true;
                }
                // replace tags in error message
                else
                {
                    $vReturn = str_replace(
                        array(
                            '_:_VALUE_:_',
                            '_:_TEST_:_'
                        ),
                        array(
                            $sValue,
                            $vTest
                        ),
                        $vReturn
                    );
                }

                return $vReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validates whether or not the value provided is a hexadecimal number.
         *
         * @param   string          $sValue         The value to test.
         * @param   boolean         $bStrict        Flag for a strict check. If false, allows '#' at beginning of $sValue.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateHex( $sValue, $sStrict = 'false', $sErrorMessage = null )
        {
            try
            {
                // verify that a string was provided to test
                cStringUtilities::VerifyString( $sValue );

                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // initialize  the check to continue validation
                $bContinueCheck = true;

                // if this isn't a strict check and this starts with a hex symbol
                if( $sStrict == 'false' && substr( $sValue, 0, 1 ) == '#' )
                {
                    $sValue = substr( $sValue, 1 );
                }
                elseif( $sStrict == 'true' && substr( $sValue, 0, 1 ) == '#' )
                {
                    $bContinueCheck = false;
                }

                // check if the value supplied is valid
                if( $bContinueCheck && ctype_xdigit( $sValue ) )
                {
                    $vReturn = true;
                }

                return $vReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validates whether or not the value provided contains only lowercase letters.
         *
         * @param   string          $sValue         The value to test.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateLower( $sValue, $sErrorMessage = null )
        {
            try
            {
                // verify that a string was provided to test
                cStringUtilities::VerifyString( $sValue );

                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( ctype_lower( $sValue ) )
                {
                    $vReturn = true;
                }

                return $vReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validate whether or not the length of the value
         * provided is less than or equal to the test value.
         *
         * @param   string          $sValue         The value to test.
         * @param   int | string    $vTest          The value to test against.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if $vTest is nor numeric or if an
         *                          exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateMaxLength( $sValue, $vTest, $sErrorMessage = null )
        {
            try
            {
                // check if the length provided is numeric
                if( !is_numeric( $vTest ))
                {
                    throw new Exception( 'Test value provided is not numeric: <pre>' . print_r( $vTest, true ) . '</pre>' );
                }
                elseif( is_string( $vTest ) )
                {
                    // get the float value if possible
                    if( is_float( $vTest ) )
                    {
                        $vTest = floatval( $vTest );
                    }
                    // get the integer value
                    else
                    {
                        $vTest = intval( $vTest );
                    }
                }

                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value provided is less than or equal to the length provided
                if( strlen( $sValue ) <= $vTest )
                {
                    $vReturn = true;
                }

                // if there is an error message, replace tags with
                // value string length and max string length
                if( $vReturn !== true )
                {
                    $vReturn = str_replace(
                        array(
                            '_:_CHARS_:_',
                            '_:_MAX_:_'
                        ),
                        array(
                            strlen( $sValue ),
                            $vTest
                        ),
                        $vReturn
                    );
                }

                return $vReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validate whether or not the length of the value
         * provided is less than or equal to the test value.
         *
         * @param   string          $sValue         The value to test.
         * @param   int | string    $vTest          The value to test against.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if $vTest is not numeric or if an
         *                          exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateMinLength( $sValue, $vTest, $sErrorMessage = null )
        {
            try
            {
                // check if the length provided is numeric
                if( !is_numeric( $vTest ))
                {
                    throw new Exception( 'Test value provided is not numeric: <pre>' . print_r( $vTest, true ) . '</pre>' );
                }
                elseif( is_string( $vTest ) )
                {
                    // get the float value if possible
                    if( is_float( $vTest ) )
                    {
                        $vTest = floatval( $vTest );
                    }
                    // get the integer value
                    else
                    {
                        $vTest = intval( $vTest );
                    }
                }

                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value provided is less than or equal to the length provided
                if( strlen( $sValue ) >= $vTest )
                {
                    $vReturn = true;
                }

                // if there is an error message, replace tags with
                // value string length and max string length
                if( $vReturn !== true )
                {
                    $vReturn = str_replace(
                        array(
                            '_:_CHARS_:_',
                            '_:_MIN_:_'
                        ),
                        array(
                            strlen( $sValue ),
                            $vTest
                        ),
                        $vReturn
                    );
                }

                return $vReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validates whether or not the value provided
         * contains only printable characters.
         *
         * @param   string          $sValue         The value to test.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidatePrintable( $sValue, $sErrorMessage = null )
        {
            try
            {
                // verify that a string was provided to test
                cStringUtilities::VerifyString( $sValue );

                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( ctype_print( $sValue ) )
                {
                    $vReturn = true;
                }

                return $vReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validates whether or not the value provided
         * matches the pattern provided.
         *
         * @param   string          $sValue         The value to test.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateRegex( $sValue, $sPattern, $sErrorMessage = null )
        {
            try
            {
                // verify that a string was provided to test
                cStringUtilities::VerifyString( $sValue );

                // verify that a string was provided as a pattern
                cStringUtilities::VerifyString( $sPattern );

                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( preg_match( $sPattern, $sValue ) )
                {
                    $vReturn = true;
                }
                // if there is an error message, replace pattern tag
                else
                {
                    $vReturn = str_replace(
                        array(
                            '_:_PATTERN_:_'
                        ),
                        array(
                            $sPattern
                        ),
                        $vReturn
                    );
                }

                return $vReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validates whether or not the value provided is a string.
         *
         * @param   mixed           $vValue         The value to test.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateString( $vValue, $sErrorMessage = null )
        {
            try
            {
                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( is_string( $vValue ) )
                {
                    $vReturn = true;
                }

                return $vReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validates whether or not the value provided contains only uppercase letters.
         *
         * @param   string          $sValue         The value to test.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateUpper( $sValue, $sErrorMessage = null )
        {
            try
            {
                // verify that a string was provided to test
                cStringUtilities::VerifyString( $sValue );

                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( ctype_upper( $sValue ) )
                {
                    $vReturn = true;
                }

                return $vReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>