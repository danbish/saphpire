<?php
    /**
     * A sniff that shows warnings when "elseif" is used instead of "else if".
     *
     * @author Dylan Pruitt
     */
    class PHP_CodeSniffer_Sniffs_Syntax_ElseifWarningSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_ELSEIF );
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
            // Add a warning for the use of elseif
            $sError = "Use 'else if' instead of 'elseif'.";
            $oPHPCSFile->addWarning( $sError, $iStackPtr );
        }
    }
?>