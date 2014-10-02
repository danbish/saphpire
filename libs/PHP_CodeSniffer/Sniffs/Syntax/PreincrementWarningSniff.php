<?php
    /**
     * A sniff that shows warnings when pre-increment is used instead of post-increment.
     *
     * @author Dylan Pruitt
     */
    class PHP_CodeSniffer_Sniffs_Syntax_PreincrementWarningSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_INC );
        }

        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile The file being scanned.
         * @param int                  $iStackPtr  The position of the current token in the stack passed in $aTokens.
         *
         * @return void
         */
        public function process(PHP_CodeSniffer_File $oPHPCSFile, $iStackPtr)
        {
            // Get position of next variable after increment operator.
            $iNextVar = $oPHPCSFile->findNext( T_VARIABLE, $iStackPtr );

            // If variable is next in the stack add a warning.
            if( $iNextVar != ( $iStackPtr + 1 ) )
            {
                $sWarning = "Use pre-increment instead of post-increment.";
                $oPHPCSFile->addWarning( $sWarning, $iStackPtr );
            }
        }
    }
?>