<?php
	/**
	 * Debugging functions. Output any amount of variables with v(),
	 * output variables and stop execution with dv(), and profile
	 * with saveTime() and getTotalTime.
	 *
	 * @author  Ryan Masters
	 *
	 * @package Core_0
	 * @version 0.1
	 */

	/**
	 * Print a formatted version of all params.
	 *
	 * @param 	OPTIONAL *	Anything can be passed in.
	 * @return	null
	 */
	function v()
	{
		// get all the arguments passed to this function
	    $args = func_get_args();

	    // output all the arguments in a clean format
	    foreach( $args as $arg )
	    {
	    	if( !empty( $arg ) )
	    	{
		        echo ( ( bIS_CLI ) ? '' : '<pre>' ),  print_r( $arg, true ),  ( ( bIS_CLI ) ? "\n\n" : '</pre>' );
	    	}
		    else
		    {
		    	echo ( ( bIS_CLI ) ? '' : '<pre>' ),  var_dump( $arg ),  ( ( bIS_CLI ) ? "\n\n" : '</pre>' );
		    }
	    }

		// get info about the function that called this
    	$aCallers = debug_backtrace();

    	// set the caller correctly
    	if( isset( $aCallers[ 1 ] ) )
    	{
    		// get the caller
    		$aCaller = $aCallers[ 1 ];
    	}
    	else
    	{
    		// get the caller
    		$aCaller = $aCallers[ 0 ];
    	}

   		// let the developer know where the call came from so we can remove it
   		echo 'Called from: ',
  			 str_replace( sBASE_INC_PATH, '', $aCaller[ 'file' ] ),
   			 ' on line ',
   			 $aCaller[ 'line' ],
   			 ( bIS_CLI ) ? "\n\n" : '<br/><br/>';
	}

	/**
	 * Print a formatted version of all params and stop execution.
	 *
	 * @param 	OPTIONAL *	Anything can be passed in.
	 * @return	null
	 */
	function dv()
	{
	    $args = func_get_args();
	    foreach( $args as $arg )
	    {
	    	v( $arg );
	    }
	    die();
	}

	/**
	 * A list of times that have been saved.
	 *
	 * @var array $aTimes
	 */
	$aTimes = array();

	/**
	 * Resets the $aTimes variable.
	 */
	function clearTimes()
	{
		global $aTimes;
		$aTimes = array();
	}

	/**
	 * Returns the list of times saved.
	 */
	function getTimes()
	{
		global $aTimes;
		return $aTimes;
	}

	/**
	 * Save the current time in the $aTimes variable.
	 *
	 * @param string $sTag	Optional tag with which to associate the saved time.
	 */
	function saveTime( $sTag = null )
	{
		global $aTimes;
		if( !empty($sTag) )
		{
			$aTimes[ $sTag ] = microtime( true );
		}
		else
		{
			$aTimes[] = microtime( true );
		}
	}

	/**
	 * Returns the total time between two periods.
	 *
	 * If no specific periods are sent in, returns total time
	 * between the first and last entries in $aTimes.
	 *
	 * If both periods are provided, returns total time
	 * between the periods.
	 *
	 * @param 	null | string $vStart	Beginning period of time to measure.
	 * @param 	null | string $vEnd		Ending period of time to meausre.
	 *
	 * @return	string	The elapsed time between the start and end periods.
	 */
	function getTotalTime( $vStart = null, $vEnd = null )
	{
		global $aTimes;
		$iCount = count( $aTimes );
		if( $iCount == 1 ) {
			if( is_string( key( $aTimes ) ) ) {
				saveTime( 'end' );
			}
			else
			{
				saveTime();
			}
		}
		if( !empty( $vStart ) && !empty( $vEnd ) ) {
			$fStart = $aTimes[ $vStart ];
			$fEnd   = $aTimes[ $vEnd   ];
		}
		else
		{
			$fStart = current( $aTimes );
			$fEnd   = end( $aTimes );
		}

		return sprintf( '%f', $fEnd - $fStart );
	}
?>