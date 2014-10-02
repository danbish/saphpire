<?php
    //Exception handler
    require_once( sCORE_INC_PATH . '/includes/ErrorHandling.php' );

    // business and presentation classes
    require_once( sCORE_INC_PATH . '/classes/cBusPing.php' );
    require_once( sCORE_INC_PATH . '/classes/cPresPing.php' );

    // initialize
    $oBusiness     = new cBusPing();
    $oPresentation = new cPresPing();
?>