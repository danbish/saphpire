<?php
    /**
     * Ensures that switch cases are formatted appropriately.
     *
     * @author Ryan Masters
     */
    class PHP_CodeSniffer_Sniffs_Spacing_SwitchDeclarationsSniff implements PHP_CodeSniffer_Sniff
    {
        /**
         * A list of tokenizers this sniff supports.
         *
         * @var array
         */
        public $supportedTokenizers = array( 'PHP', 'JS' );

        /**
         * Returns an array of tokens this test wants to listen for.
         *
         * @return array
         */
        public function register()
        {
            return array( T_SWITCH );
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
            // get all the tokens for this file
            $aTokens = $oPHPCSFile->getTokens();

            // We can't process SWITCH statements unless we know where they start and end.
            if( isset( $aTokens[ $iStackPointer ][ 'scope_opener' ]) === false
                || isset( $aTokens[ $iStackPointer ][ 'scope_closer' ]) === false )
            {
                return;
            }

            $aSwitch        = $aTokens[ $iStackPointer ];
            $iNextCase      = $iStackPointer;
            $iCaseAlignment = ( $aSwitch[ 'column' ] + 4 );
            $iCaseCount     = 0;
            $bFoundDefault  = false;

            while (( $iNextCase = $oPHPCSFile->findNext(array(T_CASE, T_DEFAULT, T_SWITCH), ( $iNextCase + 1), $aSwitch[ 'scope_closer' ])) !== false)
            {
                // Skip nested SWITCH statements; they are handled on their own.
                if( $aTokens[ $iNextCase ][ 'code' ] === T_SWITCH)
                {
                    $iNextCase = $aTokens[ $iNextCase ][ 'scope_closer' ];
                    continue;
                }

                if( $aTokens[ $iNextCase ][ 'code' ] === T_DEFAULT)
                {
                    $sType         = 'Default';
                    $bFoundDefault = true;
                }
                else {
                    $sType = 'Case';
                    ++$iCaseCount;
                }

                if( $aTokens[ $iNextCase ][ 'content' ] !== strtolower( $aTokens[ $iNextCase ][ 'content' ]))
                {
                    $sExpected = strtolower( $aTokens[ $iNextCase ][ 'content' ]);
                    $sError    = strtoupper( $sType) . ' keyword must be lowercase; expected "%s" but found "%s"';
                    $data     = array( $sExpected, $aTokens[ $iNextCase ][ 'content' ], );
                    $oPHPCSFile->addError( $sError, $iNextCase, $sType.'NotLower', $data);
                }

                if( $aTokens[ $iNextCase ][ 'column' ] !== $iCaseAlignment)
                {
                    $sError = strtoupper( $sType) . ' keyword must be indented 4 spaces from SWITCH keyword';
                    $oPHPCSFile->addError( $sError, $iNextCase, $sType.'Indent' );
                }

                if( $sType === 'Case'
                    && ( $aTokens[( $iNextCase + 1)][ 'type' ] !== 'T_WHITESPACE'
                    || $aTokens[( $iNextCase + 1)][ 'content' ] !== ' ' )
                )
                {
                    $sError = 'CASE keyword must be followed by a single space';
                    $oPHPCSFile->addError( $sError, $iNextCase, 'SpacingAfterCase' );
                }

                $iOpener = $aTokens[ $iNextCase ][ 'scope_opener' ];
                if( $aTokens[( $iOpener - 1)][ 'type' ] !== 'T_WHITESPACE' )
                {
                    $sError = 'There must be a space before the colon in a ' .strtoupper( $sType) . ' statement';
                    $oPHPCSFile->addError( $sError, $iNextCase, 'SpaceBeforeColon' . $sType);
                }

                $iNextBreak = $aTokens[ $iNextCase ][ 'scope_closer' ];
                if( $aTokens[ $iNextBreak][ 'code' ] === T_BREAK || $aTokens[ $iNextBreak][ 'code' ] === T_RETURN)
                {
                    if( $aTokens[ $iNextBreak][ 'scope_condition' ] === $iNextCase)
                    {
                        // Only need to check a couple of things once, even if the
                        // break is shared between multiple case statements, or even
                        // the default case.
                        if( $aTokens[ $iNextBreak][ 'column' ] !== $iCaseAlignment + 4 )
                        {
                            $sError = 'Case breaking statement must be indented 8 spaces from SWITCH keyword.';
                            $oPHPCSFile->addError( $sError, $iNextBreak, 'BreakIndent' );
                        }

                        $iBreakLine = $aTokens[ $iNextBreak][ 'line' ];
                        $iPrevLine  = 0;
                        for ( $i = ( $iNextBreak - 1); $i > $iStackPointer; $i-- )
                        {
                            if( $aTokens[ $i][ 'type' ] !== 'T_WHITESPACE' )
                            {
                                $iPrevLine = $aTokens[ $i][ 'line' ];
                                break;
                            }
                        }

                        if( $iPrevLine !== ( $iBreakLine - 1 ) )
                        {
                            $sError = 'Blank lines are not allowed before case breaking statements';
                            $oPHPCSFile->addError( $sError, $iNextBreak, 'SpacingBeforeBreak' );
                        }

                        $iBreakLine = $aTokens[ $iNextBreak][ 'line' ];
                        $nextLine  = $aTokens[ $aTokens[ $iStackPointer ][ 'scope_closer' ] ][ 'line' ];
                        $semicolon = $oPHPCSFile->findNext( T_SEMICOLON, $iNextBreak );
                        for ( $i = ( $semicolon + 1); $i < $aTokens[ $iStackPointer ][ 'scope_closer' ]; ++$i )
                        {
                            if( $aTokens[ $i][ 'type' ] !== 'T_WHITESPACE' )
                            {
                                $nextLine = $aTokens[ $i][ 'line' ];
                                break;
                            }
                        }

                        if( $sType === 'Case' )
                        {
                            // Ensure the BREAK statement is followed by
                            // a single blank line, or the end switch brace.
                            if( $nextLine !== ( $iBreakLine + 2 ) && $i !== $aTokens[ $iStackPointer ][ 'scope_closer' ])
                            {
                                $sError = 'Case breaking statements must be followed by a single blank line';
                                $oPHPCSFile->addError( $sError, $iNextBreak, 'SpacingAfterBreak' );
                            }
                        }
                        else {
                            // Ensure the BREAK statement is not followed by a blank line.
                            if( $nextLine !== ( $iBreakLine + 1 ) )
                            {
                                $sError = 'Blank lines are not allowed after the DEFAULT case\'s breaking statement';
                                $oPHPCSFile->addError( $sError, $iNextBreak, 'SpacingAfterDefaultBreak' );
                            }
                        }

                        $caseLine = $aTokens[ $iNextCase ][ 'line' ];
                        $nextLine = $aTokens[ $iNextBreak][ 'line' ];
                        for ( $i = ( $iOpener + 1); $i < $iNextBreak; $i++)
                        {
                            if( $aTokens[ $i][ 'type' ] !== 'T_WHITESPACE' )
                            {
                                $nextLine = $aTokens[ $i][ 'line' ];
                                break;
                            }
                        }

                        if( $nextLine !== ( $caseLine + 1))
                        {
                            $sError = 'Blank lines are not allowed after ' .strtoupper( $sType) . ' statements';
                            $oPHPCSFile->addError( $sError, $iNextCase, 'SpacingAfter' . $sType);
                        }
                    }//end if

                    if( $aTokens[ $iNextBreak][ 'code' ] === T_BREAK)
                    {
                        if( $sType === 'Case' )
                        {
                            // Ensure empty CASE statements are not allowed.
                            // They must have some code content in them. A comment is not enough.
                            // But count RETURN statements as valid content if they also
                            // happen to close the CASE statement.
                            $foundContent = false;
                            for ( $i = ( $aTokens[ $iNextCase ][ 'scope_opener' ] + 1); $i < $iNextBreak; $i++)
                            {
                                if( $aTokens[ $i][ 'code' ] === T_CASE)
                                {
                                    $i = $aTokens[ $i][ 'scope_opener' ];
                                    continue;
                                }

                                if(in_array( $aTokens[ $i][ 'code' ], PHP_CodeSniffer_Tokens::$emptyTokens) === false)
                                {
                                    $foundContent = true;
                                    break;
                                }
                            }

                            if( $foundContent === false)
                            {
                                $sError = 'Empty CASE statements are not allowed';
                                $oPHPCSFile->addError( $sError, $iNextCase, 'EmptyCase' );
                            }
                        }
                        else {
                            // Ensure empty DEFAULT statements are not allowed.
                            // They must (at least) have a comment describing why
                            // the default case is being ignored.
                            $foundContent = false;
                            for ( $i = ( $aTokens[ $iNextCase ][ 'scope_opener' ] + 1); $i < $iNextBreak; $i++)
                            {
                                if( $aTokens[ $i][ 'type' ] !== 'T_WHITESPACE' )
                                {
                                    $foundContent = true;
                                    break;
                                }
                            }

                            if( $foundContent === false)
                            {
                                $sError = 'Comment required for empty DEFAULT case';
                                $oPHPCSFile->addError( $sError, $iNextCase, 'EmptyDefault' );
                            }
                        }//end if
                    }//end if
                }
            }

            if( $bFoundDefault === false)
            {
                $sError = 'All SWITCH statements must contain a DEFAULT case';
                $oPHPCSFile->addError( $sError, $iStackPointer, 'MissingDefault' );
            }

            if( $aTokens[ $aSwitch[ 'scope_closer' ] ][ 'column' ] !== $aSwitch[ 'column' ])
            {
                $sError = 'Closing brace of SWITCH statement must be aligned with SWITCH keyword';
                $oPHPCSFile->addError( $sError, $aSwitch[ 'scope_closer' ], 'CloseBraceAlign' );
            }

            if( $iCaseCount === 0)
            {
                $sError = 'SWITCH statements must contain at least one CASE statement';
                $oPHPCSFile->addError( $sError, $iStackPointer, 'MissingCase' );
            }
        }
    }
?>