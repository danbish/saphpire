<?php
    /**
     * Make sure php file contains opening and closing tags.
     *
     * @author Jassiem Moore
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Syntax_RequireOpenAndCloseTagsSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_OPEN_TAG );
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
            // get all the token in the file
            $aTokens = $oPHPCSFile->getTokens();

            // check if there other interface/class declarations.
            if( !$oPHPCSFile->findNext( T_CLOSE_TAG, $iStackPointer + 1 ) )
            {
                $sError = "PHP files require opening tags and closing tags.";
                $oPHPCSFile->addError( $sError, $iStackPointer );
            }
        }
    }
?>