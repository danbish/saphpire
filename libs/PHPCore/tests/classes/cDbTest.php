<?php
//require '../../config.php';
require '../../classes/cMySqlDb.php';

class cBaseDbTest extends PHPUnit_Framework_TestCase
{
	protected $oDb;

	public function setUp()
	{	
		//db configuration
		$aDbConf = array
		(
			'sDbName' => 'test',
			'sAdapter' => 'mysql',
			'sHost' => 'localhost',
			'sUsername' => 'root',
			'sPassword' => ''
		);
		$this->oDb = new cMySqlDb($aDbConf);
		$this->oDb->GetConnection();

	}
	
	/**
	 * Tests the RunQuery() function.
	 */
	public function testRunQuery()
	{
		$sQuery = 'Insert into names Values(null, :firstname, :lastname)';
		$aBinds = array
		(
			'firstname' => 'jassiem',
			'lastname' => 'moore'
		);
		
		$bSucc = $this->oDb->RunQuery($sQuery, $aBinds);
		$this->assertTrue($bSucc);
	}
	
	/**
	 * Tests the GetQueryResults() function
	 */
	public function testGetQueryResults()
	{
		$sQuery = 'Select f_name from names Where f_name = :firstname';
		$aBinds = array();
		$aBinds['firstname'] = 'jassiem';
		
		$aResults = $this->oDb->GetQueryResults($sQuery, $aBinds);
		$this->assertNotEmpty($aResults);
	}
	
	/**
	 * Tests the GetSingleQueryResults() function
	 */
	public function testGetSingleQueryResults()
	{
		$sQuery = 'Select f_name from names Where f_name = :firstname';
		$aBinds = array();
		$aBinds['firstname'] = 'jassiem';
		
		//should throw exception because query will return more than 1 result
		try 
		{
			$aResults = $this->oDb->GetSingleQueryResults($sQuery, $aBinds);
		}
		catch(Exception $e)
		{
			//reset query 
			$sQuery = 'Select * from names Where l_name = :lastname';
			$aBinds = array();
			$aBinds['lastname'] = 'career';
			
			$aResults = $this->oDb->GetSingleQueryResults($sQuery, $aBinds);
			$this->assertNotEmpty($aResults);
			return;
		}

		$this->fail('Exception was expected.');
	}
	
	/**
	 * Number returned by column count is based on what columns are asked 
	 * for in the query.
	 * E.g. Select * is all columns
	 * Select name, date is 2 columns.
	 * Returns # of expected columns even if result set is empty.
	 */
	public function testGetQueryColumnCount()
	{
		$sQuery = 'Select f_name, id, l_name from names Where f_name = :firstname';
		$aBinds = array();
		$aBinds['firstname'] = 'blue';
		
		$iResults = $this->oDb->ReturnColCount($sQuery, $aBinds);
		$this->assertEquals(3, $iResults);
	}
	
	/**
	 * @group new
	 *//*
	public function testGetQueryRowcount()
	{
		//run query so that you can find last # returned rows
		$sQuery = 'Select * From names Where f_name = jassiem';
		$aBinds = array
		(
			'firstname' => 'jassiem'
		);
		//$this->oDb->RunQuery($sQuery, $aBinds);
		
		//should be more than 10 rows returned
		$iRows = $this->oDb->ReturnRowCount($sQuery);
		$this->assertGreaterThan(10, $iRows);
	}*/
	
	/**
	 * @group new
	 */
	public function testGetLastSequenceId()
	{
		//run query so that there is a last id to look for
		$sQuery = 'Insert into names values(null, :firstname, :lastname)';
		$aBinds = array();
		$aBinds['firstname'] = 'Django';
		$aBinds['lastname'] = 'Unchained';
		$this->oDb->RunQuery($sQuery, $aBinds);
		
		$iLastId = $this->oDb->GetLastSequenceId();
		//after query last id should be 13
		$this->assertGreaterThanOrEqual(12, $iLastId);	
	}
	
	public function testStartTransaction()
	{
		//turns off autocommit mode
		$bStTrans = $this->oDb->StartTransaction();
		$this->assertTrue($bStTrans);
	}
	
	public function testCommit()
	{
		//start an active transaction
		$this->oDb->StartTransaction();
		//commits changes and turns on autocommit mode
		$bComm = $this->oDb->Commit();
		$this->assertTrue($bComm);
	}
	
	public function testRollback()
	{
		//start an active transaction
		$this->oDb->StartTransaction();
		$bRoll = $this->oDb->Rollback();
		$this->assertTrue($bRoll);
	}
	
}

?>