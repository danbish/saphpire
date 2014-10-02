<?php
    /**
    * A sniff that shows errors when multiple return statements are used.
    *
    * @author Dylan Pruitt
    * @package CodeSniffer
    * @version 0.1
    */
    class PHP_CodeSniffer_Sniffs_Function_NoMultipleReturnSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_FUNCTION );
        }

        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $oPhpcsFile The file being scanned.
         * @param int                  $iStackPtr  The position of the current token in the
         *                                         stack passed in $aTokens.
         *
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPhpcsFile, $iStackPtr )
        {
            // get all the tokens in the file
            $aTokens = $oPhpcsFile->getTokens();
            $iReturns = 0;

            // if the scope opener and closer aren't there, it's a declaration not a definition
            if( isset( $aTokens[ $iStackPtr ][ 'scope_opener' ] ) && isset( $aTokens[ $iStackPtr ][ 'scope_closer' ] ) )
            {
                // get positions of opening and closing bracket for function
                $iOpenBrack  = $aTokens[ $iStackPtr ][ 'scope_opener' ];
                $iCloseBrack = $aTokens[ $iStackPtr ][ 'scope_closer' ];

                for( $i =  $iOpenBrack; $i < $iCloseBrack; ++$i )
                {
                    // If a return is found, increment the return counter.
                    if( $aTokens[ $i ][ 'type' ] == 'T_RETURN' )
                    {
                        ++$iReturns;

                        // If more than one return is found, add an error.
                        if( $iReturns > 1 )
                        {
                            $sError = 'Multiple return statements used.';
                            $oPhpcsFile->addError( $sError, $i );
                        }
                    }
                }
            }
        }
    }
?>