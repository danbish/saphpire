<?php
    /**
     * General string utility functions.
     *
     * @author  Ryan Masters
     *
     * @package Core_0
     * @version 0.1
     */
    class cStringUtilities {
        /**
         * Convenience function for checking if a value to test is a string.
         *
         * @param   mixed       $vValue
         *
         * @throws  Exception   Thrown when value provided is not a string.
         */
        public static function VerifyString( $vValue )
        {
            // check if the value provided is a string
            if( !is_string( $vValue ) )
            {
                throw new Exception( 'Value provided is not a string. Type: ' . ( is_object( $vValue ) ? get_class( $vValue ) : gettype( $vValue ) ) );
            }
        }

        /**
         * Returns the given camelCasedWord as an underscored_word.
         *
         * @param   string $sCamelCasedWord Camel-cased word to be "underscorized"
         *
         * @return  string Underscore-syntaxed version of the $camelCasedWord
         */
        public static function Underscore( $sCamelCasedWord )
        {
            try
            {
                // ensure that we're working with a string
                self::VerifyString( $sCamelCasedWord );

                return strtolower( preg_replace( '/(?<=\\w)([A-Z])/', '_\\1', $sCamelCasedWord ) );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Returns the given camelCasedWord as a Human Readable Word Group.
         *
         * @param   string $sCamelCasedWord String to be made more readable
         *
         * @return  string Human-readable string
         */
        public static function Humanize( $sCamelCasedWord )
        {
            try
            {
                // ensure that we're working with a string
                self::VerifyString( $sCamelCasedWord );

                return ucwords( str_replace( "_", " ", self::Underscore( trim( $sCamelCasedWord ) ) ) );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Replaces all whitespace characters from a string with the string provided.
         *
         * @param   string  $sString    The string to strip whitespace from.
         * @param   string  $sReplace   Replacement string.
         *
         * @return  string
         */
        public static function ReplaceWhitespace( $sString, $sReplace = '' )
        {
            try
            {
                // ensure that we're working with a strings
                self::VerifyString( $sString  );
                self::VerifyString( $sReplace );

                return preg_replace( '/\s+/', $sReplace, $sString );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Extract only the scheme part out of a URI string.
         *
         * Will return the scheme if found, or null if no scheme found (URI may
         * still be valid, but not full).
         *
         * @param  string $sUriString
         *
         * @throws InvalidArgumentException
         *
         * @return string | null
         */
        public static function ParseSchemeFromUrl( $sUri )
        {
            try
            {
                // ensure that we're working with a string
                self::VerifyString( $sUri );

                // try to get the scheme
                if( preg_match( '/^([A-Za-z][A-Za-z0-9\.\+\-]*):/', $sUri, $aMatch ) )
                {
                    return $aMatch[ 1 ];
                }

                return null;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Parses a url into parts:
         *  - scheme
         *
         * @param   string  $sUri   The URL to split.
         *
         * @return  array
         */
        public static function SplitUrl( $sUri )
        {
            try
            {
                // ensure that we're working with a string
                self::VerifyString( $sUri );

                // initialize  the parts
                $aParts = array();

                // try to get scheme
                $sScheme = self::ParseSchemeFromUrl( $sUri );
                if( $sScheme !== null )
                {
                    // add to the list of parts
                    $aParts[ 'scheme' ] = $sScheme;

                    // remove scheme from url
                    $sUri = substr( $sUri, strlen( $sScheme ) + 1 );
                }

                // try to get host, userInfo, and port
                if( preg_match( '|^//([^/\?#]*)|', $sUri, $aMatch ) )
                {
                    $sAuthority = $aMatch[ 1 ];
                    $sUri       = substr( $sUri, strlen( $aMatch[ 0 ] ) );

                    // split authority into userInfo and host
                    if ( strpos( $sAuthority, '@' ) !== false )
                    {
                        // The userInfo can also contain '@' symbols; split $sAuthority
                        // into segments, and set it to the last segment.
                        $aSegments  = explode( '@' , $sAuthority );
                        $sAuthority = array_pop( $aSegments );
                        $userInfo   = implode( '@', $aSegments );

                        // add to the list of parts
                        $aParts[ 'userInfo' ] = $userInfo;
                    }

                    // try to get the port
                    $iMatches = preg_match( '/:[\d]{1,5}$/', $sAuthority, $aMatch );
                    if( $iMatches === 1 )
                    {
                        // get the port and port length
                        $iPort = intval( substr( $aMatch[ 0 ], 1 ) );
                        $iPortLength = strlen( $aMatch[ 0 ] );

                        // add to the list of parts
                        $aParts[ 'port' ] = $iPort;

                        // remove the port from the url
                        $sAuthority = substr( $sAuthority, 0, -$iPortLength );
                    }

                    // add to the list of parts
                    $aParts[ 'host' ] = $sAuthority;
                }

                // if there is nothing left to parse, return
                if( !$sUri )
                {
                    return $aParts;
                }

                // Capture the path
                if( preg_match( '|^[^\?#]*|', $sUri, $aMatch ) )
                {
                    // add to the list of parts
                    $aParts[ 'path' ] = $aMatch[0];

                    // remove path from url
                    $sUri = substr( $sUri, strlen( $aMatch[ 0 ] ) );
                }

                // if there is nothing left to parse, return
                if( !$sUri )
                {
                    return $aParts;
                }

                // Capture the query
                if( preg_match( '|^\?([^#]*)|', $sUri, $aMatch ) )
                {
                    // add to the list of parts
                    $aParts[ 'query' ] = $aMatch[ 1 ];

                    // remove query from url
                    $sUri = substr( $sUri, strlen( $aMatch[ 0 ] ) );
                }

                // if there is nothing left to parse, return
                if( !$sUri )
                {
                    return $aParts;
                }

                // All that's left is the fragment
                if( $sUri && substr( $sUri, 0, 1 ) == '#' )
                {
                    // add to the list of parts
                    $aParts[ 'fragment' ] = substr( $sUri, 1 );
                }

                return $aParts;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Cross platform version of money_format.
         *
         * Currently only one format is supported: '%.2n'
         *    Ex: 23432.265 => $23,432.27
         *
         * @todo convert this to use formats
         *
         * @param  string | int | float $vAmount  Value to be converted into a formatted string.
         *
         * @throws Exception            Thrown if a non-numeric value is provided.
         *
         * @return string
         */
        public static function MoneyFormat( $vAmount )
        {
            if( is_string( $vAmount ) )
            {
                // get the value as a float if possible
                if( is_float( $vAmount ) )
                {
                    $vAmount = floatval( $vAmount );
                }
                else
                {
                    throw new Exception( 'Numeric value required.' );
                }
            }
            elseif( !is_numeric( $vAmount ) )
            {
                throw new Exception( 'Numeric value required.' );
            }

            setlocale( LC_ALL, '' ); // locale will be different on each system.
            $aLocale = localeconv(); // get the locale settings

            return $aLocale[ 'currency_symbol' ] . number_format( $vAmount, 2, $aLocale[ 'decimal_point' ], $aLocale[ 'thousands_sep' ] );
        }

        /**
         * Check if a string is included in a list by looking for exact
         * matches as well as fnmatch and regular expression patterns.
         *
         * @param string $sTestString   String to test.
         * @param array  $aPatterns     Patterns to test against string
         */
        public static function PatternMatches( $sTestString, array $aPatterns )
        {
            try
            {
                // make sure we're sending in a string
                self::VerifyString( $sTestString );

                // check if the pattern matches exactly
                if( in_array( $sTestString, $aPatterns ) )
                {
                    return true;
                }

                // check for filename cases
                $iPatternCount = count( $aPatterns );
                for( $i = 0; $i < $iPatternCount; ++$i )
                {
                    // make sure the pattern is a string
                    self::VerifyString( $aPatterns[ $i ] );

                    // check if this pattern matches the entry
                    if( fnmatch( $aPatterns[ $i ], $sTestString ) )
                    {
                        return true;
                    }
                }

                // check for regular expressions
                for( $i = 0; $i < $iPatternCount; ++$i )
                {
                    // check if this pattern matches the entry
                    if( @preg_match( $aPatterns[ $i ], $sTestString ) )
                    {
                        return true;
                    }
                }

                return false;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Convert a period delimited string into an array.
         *
         * @param  string  $sString  String to process.
         * @param  mixed   $vValue   Value to set for the deepest nested key.
         *
         * @return array
         */
        public static function StringToArray( $sString, $vValue = null )
        {
            try
            {
                // break apart string into keys
                $aKeys  = explode( '.', $sString );

                // initialize array to return
                $aReturn = array();

                // get reference of return array
                $aArrayRef = &$aReturn;

                // build out the array to return by changing the reference
                $iKeyCount = count( $aKeys );
                for( $i = 0; $i < $iKeyCount; ++$i )
                {
                    // create new key
                    $aArrayRef[ $aKeys[ $i ] ] = array();

                    // set reference to new value
                    $aArrayRef = &$aArrayRef[ $aKeys[ $i ] ];

                    // add value if we're done
                    if( empty( $aArrayRef ) && $i + 1 == $iKeyCount )
                    {
                        $aArrayRef = $vValue;
                    }
                }

                // get rid of the reference
                unset( $aArrayRef );

                return $aReturn;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>