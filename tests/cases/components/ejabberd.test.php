<?php
App::import('Controller', 'App');
App::import('Component', 'Ejabberd');

class EjabberdComponentTestController extends AppController {
	var $name = 'Test';
	var $uses = array();
	var $components = array(
		'Ejabberd',
	);
}

class EjabberdTest extends CakeTestCase
{
	var $fixtures = array('app.helps', 'app.app_category', 'app.app_data', 'app.application', 'app.app_module', 'app.attachment', 'app.digest', 'app.discussion', 'app.doc', 'app.docs_permission', 'app.docs_tag', 'app.docs_type_data', 'app.docs_type_field', 'app.docs_type', 'app.docs_type_row', 'app.docs_version', 'app.group', 'app.groups_address', 'app.groups_association', 'app.groups_award', 'app.groups_interest', 'app.groups_phone', 'app.groups_projects', 'app.groups_publication', 'app.groups_setting', 'app.groups_url', 'app.groups_users', 'app.inbox', 'app.inbox_hash', 'app.interest', 'app.message_archive', 'app.message', 'app.note', 'app.ontology_concept', 'app.preference', 'app.project', 'app.projects_association', 'app.projects_interest', 'app.projects_setting', 'app.projects_url', 'app.projects_users', 'app.role', 'app.setting', 'app.site_role', 'app.tag', 'app.type', 'app.url', 'app.user', 'app.users_address', 'app.users_association', 'app.users_award', 'app.users_education', 'app.users_interest', 'app.users_job', 'app.users_phone', 'app.users_preference', 'app.users_publication', 'app.users_url', 'app.ldap_user');

	function startTest()
	{
		Configure::write('Chat.enabled', true);

		$this->Controller = new EjabberdComponentTestController();
		$this->Controller->constructClasses();
		$this->Controller->Component->initialize($this->Controller);
	}

	function testEjabberdInstance() {
		$this->assertTrue(is_a($this->Controller->Ejabberd, 'EjabberdComponent'));
	}

	function testRegister()
	{
		$username = 'newuser';
		$password = '01bc57a65eb2aa3daa72c7341ff2ac2ad5507c1f';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->register($username, $password);
		$this->assertTrue($result);
	}

