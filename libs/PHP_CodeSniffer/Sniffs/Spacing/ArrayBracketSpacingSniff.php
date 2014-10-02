<?php
    /**
     * This sniff makes sure that array brackets are spaced correctly.
     * Code is taken from Squiz standards array bracket spacing sniff.
     */
    class PHP_CodeSniffer_Sniffs_Spacing_ArrayBracketSpacingSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Returns an array of tokens this test wants to listen for.
         *
         * @return array
         */
        public function register()
        {
            return array(
                    T_OPEN_SQUARE_BRACKET,
                    T_CLOSE_SQUARE_BRACKET,
                   );

        }//end register()


        /**
         * Processes this sniff, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile The current file being checked.
         * @param int                  $iStackPtr  The position of the current token in the
         *                                         stack passed in $tokens.
         *
         * @return void
         */
        public function process(PHP_CodeSniffer_File $oPHPCSFile, $iStackPtr)
        {
            $aTokens = $oPHPCSFile->getTokens();

            // Column number is needed to check whitespace.  Multiple spaces can count
            // as the same whitespace token.
            $iColNum = $aTokens[ $iStackPtr ][ 'column' ];

            if( $aTokens[ $iStackPtr ][ 'type' ] == 'T_OPEN_SQUARE_BRACKET' )
            {
                if( $aTokens[ $iStackPtr + 1 ][ 'type' ] == 'T_CLOSE_SQUARE_BRACKET' )
                {
                    return;
                }
                else if( $aTokens[ $iStackPtr + 1 ][ 'type' ] == 'T_WHITESPACE' )
                {
                    $iStrLength = strlen( $aTokens[ $iStackPtr + 1 ][ 'content' ] );
                    if( $iStrLength > 1 )
                    {
                        $sError = 'Incorrect spacing inside array brackets.';
                        $oPHPCSFile->addError( $sError,  $iStackPtr );
                    }
                }
                else
                {
                    $sError = 'Incorrect spacing inside array brackets.';
                    $oPHPCSFile->addError( $sError,  $iStackPtr );
                }
            }
            else if( $aTokens[ $iStackPtr ][ 'type' ] == 'T_CLOSE_SQUARE_BRACKET' )
            {
                if( $aTokens[ $iStackPtr - 1 ][ 'type' ] == 'T_OPEN_SQUARE_BRACKET' )
                {
                    return;
                }
                else if( $aTokens[ $iStackPtr - 1 ][ 'type' ] == 'T_WHITESPACE' )
                {
                    $iStrLength = strlen( $aTokens[ $iStackPtr - 1 ][ 'content' ] );
                    if( $iStrLength > 1 )
                    {
                        $sError = 'Incorrect spacing inside array brackets.';
                        $oPHPCSFile->addError( $sError,  $iStackPtr );
                    }
                }
                else
                {
                    $sError = 'Incorrect spacing inside array brackets.';
                    $oPHPCSFile->addError( $sError,  $iStackPtr );
                }
            }
        }//end process()
    }//end class
?>
