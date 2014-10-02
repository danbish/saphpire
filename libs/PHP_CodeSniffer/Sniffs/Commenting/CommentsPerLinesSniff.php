<?php
    /**
     * This sniff makes sure that code is well commented.
     *
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Commenting_CommentsPerLinesSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Max number of lines that can be uncommented.
         *
         * @var integer
         */
        private $iMaxLines = 20;

        /**
         * Returns the token types that this sniff is interested in.
         *
         * @return array(int)
         */
        public function register()
        {
            return array( T_OPEN_TAG );
        }

        /**
         * Processes the tokens that this sniff is interested in.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile      The file where the token was found.
         * @param int                  $iStackPointer   The position in the stack where the token was found.
         *
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPointer )
        {
            // get the tokens that we're dealing with
            $aTokens = $oPHPCSFile->getTokens();

            // save the original stack pointer
            $iOriginalStackPointer = $iStackPointer;

            // get the line number that this starts on
            $iLine = $aTokens[ $iStackPointer ][ 'line' ];

            // set comment found flag
            $iUncommentedLines = 0;

            // check if this stack pointer is set
            // this is needed so we don't try to check before
            // the beginning of the file or after the end
            $bIsSet = isset( $aTokens[ $iStackPointer ] );

            // scan file
            while( $bIsSet )
            {
                // If a new line is reached, replace $iLine and increment the number of uncommented lines.
                if( $aTokens[ $iStackPointer ][ 'line' ] > $iLine )
                {
                    $iLine = $aTokens[ $iStackPointer ][ 'line' ];

                    // Ignore multiline strings like queries.
                    if( $aTokens[ $iStackPointer ][ 'type' ] != 'T_CONSTANT_ENCAPSED_STRING' )
                    {
                        ++$iUncommentedLines;
                    }

                    if( $iUncommentedLines > $this->iMaxLines )
                    {
                        // reset uncommented lines so we don't get an error every line after $iMaxLines has been reached.
                        $iUncommentedLines = 0;

                        // if a comment was not found, there's an error
                        $sError = 'Add more comments above this line. At least one comment is recommended every ' . $this->iMaxLines . ' lines.';
                        $oPHPCSFile->addWarning( $sError, $iStackPointer, 'CommentsPerLines' );
                    }
                }

                // If a comment is found, reset uncommented lines.
                if( $aTokens[ $iStackPointer ][ 'type' ] == 'T_COMMENT' || $aTokens[ $iStackPointer ][ 'type' ] == 'T_DOC_COMMENT' )
                {
                    $iUncommentedLines = 0;
                }

                // move the stack pointer
                ++$iStackPointer;

                // check if the array key exists
                $bIsSet = isset( $aTokens[ $iStackPointer ] );
            }
        }
    }
?>