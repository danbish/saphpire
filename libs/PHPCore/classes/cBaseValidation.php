<?php
    // get the string utility class
    require_once( sCORE_INC_PATH . '/classes/cStringUtilities.php' );

    /**
     * Data validation base class.
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
     * @uses    cStringUtilities
     *
     * @author  Ryan Masters
     *
     * @package Core_0
     * @version 0.1
     *
     * @TODO: Barcode, CreditCard, Hostname, Iban, Isbn, PostCode, strict email (email address valid format and it exists)
     *       URL (strict), profanity?, validate positive and negative integers/floats
     */
    class cBaseValidation
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
        protected $aErrorMessages = array(
            'ValidateBetween'  => 'The value provided is not between _:_MIN_:_ and _:_MAX_:_.',
            'ValidateDate'     => 'The value provided is not a valid date.',
            'ValidateDecimal'  => 'The value provided is not a positive or negative decimal number.',
            'ValidateEquals'   => '_:_VALUE_:_ does not equal _:_TEST_:_.',
            'ValidateGreater'  => 'The value provided is less than the minimum value _:_GREATER_:_.',
            'ValidateInteger'  => 'The value provided is not a positive or negative integer.',
            'ValidateLess'     => 'The value provided is greater than the maximum value _:_LESS_:_.',
            'ValidateRequired' => 'The value provided is empty.',
            'ValidateNumeric'  => 'The value provided is not a positive or negative number.'
        );

        /**
         * Creates a timestamp from the provided string or DateTime object.
         *
         * @param   string | DateTime   $vValue     Value to be converted.
         *
         * @return  int
         */
        private function CreateTimestamp( $vValue )
        {
            try
            {
                if( $this->ValidateDate( $vValue ) === true )
                {
                    if( is_string( $vValue ) )
                    {
                        $vValue = strtotime( $vValue );
                    }
                    elseif( $vValue instanceof DateTime )
                    {
                        $vValue = getTimestamp();
                    }
                }

                return $vValue;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Returns the error message for the validation function that called this function.
         *
         * @param   string      $sErrorMessage
         *
         * @throws  Exception   Thrown if error message provided is not a string or if
         *                      no message was provided and a default does not exist.
         *
         * @return  string
         */
        public function GetErrorMessage( $sErrorMessage = null )
        {
            // check if an error message was not supplied
            if( empty( $sErrorMessage ) )
            {
                // get the function that called this
                $aCallers = debug_backtrace();
                $sFunctionName = $aCallers[ 1 ][ 'function' ];

                // check if the function name provided has an error message
                if( !empty( $this->aErrorMessages[ $sFunctionName ] ) )
                {
                    return $this->aErrorMessages[ $sFunctionName ];
                }
                else
                {
                    throw new Exception( 'Error message does not exist for function: ' . $sFunctionName );
                }
            }
            // check if the error message is a string
            elseif( is_string( $sErrorMessage ) )
            {
                return $sErrorMessage;
            }
            else
            {
                throw new Exception( 'Error message provided is not a string: <pre>' . print_r( $sErrorMessage, true ) . '</pre>' );
            }
        }

        /**
         * Retrieves a list of validators and what they validate.
         *
         * @return array    Structure: array( validator => explanation )
         */
        public function GetValidators()
        {
            // initialize the list of validators to return
            $aValidators = array();

            // get a reflection of this class
            $oReflectionClass = new ReflectionClass( $this );

            // get the methods in this class
            $aMethods = $oReflectionClass->getMethods();

            // find all the validators
            $iMethodCount = count( $aMethods );
            for( $i = 0; $i < $iMethodCount; ++$i )
            {
                // get the method's name
                $oMethod     = $aMethods[ $i ];
                $sMethodName = $oMethod->name;

                // get the validation methods
                if( strlen( $sMethodName ) > 8 && substr( $sMethodName, 0, 8 ) == 'Validate' )
                {
                    // get the doc block for this method
                    $sDocBlock = $oMethod->getDocComment();

                    // if a doc block is provided, parse it
                    if( !empty( $sDocBlock ) )
                    {
                        // parse the doc block
                        $sDocBlock = preg_replace( '/\s+/', ' ', $sDocBlock );
                        $sDocBlock = str_replace( '/** * ', '', $sDocBlock );
                        $sDocBlock = str_replace( '* ', '', $sDocBlock );
                        $sDocBlock = explode( '@', $sDocBlock );
                        $sDocBlock = $sDocBlock[ 0 ];

                        // save the method and it's documentation
                        $aValidators[ $sMethodName ] = $sDocBlock;
                    }
                    // save the method as undocumented
                    else
                    {
                        $aValidators[ $sMethodName ] = 'Undocumented';
                    }
                }
            }

            return $aValidators;
        }

        /**
         * Validates whether or not the value provided is between the two test values.
         *
         * If the value provided is an integer, the function acts as expected.
         * If the value provided is a string, its length is compared to test values.
         *
         * @param   int | string    $vValue         The value to test.
         * @param   int | string    $vMin           The minimum value to compare against.
         * @param   int | string    $vMax           The maximum value to compare against.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateBetween( $vValue, $vMin, $vMax, $sErrorMessage = null )
        {
            try
            {
                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value is a string
                if( is_string( $vValue ) )
                {
                    // get the float value if possible
                    if( is_float( $vValue ) )
                    {
                        $vValue = floatval( $vValue );
                    }
                    // get the integer value
                    else
                    {
                        $vValue = intval( $vValue );
                    }
                }
                elseif( !is_numeric( $vValue ) )
                {
                    throw new Exception( 'Value provided is not a string and is numeric: <pre>' . print_r( $vValue, true ) . '</pre>' );
                }

                // check if the value is numeric
                if( !is_numeric( $vMin ) )
                {
                    throw new Exception( 'Minimum value provided is not numeric: <pre>' . print_r( $vMin, true ) . '</pre>' );
                }
                elseif( is_string( $vMin ) )
                {
                    // get the float value if possible
                    if( is_float( $vMin ) )
                    {
                        $vMin = floatval( $vMin );
                    }
                    // get the integer value
                    else
                    {
                        $vMin = intval( $vMin );
                    }
                }

                // check if the value is numeric
                if( !is_numeric( $vMax ) )
                {
                    throw new Exception( 'Maximum value provided is not numeric: <pre>' . print_r( $vMax, true ) . '</pre>' );
                }
                elseif( is_string( $vMax ) )
                {
                    // get the float value if possible
                    if( is_float( $vMax ) )
                    {
                        $vMax = floatval( $vMax );
                    }
                    // get the integer value
                    else
                    {
                        $vMax = intval( $vMax );
                    }
                }

                // check if min is greater than max
                if( $vMin > $vMax )
                {
                    throw new Exception( "Minimum value provided is greater than the maximum value provided. Min: $vMin Max: $vMax" );
                }

                // check if the value is between the range
                if( $vMin <= $vValue && $vValue <= $vMax )
                {
                    $vReturn = true;
                }
                else
                {
                    // replace the tags with provided values
                    $vReturn = str_replace(
                        array(
                            '_:_MIN_:_',
                            '_:_MAX_:_'
                        ),
                        array(
                            $vMin,
                            $vMax
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
         * Validates whether or not the value provided is a valid date in the format provided.
         *
         * @param   int | string | DateTime     $vValue         The value to test.
         * @param   string                      $sFormat        Format to validate against.
         * @param   string                      $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateDate( $vValue, $sFormat, $sErrorMessage = null )
        {
            try
            {
                // verify that a string was provided to test
                cStringUtilities::VerifyString( $sFormat );

                // if an error message was not supplied, default it
                $vValid = $this->GetErrorMessage( $sErrorMessage );

                // check if value is in a correct format
                if( !is_string( $vValue ) && !is_int( $vValue ) && !( $vValue instanceof DateTime ) )
                {
                    throw new Exception( 'Value provided is not a string, integer, or DateTime object: <pre>' . print_r( $vValue, true ) . '</pre>' );
                }

                // initialize a temporary check
                $bTempCheck = true;

                // check if the value provided is a valid date
                if( is_int( $vValue ) || ( is_string( $vValue ) && null !== $sFormat ) )
                {
                    // convert int into a date
                    if( is_int( $vValue ) )
                    {
                        $oDate = date_create("@$vValue");
                    }
                    // convert string into a date
                    else
                    {
                        $oDate = DateTime::createFromFormat( $sFormat, $vValue );
                    }

                    // check for warnings or errors
                    $aErrors = DateTime::getLastErrors();

                    if( $aErrors[ 'warning_count' ] > 0 || $aErrors[ 'error_count' ] > 0 || $oDate === false)
                    {
                        $bTempCheck = false;
                    }
                }

                // check if all intermediary checks passed
                if( $bTempCheck )
                {
                    $vValid = true;
                }

                return $vValid;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validates whether or not the value provided is between the two test values.
         *
         * If the value provided is an integer, the function acts as expected.
         * If the value provided is a string, its length is compared to test values.
         *
         * @param   int | string | DateTime     $vValue         The value to test.
         * @param   int | string | DateTime     $vMin           The minimum value to compare against.
         * @param   int | string | DateTime     $vMax           The maximum value to compare against.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         *
         * @TODO: check if this works
         */
        public function ValidateDateBetween( $vValue, $vMin, $vMax, $sErrorMessage = null )
        {
            try
            {
                // convert all dates into ints
                $this->CreateTimestamp( $vValue );
                $this->CreateTimestamp( $vMin   );
                $this->CreateTimestamp( $vMax   );

                // check if this is valid
                $vReturn = $this->ValidateBetween( $vValue, $vMin, $vMax );

                return $vReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Validates whether or not the value provided
         * contains only a positive or negative decimal number.
         *
         * @param   string          $sValue         The value to test.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateDecimal( $sValue, $sErrorMessage = null )
        {
            try
            {
                // verify that a string was provided to test
                cStringUtilities::VerifyString( $sValue );

                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( preg_match( "/^[\-+]?[0-9]+\.[0-9]+$/", $sValue ) )
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
         * Validates whether or not the value provided matched the test value.
         *
         * @param   mixed           $vValue         The value to test.
         * @param   mixed           $vTest          The value to test against.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateEquals( $vValue, $vTest, $sErrorMessage = null )
        {
            try
            {
                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( $vValue === $vTest )
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
                            print_r( $vValue, true ),
                            print_r( $vTest,  true )
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
         * is greater than the test value.
         *
         * @param   int | string    $vValue         The value to test.
         * @param   int | string    $vTest          The value to test against.
         * @param   int | string    $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if $vValue or $vTest are not numeric or
         *                          if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateGreater( $vValue, $vTest, $sErrorMessage = null )
        {
            try
            {
                // check if the value is numeric
                if( is_string( $vValue ) )
                {
                    // get the value as a float if possible
                    if( is_float( $vValue ) )
                    {
                        $vValue = floatval( $vValue );
                    }
                    // get the integer value
                    else
                    {
                        $vValue = intval( $vValue );
                    }
                }
                if( !is_numeric( $vValue ) )
                {
                    throw new Exception( 'Value provided is not numeric: <pre>' . print_r( $vValue, true ) . '</pre>' );
                }

                // check if the test value is numeric
                if( !is_numeric( $vTest ) )
                {
                    throw new Exception( 'Test value provided is not numeric: <pre>' . print_r( $vTest, true ) . '</pre>' );
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
                if( $vValue > $vTest )
                {
                    $vReturn = true;
                }
                // replace tag in error message
                else
                {
                    $vReturn = str_replace(
                        array(
                            '_:_GREATER_:_'
                        ),
                        array(
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
         * Validates whether or not the value provided is a positive or negative integer.
         *
         * @param   int | string    $vValue         The value to test.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateInteger( $vValue, $sErrorMessage = null )
        {
            try
            {
                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( is_int( $vValue ) || is_string( $vValue ) && preg_match( "/^[\-+]?[0-9]+$/", $vValue ) )
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
         * is less than the test value.
         *
         * @param   int | string    $vValue         The value to test.
         * @param   int | string    $vTest          The value to test against.
         * @param   int | string    $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if $vValue or $vTest are not numeric or
         *                          if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateLess( $vValue, $vTest, $sErrorMessage = null )
        {
            try
            {
                // check if the value is numeric
                if( is_string( $vValue ) )
                {
                    // get the float value if possible
                    if( is_float( $vValue ) )
                    {
                        $vValue = floatval( $vValue );
                    }
                    // get the integer value
                    else
                    {
                        $vValue = intval( $vValue );
                    }
                }
                if( !is_numeric( $vValue ) )
                {
                    throw new Exception( 'Value provided is not numeric: <pre>' . print_r( $vValue, true ) . '</pre>' );
                }

                // check if the test value is numeric
                if( !is_numeric( $vTest ) )
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

                // check if the value supplied is valid
                if( $vValue < $vTest )
                {
                    $vReturn = true;
                }
                // replace tag in error message
                else
                {
                    $vReturn = str_replace(
                        array(
                            '_:_LESS_:_'
                        ),
                        array(
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
         * Validate whether or not the value provided is empty.
         *
         * @param   mixed           $vValue         The value to test.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateRequired( $vValue, $sErrorMessage = null )
        {
            try
            {
                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( is_string( $vValue ) && trim( $vValue ) !== '' )
                {
                    $vReturn = true;
                }
                elseif( !empty( $vValue ) )
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
         * contains only numeric characters.
         *
         * @param   int | string    $vValue         The value to test.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateNumeric( $vValue, $sErrorMessage = null )
        {
            try
            {
                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( is_numeric( $vValue ) )
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