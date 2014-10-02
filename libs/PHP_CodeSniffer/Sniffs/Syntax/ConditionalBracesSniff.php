<?php
    /**
     * A sniff that shows errors when conditionals do not have braces.
     *
     * @author Dylan Pruitt
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Syntax_ConditionalBracesSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_IF, T_ELSE, T_FOR, T_FOREACH, T_WHILE, T_SWITCH );
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
            $aTokens            = $oPHPCSFile->getTokens();
            $iNextConditional   = $oPHPCSFile->findNext( T_IF, $iStackPtr+1 );

            // if the conditional is an elseif return on the else
            if( $iNextConditional == ( $iStackPtr + 2 ) )
            {
                return;
            }

            // if there is no scoper opener add an error
            if( !isset( $aTokens[ $iStackPtr ][ 'scope_opener' ] ) )
            {
                // add error
                $sError = "Conditional statements must have braces '{}'.";
                $oPHPCSFile->addError( $sError, $iStackPtr );
            }
        }
    }
?>