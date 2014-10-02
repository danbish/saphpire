<?php
    /**
     * Make sure function name follows naming rules.
     *
     * @author Jassiem Moore
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Naming_EnsureCorrectFunctionNamingSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Minimum length of function names.
         *
         * @var integer
         */
        private $iMinLength = 5;

        /**
         * Maximum length of function names.
         *
         * @var integer
         */
        private $iMaxLength = 30;

        /**
         * List of allowed exceptions to naming rules.
         *
         * @var array
         */
        private $aAllowedExceptions = array(
            '__construct',
            '__destruct',
            '__call',
            '__callStatic',
            '__get',
            '__set',
            '__isset',
            '__unset',
            '__sleep',
            '__wakeup',
            '__toString',
            '__invoke',
            '__set_state',
            '__clone'
        );

        /**
         * Returns the token types that this sniff is interested in.
         *
         * @return array(int)
         */
        public function register()
        {
            return array( T_FUNCTION );
        }

        /**
         * Processes the tokens that this sniff is interested in.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile The file where the token was found.
         * @param int                  $iStackPointer  The position in the stack where
         *                                        the token was found.
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPointer )
        {
            // get all the tokens in the file
            $aTokens = $oPHPCSFile->getTokens();

            // get the name of the function
            $iFunctionNamePos   = $oPHPCSFile->findNext( T_STRING, $iStackPointer );
            $sFunctionName      = $aTokens[ $iFunctionNamePos ][ 'content' ];

            // get acceptable acronyms
            $aAcceptedAcros = parse_ini_file( 'acronyms.ini' );
            $sAcceptedAcros = implode( '|', $aAcceptedAcros );

            // report error if regex fails
            if( !in_array( $sFunctionName, $this->aAllowedExceptions )
                && preg_match( "/^(?=.{{$this->iMinLength},{$this->iMaxLength}}$)(([A-Z]|{$sAcceptedAcros}[A-Z]{0,1})[0-9a-z]+)|{$sAcceptedAcros}+$/", $sFunctionName ) != 1 )
            {
                $sError = 'Function "' . $sFunctionName . '" does not follow naming rules.';
                $oPHPCSFile->addError( $sError, $iStackPointer );
            }

            // warn user if numbers are present
            if( preg_match( '/[0-9].+/', $sFunctionName ) == 1 )
            {
                $sWarning = 'Numbers are allowed in function names, but are uncommon.';
                $oPHPCSFile->addWarning( $sWarning, $iStackPointer );
            }
        }
    }
?>