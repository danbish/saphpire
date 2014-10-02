<?php
    /**
     * A sniff that shows errors when heredocs are used.
     *
     * @author Dylan Pruitt
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Syntax_NoHereDocsSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_HEREDOC );
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
            $sError = 'Here Documents are not allowed.';
            $oPHPCSFile->addError( $sError, $iStackPointer);
        }
    }
?>