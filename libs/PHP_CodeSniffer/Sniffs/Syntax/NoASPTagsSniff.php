<?php
    /**
     * A sniff that shows errors when ASP tags are used.
     *
     * @author Dylan Pruitt
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Syntax_NoASPTagsSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register() 
        {
            return array( T_MODULUS );
        }

        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $oPhpcsFile The file being scanned.
         * @param int                  $iStackPtr  The position of the current token in the
         *                                        stack passed in $aTokens.
         *
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPhpcsFile, $iStackPtr )
        {
            // get all the tokens in the file
            $aTokens = $oPhpcsFile->getTokens();

            // check if the ASP tags exist
            if( ( $aTokens[ $iStackPtr - 1 ][ 'type' ] == 'T_LESS_THAN' )
                || ( $aTokens[ $iStackPtr + 1 ][ 'type' ] == 'T_GREATER_THAN' ) )
            {
                // add error
                $sError = 'ASP style tags are not allowed';
                $oPhpcsFile->addError( $sError, $iStackPtr );
            }
        }
    }
?>