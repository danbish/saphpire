<?php
    /**
     * Make sure variable name follows naming rules.
     *
     * @author Jassiem Moore
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Naming_EnsureCorrectVariableNamingSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * Minimum length of function names.
         *
         * @var integer
         */
        private $iMinLength = 5;

        /**
         * Maximum length of function names.
         *
         * @var integer
         */
        private $iMaxLength = 30;

        /**
         * Built-in PHP variables that are exceptions to our naming conventions.
         *
         * @var array
         */
        private $aAllowedExceptions = array(
            '$_POST',
            '$_GET',
            '$_SERVER',
            '$_FILES',
            '$_COOKIE',
            '$_SESSION',
            '$_REQUEST',
            '$_ENV',
            '$_GLOBALS',
            '$this',
            '$i',
            '$j',
            '$k',
            '$oDb'
        );

        /**
         * Generic variable names that are not allowed.
         *
         * @var array
         */
        private $aGenerics = array(
            '$sString',
            '$sQuery',
            '$aArray',
            '$sTemp',
            '$sConcat',
            '$aTemp',
            '$iCount',
            '$fFloat',
            '$iInt',
            '$bBool',
            '$oObject',
            '$rHandle',
            '$rhHandle',
            '$rResource',
            '$rhResource'
        );

        /**
         * Returns the token types that this sniff is interested in.
         *
         * @return array(int)
         */
        public function register()
        {
            return array( T_VARIABLE );
        }

        /**
         * Processes the tokens that this sniff is interested in.
         *
         * @param PHP_CodeSniffer_File $oPHPCSFile The file where the token was found.
         * @param int                  $iStackPointer  The position in the stack where
         *                                        the token was found.
         * @return void
         */
        public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPointer )
        {
            // get all the tokens in the file
            $aTokens = $oPHPCSFile->getTokens();

            // get the variable's name
            $sVarName = $aTokens[ $iStackPointer ][ 'content' ];

            // get all acceptable acronyms
            $aAcceptedAcros = parse_ini_file( "acronyms.ini" );
            $sAcceptedAcros = implode( '|', $aAcceptedAcros );

            /* report error if regex fails or it's in the list of generic names */

            // check for generic names
            if( in_array( $sVarName, $this->aGenerics ) )
            {
                // set an error
                $sError = 'Variable "' . $sVarName . '" is too generic.';
                $aData  = array( trim( $aTokens[ $iStackPointer ][ 'content' ] ) );
                $oPHPCSFile->addError( $sError, $iStackPointer, 'Found', $aData );
            }
            // check naming rules
            elseif( !in_array( $sVarName, $this->aAllowedExceptions )
                    && preg_match( "/^(?=.{{$this->iMinLength},{$this->iMaxLength}})[\$]([aifbosv]|rh|ts|mt)(([A-Z]|{$sAcceptedAcros}[A-Z]{0,1})[0-9a-z]+)|{$sAcceptedAcros}+$/", $sVarName ) != 1 )
            {
                // set an error
                $sError = 'Variable "' . $sVarName . '" does not follow naming rules.';
                $aData  = array( trim( $aTokens[ $iStackPointer ][ 'content' ] ) );
                $oPHPCSFile->addError( $sError, $iStackPointer, 'Found', $aData );
            }

            // warn user if numbers are present
            if( preg_match( '/[0-9].+/', $sVarName ) == 1 )
            {
                $sWarning = 'Numbers are allowed in variable names, but should be avoided.';
                $oPHPCSFile->addWarning( $sWarning, $iStackPointer );
            }
        }
    }
?>