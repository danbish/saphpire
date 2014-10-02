<?php
    /**
    * A sniff that shows warnings when count() is used in a loop.
    *
    * @author: Dylan Pruitt
    */
    class PHP_CodeSniffer_Sniffs_Syntax_CountInLoopWarningSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_FOR, T_FOREACH, T_WHILE );
        }
     
        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile The file being scanned.
         * @param int                  $iStackPtr  The position of the current token in the
         *                                         stack passed in $tokens.
         *
         * @return void
         */
        public function process(PHP_CodeSniffer_File $oPHPCSFile, $iStackPtr)
        {
            $aTokens = $oPHPCSFile->getTokens();

            // Set left and right parenthesis stack values.
            $iLeftParen = $aTokens[ $iStackPtr ][ 'parenthesis_opener' ];
            $iRightParen = $aTokens[ $iStackPtr ][ 'parenthesis_closer' ];

            for( $i = $iLeftParen; $i < $iRightParen; $i++ )
            {
                // If the content of a token is count, and there is a nested parenthesis attached to it // show an error.
                if( ( $aTokens[ $i ][ 'content' ] == 'count' ) 
                    && ( array_key_exists( 'nested_parenthesis', $aTokens[ $i ] ) ) )
                {
                    $sWarning = 'Do not use count in a loop.';
                    $oPHPCSFile->addWarning( $sWarning, $iStackPtr );
                }
            }
        }
    }
?>