<?php
	/**
	 * Base requirements for an application are included here.
	 *
	 * @author  Ryan Masters
	 *
	 * @package Core_0
	 * @version 0.1
	 */
	require_once( sCORE_INC_PATH . '/classes/cTemplate.php' );
	require_once( sCORE_INC_PATH . '/classes/cBaseBusiness.php' );
	require_once( sCORE_INC_PATH . '/classes/cBasePresentation.php' );
	
	// make sure we have a business layer and an presentation layer
    $oBusiness     = new cBaseBusiness();
	$oPresentation = new cBasePresentation();
?>