<?php
    /**
     * Interface for database systems.
     *
     * @author  Ryan Masters
     * @author  James Upp
     *
     * @package Core_0
     * @version 0.2
     */
    interface ifDb
    {
        /**
         * Creates a connection to the database.
         *
         * @todo    Change the name of this. Get should be reserved
         *            for a function that returns a value; a better name
         *            would be CreateConnection or EstablishConnection.
         *
         * @return null
         */
        public function GetConnection();

        /**
         * Runs an SQL statement, and returns true if the query was successful.
         *
         * @param  string      $sQuery              Query to run.
         * @param  array       $aBindVariables    Array of bind variables.
         *
         * @throws Exception
         *
         * @return boolean
         *
         * Example:
         *    RunQuery( 'INSERT INTO `Name` VALUES ( null, :firstname, :lastname ) ',
         *                  array( 'firstname' => 'John', 'lastname' => 'Smith' ) );
         */
        public function RunQuery( $sQuery, array $aBindVariables = array() );

        /**
         * Runs an SQL query and returns the results in an array.
         * Only works with 'SELECT' statements.
         *
         * @param    string      $sQuery              Select statement to run.
         * @param    array       $aBindVariables    Array of bind variables.
         *
         * @throws   Exception
         *
         * @return   array
         *
         * Example:
         *    GetQueryResults( 'SELECT * FROM `Name` WHERE first_name = :firstname',
         *                            array( 'firstname' => 'John' ) );
         */
        public function GetQueryResults( $sQuery, array $aBindVariables = array() );

        /**
         * Runs an SQL query and returns the results in an array.
         * Only works with 'SELECT' statements expecting one row.
         *
         * @param    string      $sQuery              Select statement to run.
         * @param    array       $aBindVariables    Array of bind variables.
         *
         * @throws   Exception
         *
         * @return   array
         *
         * Example:
         *    GetSingleQueryResults( 'SELECT * FROM `Name` WHERE first_name = :firstname',
         *                                   array( 'firstname' => 'John' ) );
         */
        public function GetSingleQueryResults( $sQuery, array $aBindVariables = array() );

        /**
         * Runs an SQL query and returns the number of columns.
         *
         * @param    string      $sQuery              Select statement to run.
         * @param    array       $aBindVariables    Array of bind variables.
         *
         * @throws   Exception
         *
         * @return   integer
         *
         * Example:
         *    ReturnColCount( 'select * from `names` WHERE Name_First = :firstname',
         *                          array( 'firstname' => 'John' ) );
         */
        public function ReturnColCount( $sQuery, array $aBindVariables = array() );

        /**
         * Returns the number of rows affected by the last statement executed
         * or a given select statement.
         *
         * If $sQuery is empty, then the number of rows affected by the last
         *    statement executed is given.
         * If $sQuery is a SELECT query, then the number of rows found is given.
         *
         * @param  string $sQuery              not used
         *
         * @throws Exception
         *
         * @return integer
         */
        public function ReturnRowCount( $sQuery );

        /**
         * Returns the auto incremented id from the last insert made.
         *
         * @param    string      $sQuery              not used
         *
         * @throws   Exception
         *
         * @return   integer
         */
        public function GetLastSequenceId( $sQuery );

        /**
         * Returns the next id in the sequence.
         *
         * @param   string      $sSequenceName      The name of the sequence.
         *
         * @throws  Exception
         *
         * @return  integer
         */
        public function GetNextSequenceId( $sQuery );

        public function RunProcedure( $sProcedure );

        /**
         * Frees up the connection, but does not close it.
         *
         * @param  string  $sQuery      not used
         *
         * @return boolean
         */
        public function FreeResults( $sQuery );

        /**
         * Starts a new transaction.
         *
         * @return boolean
         */
        public function StartTransaction();

        /**
         * Commits any changes made since a new transaction was started.
         *
         * @return boolean
         */
        public function Commit();

        /**
         * Rolls back any changes made since a new transaction was started.
         *
         * @return boolean
         */
        public function Rollback();

        /**
         * Destructor.
         */
        public function __destruct();
    }
?>