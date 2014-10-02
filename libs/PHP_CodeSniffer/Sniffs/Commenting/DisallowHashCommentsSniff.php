<?php
    /**
     * This sniff throws up a warning for the use of Perl style hash comments.
     *
     * @author Jassiem Moore
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Commenting_DisallowHashCommentsSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Returns the token types that this sniff is interested in.
         *
         * @return array(int)
         */
        public function register()
        {
            return array( T_COMMENT );
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
            // get all the tokens in the file
            $aTokens = $oPHPCSFile->getTokens();

            // check if this comment is a hash comment
            if( $aTokens[ $iStackPointer ][ 'content' ]{ 0 } === '#' )
    		{
                // add a warning for this comment
                $sWarning = 'Hash comments are prohibited; found %s';
                $aData    = array( trim( $aTokens[ $iStackPointer ][ 'content' ] ) );
                $oPHPCSFile->addWarning( $sWarning, $iStackPointer, 'HashComment', $aData );
            }
        }
    }
?>