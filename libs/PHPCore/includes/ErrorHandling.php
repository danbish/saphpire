<?php
	/**
	 * Functions that handle errors and exceptions.
	 *
	 * @author  Ryan Masters
	 *
	 * @package Core_0
	 * @version 0.1
	 */

	/**
	 * Capture exception data for logging and rethrow to the next level.
	 *
	 * If the exception does not have a previous exception, then it will
	 * be logged as the originating exception.
	 *
	 * If an error code is not sent in, it will be extracted from the
	 * exception or the previous exception. If it is still not set,
	 * a default value of 0 will be used.
	 *
	 * If a class name is not sent in, it will use the class of the exception.
	 *
	 * If a message is not sent in, it will use the previous exception's message.
	 *
	 * @param	Exception 				$oException			Exception that has been caught.
	 * @param	null | string | int		$vErrorCode			Error code to throw with new Exception.
	 * @param	null | string			$vMessage			Message to throw with new exception.
	 * @param	null | string			$vExceptionClass	Class to rethrow.
	 *
	 * @return Exception
	 */
	function BubbleException( $oException, $vErrorCode = null, $vMessage = null, $vExceptionClass = null )
	{
		// get the code
		$vCurrentCode = $oException->getCode();

		// get error message
		$vMessage = $oException->getMessage();

		// get the previous exception
		$oPrevException = $oException->getPrevious();

		// if no error code was passed in, set it to the current, previous, or default
		if( $vErrorCode === null )
		{
			// if the current code is null, check the previous exception
			if( $vCurrentCode === null )
			{
				// if there is no previous exception, default the error code
				if( $oPrevException === null )
				{
					$vErrorCode = 0;
				}
				// try to use the previous exception's error code
				else
				{
					// get the previous error code
					$vPrevCode = $oPrevException->getCode();

					// if there is no code, default it
					if( $vPrevCode === null )
					{
						$vErrorCode = 0;
					}
					// use the previous exception's error code
					else
					{
						$vErrorCode = $vPrevCode;
					}
				}
			}
			// current code is not null, so use it's code to bubble up
			else
			{
				$vErrorCode = $vCurrentCode;
			}
		}

		// set the exception type
		$sExceptionClass = $vExceptionClass;
		if( $vExceptionClass === null )
		{
			$sExceptionClass = get_class( $oException );
		}

		// set the message to accompany new exception
		if( $vMessage === null )
		{
			// get the previous message
			$aCodesAndMessages = cLogger::GetCodesAndMessages();
			$sPreviousMessage = '';
			$iMessageCount = count( $aCodesAndMessages ) - 1;
			for( $i = $iMessageCount; $i > -1; --$i )
			{
				list( $vTempCode, $sTempMessage ) = $aCodesAndMessages[ $i ];
				if( strtolower( substr( $sTempMessage, 0, 10 ) ) !== 'rethrowing' )
				{
					$sPreviousMessage = $sTempMessage;
					break;
				}
			}
			$sMessage = 'Rethrowing caught exception: ' . $sPreviousMessage;
		}
		else
		{
			$sMessage = $vMessage;
		}

		// save the code and the message
		cLogger::AddCodeAndMessage( $vErrorCode, $sMessage );

		// check if this is the initial exception
		if( $oPrevException === null )
		{
			// log the exception
			cLogger::LogException( $oException );
		}

		// create the bubbled exception and return it
		return new $sExceptionClass( $sMessage, $vErrorCode, $oException );
	}

	/**
	 * Returns the soft error message for the specified error code.
	 *
	 * @param	int | string $sErrorCode
	 *
	 * @return	string
	 */
	function GetErrorMessage( $sErrorCode )
	{
		// get config information
		$aConfig = GetConfig( true );
		if( !isset( $aConfig[ 'error' ][ 0 ] ) )
		{
			$aConfig[ 'error' ] = array( $aConfig[ 'error' ] );
		}

		// search for this error
		$iErrorCount = count( $aConfig[ 'error' ] );
		for( $i = 0; $i < $iErrorCount; ++$i )
		{
			// if we've found the error we're looking for, send back the message
			if( $aConfig[ 'error' ][ $i ][ 'code' ] == $sErrorCode )
			{
				return $aConfig[ 'error' ][ $i ][ 'message' ];
			}
		}

		// send back the default message
		return 'An unexpected error has occured.';
	}

	/**
     * Centralized exception handling.
     *
     * All uncaught exceptions or triggered errors will be handled
     * by this function all caught exceptions will bubble up into
     * this function.
	 *
	 * If the environment is local or dev, shows the exception stack trace.
	 * Otherwise, uses LoadErrors() and displays soft error message.
	 *
	 * @param  Exception $oException
	 *
	 * @return null
	 */
	function ExceptionHandler( $oException )
	{
		// if this is a top level problem, make sure to log the exception
		if( $oException->getPrevious() === null )
		{
			BubbleException( $oException );
		}

		// check app environment
		if( !defined( 'sAPPLICATION_ENV' )
            || sAPPLICATION_ENV != 'prod'
            || IsDevLoggedIn() )
		{
			// get developer presentation layer
			require_once( sCORE_INC_PATH . '/classes/cDevPresentation.php' );
			$oPresentation = new cDevPresentation();

			// display most recent exception
			echo $oPresentation->GetExceptionLogPage( true );
		}
		// get page to display based on error code
		else
		{
			// send them off to the generic error file with the code
			header( 'Location: ./Error.php?error=' . $oException->getCode() );
			exit(1);
		}
	}

	/**
	 * Logs all errors that occur.
	 *
	 * @param  integer  $iNumber   Error number.
	 * @param  string   $sMessage  Error message.
	 * @param  string   $sFile     File error occurred in.
	 * @param  string   $sLine     Line that caused the error.
	 * @param  array    $aContext  Variables in scope at the time the error occurred.
	 *
	 * @return null
	 */
	function ErrorHandler( $iNumber, $sMessage, $sFile, $sLine, $aContext )
	{
		// set the message to log and show developers
		$sLogMessage = "Error '$iNumber' occurred in $sFile on line $sLine. Message: $sMessage. Context: <pre>" . print_r( $aContext, true ) . '</pre>';

		// log the error
		cLogger::Log( 'error', $sLogMessage );

		// check app environment
		if( !defined( 'sAPPLICATION_ENV' )
            || sAPPLICATION_ENV != 'prod'
            || IsDevLoggedIn() )
		{
			// @todo: update this to cleanup the output
			echo error_reporting() != 0 ? $sLogMessage : '';
		}
		else
		{
			// send them off to the generic error file with the code
			header( 'Location: ./Error.php?error=' . $iNumber );
			exit(1);
		}
	}

	// set the ErrorHandler function to run every time there is an error
	set_error_handler( 'ErrorHandler' );
?>