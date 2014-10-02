<?php
    /**
    * A sniff that shows errors when there are nested ternary statements.
    *
    * @author Dylan Pruitt
    */
    class PHP_CodeSniffer_Sniffs_Syntax_NoNestedTernarySniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_INLINE_THEN );
        }

        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile The file being scanned.
         * @param int                  $iStackPtr  The position of the current token in the
         *                                         stack passed in $aTokens.
         *
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPtr )
        {
            // get all the tokens in the file
            $aTokens = $oPHPCSFile->getTokens();
            $iCount  = $iStackPtr + 1;

            // while on the same line
            while( $aTokens[ $iCount ][ 'line' ] == $aTokens[ $iStackPtr ][ 'line' ] )
            {
                // if you encounter another ternary operator add an error
                if( $aTokens[ $iCount ][ 'type' ] == 'T_INLINE_THEN' )
                {
                    $sError = "Nested ternaries not allowed.";
                    $oPHPCSFile->addError( $sError, $iStackPtr );
                    return;
                }
                $iCount++;
            }
        }
    }
?>