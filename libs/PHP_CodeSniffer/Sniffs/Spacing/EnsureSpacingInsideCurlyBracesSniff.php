<?php
    /**
     * This sniff makes sure that code inside of two curly braces is indented 1 Tab.
     * 1 Tab = 4 spaces.
     *
     * @author Jassiem Moore
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Spacing_EnsureSpacingInsideCurlyBracesSniff implements PHP_CodeSniffer_Sniff
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
            // get all the tokens in the file
            $aTokens = $oPHPCSFile->getTokens();

            // set the expected indentation level
            $iExpectedIndent = $aTokens[ $iStackPointer ][ 'column' ] + 4;

            // check all lines between open and close bracket for correct indentation
            for( $i = $aTokens[ $iStackPointer ][ 'bracket_opener' ] + 1; $i < $aTokens[ $iStackPointer ][ 'bracket_closer' ]; ++$i )
            {
                if( $aTokens[ $i ][ 'column' ] < $iExpectedIndent
                    && $aTokens[ $i ][ 'type' ] != 'T_WHITESPACE'
                    && $aTokens[ $i ][ 'type' ] != 'T_DOC_COMMENT'
                    && $aTokens[ $i ][ 'type' ] != 'T_CONSTANT_ENCAPSED_STRING'
                    && $aTokens[ $i ][ 'content' ][ 0 ] != '$' )
                {
                    $sError = 'Incorrect indentation. 4 spaces are needed after opening brace.' . $aTokens[ $i ][ 'column' ];
                    $oPHPCSFile->addError( $sError, $i );
                    return;
                }
            }
        }
    }
?>