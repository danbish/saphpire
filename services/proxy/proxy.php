<?php
    /**
     * Service Request Proxy Controller
     *
     * Proxifies the request and returns the
     * response code and body to the client.
     */
    // load configuration
    require_once( '../../config.php' );
    try
    {
        // bootstrap
        require_once( sCORE_INC_PATH . '/includes/ProxyBootstrap.php' );

        // perform the request
        list( $iResponseCode, $sResponseBody ) = $oBusiness->ProxifyRequest();

        // output header and response body.
        header( 'Content-Type: text/plain' );
        header( $aHttpStatusCodes[ $iResponseCode ], true, $iResponseCode );
        die( $sResponseBody );
    }
    catch( Exception $oException )
    {
        ExceptionHandler( $oException );
    }
?>