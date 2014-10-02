<?php
    /**
     * This sniff makes sure that functions are spaced correctly.
     * Code is taken from Squiz standards function spacing sniff.
     */
    class PHP_CodeSniffer_Sniffs_Spacing_FunctionSpacingSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * The number of blank lines between functions.
         *
         * @var int
         */
        public $spacing = 1;


        /**
         * Returns an array of tokens this test wants to listen for.
         *
         * @return array
         */
        public function register()
        {
            return array( T_FUNCTION );

        }//end register()


        /**
         * Processes this sniff when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
         * @param int                  $stackPtr  The position of the current token
         *                                        in the stack passed in $tokens.
         *
         * @return void
         */
        public function process(PHP_CodeSniffer_File $phpcsFile, $iStackPtr)
        {
            $tokens        = $phpcsFile->getTokens();
            $this->spacing = (int) $this->spacing;

            /*
                Check the number of blank lines
                after the function.
            */

            if (isset($tokens[$iStackPtr]['scope_closer']) === false) {
                // Must be an interface method, so the closer is the semi-colon.
                $closer = $phpcsFile->findNext(T_SEMICOLON, $iStackPtr);
            } else {
                $closer = $tokens[$iStackPtr]['scope_closer'];
            }

            $nextLineToken = null;
            for ($i = $closer; $i < $phpcsFile->numTokens; $i++) {
                if (strpos($tokens[$i]['content'], $phpcsFile->eolChar) === false) {
                    continue;
                } else {
                    $nextLineToken = ($i + 1);
                    break;
                }
            }

            if (is_null($nextLineToken) === true) {
                // Never found the next line, which means
                // there are 0 blank lines after the function.
                $foundLines = 0;
            } else {
                $nextContent = $phpcsFile->findNext(array(T_WHITESPACE), ($nextLineToken + 1), null, true);
                if ($nextContent === false) {
                    // We are at the end of the file.
                    $foundLines = 0;
                } else {
                    $foundLines = ($tokens[$nextContent]['line'] - $tokens[$nextLineToken]['line']);
                }
            }

            if ($foundLines !== $this->spacing)
            {
                // Find the next function, if none are found return.
                $iNextFunction = $phpcsFile->findNext( T_FUNCTION, $iStackPtr + 1 );
                if( !$iNextFunction )
                {
                    return;
                }

                $error = 'Expected %s blank line';
                if ($this->spacing !== 1) {
                    $error .= 's';
                }

                $error .= ' after function; %s found';
                $data   = array(
                           $this->spacing,
                           $foundLines
                          );
                $phpcsFile->addError($error, $closer, 'After', $data);
            }
        }//end process()
    }//end class
?>
