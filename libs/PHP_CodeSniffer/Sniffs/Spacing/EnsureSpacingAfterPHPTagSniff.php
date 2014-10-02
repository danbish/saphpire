<?php
    /**
     * This sniff makes sure that all code after inside PHP tags is indented by at least 4 spaces.
     * It also ensures that no indentation is applied to open or close tags.
     *
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Spacing_EnsureSpacingAfterPHPTagSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Returns the token types that this sniff is interested in.
         *
         * @return array
         */
        public function register()
        {
            return array( T_OPEN_TAG );
        }

        /**
         * Processes the tokens that this sniff is interested in.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile     The file where the token was found.
         * @param int                  $iStackPointer  The position in the stack where the token was found.
         *
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPointer )
        {
            // get the tokens
            $aTokens = $oPHPCSFile->getTokens();

            // check if there is anything before the opening PHP tag
            if( $aTokens[ $iStackPointer ][ 'column' ] != 1 )
            {
                $sError = 'Incorrect indentation. Open PHP tags should have no indentation.';
                $oPHPCSFile->addError( $sError, $iStackPointer, 'PHPTags' );
            }

            // check all the tokens inside the PHP tags
            $iSize = count( $aTokens );
            for( $i = 1; $i < $iSize; ++$i )
            {
                // check all tokens after open tag except whitespace, doc blocks, and close tags
                if( $aTokens[ $i ][ 'column' ] < 5
                    && $aTokens[ $i ][ 'type' ] != 'T_WHITESPACE'
                    && $aTokens[ $i ][ 'type' ] != 'T_DOC_COMMENT'
                    && $aTokens[ $i ][ 'type' ] != 'T_OPEN_TAG'
                    && $aTokens[ $i ][ 'type' ] != 'T_CLOSE_TAG'
                    && $aTokens[ $i ][ 'type' ] != 'T_CONSTANT_ENCAPSED_STRING' )
                {
                    // add an error
                    $sError = 'Incorrect indentation. Needs to be at least 4 spaces from opening tag.';
                    $oPHPCSFile->addError( $sError, $i, 'PHPTags');
                }
                // check close tags
                elseif( $aTokens[ $i ][ 'column' ] > 1 && $aTokens[ $i ][ 'type' ] == "T_CLOSE_TAG" )
                {
                    // add an error
                    $sError = 'Incorrect indentation. Close PHP tags should have no indentation.';
                    $oPHPCSFile->addError( $sError, $i, 'PHPTags');
                }
            }
        }
    }
?>