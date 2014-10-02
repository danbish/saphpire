<?php
	/**
	 * Make sure variable name follows naming rules.
	 *
	 * @author Jassiem Moore
	 * @author Ryan Masters
	 */
	class PHP_CodeSniffer_Sniffs_Naming_EnsureCorrectConstantNamingSniff implements PHP_CodeSniffer_Sniff
	{
		/**
		 * Minimum length of constant names.
		 *
		 * @var integer
		 */
		private $iMinLength = 5;

		/**
		 * Maximum length of constant names.
		 *
		 * @var integer
		 */
		private $iMaxLength = 30;

		/**
	     * Returns the token types that this sniff is interested in.
	     *
	     * @return array(int)
	     */
	    public function register()
	    {
	        return array( T_CONST );
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
	    	// get all the tokens for this file
	        $aTokens = $oPHPCSFile->getTokens();

	        // get the position of the constant's name
	        $iConstNamePos = $oPHPCSFile->findNext( T_STRING, $iStackPointer );

	        // get the constant's name
	        $sConstName = $aTokens[ $iConstNamePos ][ 'content' ];

	        // report error if regex fails
	        if( preg_match( "/^(?=.{{$this->iMinLength},{$this->iMaxLength}}$)[aifsbosr][A-Z0-9_]+$/", $sConstName ) != 1 )
	        {
	        	// set the error
    			$sError = "Constant \"$sConstName\" does not follow naming rules.";
            	$aData   = array( trim( $aTokens[ ++$iStackPointer ][ 'content' ] ) );
            	$oPHPCSFile->addError( $sError, $iStackPointer, 'Found', $aData );
	        }

	        // warn user if numbers are present
        	if( preg_match( '/[0-9].+/', $sConstName ) == 1 )
        	{
            	$sWarning = 'Numbers are allowed in user-defined constants, but should be avoided.';
            	$oPHPCSFile->addWarning( $sWarning, $iStackPointer, 'Found', $aData );
        	}
	    }
	}
?>