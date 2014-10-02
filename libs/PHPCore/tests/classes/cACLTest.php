<?php
require '../../config.php';
require '../../classes/cACL.php';

class cACLTest extends PHPUnit_Framework_TestCase
{
	protected $oACL;

	public function setUp()
	{
		$this->oACL = new cACL();
		$sUser = 1;
		$sUserType = 1;
	}
	
	/**
	 * Tests the UserHasAccess function
	 */
	public function testUserHasAccess()
	{
		try 
		{
			$this->oACL->UserHasAccess($sUser);	
		}
		catch(Exception $e)
		{
			$_SERVER['PHP-SELF'] = 'AdminContact.php';
			$sUser = 'jassie2';
			$this->assertTrue($this->oACL->UserTypeHasAccess($sUser));
			
			//test blacklist case
			unset($_SERVER['PHP-SELF']);
			$this->assertTrue($this->oACL->UserTypeHasAccess($sUser));
			return;
		}
		
		$this->fail('An expected exception has not been thrown.');
	}
	
	public function testUserTypeHasAccess()
	{
		try
		{
			$this->oACL->UserTypeHasAccess($sUserType);
		}
		catch(Exception $e)
		{
			$_SERVER['PHP-SELF'] = 'AdminContact.php';
			$sUserType = 'sysAdmin';
			$this->assertTrue($this->oACL->UserTypeHasAccess($sUserType));

			//test blacklist case
			unset($_SERVER['PHP-SELF']);
			$this->assertTrue($this->oACL->UserTypeHasAccess($sUserType));
			return;
		}
		
		$this->fail('An expected exception has not been thrown.');
	}
	
}

?>