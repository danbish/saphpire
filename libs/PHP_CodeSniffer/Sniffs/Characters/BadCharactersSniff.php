<?php
    /**
     * This sniff makes sure that no bad characters are found.
     *
     * Bad characters are defined as having ASCII value < 32
     * excluding tab, newline, and carriage return.
     *
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Characters_BadCharactersSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Returns the token types that this sniff is interested in.
         *
         * @return array(int)
         */
        public function register()
        {
            return array( T_BAD_CHARACTER );
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
            // get the tokens that we're dealing with
            $aTokens = $oPHPCSFile->getTokens();

            // add error for this position
            $sError = 'Bad character found on line ' . $aTokens[ $iStackPointer ][ 'line' ] . ' at column ' . $aTokens[ $iStackPointer ][ 'column' ];
            $oPHPCSFile->addError( $sError,  $iStackPointer, 'BadCharacter' );
        }
    }
?>