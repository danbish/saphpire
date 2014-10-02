<?php
    /**
     * A sniff that shows errors when array variables are used as function parameters but
     * do not include the word array in front of them.
     *
     * @author Dylan Pruitt
     */

    class PHP_CodeSniffer_Sniffs_Syntax_ArrayParamSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_FUNCTION, T_CLOSURE );
        }

        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile The file being scanned.
         * @param int                  $iStackPtr  The position of the current token in the stack passed in $aTokens.
         *
         * @return void
         */
        public function process(PHP_CodeSniffer_File $oPHPCSFile, $iStackPtr)
        {
            // get all the tokens in the file
            $aTokens = $oPHPCSFile->getTokens();

            // Get left and right parenthesis stack positions.
            $iLeftParen = $aTokens[ $iStackPtr ][ 'parenthesis_opener' ];
            $iRightParen = $aTokens[ $iStackPtr ][ 'parenthesis_closer' ];

            for( $i = $iLeftParen; $i < $iRightParen; $i++ )
            {
                // If the parameter is an array.
                if( ( $aTokens[ $i ][ 'type' ] == 'T_VARIABLE' )
                    && ( substr( $aTokens[ $i ][ 'content' ], 0, 2 ) == '$a' ) )
                {
                    // Check to see if an array reference is being passed.
                    if( $aTokens[ $i - 1 ][ 'type' ] == 'T_BITWISE_AND' )
                    {
                        if( $aTokens[ $i - 3 ][ 'type' ] != 'T_ARRAY_HINT' )
                        {
                            $sError = "No 'array' in front of array parameter.";
                            $oPHPCSFile->addError( $sError, $iStackPtr );
                            return;
                        }
                        return;
                    }

                    // If the previous token (skip whitespace) is not an array hint.
                    if( $aTokens[ $i - 2 ][ 'type' ] != 'T_ARRAY_HINT' )
                    {
                        $sError = "No 'array' in front of array parameter.";
                        $oPHPCSFile->addError( $sError, $iStackPtr );
                    }
                }
            }
        }
    }
?>