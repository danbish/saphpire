<?php
    /**
     * A sniff that shows errors when only one equal sign is used inside of conditionals.
     *
     * @author Dylan Pruitt
     */

    class PHP_CodeSniffer_Sniffs_Syntax_SingleConditionalEqualSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_IF, T_WHILE );
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
                // If there is an equal inside the parentheses.
                if( ( $aTokens[ $i ][ 'type' ] == 'T_EQUAL' ) )
                {
                    $sWarning = "Don't use a single equal inside of conditionals.";
                    $oPHPCSFile->addWarning( $sWarning, $iStackPtr );
                }
            }
        }
    }
?>