<?php
    /**
     * A sniff that shows errors when conditional contents are not on a new line.
     *
     * @author Jassiem Moore
     * @author Ryan Masters
     * @author Dylan Pruitt
     */
    class PHP_CodeSniffer_Sniffs_Syntax_ConditionalContentsNewLineSniff implements PHP_CodeSniffer_Sniff
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

            // check everything that's in the current scope
            if( isset( $aTokens[ $iStackPtr ][ 'scope_opener' ] ) && isset( $aTokens[ $iStackPtr ][ 'scope_closer' ] ) )
            {
                for( $i = $aTokens[ $iStackPtr ][ 'scope_opener' ]; $i < $aTokens[ $iStackPtr ][ 'scope_closer' ]; ++$i )
                {
                    // if any of the conditional contents are on the same line as the declaration add an error
                    if( $aTokens[ $i ][ 'line' ] == $aTokens[ $iStackPtr ][ 'line' ] )
                    {
                        $sError = "Conditional contents must be on a new line.";
                        $oPHPCSFile->addError( $sError, $iStackPtr );
                        return;
                    }
                }
            }
            else
            {
                // add an error
                $sError = "Open and close curly braces are required.";
                $oPHPCSFile->addError( $sError, $iStackPtr );
            }
        }
    }
?>