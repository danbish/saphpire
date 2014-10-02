<?php
/**
 * @see SCP::put()
 * Reads data from a local file.
 */
define( 'NET_SCP_LOCAL_FILE' , 1);
// Reads data from a string.
define( 'NET_SCP_STRING' ,  2);

/**
 * @see SCP::_send()
 * @see SCP::_receive()
 */
// SSH1 is being used.
define( 'NET_SCP_SSH1' , 1);
// SSH2 is being used.
define( 'NET_SCP_SSH2' ,  2);
?>