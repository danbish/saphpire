<?php
    require_once( sCORE_INC_PATH . '/classes/cArrayUtilities.php' );

    /**
     * XML handling utilities.
     *
     * @author  Ryan Masters
     * @author  James Upp
     *
     * @package Core_0
     * @version 0.2
     */
    class cXmlUtilities
    {
        /**
         * Instance of cArrayUtilities used with
         * converting XML to/from an array.
         *
         * @var cArrayUtilities
         */
        protected $oArrayUtilities;

        /**
         * Create an instance of this class and
         * set an instance of cArrayUtilities.
         */
        public function __construct()
        {
            $this->oArrayUtilities = new cArrayUtilities();
        }

        /**
         * Convert a valid XML string into an array.
         *
         * @param   string  $sXml
         *
         * @return  array
         */
        public function ToArray( $sXml )
        {
            try
            {
                // make sure this is a string
                if( !is_string( $sXml ) )
                {
                    throw new Exception( 'XML provided is not a string.' );
                }

                // load the xml into an object
                $oXml = simplexml_load_string( $sXml );

                // check for an error
                if( $oXml === false )
                {
                    throw new Exception( 'Could not load xml.' );
                }

                // convert the object to an array
                $aReturn = json_decode( json_encode( (array) $oXml ), 1 );

                // check for an error
                if( $aReturn === null || json_last_error() !== JSON_ERROR_NONE )
                {
                    throw new Exception( 'Could not parse xml into array: ' . $sXml );
                }

                return $aReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Reads an XML file into an array.
         *
         * @param   string  $sPath  Full path to file.
         *
         * @throws  Exception
         *
         * @return  array
         */
        public function ReadArrayFromFile( $sPath )
        {
            try
            {
                // load the xaml from the file into an object
                $oXml = simplexml_load_file( $sPath, 'SimpleXMLElement', LIBXML_NOCDATA );

                // check for an error
                if( $oXml === false )
                {
                    throw new Exception( 'Could read not xml from file: ' . $sPath );
                }

                // convert to an array
                $aReturn = json_decode( json_encode( ( array ) $oXml ), 1);

                // Trim all elements of array
                $aReturn = $this->oArrayUtilities->ApplyFunctionsRecursively( $aReturn, array( 'trim' ) );

                // check for an error
                if( empty ( $aReturn ) )
                {
                    throw new Exception( 'Could not convert xml to array.' );
                }

                return $aReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Builds an XML string from an array.
         * Called by ArrayToXml.
         *
         * @param   array               $aArray             Array to build XML with.
         * @param   SimpleXmlElement    $oSimpleXmlElement  Object to handle XML creation.
         */
        protected function RecursiveArrayToXml( $aArray, &$oSimpleXmlElement )
        {
            try
            {
                foreach( $aArray as $vKey => $vValue )
                {
                    if( is_array( $vValue ) )
                    {
                        if( !is_numeric( $vKey ) )
                        {
                            $oSubnode = $oSimpleXmlElement->addChild( "$vKey" );
                            $this->RecursiveArrayToXml( $vValue, $oSubnode );
                        }
                        else
                        {
                            $this->RecursiveArrayToXml( $vValue, $oSimpleXmlElement );
                        }
                    }
                    else
                    {
                        // check if we need to wrap the value with CDATA
                        if( is_string( $vValue ) &&
                            ( strpos( $vValue, '&' ) !== false
                              || strpos( $vValue, '<' ) !== false
                              || strpos( $vValue, '>' ) !== false ) )
                        {
                            // check if the characters not allowed in CDATA are present
                            if( strpos( $vValue, ']]>' ) )
                            {
                                throw new Exception( 'Invalid characters found in section that will be wrapped in CDATA.' );
                            }

                            // wrap with CDATA
                            $oNode = dom_import_simplexml( $oSimpleXmlElement->addChild( "$vKey" ) );
                            $oNode->appendChild( $oNode->ownerDocument->createCDATASection( $vValue ) );
                        }
                        else
                        {
                            $oSimpleXmlElement->addChild( "$vKey", "$vValue" );
                        }
                    }
                }
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Builds an XML string from an array.
         *
         * @param   string  $sBaseNode  The base node of the XML document.
         * @param   array   $aArray     The array to convert.
         * @param   boolean $bNewLines  Set to true to add new lines between every node.
         *
         * @return  string  XML
         */
        public function ArrayToXml( $sBaseNode, $aArray, $bNewLines = false )
        {
            try
            {
                // create the basenode
                $oSimpleXmlElement = new SimpleXMLElement( "<$sBaseNode></$sBaseNode>", LIBXML_NOCDATA );

                // recursively create the xml
                $this->RecursiveArrayToXml( $aArray, $oSimpleXmlElement );

                // create a dom document
                $oDom = new DOMDocument( '1.0', 'utf-8' );

                // set formatting information if needed
                if( $bNewLines )
                {
                    $oDom->preserveWhiteSpace = false;
                    $oDom->formatOutput = true;
                }

                // load the xml into the document
                $bSuccess = $oDom->loadXML( $oSimpleXmlElement->asXml() );

                // check if the load was successful
                if( !$bSuccess )
                {
                    throw new Exception( 'Could not load XML into DOMDocument.' );
                }

                // save the xml and return it
                return str_replace( '<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>', $oDom->saveXML() );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>