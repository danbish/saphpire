<?php
	/**
	 * cDbAbs
	 * Class to instantiate the paticular class for the specific database platform
	 *
	 * @author	Ryan Masters
	 * @author	James Upp
	 *
	 * @package Core_0
	 * @version 0.1
	 *
	 * See example below for implementation of the methods that instansiate objects of paticular database platform
	 *      Example of usage of the static method GetDbObj
	 *      $oOra = cDbAbs::GetDbObj ( $aDb, 'DBSMART' );
	 */
	class cDbAbs
	{
	    /**
	     * Database configuration array
	     *
	     * @var array $aDbConf
	     */
	    protected $aDbConf = array();

		/**
	     * GetDbObj()
	     * Returns a database object based upon the configuration information
	     * supplied in the constructor.
	     *
	     * @param   array   $aDbConf    - Database configuration array
	     * @param   string  $sDbKey     - Database configuration identifier
	     * @return  object  			- Returns the requested database object or false on failure
	     */
	    public static function GetDbObj ( $aDbConf , $sDbKey )
	    {
	    	// initialize  the return value
	        $oReturn = false;

	        // if we dont have a config throw an exception
	        if( !is_array ( $aDbConf ) || sizeof ( $aDbConf ) == 0 || !isset (  $aDbConf[ $sDbKey ] ) || !is_array ( $aDbConf[ $sDbKey ] )  )
	        {
	            throw new Exception ("The requested Database configuration  (".$sDbKey.") does not exist or is incomplete.", 3 );
	            exit(1);
	        }

	        // set a var for ease of use
	        $aSpecConf = $aDbConf[ $sDbKey ];

	        try
	        {
	            // make the connection
	            switch( strtoupper( $aSpecConf[ 'sAdapter' ] ) )
	            {
	                //Connection to Oracle
	                case "ORACLE":
	                    require_once( sCORE_INC_PATH . '/classes/cOracleDb.php' );
	                    // Make an Oracle Database Object
	                    $oReturn = new cOracleDb ( $aSpecConf );
	                    break;

	                // Connection to MySQL
	                case "MYSQL":
	                    require_once( sCORE_INC_PATH . '/classes/cMySqlDb.php' );
	                    // Make a MySQL Database Object
	                    $oReturn = new cMySqlDb ( $aSpecConf );
	                    break;

	                // Otherwise throw an exception because there is no valid connection
	                default:
	                    throw new Exception ( "No VALID connection was defined", 3 );
	                    exit(1);
	            }

	            // get the connection
	            $oReturn->GetConnection();

	            return $oReturn;
	        }
			catch( Exception $oException )
    		{
    			throw BubbleException( $oException );
    		}
	    }
	}
?>