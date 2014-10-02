<?php
    // load our configuration.
    require_once( '../../../../config.php' );

    try
    {
        // lace up.
        require_once( sBASE_INC_PATH . '/services/ping/includes/PingBootstrap.php' );

        // handle ping.
        $sPingResponse = $oBusiness->Ping();

        // show result.
        echo $oPresentation->ShowPingResponse( $sPingResponse );
    }
    catch ( Exception $oException )
    {
        cLogger::Log( 'exception', 'There was an exception in the ping!', $oException );
    }
?>