<?php
    /**
    * A sniff that shows errors when debug functions are used.
    *
    * @author Dylan Pruitt
    */
    class PHP_CodeSniffer_Sniffs_Function_NoDebugFunctionsSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Registers the tokens that this sniff wants to listen for.
         *
         * @return void
         */
        public function register()
        {
            return array( T_STRING );
        }

        /**
         * Processes this test, when one of its tokens is encountered.
         *
         * @param PHP_CodeSniffer_File $oPhpcsFile The file being scanned.
         * @param int                  $iStackPtr  The position of the current token in the
         *                                         stack passed in $aTokens.
         *
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPhpcsFile, $iStackPtr )
        {
            // get all the tokens in the file
            $aTokens = $oPhpcsFile->getTokens();

            // regular expression for function declarations
            $sFunctionRegex = '/function[\s\n]+(\S+)[\s\n]*\(/';

            // array to hold functions
            $aFunctions = array();

            // get contents of debug.php
            $sCoreDirectory = dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) );
            $sFileContents = file_get_contents( $sCoreDirectory . '\libs\PHPCore\includes\Debug.php' );

            // get all functions in file contents and add them to the function array
            preg_match_all( $sFunctionRegex, $sFileContents, $aFunctions );
            if( count( $aFunctions ) > 1 )
            {
                $aFunctions = $aFunctions[ 1 ];
            }

            // check to see if string is a function call
            if( $aTokens[ $iStackPtr + 1 ][ 'type' ] == 'T_OPEN_PARENTHESIS' )
            {
                // if the function call is in the array from debug.php add an error
                foreach( $aFunctions as $sFunction )
                {
                    if( $aTokens[ $iStackPtr ][ 'content' ] == $sFunction )
                    {
                        $sError = "Debug function used.";
                        $oPhpcsFile->addError( $sError, $iStackPtr );
                    }
                }
            }
        }
    }
?>