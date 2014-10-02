<?php
    /**
     * A sniff that shows errors when '<>' is used.
     *
     * @author Dylan Pruitt
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Syntax_NoNotEqualSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_IS_NOT_EQUAL );
        }

        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile     The file being scanned.
         * @param int                  $iStackPointer  The position of the current token in the stack passed in $aTokens.
         *
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPointer )
        {
            // get all the tokens in the file
            $aTokens = $oPHPCSFile->getTokens();

            // check if this is != or <>
            if( $aTokens[ $iStackPointer ][ 'content' ] == '<>' )
            {
                // set the error
                $sError = "Use of the not equal operator '<>' is not allowed.";
                $oPHPCSFile->addError( $sError, $iStackPointer );
            }
        }
    }
?>