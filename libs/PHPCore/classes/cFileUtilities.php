<?php
    // get access to the string utilities
    require_once( sCORE_INC_PATH . '/classes/cStringUtilities.php' );

	/**
	 * Basic file handling functionality.
	 *
	 * @author  Ryan Masters
     *
     * @package Core_0
	 * @version 0.3
	 */
    class cFileUtilities
    {
    	/**
    	 * Gets the contents of a directory.
         *
         * @todo: get recursive patterns working
    	 *
    	 * @param	string 		$sPath		Path to directory.
    	 * @param	array		$aOptions	Array of options to apply to results. Options:
         *                                      verbose         -   Boolean to return "file" or "folder" with name. Defaults to false.
         *                                                          This parameter will change structure of return value to:
         *                                                              array( 0 => array( 'type' => 'file', 'name' => 'file_name' ) )
         *                                      include_hidden  -   Boolean to exclude hidden files and folders. Defaults to false.
         *                                      exclude_pattern -   String or array of strings of file names or regex patterns to exclude.
         *                                                          Defaults to array().
         *                                      include_pattern -   String or array of strings of file names or regex patterns to include.
         *                                                          Defaults to array( '*' ) to include all files.
         *                                      include_path    -   Boolean to include path to files. Defaults to false.
         *                                      files_only      -   Boolean to only include files. Defaults to false.
         *                                      folders_only    -   Boolean to only include folders. Defaults to false.
         *                                      recursive       -   Boolean to get contents recursively. Defaults to false.
         *                                                          Changes array structure of return value to:
         *                                                              array( 0 => 'path/to/file', 1 => 'path/to/different/file' )
    	 *
    	 * @throws	Exception	Thrown if path is malformed or an exception was thrown from a lower level.
    	 *
    	 * @return	array		Contents of the directory.
         *                      Depending on the options specified, it may be in one of the following forms:
         *                          1. array( 'file name', 'folder name', ... )
         *                          2. array( 0 => array( 'type' => 'file', 'name' => 'file_name' )
         *                          3. array( 0 => 'path/to/file', 1 => 'path/to/different/file' )
         *
    	 */
    	public static function GetDirectoryContents( $sPath, array $aOptions = array() )
    	{
            // check if we should include the type
            $bVerbose = false;
            if( in_array( 'verbose', $aOptions ) )
            {
                $bVerbose = true;
            }

            // check if we should exclude hidden files
            $bExcludeHiddenFiles = true;
            if( in_array( 'include_hidden', $aOptions ) )
            {
                $bExcludeHiddenFiles = false;
            }

            // check if there are any files/folders to exclude
            $aExclude = array();
            if( array_key_exists( 'exclude_pattern', $aOptions ) )
            {
                // check if patterns are an array or a string
                if( is_array( $aOptions[ 'exclude_pattern' ] ) )
                {
                    $aExclude = $aOptions[ 'exclude_pattern' ];
                }
                elseif( is_string( $aOptions[ 'exclude_pattern' ] ) )
                {
                    $aExclude = array( $aOptions[ 'exclude_pattern' ] );
                }
                else
                {
                    throw new Exception( 'Exclude pattern provided incorrectly.' );
                }
            }

            // check if there are any files/folders to include
            $aInclude = array( '*' );
            if( array_key_exists( 'include_pattern', $aOptions ) )
            {
                // check if patterns are an array or a string
                if( is_array( $aOptions[ 'include_pattern' ] ) )
                {
                    $aInclude = $aOptions[ 'include_pattern' ];
                }
                elseif( is_string( $aOptions[ 'include_pattern' ] ) )
                {
                    $aInclude = array( $aOptions[ 'include_pattern' ] );
                }
                else
                {
                    throw new Exception( 'Include pattern provided incorrectly.' );
                }
            }

            // check if we should include the path to each file
            $bIncludePaths = false;
            if( in_array( 'include_path', $aOptions ) )
            {
                $bIncludePaths = true;
            }

            // check if we should include only files
            $bFilesOnly = false;
            if( in_array( 'files_only', $aOptions ) )
            {
                $bFilesOnly = true;
            }

            // check if we should include only folders
            $bFoldersOnly = false;
            if( in_array( 'folders_only', $aOptions ) )
            {
                $bFoldersOnly = true;
            }

            // check if we should pull everything recursively
            $bRecursive = false;
            if( in_array( 'recursive', $aOptions ) )
            {
                $bRecursive = true;
            }

            // check for stupidity
            if( $bFilesOnly && $bFoldersOnly )
            {
                return array();
            }

    		try
    		{
	    		// check if the path provided is a string
	    		if( !is_string( $sPath ) )
	    		{
	    			throw new Exception( 'Path provided is not a string.' );
	    		}

	    		// initialize  the array of files in this directory
	    		$aFiles = array();

	    		// check if the directory exists
	    		if( file_exists( $sPath ) && is_dir( $sPath ) )
	    		{
                    // check if this is recursive or not
                    if( $bRecursive )
                    {
                        $oDirectoryIterator = new RecursiveDirectoryIterator( $sPath );
                        $oIterator = new RecursiveIteratorIterator( $oDirectoryIterator );
                    }
                    else
                    {
                        $oDirectoryIterator = new DirectoryIterator( $sPath );
                        $oIterator = new IteratorIterator( $oDirectoryIterator );
                    }

                    // cycle over the iterator and only add what's needed
                    foreach( $oIterator as $sTempPath => $oFile )
                    {
                        // make sure we have a path to work with
                        if( is_numeric( $sTempPath ) )
                        {
                            $sTempPath = $oFile->getPath();
                        }

                        // get file name
                        $sEntry = $oFile->getFilename();

                        // get directory name correctly
                        if( $sEntry == '.' )
                        {
                            $aEntry = explode( '/', $sTempPath );

                            if( $bRecursive )
                            {
                                $sEntry = $aEntry[ count( $aEntry ) - 2 ];
                            }
                            else
                            {
                                $sEntry = $aEntry[ count( $aEntry ) - 1 ];
                            }
                            $sTempPath = substr( $sTempPath, 0, -2 );
                        }

                        // check if we should skip this because it's hidden
                        if( !$bExcludeHiddenFiles
                            || ( $bExcludeHiddenFiles
                                 && substr( $sEntry, 0, 1 ) != '.'
                                 && stripos( $sTempPath, '/.' ) === false ) )
                        {
                            // check if we want this
                            if( ( !$bFilesOnly && !$bFoldersOnly )
                                  || ( $bFilesOnly && $oFile->isFile() )
                                  || ( $bFoldersOnly && $oFile->isDir() ) )
                            {
                                // check if the patterns match
                                // @todo: this is stupidly slow
                                if( !cStringUtilities::PatternMatches( $sEntry, $aExclude ) && cStringUtilities::PatternMatches( $sEntry, $aInclude ) )
                                {
                                    // check if we should include the type or not
                                    if( $bVerbose )
                                    {
                                        $aFiles[] = array(
                                            'type' => ( $oFile->isFile() ) ? 'file' : 'folder',
                                            'name' => ( $bRecursive || $bIncludePaths ) ? $oFile->getRealPath() : $sEntry
                                        );
                                    }
                                    else
                                    {
                                        $aFiles[] = ( $bRecursive || $bIncludePaths ) ? $oFile->getRealPath() : $sEntry;
                                    }
                                }
                            }
                        }
                    }
	    		}
	    		else
	    		{
	    			throw new Exception( 'Path provided is not a directory: ' . $sPath );
	    		}

				return $aFiles;
    		}
    		catch( Exception $oException )
    		{
    			throw BubbleException( $oException );
    		}
    	}

    	/**
    	 * Serializes the data passed in and saves it to the location provided.
    	 *
         * @param   string  $sPath  Where to save the data.
    	 * @param	mixed	$vData	The data to be saved.
    	 * @param	string	$sMode	The mode to open the file with.
    	 * 							Modes are used by fopen().
    	 * 							Mode 'w' is used by default.
    	 *
    	 * @return	int | false		Returns false on failure, number of bytes written on success.
    	 */
    	public static function SaveSerializedData( $sPath, $vData, $sMode = 'w' )
    	{
    		try
    		{
    			// serialize the data
	    		$sSerialized = serialize( $vData );

	    		// open the file to save it in
	    		$rhHandle = fopen( $sPath, $sMode );

	    		// save the data
	    		return fwrite( $rhHandle, $sSerialized );
    		}
    		catch( Exception $oException )
    		{
    			throw BubbleException( $oException );
    		}
    	}

    	/**
    	 * Retrieves unserialized data from a serialized file.
    	 *
    	 * @param	string	$sPath	The path to the file containing serialized data.
    	 *
    	 * @return	mixed			Unserialized data.
    	 */
    	public static function GetUnserializedData( $sPath )
    	{
    		try
    		{
	    		return unserialize( file_get_contents( $sPath ) );
    		}
    		catch( Exception $oException )
    		{
    			throw BubbleException( $oException );
    		}
    	}
    }
?>