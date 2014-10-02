<?php
    /**
     * A sniff that shows errors when functions have no set scope.
     *
     * @author Dylan Pruitt
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Syntax_FunctionScopeSniff implements PHP_CodeSniffer_Sniff
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
         * @param PHP_CodeSniffer_File $oPHPCSFile The file being scanned.
         * @param int                  $iStackPtr  The position of the current token in the stack passed in $aTokens.
         *
         * @return void
         */
        public function process(PHP_CodeSniffer_File $oPHPCSFile, $iStackPtr)
        {
            // get all the tokens in the file
            $aTokens = $oPHPCSFile->getTokens();
            $sType = '';

            // watch out for array offsets.
            if( $iStackPtr > 2 )
            {
                $sType = $aTokens[ $iStackPtr - 2 ][ 'type' ];
            }

            if( $sType == 'T_STATIC' && $iStackPtr > 4 )
            {
                $sType = $aTokens[ $iStackPtr - 4 ][ 'type' ];
            }

            // if there is a scope return.
            if( ( $sType == 'T_PUBLIC' )    ||
                ( $sType == 'T_PRIVATE' )   ||
                ( $sType == 'T_PROTECTED' ) )
            {
                return;
            }

            $sError = 'No scope declared for function.';
            $oPHPCSFile->addError( $sError, $iStackPtr );
        }
    }
?>