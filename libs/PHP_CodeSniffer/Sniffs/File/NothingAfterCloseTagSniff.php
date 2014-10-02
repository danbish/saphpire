<?php
	/**
	 * Sniff to ensure that there is nothing after the PHP closing tag.
	 *
	 * @author Jassiem Moore
	 * @author Ryan Masters
	 */
	class PHP_CodeSniffer_Sniffs_File_NothingAfterCloseTagSniff implements PHP_CodeSniffer_Sniff
	{
		/**
	     * Returns the token types that this sniff is interested in.
	     *
	     * @return  array  Tags constants.
	     */
	    public function register()
	    {
	        return array( T_CLOSE_TAG );
	    }

	    /**
	     * Processes the tokens that this sniff is interested in.
	     *
	     * @param  PHP_CodeSniffer_File $oPHPCSFile      The file where the token was found.
	     * @param  int                  $iStackPointer   The position in the stack where the token was found.
	     *
	     * @return void
	     */
	    public function process( PHP_CodeSniffer_File $oPHPCSFile, $iStackPointer )
	    {
	    	// get the tokens that we're interested in
	        $aTokens = $oPHPCSFile->getTokens();

	        // check if there is anything after the closing tag
	        if( isset( $aTokens[ $iStackPointer + 1 ] ) )
	        {
    			$sError = 'There should not be any characters after the closing PHP tag.';
            	$aData  = array( trim( $aTokens[$iStackPointer + 1 ][ 'content' ] ) );
            	$oPHPCSFile->addError( $sError, $iStackPointer + 1, 'Found', $aData );
	        }
	    }
	}
?>