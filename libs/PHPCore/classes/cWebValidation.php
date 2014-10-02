<?php
    // get the string utility class
    require_once( sCORE_INC_PATH . '/classes/cStringUtilities.php' );

    // get the base class
    require_once( sCORE_INC_PATH . '/classes/cBaseValidation.php' );

    /**
     * Web validation class for URLs, emails, and IP addresses.
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
     * @version 0.2
     */
    class cWebValidation extends cBaseValidation
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
            'ValidateEmail' => 'The value provided is not a valid email.',
            'ValidateIPv4'  => '_:_IP_:_ is not a valid IPv4 address.',
            'ValidateIPv6'  => '_:_IP_:_ is not a valid IPv6 address.',
            'ValidateURL'   => 'The value provided is not a valid URL.'
        );

        /**
         * List of user defined URL schemes.
         *
         * @var array
         */
        protected $aValidSchemes = array();

        /**
         * Validates whether or not the value provided is a valid email address.
         *
         * @param   string          $sValue         The value to test.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateEmail( $sValue, $sErrorMessage = null )
        {
            try
            {
                // verify that a string was provided to test
                cStringUtilities::VerifyString( $sValue );

                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( filter_var( $sValue, FILTER_VALIDATE_EMAIL ) )
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
         * Validates whether or not the value provided is a valid IPv4 address.
         *
         * @param   string          $sValue         The value to test.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateIPv4( $sValue, $sErrorMessage = null )
        {
            try
            {
                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( filter_var( $sValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
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
         * Validates whether or not the value provided is a valid IPv6 address.
         *
         * @param   string          $sValue         The value to test.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateIPv6( $sValue, $sErrorMessage = null )
        {
            try
            {
                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( filter_var( $sValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) )
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
         * Validates whether or not the value provided is a valid URL.
         *
         * @uses    ValidateScheme
         * @uses    VaidateHost
         *
         * @param   string          $sValue         The value to test.
         * @param   string          $sErrorMessage  Error message to return on failure.
         *
         * @throws  Exception       Thrown if an exception was caught at a lower level.
         *
         * @return  true | string   Returns an error message if the validation fails.
         */
        public function ValidateURL( $sValue, $sErrorMessage = null )
        {
            try
            {
                // verify that a string was provided to test
                cStringUtilities::VerifyString( $sValue );

                // if an error message was not supplied, default it
                $vReturn = $this->GetErrorMessage( $sErrorMessage );

                // check if the value supplied is valid
                if( filter_var( $sValue, FILTER_VALIDATE_URL ) )
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