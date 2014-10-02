<?php
    /**
     * A sniff that makes sure there aren't multiple class/interface declarations.
     * Author: Jassiem Moore
     *
     */
    class PHP_CodeSniffer_Sniffs_Syntax_NoMultipleClassOrInterfaceDeclarationsSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_CLASS, T_INTERFACE );
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
            // get all the tokens in a file
            $aTokens = $oPHPCSFile->getTokens();

            //declaration already encountered, print error
            if( $oPHPCSFile->findNext( array( T_CLASS, T_INTERFACE ), $iStackPointer + 1 ) )
            {
                // set the error
                $sError = "Only one class or interface declaration is allowed per file.";
                $oPHPCSFile->addError( $sError, $iStackPointer );
            }
        }
    }
?>