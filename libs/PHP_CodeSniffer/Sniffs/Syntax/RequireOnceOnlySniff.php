<?php
    /**
     * A sniff that shows errors when files are included with anything other than require_once.
     *
     * @author Dylan Pruitt
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Syntax_RequireOnceOnlySniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE );
        }

        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile     The file being scanned.
         * @param int                  $iStackPointer  The position of the current token in the stack passed in $aTokens.
         *
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPointer )
        {
            // get all the tokens in this file
            $aTokens = $oPHPCSFile->getTokens();

            // add the error
            $sError = 'Files should be included with require_once.';
            $oPHPCSFile->addError( $sError, $iStackPointer );
        }
    }
?>