<?php
    /**
     * Interface for the request classes.
     *
     * Forces them to implement the four
     * request methods: GET, POST, PUT, and DELETE
     *
     * @author Michael Alphonso
     *
     * @package Request
     *
     * @version 0.0.0.1
     */
    interface ifRequest
    {
        /**
         * Abstract GET method stub
         */
        public function Get( $sUrl );

        /**
         * Abstract POST method stub
         */
        public function Post( $sUrl, array $aFields );

        /**
         * Abstract PUT method stub
         */
        public function Put( $sUrl, $vRequestBody, $bJsonEncode = false );

        /**
         * Abstract DELETE method stub
         */
        public function Delete( $sUrl, array $aFields = array() );

        /**
         * Destructor.
         */
        public function __destruct();
    }
?>