	function testRegisterNullUsername()
	{
		$username = null;
		$password = '01bc57a65eb2aa3daa72c7341ff2ac2ad5507c1f';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		try
		{
			$result = $this->Controller->Ejabberd->register($username, $password);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testRegisterInvalidUsername()
	{
		$username = array(
			'invalid' => 'invalid',
		);
		$password = '01bc57a65eb2aa3daa72c7341ff2ac2ad5507c1f';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		try
		{
			$result = $this->Controller->Ejabberd->register($username, $password);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testRegisterNullPassword()
	{
		$username = 'newuser';
		$password = null;

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		try
		{
			$result = $this->Controller->Ejabberd->register($username, $password);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testRegisterInvalidPassword()
	{
		$username = 'newuser';
		$password = array(
			'invalid' => 'invalid',
		);

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		try
		{
			$result = $this->Controller->Ejabberd->register($username, $password);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testUnregister()
	{
		$username = 'newuser';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->unregister($username);
		$this->assertTrue($result);
	}

	function testUnregisterNullUsername()
	{
		$username = null;

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		try
		{
			$result = $this->Controller->Ejabberd->unregister($username);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testUnregisterInvalidUsername()
	{
		$username = array(
			'invalid' => 'invalid',
		);

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		try
		{
			$result = $this->Controller->Ejabberd->unregister($username);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testChangePassword()
	{
		$username = 'testuser';
		$password = 'newpass';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->change_password($username, $password);
		$this->assertTrue($result);
	}

	function testChangePasswordNullUsername()
	{
		$username = null;
		$password = 'newpass';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		try
		{
			$result = $this->Controller->Ejabberd->change_password($username, $password);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testChangePasswordInvalidUsername()
	{
		$username = array(
			'invalid' => 'invalid',
		);
		$password = 'newpass';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		try
		{
			$result = $this->Controller->Ejabberd->change_password($username, $password);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testChangePasswordNullPassword()
	{
		$username = 'testuser';
		$password = null;

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		try
		{
			$result = $this->Controller->Ejabberd->change_password($username, $password);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testChangePasswordInvalidPassword()
	{
		$username = 'testuser';
		$password = array(
			'invalid' => 'invalid',
		);

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		try
		{
			$result = $this->Controller->Ejabberd->change_password($username, $password);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testAddRosterItem()
	{
		$user = 'testuser';
		$other = 'newuser';
		$nick = 'New User';
		$group = 'Testing';
		$subs = 'both';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->add_rosteritem($user, $other, $nick, $group, $subs);

		$this->assertTrue($result);
	}

	function testAddRosterItemNullUser()
	{
		$user = null;
		$other = 'newuser';
		$nick = 'New User';
		$group = 'Testing';
		$subs = 'both';

		try
		{
			$this->Controller->Ejabberd->add_rosteritem($user, $other, $nick, $group, $subs);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testAddRosterItemInvalidUser()
	{
		$user = array(
			'invalid' => 'invalid',
		);
		$other = 'newuser';
		$nick = 'New User';
		$group = 'Testing';
		$subs = 'both';

		try
		{
			$this->Controller->Ejabberd->add_rosteritem($user, $other, $nick, $group, $subs);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testAddRosterItemNullOther()
	{
		$user = 'testuser';
		$other = null;
		$nick = 'New User';
		$group = 'Testing';
		$subs = 'both';

		try
		{
			$this->Controller->Ejabberd->add_rosteritem($user, $other, $nick, $group, $subs);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testAddRosterItemInvalidOther()
	{
		$user = 'testuser';
		$other = array(
			'invalid' => 'invalid',
		);
		$nick = 'New User';
		$group = 'Testing';
		$subs = 'both';

		try
		{
			$this->Controller->Ejabberd->add_rosteritem($user, $other, $nick, $group, $subs);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testAddRosterItemNullNick()
	{
		$user = 'testuser';
		$other = 'newuser';
		$nick = null;
		$group = 'Testing';
		$subs = 'both';

		try
		{
			$this->Controller->Ejabberd->add_rosteritem($user, $other, $nick, $group, $subs);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testAddRosterItemInvalidNick()
	{
		$user = 'testuser';
		$other = 'newuser';
		$nick = array(
			'invalid' => 'invalid',
		);
		$group = 'Testing';
		$subs = 'both';

		try
		{
			$this->Controller->Ejabberd->add_rosteritem($user, $other, $nick, $group, $subs);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testAddRosterItemNullGroup()
	{
		$user = 'testuser';
		$other = 'newuser';
		$nick = 'New User';
		$group = null;
		$subs = 'both';

		try
		{
			$this->Controller->Ejabberd->add_rosteritem($user, $other, $nick, $group, $subs);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}
	
	function testAddRosterItemInvalidGroup()
	{
		$user = 'testuser';
		$other = 'newuser';
		$nick = 'New User';
		$group = array(
			'invalid' => 'invalid',
		);
		$subs = 'both';

		try
		{
			$this->Controller->Ejabberd->add_rosteritem($user, $other, $nick, $group, $subs);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testAddRosterItemNullSubs()
	{
		$user = 'testuser';
		$other = 'newuser';
		$nick = 'New User';
		$group = 'Testing';
		$subs = null;

		try
		{
			$this->Controller->Ejabberd->add_rosteritem($user, $other, $nick, $group, $subs);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testAddRosterItemInvalidSubs()
	{
		$user = 'testuser';
		$other = 'newuser';
		$nick = 'New User';
		$group = 'Testing';
		$subs = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Ejabberd->add_rosteritem($user, $other, $nick, $group, $subs);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testDeleteRosterItem()
	{
		$user = 'testuser';
		$other = 'newuser';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->delete_rosteritem($user, $other);

		$this->assertTrue($result);
	}

	function testDeleteRosterItemNullUser()
	{
		$user = null;
		$other = 'newuser';

		try
		{
			$this->Controller->Ejabberd->delete_rosteritem($user, $other);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testDeleteRosterItemInvalidUser()
	{
		$user = array(
			'invalid' => 'invalid',
		);
		$other = 'newuser';

		try
		{
			$this->Controller->Ejabberd->delete_rosteritem($user, $other);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testDeleteRosterItemNullOther()
	{
		$user = 'testuser';
		$other = null;

		try
		{
			$this->Controller->Ejabberd->delete_rosteritem($user, $other);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testDeleteRosterItemInvalidOther()
	{
		$user = 'testuser';
		$other = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Ejabberd->delete_rosteritem($user, $other);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgCreate()
	{
		$group = 'testgroup';
		$name = 'newgroup';
		$description = 'Group: newgroup';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->srg_create($group, $name, $description);

		$this->assertTrue($result);
	}

	function testSrgCreateNullGroup()
	{
		$group = null;
		$name = 'newgroup';
		$description = 'Group: newgroup';

		try
		{
			$this->Controller->Ejabberd->srg_create($group, $name, $description);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgCreateInvalidGroup()
	{
		$group = array(
			'invalid' => 'invalid',
		);
		$name = 'newgroup';
		$description = 'Group: newgroup';

		try
		{
			$this->Controller->Ejabberd->srg_create($group, $name, $description);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgCreateNullName()
	{
		$group = 'testgroup';
		$name = null;
		$description = 'Group: newgroup';

		try
		{
			$this->Controller->Ejabberd->srg_create($group, $name, $description);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgCreateInvalidName()
	{
		$group = 'testgroup';
		$name = array(
			'invalid' => 'invalid',
		);
		$description = 'Group: newgroup';

		try
		{
			$this->Controller->Ejabberd->srg_create($group, $name, $description);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgCreateNullDescription()
	{
		$group = 'testgroup';
		$name = 'newgroup';
		$description = null;

		try
		{
			$this->Controller->Ejabberd->srg_create($group, $name, $description);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgCreateInvalidDescription()
	{
		$group = 'testgroup';
		$name = 'newgroup'; 
		$description = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Ejabberd->srg_create($group, $name, $description);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgDelete()
	{
		$group = 'testgroup';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->srg_delete($group);

		$this->assertTrue($result);
	}

	function testSrgDeleteNullGroup()
	{
		$group = null;

		try
		{
			$this->Controller->Ejabberd->srg_delete($group);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgDeleteInvalidGroup()
	{
		$group = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Ejabberd->srg_delete($group);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgUserAdd()
	{
		$user = 'testuser';
		$group = 'testgroup';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->srg_user_add($user, $group);

		$this->assertTrue($result);
	}

	function testSrgUserAddNullUser()
	{
		$user = null;
		$group = 'testgroup';

		try
		{
			$this->Controller->Ejabberd->srg_user_add($user, $group);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgUserAddInvalidUser()
	{
		$user = array(
			'invalid' => 'invalid',
		);
		$group = 'testgroup';

		try
		{
			$this->Controller->Ejabberd->srg_user_add($user, $group);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgUserAddNullGroup()
	{
		$user = 'testuser';
		$group = null;

		try
		{
			$this->Controller->Ejabberd->srg_user_add($user, $group);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgUserAddInvalidGroup()
	{
		$user = 'testuser';
		$group = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Ejabberd->srg_user_add($user, $group);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgUserDel()
	{
		$user = 'testuser';
		$group = 'testgroup';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->srg_user_del($user, $group);

		$this->assertTrue($result);
	}

	function testSrgUserDelNullUser()
	{
		$user = null;
		$group = 'testgroup';

		try
		{
			$this->Controller->Ejabberd->srg_user_del($user, $group);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgUserDelInvalidUser()
	{
		$user = array(
			'invalid' => 'invalid',
		);
		$group = 'testgroup';

		try
		{
			$this->Controller->Ejabberd->srg_user_del($user, $group);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgUserDelNullGroup()
	{
		$user = 'testuser';
		$group = null;

		try
		{
			$this->Controller->Ejabberd->srg_user_del($user, $group);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgUserDelInvalidGroup()
	{
		$user = 'testuser';
		$group = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Ejabberd->srg_user_del($user, $group);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgSetName()
	{
		$group = 'testgroup';
		$name = 'newgroup';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->srg_set_name($group, $name);

		$this->assertTrue($result);
	}

	function testSrgSetNameNullGroup()
	{
		$group = null;
		$name = 'newgroup';

		try
		{
			$this->Controller->Ejabberd->srg_set_name($group, $name);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgSetNameInvalidGroup()
	{
		$group = array(
			'invalid' => 'invalid',
		);
		$name = 'newgroup';

		try
		{
			$this->Controller->Ejabberd->srg_set_name($group, $name);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgSetNameNullName()
	{
		$group = 'testgroup';
		$name = null; 

		try
		{
			$this->Controller->Ejabberd->srg_set_name($group, $name);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgSetNameInvalidName()
	{
		$group = 'testgroup';
		$name = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Ejabberd->srg_set_name($group, $name);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgSetDescription()
	{
		$group = 'testgroup';
		$description = 'Group: newgroup';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->srg_set_description($group, $description);

		$this->assertTrue($result);
	}

	function testSrgSetDescriptionNullGroup()
	{
		$group = null;
		$description = 'Group: newgroup';

		try
		{
			$this->Controller->Ejabberd->srg_set_description($group, $description);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgSetDescriptionInvalidGroup()
	{
		$group = array(
			'invalid' => 'invalid',
		);
		$description = 'Group: newgroup';

		try
		{
			$this->Controller->Ejabberd->srg_set_description($group, $description);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgSetDescriptionNullDescription()
	{
		$group = 'testgroup';
		$description = null;

		try
		{
			$this->Controller->Ejabberd->srg_set_description($group, $description);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSrgSetDescriptionInvalidDescription()
	{
		$group = 'testgroup';
		$description = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Ejabberd->srg_set_description($group, $description);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testCreateRoom()
	{
		$name = 'testname';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->create_room($name);

		$this->assertTrue($result);
	}

	function testCreateRoomNullName()
	{
		$name = null;

		try
		{
			$this->Controller->Ejabberd->create_room($name);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testCreateRoomInvalidName()
	{
		$name = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Ejabberd->create_room($name);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testDestroyRoom()
	{
		$name = 'testname';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->destroy_room($name);

		$this->assertTrue($result);
	}

	function testDestroyRoomNullName()
	{
		$name = null;

		try
		{
			$this->Controller->Ejabberd->destroy_room($name);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testDestroyRoomInvalidName()
	{
		$name = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Ejabberd->destroy_room($name);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testChangeRoomOption()
	{
		$name = 'roomname';
		$option = 'testoption';
		$value = 'newvalue';

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->change_room_option($name, $option, $value);

		$this->assertTrue($result);
	}

	function testChangeRoomOptionNullName()
	{
		$name = null;
		$option = 'testoption';
		$value = 'newvalue';

		try
		{
			$this->Controller->Ejabberd->change_room_option($name, $option, $value);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testChangeRoomOptionInvalidName()
	{
		$name = array(
			'invalid' => 'invalid',
		);
		$option = 'testoption';
		$value = 'newvalue';

		try
		{
			$this->Controller->Ejabberd->change_room_option($name, $option, $value);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testChangeRoomOptionNullOption()
	{
		$name = 'roomname';
		$option = null;
		$value = 'newvalue';

		try
		{
			$this->Controller->Ejabberd->change_room_option($name, $option, $value);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testChangeRoomOptionInvalidOption()
	{
		$name = 'roomname';
		$option = array(
			'invalid' => 'invalid',
		);
		$value = 'newvalue';

		try
		{
			$this->Controller->Ejabberd->change_room_option($name, $option, $value);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testChangeRoomOptionNullValue()
	{
		$name = 'roomname';
		$option = 'testoption';
		$value = null;

		try
		{
			$this->Controller->Ejabberd->change_room_option($name, $option, $value);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testChangeRoomOptionInvalidValue()
	{
		$name = 'roomname';
		$option = 'testoption';
		$value = array(
			'invalid' => 'invalid',
		);

		try
		{
			$this->Controller->Ejabberd->change_room_option($name, $option, $value);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSetPersistent()
	{
		$name = 'roomname';
		$value = true;

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->set_persistent($name, $value);

		$this->assertTrue($result);
	}

	function testSetPersistentNullName()
	{
		$name = null;
		$value = true;

		try
		{
			$this->Controller->Ejabberd->set_persistent($name, $value);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSetPersistentInvalidName()
	{
		$name = array(
			'invalid' => 'invalid',
		);
		$value = true;

		try
		{
			$this->Controller->Ejabberd->set_persistent($name, $value);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSetPersistentNullValue()
	{
		$name = 'roomname';
		$value = null;

		try
		{
			$this->Controller->Ejabberd->set_persistent($name, $value);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSetPersistentInvalidValue()
	{
		$name = 'roomname';
		$value = 'invalid';

		try
		{
			$this->Controller->Ejabberd->set_persistent($name, $value);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSetLogging()
	{
		$name = 'roomname';
		$value = true;

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->set_logging($name, $value);

		$this->assertTrue($result);
	}

	function testSetLoggingNullName()
	{
		$name = null;
		$value = true;

		try
		{
			$this->Controller->Ejabberd->set_logging($name, $value);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSetLoggingInvalidName()
	{
		$name = array(
			'invalid' => 'invalid',
		);
		$value = true;

		try
		{
			$this->Controller->Ejabberd->set_logging($name, $value);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSetLoggingNullValue()
	{
		$name = 'roomname';
		$value = null;

		try
		{
			$this->Controller->Ejabberd->set_logging($name, $value);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSetLoggingInvalidValue()
	{
		$name = 'roomname';
		$value = 'invalid';

		try
		{
			$this->Controller->Ejabberd->set_logging($name, $value);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSend()
	{
		$command = 'status';
		$params = array(
			'id' => 1,
		); 

		$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
		$result = $this->Controller->Ejabberd->_send($command, $params);
		$expected = array(
			array(
				'id' => 1,
			),
		);

		$this->assertEqual($result, $expected);
	}

	function testSendNullCommand()
	{
		$command = null;
		$params = array(
			'id' => 1,
		);

		try
		{
			$this->Controller->Ejabberd->_send($command, $params);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSendInvalidCommand()
	{
		$command = array(
			'invalid' => 'invalid',
		);
		$params = array(
			'id' => 1,
		);

		try
		{
			$this->Controller->Ejabberd->_send($command, $params);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSendNullParams()
	{
		$command = 'status';
		$params = null;

		try
		{
			$this->Controller->Ejabberd->_send($command, $params);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSendInvalidParams()
	{
		$command = 'status';
		$params = 'invalid';

		try
		{
			$this->Controller->Ejabberd->_send($command, $params);
			$this->fail('InvalidArgumentException was expected.');
		}
		catch(InvalidArgumentException $e)
		{
			$this->pass();
		}
	}

	function testSetMembersOnlyEmptyName() {
                try
                {
                        $this->Controller->Ejabberd->set_members_only(null, true);
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetMembersOnlyBoolName() {
                try
                {
                        $this->Controller->Ejabberd->set_members_only(true, true);
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetMembersOnlyIntName() {
                try
                {
                        $this->Controller->Ejabberd->set_members_only(1, true);
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetMembersOnlyStringValue() {
                try
                {
                        $this->Controller->Ejabberd->set_members_only('string', 'string');
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetMembersOnlyValid() {
                try
                {
			$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
                        $this->Controller->Ejabberd->set_members_only('Group_1', true);
                }
                catch(InvalidArgumentException $e)
                {
                        $this->fail();
                }
	}

	function testSetRoomAffiliationEmptyName() {
                try
                {
                        $this->Controller->Ejabberd->set_room_affiliation(null, 'string', 'owner');
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetRoomAffiliationIntName() {
                try
                {
                        $this->Controller->Ejabberd->set_room_affiliation(1, 'string', 'owner');
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetRoomAffiliationBoolName() {
                try
                {
                        $this->Controller->Ejabberd->set_room_affiliation(true, 'string', 'owner');
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetRoomAffiliationEmptyUsername() {
                try
                {
                        $this->Controller->Ejabberd->set_room_affiliation('string', null, 'owner');
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetRoomAffiliationBoolUsername() {
                try
                {
                        $this->Controller->Ejabberd->set_room_affiliation('string', true, 'owner');
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetRoomAffiliationIntUsername() {
                try
                {
                        $this->Controller->Ejabberd->set_room_affiliation('string', 1, 'owner');
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetRoomAffiliationEmptyAffiliation() {
                try
                {
                        $this->Controller->Ejabberd->set_room_affiliation('string', 'string', null);
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetRoomAffiliationNotInArray() {
                try
                {
                        $this->Controller->Ejabberd->set_room_affiliation('string', 'string', 'kitten');
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetRoomAffiliationValid() {
                try
                {
                        $this->Controller->Ejabberd->set_room_affiliation('string', 'string', 'owner');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->fail();
                }
	}

	function testSetPasswordProtectedEmptyName() {
                try
                {
                        $this->Controller->Ejabberd->set_password_protected(null, true);
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetPasswordProtectedIntName() {
                try
                {
                        $this->Controller->Ejabberd->set_password_protected(1, true);
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetPasswordProtectedBoolName() {
                try
                {
                        $this->Controller->Ejabberd->set_password_protected(true, true);
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetPasswordProtectedIntValue() {
                try
                {
                        $this->Controller->Ejabberd->set_password_protected('string', 1);
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetPasswordProtectedStringValue() {
                try
                {
                        $this->Controller->Ejabberd->set_password_protected('string', 'string');
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetPasswordProtectedValid() {
                try
                {
			$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
                        $this->Controller->Ejabberd->set_password_protected('string', true);
                }
                catch(InvalidArgumentException $e)
                {
                        $this->fail();
                }
	}

	function testSetPasswordEmptyName() {
                try
                {
                        $this->Controller->Ejabberd->set_password(null, 'string');
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetPasswordIntName() {
                try
                {
                        $this->Controller->Ejabberd->set_password(1, 'string');
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetPasswordBoolName() {
                try
                {
                        $this->Controller->Ejabberd->set_password(true, 'string');
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetPasswordEmptyValue() {
                try
                {
                        $this->Controller->Ejabberd->set_password('string', null);
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetPasswordIntValue() {
                try
                {
                        $this->Controller->Ejabberd->set_password('string', 1);
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetPasswordBoolValue() {
                try
                {
                        $this->Controller->Ejabberd->set_password('string', true);
                        $this->fail('InvalidArgumentException was expected.');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->pass();
                }
	}

	function testSetPasswordValid() {
                try
                {
			$this->Controller->Ejabberd->server = 'http://hoth/testing/postecho.php';
                        $this->Controller->Ejabberd->set_password('string', 'string');
                }
                catch(InvalidArgumentException $e)
                {
                        $this->fail();
                }
	}

	function endTest() {
		unset($this->Controller);
		ClassRegistry::flush();	
	}
}
?>
