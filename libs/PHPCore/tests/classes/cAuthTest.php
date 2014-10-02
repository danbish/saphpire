<?php
require '../../config.php';
require '../../classes/cAuth.php';

class cAuthTest extends PHPUnit_Framework_TestCase
{
	protected $oAuth;

	public function setUp()
	{
		$this->oAuth = new cAuth();
		$_SESSION[ 'sUser' ]     = 'dev';
		$_SESSION[ 'sUserType' ] = 'SysAdmin';
	}
	
	/**
	 * Checks IsAutheticated function to make sure it correctly gets authentication status
	 */
	public function testAuthentication()
	{
		$this->assertTrue($this->oAuth->IsAuthenticated());
		unset($_SESSION['sUser']);
		$this->assertFalse($this->oAuth->IsAuthenticated());
	}
	
	/**
	 * Checks getUser function.
	 */
	public function testGetUser()
	{
		$this->assertNotNull($this->oAuth->GetUser());
		unset($_SESSION['sUser']);
		$this->assertNull($this->oAuth->GetUser());
	}
	
	public function testGetUserType()
	{
		$this->assertNotNull($this->oAuth->GetUserType());
		unset($_SESSION['sUserType']);
		$this->assertNull($this->oAuth->GetUserType());
	}
	
	public function testLogout()
	{
		$this->oAuth->Logout();
		$this->assertNull($_SESSION['sUser']);
		$this->assertNull($_SESSION['sUserType']);
	}
	
	
}