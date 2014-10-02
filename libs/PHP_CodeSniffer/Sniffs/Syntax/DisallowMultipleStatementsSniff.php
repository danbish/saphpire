<?php
    /**
     * This sniff makes sure that there is only one statement per line.
     * Code is taken from Generic standards disallow multiple statements sniff.
     */
    class PHP_CodeSniffer_Sniffs_Syntax_DisallowMultipleStatementsSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Returns an array of tokens this test wants to listen for.
         *
         * @return array
         */
        public function register()
        {
            return array(T_SEMICOLON);

        }//end register()


        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
         * @param int                  $stackPtr  The position of the current token in
         *                                        the stack passed in $tokens.
         *
         * @return void
         */
        public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
        {
            $tokens = $phpcsFile->getTokens();

            $prev = $phpcsFile->findPrevious(T_SEMICOLON, ($stackPtr - 1));
            if ($prev === false) {
                return;
            }

            // Ignore multiple statements in a FOR condition.
            if (isset($tokens[$stackPtr]['nested_parenthesis']) === true) {
                foreach ($tokens[$stackPtr]['nested_parenthesis'] as $bracket) {
                    if (isset($tokens[$bracket]['parenthesis_owner']) === false) {
                        // Probably a closure sitting inside a function call.
                        continue;
                    }

                    $owner = $tokens[$bracket]['parenthesis_owner'];
                    if ($tokens[$owner]['code'] === T_FOR) {
                        return;
                    }
                }
            }

            if ($tokens[$prev]['line'] === $tokens[$stackPtr]['line']) {
                $error = 'Each PHP statement must be on a line by itself';
                $phpcsFile->addError($error, $stackPtr, 'SameLine');
                return;
            }
        }//end process()
    }//end class
?>
