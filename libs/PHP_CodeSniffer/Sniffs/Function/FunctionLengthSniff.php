<?php
	/**
	 * Give warning if function length is 100+, give error if function length is 200+.
	 *
	 * @author Jassiem Moore
	 * @author Ryan Masters
	 */
	class PHP_CodeSniffer_Sniffs_Function_FunctionLengthSniff implements PHP_CodeSniffer_Sniff
	{
		/**
	     * Returns the token types that this sniff is interested in.
	     *
	     * @return array(int)
	     */
	    public function register()
	    {
	        return array( T_FUNCTION );
	    }

	    /**
	     * Processes the tokens that this sniff is interested in.
	     *
	     * @param PHP_CodeSniffer_File $oPHPCSFile The file where the token was found.
	     * @param int                  $iStackPointer  The position in the stack where the token was found.
	     *
	     * @return void
	     */
	    public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPointer )
	    {
	    	// get all the tokens in the file
			$aTokens = $oPHPCSFile->getTokens();

			// if the scope opener and closer aren't there, it's a declaration not a definition
			if( isset( $aTokens[ $iStackPointer ][ 'scope_opener' ] ) && isset( $aTokens[ $iStackPointer ][ 'scope_closer' ] ) )
			{
				// get positions of opening and closing bracket for function
				$iOpenBrack  = $aTokens[ $iStackPointer ][ 'scope_opener' ];
				$iCloseBrack = $aTokens[ $iStackPointer ][ 'scope_closer' ];
				$iFuncSize   = $aTokens[ $iCloseBrack ][ 'line' ] - $aTokens[ $iOpenBrack ][ 'line' ];

				// throw warning for 100 < line <= 200
				if( $iFuncSize > 100 && $iFuncSize <= 200 )
				{
					// add a warning
					$sWarn  = 'Function length is greater than 100 lines.';
			    	$oPHPCSFile->addWarning( $sWarn, $iStackPointer );
				}
				// throw error for line > 200
				elseif( $iFuncSize > 200 )
				{
					// add an error
					$sError = 'Function length has exceeded 200 lines.';
			    	$oPHPCSFile->addError( $sError, $iStackPointer );
				}
			}
	    }
	}
?>