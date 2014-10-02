<?php
	/**
	 * Make sure class name follows naming rules.
	 *
	 * Rules:
	 * - Length of class names must be <= $iMaxLength and >= $iMinLength.
	 * - Must begin with a 'c'.
	 * - Must be InitCap after 'c'.
	 * - May not have any acronyms other than the allowed set in acronyms.ini
	 *
	 * @author Jassiem Moore
	 * @author Ryan Masters
	 */
	class PHP_CodeSniffer_Sniffs_Naming_EnsureCorrectClassNamingSniff implements PHP_CodeSniffer_Sniff
	{
		/**
		 * Minimum length of class names.
		 *
		 * @var integer
		 */
		private $iMinLength = 5;

		/**
		 * Maximum length of class names.
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
	        return array( T_CLASS );
	    }

	    /**
	     * Processes the tokens that this sniff is interested in.
	     *
	     * @param  PHP_CodeSniffer_File $oPHPCSFile 	   The file where the token was found.
	     * @param  int                  $iStackPointer     The position in the stack where the token was found.
	     *
	     * @return void
	     */
	    public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPointer )
	    {
	    	// get all the tokens in this file
			$aTokens = $oPHPCSFile->getTokens();

			// get the position of the class name
			$iClassNamePos = $oPHPCSFile->findNext( T_STRING, $iStackPointer );

			// get the class name
			$sClassName = $aTokens[ $iClassNamePos ][ 'content' ];

			// get the list of acronyms we want to allow
	        $aAcceptedAcros = parse_ini_file( "acronyms.ini" );
	        $sAcceptedAcros = implode( '|', $aAcceptedAcros );

	        // report error if regex fails
	        if( preg_match( "/^(?=.{{$this->iMinLength},{$this->iMaxLength}}$)c(([A-Z]|{$sAcceptedAcros}[A-Z]{0,1})[0-9a-z]+)+$/", $sClassName ) != 1 )
	        {
	        	// add error
    			$sError = 'Class "' . $sClassName . '" does not follow naming rules.';
            	$aData  = array( trim( $aTokens[ ++$iStackPointer ][ 'content' ] ) );
            	$oPHPCSFile->addError( $sError, $iStackPointer, 'ClassName', $aData );
	        }

	        // warn user if numbers are present
        	if( preg_match( '/[0-9].+/', $sClassName ) == 1 )
        	{
            	$sWarning = 'Numbers are allowed in class names, but should be avoided.';
            	$oPHPCSFile->addWarning( $sWarning, $iStackPointer, 'ClassName', $aData );
        	}
	    }
	}
?>