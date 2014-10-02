<?php
    /**
     * This sniff makes sure that open curly braces are on the next line.
     *
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Syntax_CurlyBracesOnNextLineSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Returns the token types that this sniff is interested in.
         *
         * @return array(int)
         */
        public function register()
        {
            return array( T_OPEN_CURLY_BRACKET );
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
            // get the tokens to analyze
            $aTokens = $oPHPCSFile->getTokens();

            // get the original stack pointer
            $iOriginalStackPointer = $iStackPointer;

            // get the line that this token is on
            $iLine = $aTokens[ $iStackPointer-- ][ 'line' ];

            // check everything that is on the same line
            while( $iStackPointer > 0 && $aTokens[ $iStackPointer ][ 'line' ] == $iLine )
            {
                // check if this is whitespace or not
                if( $aTokens[ $iStackPointer ][ 'type' ] != 'T_WHITESPACE'
                    && ( $aTokens[ $iOriginalStackPointer + 1 ][ 'content' ][ 0 ] != '$'
                         && $aTokens[ $iOriginalStackPointer + 1 ][ 'content' ][ 0 ] != '}' ) )
                {
                    $sError = 'Open braces should be on the next line.';
                    $oPHPCSFile->addError( $sError, $iOriginalStackPointer );
                    return;
                }
                --$iStackPointer;
            }
        }
    }
?>