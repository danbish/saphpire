<?php

	/**
	 * Make sure doc blocks in front of class/inteface follow proper syntax.
	 *     Some more information about this class.
	 * 	
	 * @author 	Jassiem Moore
	 * @author 	Dylan Pruitt
	 * @package sublime
	 * @version 1.0
	 */
	
	class PHP_CodeSniffer_Sniffs_Commenting_ClassDocBlockSniff implements PHP_CodeSniffer_Sniff
	{
		/**
	     * Returns the token types that this sniff is interested in.
	     *
	     * @return array(int)
	     */
	    public function register()
	    {
	        return array( T_DOC_COMMENT, T_CLASS, T_INTERFACE );

	    }//end register()


	    /**
	     * Processes the tokens that this sniff is interested in.
	     *
	     * @param PHP_CodeSniffer_File $oPHPCSFile The file where the token was found.
	     * @param int                  $iStackPtr  The position in the stack where
	     *                                        the token was found.
	     *
	     * @return void
	     */
	    public function process(PHP_CodeSniffer_File $oPHPCSFile, $iStackPtr)
	    {
			$aTokens = $oPHPCSFile->getTokens();

			if( ($aTokens[ $iStackPtr ][ 'type' ] == 'T_CLASS') || 
				($aTokens[ $iStackPtr ][ 'type' ] == 'T_INTERFACE') )
			{
				//make sure doc block comments exist before class
				if( $oPHPCSFile->findPrevious( T_DOC_COMMENT, $iStackPtr - 1 ) === FALSE )
				{
					$sError = "All class/interface declarations require doc block comments.";
					$oPHPCSFile->addError( $sError, $iStackPtr );
					return;
				}
			}

			//if this is after a class/interface declaration ignore it
			if( $oPHPCSFile->findPrevious( array( T_CLASS, T_INTERFACE ), $iStackPtr - 1 ) == true )
			{
				return;
			}

			//ignore first doc comment (/**)
			if( $aTokens[ $iStackPtr ][ 'content' ] == '/**' )
			{
				return;
			}

			//ignore if last doc comment (*/)
			if( $aTokens[ $iStackPtr ][ 'content' ] == '*/' )
			{
				return;
			}

			//for description, previous doc comment will be (/**) and 
			//next doc comment will be continue description (indented), or
			//blank line
			$iPrevDocComLoc = $oPHPCSFile->findPrevious( T_DOC_COMMENT, $iStackPtr - 1 );
			$iNextDocComLoc = $oPHPCSFile->findNext( T_DOC_COMMENT, $iStackPtr + 1 );

			//handle first line (class/interface description)
			if( $aTokens[ $iPrevDocComLoc ][ 'content' ] == '/**' && 
				$aTokens[ $iNextDocComLoc ][ 'content' ] != '*/' )
			{
				$sComment = $aTokens[ $iStackPtr ][ 'content' ];
				//check spacing
				$iSpaces  = subtr_count( $sComment, ' ', 0, 3 );
				if( $iSpaces > 1 )
				{
					$sError = "Initial description line should begin one space after the *.";
					$oPHPCSFile->addError( $sError, $iStackPtr );
				}

				//check for more lines containing description.
				while( strlen( $sComment = $aTokens[ $iStackPtr++ ][ 'content' ] ) > 2 )
				{	
					//make sure the rest of the description is indented properly
					//should be four spaces after initial description, 5 spaces after *
					$iSpaces = subtr_count( $sComment, ' ', 0, 7 );
					if( $iSpaces != 5 )
					{
						$sError = "Subsequent description lines should be indented four 
									spaces after the initial description line";
						$oPHPCSFile->addError( $sError, $iStackPtr );
					}
				}
				//do nothing for a blank line
				return;
			}

			//handle author lines.
			$sComment = $aTokens[ $iStackPtr ][ 'content' ];
			$iCommentStart = stripos( $sComment, '*' );
			$sComment = substr( $sComment, $iCommentStart );
			$iAuthPos = strpos( $sComment, '@author' );
			if( $iAuthPos !== FALSE )
			{
				//only a space should separate * and @author
				if( $iAuthPos != 2 )
				{
					$sError = "Only a single space should separate * from @author label.";
					$oPHPCSFile->addError( $sError, $iStackPtr );
				}
			}

			//handle version and package lines.
			$sComment = $aTokens[ $iStackPtr ][ 'content' ];
			$iCommentStart = stripos( $sComment, '*' );
			$sComment = substr( $sComment, $iCommentStart );
			$iPackStrPos = strpos( $sComment, '@package' );
			if( $iPackStrPos !== FALSE )
			{
				$iPackPos = $iStackPtr;
				//only a space should separate * and @package
				if( $iPackStrPos != 2 )
				{
					$sError = "Only a single space should separate * from @package label.";
					$oPHPCSFile->addError( $sError, $iStackPtr );
				}

				$iVersPos    = $oPHPCSFile->findNext( T_DOC_COMMENT, $iStackPtr + 1 );
				$sComment    = $aTokens[ $iVersPos ][ 'content' ];
				$iCommentStart = stripos( $sComment, '*' );
				$sComment = substr( $sComment, $iCommentStart );
				$iVersStrPos = strpos( $sComment, '@version' );

				//make sure package and version labels are present
				if( $iPackStrPos == TRUE && $iVersStrPos == TRUE )
				{
					$iPackCol = $aTokens[ $iPackPos ][ 'column' ];
					$iVersCol = $aTokens[ $iVersPos ][ 'column' ];

					if(  $iPackCol != $iVersCol )
					{
						//error indentation is not the same
						$sError = "Labels should have the same indentation.";
						$oPHPCSFile->addError( $sError, $iStackPtr+1 );
					}
				}
				else
				{
					//error missing labels in doc block
					$sError = "Package label, version label, or both are missing from class doc block, or the spacing is incorrect.";
					$oPHPCSFile->addError( $sError, $iStackPtr+1 );
				}
			}
	    }
	}

?>