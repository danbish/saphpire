<?php
    /**
     * A sniff that shows errors when logical AND or OR is used.
     *
     * @author Dylan Pruitt
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Syntax_DontAllowAndOrSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_LOGICAL_AND, T_LOGICAL_OR );
        }

        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile The file being scanned.
         * @param int                  $iStackPtr  The position of the current token in the
         *                                         stack passed in $aTokens.
         *
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPtr )
        {
            // add an error
            $sError = 'Do not use logical AND or OR.  Use && or ||.';
            $oPHPCSFile->addError( $sError, $iStackPtr );
        }
    }
?>