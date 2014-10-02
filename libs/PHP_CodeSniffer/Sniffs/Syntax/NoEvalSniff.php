<?php

	/**
	 * Give error if eval() is used.
	 *
	 * @author Jassiem Moore
	 */
	
	class PHP_CodeSniffer_Sniffs_Syntax_NoEvalSniff implements PHP_CodeSniffer_Sniff
	{
		/**
	     * Returns the token types that this sniff is interested in.
	     *
	     * @return array(int)
	     */
	    public function register()
	    {
	        return array( T_EVAL );

	    }//end register()


	    /**
	     * Processes the tokens that this sniff is interested in.
	     *
	     * @param PHP_CodeSniffer_File $PHPCSFile The file where the token was found.
	     * @param int                  $iStackPtr  The position in the stack where
	     *                                        the token was found.
	     *
	     * @return void
	     */
	    public function process(PHP_CodeSniffer_File $PHPCSFile, $iStackPtr)
	    {
			$tokens = $PHPCSFile->getTokens();

			//if process is called eval() is being used.
			$sError = 'Use of eval() is prohibited.';
			$data   = array( trim( $tokens[ $iStackPtr ][ 'content' ] ) );
    		$PHPCSFile->addError( $sError, $iStackPtr, 'Found', $data );
	    }
	}

?>