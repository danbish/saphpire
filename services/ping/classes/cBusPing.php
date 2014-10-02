<?php
    // include the base class
    require_once( sCORE_INC_PATH . '/classes/cBaseBusiness.php' );

    /**
     * Main business class for form processing/database access.
     *
     * @author  Academic Services :: Team Ra
     * @version 0.0.0.1
     */
    class cBusPing extends cBaseBusiness
    {
        /**
         * Business class constructor
         *
         * @throws  Exception rethrows anything it catches
         */
        public function __construct()
        {
            try
            {
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Handles the incoming ping request.
         *
         * Only allows GET method.
         *
         * @throws  Exception rethrows anything it catches.
         *
         * @return  string    Http status code
         */
        public function Ping()
        {
            try
            {
                // initialize return variable.
                $sPingResponse = '403';

                if ( $_SERVER[ 'REQUEST_METHOD' ] === 'GET'
                     && !empty( $_GET )
                     && !empty( $_GET[ 'token' ] ) )
                {
                    /////////////////////////////////////////////////////
                    // check token for validity against service broker //
                    /////////////////////////////////////////////////////

                    /////////////////////////////////////////////////
                    // check that the consumer using this token is //
                    // authorized for its use.                     //
                    /////////////////////////////////////////////////

                    // I'm alive!
                    $sPingResponse = '200';
                }
                else if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'GET' )
                {
                    // unsupported request method
                    $sPingResponse = '405';
                }
                // return response to controller.
                return $sPingResponse;
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }
    }
?>