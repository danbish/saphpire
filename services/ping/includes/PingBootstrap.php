<?php
    //Exception handler
    require_once( sCORE_INC_PATH . '/includes/ErrorHandling.php' );

    // business and presentation classes
    require_once( sBASE_INC_PATH . '/services/ping/classes/cBusPing.php' );
    require_once( sBASE_INC_PATH . '/services/ping/classes/cPresPing.php' );

    // initialize
    $oBusiness     = new cBusPing();
    $oPresentation = new cPresPing();
?>