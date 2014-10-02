<?php
    /**
     * A sniff that shows warnings when double quotes are used and there are no variables
     * in the string.
     *
     * @author Dylan Pruitt
     */

    class PHP_CodeSniffer_Sniffs_Syntax_DoubleQuotesWarningSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_CONSTANT_ENCAPSED_STRING );
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
            $sContent = $aTokens[ $iStackPtr ][ 'content' ];

            // ignore new line characters
            $bNewLine = strstr( $sContent, '\n' );
            if( $bNewLine )
            {
                return;
            }

            // ignore "'" for use in queries.
            if( $sContent == '"\'"')
            {
                return;
            }

            $sQuotes = substr( $sContent, 0, 1);

            if( $sQuotes == '"' )
            {
                $sWarning = "Do not use double quotes when there are no variables in the string.";
                $oPHPCSFile->addWarning( $sWarning, $iStackPtr );
            }
        }
    }
?>