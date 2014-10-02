<?php
    /**
     * Interface for extensions that load configuration data.
     *
     * @author  Ryan Masters
     *
     * @package Core_0
     * @version 0.1
     */
    interface ifConfigExtension
    {
        /**
         * Loads data either into application or environment level configuration.
         *
         * @param  array      $aOptions  Options to use while loading data.
         *
         * @throws Exception             Rethrows anything that is caught.
         */
        public function Load( $aOptions = array() );
    }
?>