<?php
    /**
    * A sniff that shows errors when the elvis operator (?:) is used.
    *
    * @author Dylan Pruitt
    * @author Ryan Masters
    */
    class PHP_CodeSniffer_Sniffs_Syntax_ElvisHasLeftTheBuildingSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_INLINE_THEN );
        }

        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile The file being scanned.
         * @param int                  $iStackPtr  The position of the current token in the
         *                                         stack passed in $aTokens.
         *
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPtr )
        {
            $aTokens = $oPHPCSFile->getTokens();

            if( $aTokens[ $iStackPtr + 1 ][ 'type' ] == 'T_INLINE_ELSE' )
            {
                $sError = "The elvis operator (?:) is not allowed.";
                $oPHPCSFile->addError( $sError, $iStackPtr );
            }
        }
    }
?>