<?php
    /**
     * Make sure there aren't functions declared outside of class declaration.
     *
     * @author Jassiem Moore
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Classes_NoFunctionDeclarationsOutsideClassSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_CLASS );
        }

        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param  PHP_CodeSniffer_File  $oPHPCSFile     The file being scanned.
         * @param  int                   $iStackPointer  The position of the current token in the stack passed in $aTokens.
         *
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPointer )
        {
            // get all the tokens in a file
            $aTokens     = $oPHPCSFile->getTokens();
            $iEndOfClass = $aTokens[ $iStackPointer ][ 'scope_closer' ];

            // check if there are function declarations outside of class declaration
            if( ( $iFuncStackPointer = $oPHPCSFile->findPrevious( T_FUNCTION, $iStackPointer - 1 ) )
                || ( $iFuncStackPointer = $oPHPCSFile->findNext( T_FUNCTION, $iEndOfClass ) ) )
            {
                // set the error
                $sError = 'Function declarations are not allowed outside of class declarations.';
                $oPHPCSFile->addError( $sError, $iFuncStackPointer );
            }
        }
    }
?>