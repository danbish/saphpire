<?php
    /**
     * General array utilities.
     *
     * @author  Ryan Masters
     * @author  James Upp
     *
     * @package Core_0
     * @version 0.1
     */
    class cArrayUtilities
    {
        /**
         * Given an array, walk through and apply functions provided.
         *
         * @param  array  $aArray      Array for which each element will have functions applied to it.
         * @param  array  $aFunctions  List of functions to apply to each element in the array.
         *
         * @return array  Original array after each element has had each function applied to it.
         */
        public function ApplyFunctionsRecursively( array $aArray, array $aFunctions )
        {
            try
            {
                // apply each function to each member of the array
                $iFunctionCount = count( $aFunctions );
                for( $i = 0; $i < $iFunctionCount; ++$i )
                {
                    // check if the function is callable
                    if( !is_callable( $aFunctions[ $i ] ) )
                    {
                        throw new Exception( 'Function provided is not callable: <pre>' . print_r( $aFunctions[ $i ], true ) . '</pre>' );
                    }

                    // apply this function to each member of the array
                    if( !array_walk_recursive( $aArray, $aFunctions[ $i ] ) )
                    {
                        throw new Exception( 'Could not apply function: ' . $aFunctions[ $i ] );
                    }
                }

                return $aArray;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Performs an array_unshift with a non-numeric key.
         *
         * @param   string  $sKey
         * @param   mixed   $vVal
         * @param   array   $aArray
         *
         * @return  array
         */
        public function AssociativeArrayUnshift( $sKey, $vVal, array $aArray )
        {
            try
            {
                // reverse the array
                $aArray = array_reverse( $aArray, true );

                // add the new key and value
                $aArray[ $sKey ] = $vVal;

                // reverse the array again and return it
                return array_reverse( $aArray, true );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Since array_merge_recursive merges arrays but converts duplicate keys to arrays,
         * ArrayMergeRecursiveDistinct overwrites the values in the first array with the
         * values in the second like array_merge.
         *
         * @param  array $aArray1
         * @param  array $aArray2
         *
         * @return array
         */
        public function ArrayMergeRecursiveDistinct( array &$aArray1, array &$aArray2 )
        {
            try
            {
                $aMerged = $aArray1;

                foreach ( $aArray2 as $vKey => &$vValue )
                {
                    if ( is_array ( $vValue ) && isset ( $aMerged [$vKey] ) && is_array ( $aMerged [$vKey] ) )
                    {
                        $aMerged [$vKey] = $this->ArrayMergeRecursiveDistinct( $aMerged [$vKey], $vValue );
                    }
                    else
                    {
                        $aMerged [$vKey] = $vValue;
                    }
                }

                return $aMerged;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>