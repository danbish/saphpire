<?php
	/**
	 * Base requirements for development are included here.
     *
     * @author  Ryan Masters
     *
     * @package Core_0
     * @version 0.1
	 */
	require_once( sCORE_INC_PATH . '/classes/cDevBusiness.php' );
	require_once( sCORE_INC_PATH . '/classes/cDevPresentation.php' );
	
	// make sure we have a business layer and an presentation layer
    $oBusiness     = new cDevBusiness();
	$oPresentation = new cDevPresentation();
?>