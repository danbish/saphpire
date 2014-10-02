<?php
    /**
     * A sniff that shows errors when extra whitespace lines exist.
     *
     * @author Dylan Pruitt
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Syntax_NoExtraLinesSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_WHITESPACE );
        }

        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile     The file being scanned.
         * @param int                  $iStackPointer  The position of the current token in the
         *                                             stack passed in $aTokens.
         *
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPointer )
        {
            // get all the tokens in the file
            $aTokens = $oPHPCSFile->getTokens();

            // check if whitespace is not at beginning of line return
            // and check if there is a newline
            if( $aTokens[ $iStackPointer ][ 'column' ] > 1
                && !strstr( $aTokens[ $iStackPointer ][ 'content' ], PHP_EOL ) )
            {
                return;
            }

            // count new lines
            $iNewLines = 1;
            while( isset( $aTokens[ ++$iStackPointer ] ) && $aTokens[ $iStackPointer ][ 'type' ] == 'T_WHITESPACE' )
            {
                // check if there is a newline in it
                if( strstr( $aTokens[ $iStackPointer ][ 'content' ], PHP_EOL ) )
                {
                    ++$iNewLines;

                    if( $iNewLines > 2 )
                    {
                        $sError = "Extra whitespace line.";
                        $oPHPCSFile->addError( $sError, --$iStackPointer );
                        return;
                    }
                }
            }

            // if next whitespace is not on next line return
            /*if( $aTokens[ $iNextSpace ][ 'line' ] != ( $aTokens[ $iStackPointer ][ 'line' ] + 1 ) )
            {
                return;
            }

            // if next whitespace is at beginning of column add an error
            if( $aTokens[ $iNextSpace ][ 'column' ] == 1 )
            {
                $sError = "Extra whitespace line.";
                $oPHPCSFile->addError( $sError, $iStackPointer );
            }*/
        }
    }
?>