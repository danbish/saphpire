<?php
    /**
     * String templating class.
     *
     * Process of use:
     *     - Create a template file with tags to be replaced.
     *     - Tags can be any sequence of characters, but it is recommended
     *       to keep a consistent format such as: _:_TAG_:_.
     *     - Create an instance of this class with the path (or paths) to the template files.
     *     - Use Replace() to replace the tags in a file with their values.
     *
     * Example template file:
     *     <!DOCTYPE html>
     *     <html>
     *         <head>
     *             <title>_:_TITLE_:_</title>
     *             _:_HEAD_:_
     *         </head>
     *         <body>
     *             _:_HEADER_:_
     *             _:_BODY_:_
     *             _:_FOOTER_:_
     *         </body>
     *     </html>
     *
     * Example use:
     *     // create template class instance with path to templates
     *     $oTemplate = new cTemplate( '../templates' );
     *
     *     // make replacements
     *     echo $oTemplate->Replace(
     *         array(
     *             'template'  => 'template-file.html',
     *             '_:_TAG_:_' => 'replacement value'
     *         )
     *     );
     *
     *     OR
     *
     *     // create template class instance with path to templates
     *     $oTemplate = new cTemplate( '../templates' );
     *
     *     // use one template for multiple data sets
     *     echo $oTemplate->Replace(
     *         array(
     *             'template' => 'template-file.html',
     *             array(
     *                 '_:_TAG_:_' => 'replacement value'
     *             ),
     *             array(
     *                 '_:_TAG_:_' => 'another replacement value'
     *             ),
     *             etc.
     *         )
     *     );
     *
     * Tags can also be set to an array that matches the format of the examples above.
     * Therefore, deeply nested recursive functionality is possible.
     *
     * @author Ryan Masters
     *
     * @package Core_0
     * @version 0.3
     */
    class cTemplate
    {
        /**
         * Paths to locations of template files.
         *
         * @var array
         */
        private $aPaths = array();

        /**
         * The contents of all loaded templates.
         *
         * Structure:
         *     array(
         *         file => contents
         *     )
         *
         * @var array
         */
        private $aContents = array();

        /**
         * The list of possible template keys to use.
         *
         * @var array
         */
        private $aPossibleKeys = array(
            'template' => '',
            'tmpl8'    => '',
            'templ8'   => '',
            'tmpl'     => ''
        );

        /**
         * Array of templates used when applying
         * multiple sets of data to a template.
         *
         * @var array
         */
        private $aPreviousTemplates = array();

        /**
         * Flag for whether or not to removes comments
         * from XML and HTML templates.
         *
         * @var boolean
         */
        public $bStripComments = true;

        /**
         * Accepts any number of file paths as arguments
         * and adds each to the list of paths to search
         * for templates upon a call to Load().
         *
         * @param   string  $sPath,...          The path to a template directory.
         *
         * @throws  Exception                   Rethrows anything it catches.
         */
        public function __construct()
        {
            try
            {
                // add all the paths
                $aDirectories = func_get_args();

                $this->SetTemplateDirectories( $aDirectories );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Sets template directories to search from.
         *
         * @param  array  $aDirectories  Array of file names.
         *
         * @return $this
         */
        public function SetTemplateDirectories( array $aDirectories )
        {
            try
            {
                // reset the template directories
                $this->aPaths = array();

                // reset the list of contents for loaded templates
                $this->aContents = array();

                // add all paths
                $iArgCount = count( $aDirectories );
                for( $i = 0; $i < $iArgCount; ++$i )
                {
                    // check if all the path provided is a string
                    if( is_string( $aDirectories[ $i ] ) )
                    {
                        // ensure that there is a trailing slash
                        if( substr( $aDirectories[ $i ], -1 ) !== '/' )
                        {
                            $aDirectories[ $i ] .= '/';
                        }

                        // check if it's a valid directory
                        if( !is_dir( $aDirectories[ $i ] ) )
                        {
                            throw new Exception( 'Path "' . $aDirectories[ $i ] . '" is not a valid directory.' );
                        }

                        // add the path
                        $this->aPaths[] = $aDirectories[ $i ];
                    }
                    else
                    {
                        throw new InvalidArgumentException( 'File path provided is not a string: ' . print_r( $aDirectories[ $i ], true ) );
                    }
                }

                return $this;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Check if the file exists and read the tags from the template.
         *
         * If $this->bStripComments is true, removes comments from HTML and XML
         * but leaves in conditional comments.
         *
         * @param   string      $sFileName  The file name of the template.
         *
         * @throws  Exception               An exception will be thrown if the
         *                                  file provided does not exist or there
         *                                  was a problem loading the file.
         *
         * @return  string                  Contents of the template.
         */
        public function Load( $sFileName )
        {
            try
            {
                // check if the file needs to be loaded
                if( !isset( $this->aContents[ $sFileName ] ) )
                {
                    // cycle through the paths to try to find it
                    $iPathCount = count( $this->aPaths );
                    for( $i = 0; $i < $iPathCount; ++$i )
                    {
                        // check if the file exists
                        if( file_exists( $this->aPaths[ $i ] . $sFileName ) )
                        {
                            // load file
                            $this->aContents[ $sFileName ] = @file_get_contents( $this->aPaths[ $i ] . $sFileName, true );

                            // check if file was loaded successfully
                            if( $this->aContents[ $sFileName ] === false )
                            {
                                throw new Exception( 'Could not load template file: ' . $sFileName );
                            }

                            // check if the file is XML or HTML
                            if( $this->bStripComments
                                && ( strtolower( substr( $sFileName, -4 ) ) == '.xml'
                                     || strtolower( substr( $sFileName, -5 ) ) == '.html' ) )
                            {
                                // strip non-conditional comments
                                $this->aContents[ $sFileName ] = preg_replace( '/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/', '',  $this->aContents[ $sFileName ] );
                            }

                            // return the file's contents
                            return $this->aContents[ $sFileName ];
                        }
                    }

                    throw new Exception( 'Template file could not be found: ' . $sFileName );
                }

                // return the file's contents
                return $this->aContents[ $sFileName ];
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Replace tags with values in a template.
         *
         * @param   array   $aData  Array in one of the following structures:
         *
         *  Structure 1: Use one template for multiple data sets.
         *               Useful for creating tables or lists.
         *
         *  array(
         *      <template key> => <template file>,
         *      array(
         *          <tag> => string | <structure>
         *      ),
         *      array(
         *          <tag> => string | <structure>
         *      ),
         *      etc.
         *  )
         *
         * OR
         *
         * Structure 2: Use one template for one dataset.
         *
         *  array(
         *      <template key> => <template file>,
         *      <tag> => string | <structure>,
         *      <tag> => string | <structure>,
         *      etc.
         *  )
         *
         *  Where <template key> is any key listed in $this->aPossibleKeys,
         *        <template file> is the name of a path to a template file
         *                        relative to the $this->sPath directory,
         *        <tag> is any string to be replaced in the template file,
         *        <structure> is one of the array structures provided above
         *
         * @throws  InvalidArgumentException    Thrown if there is no template provided per set of tags and values.
         *
         * @return  string                      Template contents with tags provided replaced by values calculated.
         */
        public function Replace( array $aData )
        {
            try
            {
                // initialize the template
                $sTemplate = null;

                // initialize the lists of tags and their replacement values
                $aTags = array();
                $aReplacements = array();
                $sSpecialConcatString = '';

                // find the template and cycle through the tags to find their values
                foreach( $aData as $sTag => $vValue )
                {
                    // if the $vValue is an array, then it needs to be evaluated
                    if( is_array( $vValue ) )
                    {
                        // initialize a flag for whether or not this is a normal case
                        $bNormal = false;

                        // find the template, load and unset it, evaluate values
                        foreach( $vValue as $vKey => $vTempValue )
                        {
                            // try to find the template by converting the key to lowercase
                            // and comparing it to the list of possible keys
                            if( isset( $this->aPossibleKeys[ strtolower( $vKey ) ] ) )
                            {
                                // add the tag to the list of strings to replace
                                $aTags[] = $sTag;

                                // check if this is array of arrays or not
                                if( isset( $vValue[ 0 ] ) )
                                {
                                    // add this template to the list of templates used
                                    // with multiple data sets so it can be reused
                                    $this->aPreviousTemplates[] = $this->Load( $vTempValue );

                                    // remove the template key for faster concatenation
                                    unset( $vValue[ $vKey ] );

                                    // initialize the concatenated value
                                    $sConcatString = '';

                                    // evaluate each value and concatenate into one string
                                    $iValueCount = count( $vValue );
                                    while( $iValueCount-- )
                                    {
                                        $sConcatString = $this->Replace( $vValue[ $iValueCount ] ) . $sConcatString;
                                    }

                                    // we're done with this template now, so get rid of it
                                    array_pop( $this->aPreviousTemplates );

                                    // add the evaluated value to the list of replacements
                                    $aReplacements[] = $sConcatString;
                                }
                                else
                                {
                                    // add the evaluated value to the list of replacements
                                    $aReplacements[] = $this->Replace( $vValue );
                                }

                                // set normal case to be true
                                $bNormal = true;

                                // break out because we're done with the array
                                break;
                            }
                            // assume this is a tag if it's not a template key
                            else
                            {
                                // add the tag to the list of strings to replace
                                $aTags[] = $vKey;

                                // add the evaluated value to the list of replacements
                                $aReplacements[] = is_array( $vTempValue ) ? $this->Replace( $vTempValue ) : $vTempValue;
                            }
                        }

                        // check if things were handled normally
                        if( !$bNormal )
                        {
                            // concatenate the results
                            $sSpecialConcatString .= str_replace( $aTags, $aReplacements, $sTemplate );

                            // reset the tags and replacements
                            unset( $aTags );
                            unset( $aReplacements );
                        }
                    }
                    // check if the key does not exist in the list of possible template keys
                    elseif( !isset( $this->aPossibleKeys[ strtolower( $sTag ) ] ) )
                    {
                        // add the tag and value to the list of replacements
                        $aTags[] = $sTag;
                        $aReplacements[] = $vValue;
                    }
                    else
                    {
                        // load the template
                        $sTemplate = $this->Load( $vValue );
                    }
                }

                // check if the edge case occurred
                if( !empty( $sSpecialConcatString ) )
                {
                    return $sSpecialConcatString;
                }

                // check if there are any in the list of templates for multiple data sets
                if( empty( $sTemplate ) && !empty( $this->aPreviousTemplates ) )
                {
                    // get the last template used
                    $sTemplate = end( $this->aPreviousTemplates );
                }
                // check if a template was loaded
                if( $sTemplate === null )
                {
                    throw new InvalidArgumentException( 'A valid template key was not provided: <pre>' . print_r( $aData, true ) . '</pre>' );
                }

                // replace tags with values in the current template and return the result
                return str_replace( $aTags, $aReplacements, $sTemplate );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>