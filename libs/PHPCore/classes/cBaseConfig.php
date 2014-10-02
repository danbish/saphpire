<?php
    // get the xml utilities
    require_once( sCORE_INC_PATH . '/classes/cXmlUtilities.php' );

    // get array utilities
    require_once( sCORE_INC_PATH . '/classes/cArrayUtilities.php' );

    /**
     * Base configuration management class.
     *
     * Use Read() to read in a file or array of files.
     * Optionally, provide an environment to Read() to
     * return only that environment's configuration parameters.
     *
     * Write() saves a historical copy in /logs/history.
     * Automatic history can be disabled in the constructor.
     *
     * @author  Ryan Masters
     * @author  James Upp
     *
     * @package Core_0
     * @version 0.3
     */
    class cBaseConfig
    {
        /**
         * Config file directory.
         *
         * @var string
         */
        protected $sConfigDirectory = null;

        /**
         * Extension file directory.
         *
         * @var string
         */
        protected $sExtensionDirectory = null;

        /**
         * Boolean to save history of ini files.
         *
         * @var string
         */
        protected $bAutoSaveHistory = true;

        /**
         * Instance of cXmlUtilities.
         *
         * @var cXmlUtilities
         */
        protected $oXmlUtilities;

        /**
         * Instance of cArrayUtilities.
         *
         * @var cArrayUtilities
         */
        protected $oArrayUtilities;

        /**
         * Process data from the parsed ini file.
         *
         * @param  array $aData     Array from parse_ini_file.
         *
         * @return array
         */
        protected function ProcessIni( array $aData = array() )
        {
            try
            {
                // initialize config data
                $aConfig = array();

                // process the file
                foreach( $aData as $sKey => $sValue )
                {
                    $this->ProcessIniKey( $sKey, $sValue, $aConfig );
                }

                return $aConfig;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Process a key from an ini formatted file.
         *
         * @param  string           $sKey       Key to process.
         * @param  string           $sValue     Value of key.
         * @param  array            $aConfig    Array to process values into.
         *
         * @throws RuntimeException             Thrown if an invalid key is supplied.
         *
         * @return array
         *
         * @todo   Replace this with call to cStringUtilities::StringToArray if possible
         */
        protected function ProcessIniKey( $sKey, $sValue, array &$aConfig )
        {
            try
            {
                // check if this key needs to be parsed
                if( strpos( $sKey, '.' ) !== false )
                {
                    // separate the pieces of the key
                    $aPieces = explode( '.', $sKey, 2 );

                    // check for an invalid key
                    if( !strlen( $aPieces[ 0 ] ) || !strlen( $aPieces[ 1 ] ) )
                    {
                        throw new RuntimeException( sprintf( 'Invalid key "%s"', $sKey ) );
                    }
                    // save the the pieces correctly
                    elseif( !isset( $aConfig[ $aPieces[ 0 ] ] ) )
                    {
                        if( $aPieces[ 0 ] === '0' && !empty( $aConfig ) )
                        {
                            $aConfig = array( $aPieces[ 0 ] => $aConfig );
                        }
                        else
                        {
                            $aConfig[ $aPieces[ 0 ] ] = array();
                        }
                    }
                    // check for an invalid key
                    elseif ( !is_array( $aConfig[ $aPieces[ 0 ] ] ) )
                    {
                        throw new RuntimeException( sprintf(
                            'Cannot create sub-key for "%s", as key already exists', $aPieces[ 0 ]
                        ) );
                    }

                    // process next piece of the key
                    $this->ProcessIniKey( $aPieces[ 1 ], $sValue, $aConfig[ $aPieces[ 0 ] ] );
                }
                // otherwise, just add the key
                else
                {
                    $aConfig[ $sKey ] = $sValue;
                }
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Converts an array to an string in ini format.
         *
         * @param   array   $aConfig    The array to convert.
         */
        protected function ArrayToIni( array $aConfig = array() )
        {
            try
            {
                // initialize the contents to return
                $sContents = '';

                // sort out the root elements
                $aConfig = $this->SortRootElements( $aConfig );

                foreach( $aConfig as $sSectionName => $vData )
                {
                    // check if this is an array or not
                    if( !is_array( $vData ) )
                    {
                        // prepare the value
                        $sContents .= $sSectionName . ' = ' . $this->PrepareValue( $vData ) . "\n";
                    }
                    else
                    {
                        // add the branch
                        $sContents .= '; ' . $sSectionName . "\n\n" . $this->AddBranch( $vData ) . "\n";
                    }
                }

                return $sContents;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Root elements that are not assigned to any section
         * needs to be on the top of config.
         *
         * @param  array $aConfig
         *
         * @return array
         */
        protected function SortRootElements( array $aConfig = array() )
        {
            try
            {
                $aSections = array();

                // Remove sections from config array.
                foreach( $aConfig as $vKey => $vValue )
                {
                    if( is_array( $vValue ) )
                    {
                        $aSections[ $vKey ] = $vValue;
                        unset( $aConfig[ $vKey ] );
                    }
                }

                // Read sections to the end.
                foreach( $aSections as $vKey => $vValue )
                {
                    $aConfig[ $vKey ] = $vValue;
                }

                return $aConfig;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Add a branch to an INI string recursively.
         *
         * @param  array    $aConfig
         * @param  array    $aParents
         *
         * @return string
         */
        protected function AddBranch( array $aConfig = array(), array $aParents = array() )
        {
            try
            {
                $sIniString = '';

                foreach($aConfig as $vKey => $vValue)
                {
                    // merge the parents with the key
                    $aGroup = array_merge( $aParents, array( $vKey ) );

                    // check if the value is an array
                    if( is_array( $vValue ) )
                    {
                        // add a new branch
                        $sIniString .= $this->AddBranch( $vValue, $aGroup );
                    }
                    else
                    {
                        // implode the group into prepare the value
                        $sIniString .= implode( '.', $aGroup ) . ' = ' . $this->PrepareValue( $vValue ) . "\n";
                    }
                }

                return $sIniString;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Prepare a value for INI.
         *
         * @param  mixed $vValue
         *
         * @throws RuntimeException
         *
         * @return string
         */
        protected function PrepareValue( $vValue )
        {
            if( is_integer( $vValue ) || is_float( $vValue ) )
            {
                return $vValue;
            }
            elseif( is_bool( $vValue ) )
            {
                return ( $vValue ? 'true' : 'false' );
            }
            elseif( false === strpos( $vValue, '"' ) )
            {
                return '"' . $vValue .  '"';
            }
            else
            {
                throw new RuntimeException( 'Value can not contain double quotes' );
            }
        }

        /**
         * Create an instance of this class, set config directory path,
         * enable/disable historical logs, and initialize an instance of the XML utilities.
         *
         * @param   string      $sConfigDirectory      Path to config directory.
         * @param   string      $sExtensionDirectory   Path to extension directory.
         * @param   boolean     $bAutoSaveHistory      Flag to save changes to config files.
         *
         * @throws  Exception
         */
        public function __construct( $sConfigDirectory = null, $sExtensionDirectory = null, $bAutoSaveHistory = true )
        {
            try
            {
                // set the path to the config files
                if( isset( $sConfigDirectory ) && is_string( $sConfigDirectory ) )
                {
                    // check if the file exists and is a directory
                    if( !file_exists( $sConfigDirectory ) || !is_dir( $sConfigDirectory ) )
                    {
                        throw new Exception( 'Config path provided is not a valid directory.' );
                    }
                    $this->sConfigDirectory = $sConfigDirectory;
                }
                else
                {
                    $this->sConfigDirectory = sBASE_INC_PATH . DIRECTORY_SEPARATOR . 'configs';
                }

                // set the path to extensions
                if( isset( $sExtensionDirectory ) && is_string( $sExtensionDirectory ) )
                {
                    // check if the file exists and is a directory
                    if( !file_exists( $sExtensionDirectory ) || !is_dir( $sExtensionDirectory ) )
                    {
                        throw new Exception( 'Extension path provided is not a valid directory.' );
                    }
                }
                else
                {
                    $this->sExtensionDirectory = sBASE_INC_PATH . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'PHPCoreExtensions';
                }

                // save the autosave flag
                $this->bAutoSaveHistory = $bAutoSaveHistory;

                // create the xml utilities
                $this->oXmlUtilities = new cXmlUtilities();

                // create the array utilities
                $this->oArrayUtilities = new cArrayUtilities();
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Reads a config file or an array of config files.
         *
         * @param   string | array  $vFile  The file or files to read.
         * @param   string | null   $sEnv   OPTIONAL the environment ( key ) to return.
         *
         * @throws  Exception       Thrown if:
         *                              - $vFile is not a string or array.
         *                              - Parse error occurred.
         *                              - Environment provided does not exist.
         *                              - Exception was thrown from a lower level.
         *
         * @return array
         */
        public function Read( $vFile, $sEnv = null )
        {
            try
            {
                // initialize the return value
                $aReturn = array();

                // check if $vFile is a string
                if( is_string( $vFile ) )
                {
                    $vFile = array( $vFile );
                }

                // check if $vFile is an array
                if( !is_array( $vFile ) )
                {
                    throw new Exception( 'Configuration files were not supplied correctly.' );
                }

                // load configuration files
                if( !empty( $vFile ) )
                {
                    // cycle through all the files
                    $iFileCount = count( $vFile );
                    for( $i = 0; $i < $iFileCount; ++$i )
                    {
                        // check if a string was provided
                        if( !is_string( $vFile[ $i ] ) )
                        {
                            throw new Exception( 'Configuration file provided was not provided correctly: ' . print_r( $vFile[ $i ] ) );
                        }

                        // get the extension of the file
                        $sExtension = substr( $vFile[ $i ], -4 );

                        // check if the extension is valid
                        if( $sExtension != '.xml' && $sExtension != '.ini' )
                        {
                            throw new Exception( 'Configuration file is not in xml or ini format: ' . print_r( $vFile[ $i ] ) );
                        }

                        // check if file exists
                        if( !file_exists( $this->sConfigDirectory . DIRECTORY_SEPARATOR . $vFile[ $i ] ) )
                        {
                            throw new Exception( 'File does not exist: ' . $this->sConfigDirectory . DIRECTORY_SEPARATOR . $vFile[ $i ] );
                        }

                        // load an xml file
                        if( $sExtension == '.xml' )
                        {
                            $aTemp   = $this->oXmlUtilities->ReadArrayFromFile( $this->sConfigDirectory . DIRECTORY_SEPARATOR . $vFile[ $i ] );
                            $aReturn = $this->oArrayUtilities->ArrayMergeRecursiveDistinct( $aTemp, $aReturn );
                        }
                        // load an ini file
                        elseif( $sExtension == '.ini' )
                        {
                            $aTemp   = $this->ReadIni( $vFile[ $i ] );
                            $aReturn = $this->oArrayUtilities->ArrayMergeRecursiveDistinct( $aTemp, $aReturn );
                        }
                    }
                }

                // check if an environment was provided
                if( !empty( $sEnv ) )
                {
                    // if the environment exists, set the return value
                    if( is_string( $sEnv ) && !empty( $aReturn[ $sEnv ] ) )
                    {
                        $aReturn = $aReturn[ $sEnv ];
                    }
                    // otherwise, there's a problem
                    else
                    {
                        throw new Exception( 'Environment ' . $sEnv . 'does not exist in <pre>' . print_r( $aReturn, true ) . '</pre>' );
                    }
                }

                return $aReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Loads each extension provided and merges into configuration.
         *
         * @param  array  $aConfig  Currently loaded configuration information.
         *
         * @return array            Configuration information merged with loaded extension data.
         */
        public function ReadExtensions( $aConfig )
        {
            try
            {
                // ensure that an array was passed in
                if( !is_array( $aConfig ) || !isset( $aConfig[ 'extensions' ] ) )
                {
                    throw new Exception( 'Extensions not provided correctly.' );
                }
                else
                {
                    $aExtension = $aConfig[ 'extensions' ];
                }

                // make sure the extensions are setup correctly to parse
                if( isset( $aExtension[ 'extension' ][ 'file' ] ) )
                {
                    $aExtension = array( $aExtension[ 'extension' ] );
                }
                else
                {
                    $aExtension = $aExtension[ 'extension' ];
                }

                // try to load data into all the extensions
                $iExtensionCount = count( $aExtension );
                for( $i = 0; $i < $iExtensionCount; ++$i )
                {
                    // check if file name was provided
                    if( !isset( $aExtension[ $i ][ 'file' ] ) )
                    {
                        throw new Exception( 'Extension file not provided: ' . print_r( $aExtension[ $i ] ) );
                    }

                    // check if the file name is valid
                    if( !is_string( $aExtension[ $i ][ 'file' ] ) || substr( $aExtension[ $i ][ 'file' ], 0, 1 ) !== 'c' )
                    {
                        throw new Exception( 'Extension not provided correctly: ' . print_r( $aExtension[ $i ][ 'file' ] ) );
                    }

                    // add the php extension if it's not there
                    if( substr( $aExtension[ $i ][ 'file' ], -4 ) !== '.php' )
                    {
                        $aExtension[ $i ][ 'file' ] = $aExtension[ $i ][ 'file' ] . '.php';
                    }

                    // check if extension exists
                    if( !file_exists( $this->sExtensionDirectory . DIRECTORY_SEPARATOR . $aExtension[ $i ][ 'file' ] ) )
                    {
                        throw new Exception( 'Extension does not exist: ' . $this->sExtensionDirectory . DIRECTORY_SEPARATOR . $aExtension[ $i ][ 'file' ] );
                    }

                    // load the file
                    require_once( $this->sExtensionDirectory . DIRECTORY_SEPARATOR . $aExtension[ $i ][ 'file' ] );

                    // get the class name
                    $sExtensionClass = substr( $aExtension[ $i ][ 'file' ], 0, -4 );

                    // check if the class implements the config extension interface
                    if( !in_array( 'ifConfigExtension', class_implements( $sExtensionClass ) ) )
                    {
                        throw new Exception( 'Extension does not implement the config extension interface.' );
                    }

                    // create the extension
                    $oExtension = new $sExtensionClass();

                    // get options if there are any
                    $aOptions = array();
                    if( isset( $aExtension[ $i ][ 'options' ] ) )
                    {
                        // check if there was only one option; if so, convert to array of options
                        if( !isset( $aExtension[ $i ][ 'options' ][ 'option' ][ 0 ] ) )
                        {
                            $aOptions = array( $aExtension[ $i ][ 'options' ][ 'option' ] );
                        }
                        else
                        {
                            $aOptions = $aExtension[ $i ][ 'options' ][ 'option' ];
                        }
                    }

                    // load data from the extension
                    $aLoaded = $oExtension->Load( $aOptions );
                    $aConfig = $this->oArrayUtilities->ArrayMergeRecursiveDistinct( $aLoaded, $aConfig );
                }

                return $aConfig;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Reads a ini formatted config file.
         *
         * @param   string          $sFile  The file to read.
         *
         * @throws  Exception       Thrown if:
         *                              - $sFile is not a string.
         *                              - Parse error occurred.
         *                              - Environment provided does not exist.
         *                              - Exception was thrown from a lower level.
         *
         * @return array
         */
        public function ReadIni( $sFile )
        {
            try
            {
                // initialize   the return value
                $aReturn = array();

                // check if $vFile is a string
                if( is_string( $sFile ) )
                {
                    // parse the file
                    $vParsed = parse_ini_file( $this->sConfigDirectory . DIRECTORY_SEPARATOR . $sFile );

                    // check for an error
                    if( $vParsed === false )
                    {
                        throw new Exception( 'Could not parse: ' . $sFile );
                    }

                    // process the results
                    $aReturn = $this->ProcessIni( $vParsed );
                }
                else
                {
                    throw new Exception( 'A file was not provided.' );
                }

                return $aReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Save contents of config file.
         *
         * @param   string              $sFile      The file to save.
         * @param   string | array      $sContents  The contents of the file.
         * @param   string              $sExtension OPTIONAL Type of config file to save to. If no type is provided,
         *                                          checks the name of the file for an extension. Accepted extensions
         *                                          are 'xml' and 'ini'. If an extension is not found, then the extension
         *                                          will default to XML.
         *
         * @throws  Exception   Thrown if there was a problem saving the file.
         */
        public function Write( $sFile, $vContents, $sExtension = null )
        {
            try
            {
                // check if the file provided is a string and is not empty
                if( !is_string( $sFile ) )
                {
                    throw new Exception( 'File provided is not a string.' );
                }
                elseif( empty( $sFile ) )
                {
                    throw new Exception( 'File was not provided.' );
                }

                // check if the content provided is a string or an array
                if( !is_string( $vContents ) && !is_array( $vContents ) )
                {
                    throw new Exception( 'Content provided is not a string or array.' );
                }

                // check if the extension was provided correctly
                if( !is_string( $sExtension ) && !empty( $sExtension ) )
                {
                    throw new Exception( 'Extension was not provided correctly. Please use null, \'xml\', or \'ini\'.' );
                }

                // try to get the extension from the file name
                if( empty( $sExtension ) )
                {
                    // default the extension if possible
                    $iPeriodPosition = strpos( $sFile, '.' );
                    if( $iPeriodPosition === false )
                    {
                        $sExtension = 'xml';
                    }
                    else
                    {
                        $sExtension = substr( $sFile, $iPeriodPosition + 1 );
                    }
                }

                $iPeriodPosition = strpos( $sFile, '.' );
                if( $iPeriodPosition === false )
                {
                    $sFile .= ".$sExtension";
                }

                // convert the extension to lowercase
                $sExtension = strtolower( $sExtension );
                if( $sExtension !== 'xml' && $sExtension !== 'ini' )
                {
                    throw new Exception( 'Extension was not provided correctly. Please use null, \'xml\', or \'ini\'.' );
                }

                // check if we need to convert an array to a string
                if( is_array( $vContents ) )
                {
                    // convert based on extension
                    if( $sExtension === 'xml' )
                    {
                        $sContents = $this->oXmlUtilities->ArrayToXml( 'config', $vContents );
                    }
                    else
                    {
                        $sContents = $this->ArrayToIni( $vContents );
                    }
                }
                elseif( is_string( $vContents ) )
                {
                    $sContents = $vContents;
                }

                // try to save copy of original in logs if possible
                if( $this->bAutoSaveHistory && file_exists( $this->sConfigDirectory . DIRECTORY_SEPARATOR . $sFile ) )
                {
                    // try to save a historical version of the file
                    $bSuccess = $this->SaveHistoricalConfig( $sFile );

                    // throw an exception if there was a problem
                    if( $bSuccess === false )
                    {
                        throw new Exception( 'Historical file could not be saved.' );
                    }
                }

                // try to save the file and check for an error
                if( file_put_contents( $this->sConfigDirectory . DIRECTORY_SEPARATOR . $sFile, $sContents ) === false )
                {
                    throw new Exception( 'File could not be saved: ' . $sFile );
                }

                return true;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Get the contents of a config file.
         *
         * @param   string      $sFile  The config file to use.
         *
         * @throws  Exception   Thrown if the file provided is not a string,
         *                      if the config file does not exist, or if the
         *                      file could not be read.
         *
         * @return  string      The file's contents.
         */
        public function GetConfigContents( $sFile )
        {
            try
            {
                // check if the file is a string
                if( !is_string( $sFile ) )
                {
                    throw new Exception( 'Config file provided is not a string.' );
                }

                // check if the file exists
                if( !file_exists( $this->sConfigDirectory . DIRECTORY_SEPARATOR . $sFile ) )
                {
                    throw new Exception( 'Config file does not exist: ' . $sFile );
                }

                // get the file contents
                $sContents = file_get_contents( $this->sConfigDirectory . DIRECTORY_SEPARATOR . $sFile );

                // check if there was a problem
                if( $sContents === false )
                {
                    throw new Exception( 'Could not read config file: ' . $sFile );
                }

                return $sContents;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Saves a historical version of the config file.
         *
         * @param string $sFile
         */
        public function SaveHistoricalConfig( $sFile )
        {
            try
            {
                // set the name of the historical file
                $sHistoryFile = $sFile . '_' . date( 'm-d-Y_H:i:s' );

                // get the original file contents
                $sOriginalContents = $this->GetConfigContents( $sFile );

                return file_put_contents( $this->sConfigDirectory . DIRECTORY_SEPARATOR . $sHistoryFile, $sOriginalContents );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Returns the names of all config files.
         *
         * @return array
         */
        public function GetConfigFiles()
        {
            return cFileUtilities::GetDirectoryContents(
                $this->sConfigDirectory,
                array(
                    'files_only',
                    'exclude_pattern' => array( '*.xml_*' )
                )
            );
        }

        /**
         * Returns the current config directory.
         *
         * @return string
         */
        public function GetConfigDirectory()
        {
            return $this->sConfigDirectory;
        }
    }
?>