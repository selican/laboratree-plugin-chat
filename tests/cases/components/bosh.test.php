<?php
App::import('Controller', 'App');
App::import('Component', 'Bosh');

class BoshComponentTestController extends AppController {
	var $name = 'Test';
	var $uses = array();
	var $components = array(
		'Bosh',
	);
}

class BoshTest extends CakeTestCase
{
	var $fixtures = array('app.helps', 'app.helps, app.app_category', 'app.app_data', 'app.application', 'app.app_module', 'app.attachment', 'app.digest','app.discussion', 'app.doc', 'app.docs_permission', 'app.docs_tag', 'app.docs_type_data', 'app.docs_type_field', 'app.docs_type', 'app.docs_type_row', 'app.docs_version', 'app.group', 'app.groups_address', 'app.groups_association', 'app.groups_award', 'app.groups_interest', 'app.groups_phone', 'app.groups_projects', 'app.groups_publication', 'app.groups_setting', 'app.groups_url', 'app.groups_users', 'app.inbox', 'app.inbox_hash', 'app.interest', 'app.message_archive', 'app.message', 'app.note', 'app.ontology_concept', 'app.preference', 'app.project', 'app.projects_association', 'app.projects_interest', 'app.projects_setting', 'app.projects_url', 'app.projects_users', 'app.role', 'app.setting', 'app.site_role', 'app.tag', 'app.type', 'app.url', 'app.user', 'app.users_address', 'app.users_association', 'app.users_award', 'app.users_education', 'app.users_interest', 'app.users_job', 'app.users_phone', 'app.users_preference', 'app.users_publication', 'app.users_url', 'app.ldap_user');

	function startTest()
	{
		$this->Controller = new BoshComponentTestController();
		$this->Controller->constructClasses();
		$this->Controller->Component->initialize($this->Controller);
	}

	function testBoshInstance() {
		$this->assertTrue(is_a($this->Controller->Bosh, 'BoshComponent'));
	}

	function testElement()
	{
		$element = 'test';
		$attrs = array(
			'id' => 1,
		);	

		$result = $this->Controller->Bosh->element($element, $attrs);

		$expected = '<test id="1"/>';

		$this->assertEqual($result, $expected);
	}

	function testElementContents()
	{
		$element = 'test';
		$attrs = array(
			'id' => 1,
		);	
		$contents = 'Test';

		$result = $this->Controller->Bosh->element($element, $attrs, $contents);

		$expected = '<test id="1">Test</test>';

		$this->assertEqual($result, $expected);
	}

	function testElementNullElement()
	{
		$element = null;
		$attrs = array(
			'id' => 1,
		);	

		try  
		{
			$result = $this->Controller->Bosh->element($element, $attrs);
			$this->fail('InvalidArgumentException was expected');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testElementInvalidElement()
	{
		$element = array();
		$attrs = array(
			'id' => 1,
		);	

		try  
		{
			$result = $this->Controller->Bosh->element($element, $attrs);
			$this->fail('InvalidArgumentException was expected');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testElementInvalidAttrs()
	{
		$element = 'test';
		$attrs = 'invalid';

		try  
		{
			$result = $this->Controller->Bosh->element($element, $attrs);
			$this->fail('InvalidArgumentException was expected');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testElementInvalidContents()
	{
		$element = 'test';
		$attrs = array(
			'id' => 1,
		);	
		$contents = array(
			'invalid' => 'invalid',
		);

		try  
		{
			$result = $this->Controller->Bosh->element($element, $attrs, $contents);
			$this->fail('InvalidArgumentException was expected');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSend()
	{
		$xml = '<test id="1">Test</test>';
		$bindurl = 'http://hoth/testing/postecho.php';

		$result = $this->Controller->Bosh->send($xml, $bindurl);
		$this->assertEqual($xml, $result);
	}

	function testSendNullXml()
	{
		$xml = null;
		$bindurl = 'http://hoth/testsend.php';

		try
		{
			$result = $this->Controller->Bosh->send($xml, $bindurl);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSendInvalidXml()
	{
		$xml = array();
		$bindurl = 'http://hoth/testsend.php';

		try
		{
			$result = $this->Controller->Bosh->send($xml, $bindurl);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSendNullBindurl()
	{
		$xml = '<test id="1">Test</test>';
		$bindurl = null;

		try
		{
			$result = $this->Controller->Bosh->send($xml, $bindurl);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSendInvalidBindurl()
	{
		$xml = '<test id="1">Test</test>';
		$bindurl = array();

		try
		{
			$result = $this->Controller->Bosh->send($xml, $bindurl);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testConnect()
	{
		$username = 'testuser';
		$password = '01bc57a65eb2aa3daa72c7341ff2ac2ad5507c1f';
		$bindurl = 'http://hoth:7650/http-bind';

		$result = $this->Controller->Bosh->connect($username, $password, $bindurl);
		$expected = array(
			'rid' => $result['rid'],
			'sid' => $result['sid'],
			'jid' => $result['jid'],
		);
		$this->assertEqual($result, $expected);
	}

	function testConnectNullUsername()
	{
		$username = null;
		$password = '01bc57a65eb2aa3daa72c7341ff2ac2ad5507c1f';
		$bindurl = 'http://hoth:7650/http-bind';

		try
		{
			$result = $this->Controller->Bosh->connect($username, $password, $bindurl);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testConnectInvalidUsername()
	{
		$username = null;
		$password = '01bc57a65eb2aa3daa72c7341ff2ac2ad5507c1f';
		$bindurl = 'http://hoth:7650/http-bind';

		try
		{
			$result = $this->Controller->Bosh->connect($username, $password, $bindurl);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testConnectNullPassword()
	{
		$username = 'testuser';
		$password = null;
		$bindurl = 'http://hoth:7650/http-bind';

		try
		{
			$result = $this->Controller->Bosh->connect($username, $password, $bindurl);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testConnectInvalidPassword()
	{
		$username = 'testuser';
		$password = array(
			'invalid' => 'invalid',
		);
		$bindurl = 'http://hoth:7650/http-bind';

		try
		{
			$result = $this->Controller->Bosh->connect($username, $password, $bindurl);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testConnectNullBindurl()
	{
		$username = 'testuser';
		$password = '01bc57a65eb2aa3daa72c7341ff2ac2ad5507c1f';
		$bindurl = null;

		try
		{
			$result = $this->Controller->Bosh->connect($username, $password, $bindurl);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testConnectInvalidBindurl()
	{
		$username = 'testuser';
		$password = '01bc57a65eb2aa3daa72c7341ff2ac2ad5507c1f';
		$bindurl = array(
			'invalid' => 'invalid',
		);

		try
		{
			$result = $this->Controller->Bosh->connect($username, $password, $bindurl);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function endTest() {
		unset($this->Controller);
		ClassRegistry::flush();	
	}
}
?>
