<?php
    /**
     * This sniff makes sure that tabs are not used for indentation.
     *
     * @author Jassiem Moore
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Spacing_EnsureOnlySpacesForIndentationSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Returns the token types that this sniff is interested in.
         *
         * @return array(int)
         */
        public function register()
        {
            return array( T_WHITESPACE );
        }

        /**
         * Processes the tokens that this sniff is interested in.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile      The file where the token was found.
         * @param int                  $iStackPointer   The position in the stack where the token was found.
         *
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPointer )
        {
            // get all the tokens in the file
            $aTokens = $oPHPCSFile->getTokens();

            // Make sure this is whitespace used for indentation.
            if ( strpos( $aTokens[ $iStackPointer ][ 'content' ], "\t") !== false )
            {
                // add error
                $sError = 'Spaces must be used to indent lines; tabs are not allowed.';
                $oPHPCSFile->addError($sError,  $iStackPointer, 'TabsUsed');
            }
        }
    }
?>