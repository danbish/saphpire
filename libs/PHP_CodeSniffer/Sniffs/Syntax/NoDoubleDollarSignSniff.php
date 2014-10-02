<?php
    /**
     * Give warning if $ is used more than once at the start of a variable.
     *
     * @author Dylan Pruitt
     */
    class PHP_CodeSniffer_Sniffs_Syntax_NoDoubleDollarSignSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Returns the token types that this sniff is interested in.
         *
         * @return array(int)
         */
        public function register()
        {
            return array( T_VARIABLE );

        }//end register()


        /**
         * Processes the tokens that this sniff is interested in.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile The file where the token was found.
         * @param int                  $iStackPtr  The position in the stack where
         *                                         the token was found.
         *
         * @return void
         */
        public function process(PHP_CodeSniffer_File $oPHPCSFile, $iStackPtr)
        {
            $aTokens = $oPHPCSFile->getTokens();

            // if the previous token is a $
            if( $aTokens[ $iStackPtr - 1 ][ 'type' ] == 'T_DOLLAR' )
            {
                $sWarning = 'More than one $ has been used to call a variable.';
                $oPHPCSFile->addWarning( $sWarning, $iStackPtr );
            }
        }
    }

?>