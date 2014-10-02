<?php
    /**
     * A sniff that makes sure there are not any goto statements.
     *
     * @author Jassiem Moore
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Syntax_NoGoToSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_GOTO );
        }

        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile     The file being scanned.
         * @param int                  $iStackPointer  The position of the current token in the
         *                                             stack passed in $aTokens.
         *
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPointer )
        {
            // set the error
            $sError = "Goto statements are not allowed.";
            $oPHPCSFile->addError( $sError, $iStackPointer);
        }
    }
?>