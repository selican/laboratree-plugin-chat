<?php
App::import('Controller','Chat');
App::import('Component', 'RequestHandler');
App::import('Component', 'Bosh');

Mock::generatePartial('RequestHandlerComponent', 'ChatControllerMockRequestHandlerComponent', array('prefers'));
Mock::generatePartial('BoshComponent', 'ChatControllerMockBoshComponent', array('connect'));

class ChatControllerTestChatController extends ChatController {
	var $name = 'Chat';
	var $autoRender = false;
	
	function redirect($url, $status = null, $exit = true)
	{
		$this->redirectUrl = $url;
	}
	function render($action = null, $layout = null, $file = null)
	{
		$this->renderedAction = $action;
	}

	function cakeError($method, $messages = array())
	{
		if(!isset($this->error))
		{
			$this->error = $method;
		}
	}
	function _stop($status = 0)
	{
		$this->stopped = $status;
	}
}

class ChatControllerTest extends CakeTestCase {
	var $Chat = null;
	var $fixtures = array('app.helps', 'app.app_category', 'app.app_data', 'app.application', 'app.app_module', 'app.attachment', 'app.digest','app.discussion', 'app.doc', 'app.docs_permission', 'app.docs_tag', 'app.docs_type_data', 'app.docs_type_field', 'app.docs_type', 'app.docs_type_row', 'app.docs_version', 'app.group', 'app.groups_address', 'app.groups_association', 'app.groups_award', 'app.groups_interest', 'app.groups_phone', 'app.groups_projects', 'app.groups_publication', 'app.groups_setting', 'app.groups_url', 'app.groups_users', 'app.inbox', 'app.inbox_hash', 'app.interest', 'app.message_archive', 'app.message', 'app.note', 'app.ontology_concept', 'app.preference', 'app.project', 'app.projects_association', 'app.projects_interest', 'app.projects_setting', 'app.projects_url', 'app.projects_users', 'app.role', 'app.setting', 'app.site_role', 'app.tag', 'app.type', 'app.url', 'app.user', 'app.users_address', 'app.users_association', 'app.users_award', 'app.users_education', 'app.users_interest', 'app.users_job', 'app.users_phone', 'app.users_preference', 'app.users_publication', 'app.users_url', 'app.ldap_user');
	
	function startTest() {
		$this->Chat = new ChatControllerTestChatController();
		$this->Chat->constructClasses();
		$this->Chat->Component->initialize($this->Chat);
		
		$this->Chat->Session->write('Auth.User', array(
			'id' => 9090,
			'username' => 'selenium1',
			'password' => '11111111',
			'changepass' => 0,
		));
	}
	
	function testChatControllerInstance() {
		$this->assertTrue(is_a($this->Chat, 'ChatController'));
	}
/*	
	function testAdminSrg() {
		$this->Chat->admin_srg();
	}

	function testAdminMuc() {
		$this->Chat->admin_muc();
	}*/

	function testConnectNoJS() {
		$this->Chat->connect();
		$this->assertEqual($this->Chat->error, 'error404');
	}

	function testConnectNewConnection() 
	{
                $this->Chat->Session->write('Auth.User', array(
                        'id' => 9090,
                        'username' => 'selenium1',
                        'password' => '11111111',
                        'changepass' => 0,
                ));

		$this->Chat->params = Router::parse('chat/connect.js');
		$this->Chat->beforeFilter();
		$this->Chat->Component->startup($this->Chat);

		$this->Chat->RequestHandler = new ChatControllerMockRequestHandlerComponent();
		$this->Chat->RequestHandler->setReturnValue('prefers', true);
/*
		$this->Chat->Bosh = new ChatControllerMockBoshComponent();
		$this->Chat->Bosh->setReturnValue('connect', array(
                        'rid' => $rid,
                        'sid' => $sid,
                        'jid' => $jid,
                ));

*/
		$this->Chat->Session->del('Chat.connection');

		$this->Chat->connect();

		$response = $this->Chat->viewVars['response'];
		//debug($response);
		$expected = array(
			'name' => 'Selenium Tester',
			'username' => 'selenium1',
			'popout' => '/chat/popout',
			'list' => '/chat/chatlist.json',
			'session' => null,
		);

		$copy = array(
		//	'rid',
		//	'sid',
		//	'jid',
			'domain',
			'connect',
			'0',
			'avatar',
		);
		foreach($copy as $key)
		{
			$expected[$key] = $response[$key];
		}

		ksort($response);
		ksort($expected);

		$this->assertEqual($response, $expected);
	}

	function testConnectUsedConnection()
	{
		$this->Chat->params = Router::parse('chat/connect.js');
		$this->Chat->beforeFilter();
		$this->Chat->Component->startup($this->Chat);

		$this->Chat->RequestHandler = new ChatControllerMockRequestHandlerComponent();
		$this->Chat->RequestHandler->setReturnValue('prefers', true);
		$this->Chat->connect();

		$connection = $this->Chat->viewVars['response'];
		$this->Chat->Session->write('Chat.connection', $connection);
echo $connection;
		$this->Chat->connect();

		$response = $this->Chat->viewVars['response'];
		
		$this->assertEqual($connection, $response);
	}

	function testConnectRestartConnection()
	{
		$this->Chat->params = Router::parse('chat/connect.js?restart=1');
		$this->Chat->beforeFilter();
		$this->Chat->Component->startup($this->Chat);

		$this->Chat->RequestHandler = new ChatControllerMockRequestHandlerComponent();
		$this->Chat->RequestHandler->setReturnValue('prefers', true);

		$this->Chat->connect();

		$connection = $this->Chat->viewVars['response'];
/*
                $copy = array(
                      'rid',
                      //'sid',
                      //'jid',
                        'domain',
                        'connect',
               //         '0',
                        'avatar',
                );

                foreach($copy as $key)
                {
                        $expected[$key] = $connection[$key];
                }

                ksort($expected);
*/
		$this->Chat->Session->write('Chat.connection', $connection);

		$this->Chat->params['url'] = array(
			'restart' => true,
		);

		$this->Chat->connect();
                
		$response = $this->Chat->viewVars['response'];
	
//	$this->assertEqual($expected, $response);
        $this->assertNotEqual($connection, $response);  

	}

	function testConnectJsonp()
	{
		$jsonp = 'test';

		$this->Chat->params = Router::parse('chat/connect.js?jsonp=' . $jsonp);
		$this->Chat->params['url'] = array(
			'jsonp' => $jsonp,
		);

		$this->Chat->beforeFilter();
		$this->Chat->Component->startup($this->Chat);

		$this->Chat->RequestHandler = new ChatControllerMockRequestHandlerComponent();
		$this->Chat->RequestHandler->setReturnValue('prefers', true);
		$this->Chat->connect();

		$results = $this->Chat->viewVars['jsonp'];
//debug($results);
		$this->assertEqual($jsonp, $results);
	}

	function testConnectSession()
	{
		$session = 'user:2';

		$this->Chat->params = Router::parse('chat/connect.js?session=' . $session);
		$this->Chat->params['url'] = array(
			'session' => $session,
		);

		$this->Chat->beforeFilter();
		$this->Chat->Component->startup($this->Chat);

		$this->Chat->RequestHandler = new ChatControllerMockRequestHandlerComponent();
		$this->Chat->RequestHandler->setReturnValue('prefers', true);
		$this->Chat->connect();

		$response = $this->Chat->viewVars['response'];
//debug($response);
		$this->assertTrue(isset($response['session']));
		$this->assertEqual($response['session'], $session);
	}

	function testConnectNotJs()
	{
		$this->Chat->params = Router::parse('chat/save');

		$this->Chat->beforeFilter();
		$this->Chat->Component->startup($this->Chat);

		$this->Chat->RequestHandler = new ChatControllerMockRequestHandlerComponent();
		$this->Chat->RequestHandler->setReturnValue('prefers', false);
		$this->Chat->save();

		$this->assertEqual($this->Chat->error, 'error404');
	}

	function testSave()
	{
		$form = array(
			'test',
		);

		$this->Chat->params = Router::parse('chat/save.json');
		$this->Chat->params['form'] = $form;

		$this->Chat->beforeFilter();
		$this->Chat->Component->startup($this->Chat);

		$this->Chat->RequestHandler = new ChatControllerMockRequestHandlerComponent();
		$this->Chat->RequestHandler->setReturnValue('prefers', true);
		$this->Chat->save();

		$connection = $this->Chat->Cookie->read('connection');
//debug($connection);
		$this->assertEqual($connection, $form);
	}

	function testSaveNotJson()
	{
		$this->Chat->params = Router::parse('chat/save');

		$this->Chat->beforeFilter();
		$this->Chat->Component->startup($this->Chat);

		$this->Chat->RequestHandler = new ChatControllerMockRequestHandlerComponent();
		$this->Chat->RequestHandler->setReturnValue('prefers', false);
		$this->Chat->save();

		$this->assertEqual($this->Chat->error, 'error404');
	}

	function testChatlist()
	{
		$this->Chat->params = Router::parse('chat/chatlist.json');
		$this->Chat->beforeFilter();
		$this->Chat->Component->startup($this->Chat);

		$this->Chat->RequestHandler = new ChatControllerMockRequestHandlerComponent();
		$this->Chat->RequestHandler->setReturnValue('prefers', true);
		$this->Chat->chatlist();

		$this->assertTrue(isset($this->Chat->viewVars['list']));

		$list = $this->Chat->viewVars['list'];
//debug($list);
		$expected = array(
			'rows' => array(
				array(
					'id' => $list['rows'][0]['id'],
                    			'name' => 'Another Private Test Group',
                    			'text' => 'Another Private Test Group',
                    			'leaf' => true,
                    			'description' => 'Test Group',
					'username' => 'anotherprivatetestgroup',
                    			'session' => 'group:group_3',
                    			'token' => 'group:3',
                    			'type' => 'group',
                    			'email' => 'anothergrp+private@example.com',
                    			'privacy' =>  'private',
                    			'image' => '/img/groups/default_small.png',
                    			'role' => 'group.manager',
                    			'members' => 5,
                    			'projects' => 0,

				),
/*				array(
					'id' => $list['rows'][1]['id'],
					'name' => 'Another Private Test Project',
                    			'text' => 'Another Private Test Project',
                    			'leaf' => true,
					'description' => 'Another Private Test Project',
					'session' => 'group:project_3',
					'token' => 'project:3',
					'type' => 'project',
					'email' => 'anotherprj+private@example.com',
					'privacy' => 'private',
					'image' => '/img/projects/default_small.png',
					'role' => 'project.manager',
					'members' => 4,
					'group' => 'User: Test User',
					'group_type' => 'user',
					'group_id' => null,
				),
				array(
					'id' => $list['rows'][2]['id'],
					'name' => 'Private Test Group',
                    			'text' => 'Private Test Group',
                    			'leaf' => true,
                    			'description' => 'Test Group',
					'username' => 'privatetestgroup',
					'session' => 'group:group_1',
					'token' => 'group:1',
					'type' => 'group',
					'email' => 'testgrp+private@example.com',
					'privacy' => 'private',
					'image' => '/img/groups/default_small.png',
					'role' => 'group.manager',
					'members' => 2,
					'projects' => 1,
				),
				array(
					'id' => $list['rows'][3]['id'],
					'name' => 'Private Test Project',
					'text' => 'Private Test Project',
					'leaf' => true,
					'description' => 'Private Test Project',
					'session' => 'group:project_1',
					'token' => 'project:1',
					'type' => 'project',
					'email' => 'testprj+private@example.com',
					'privacy' => 'private',
					'image' => '/img/projects/default_small.png',
					'role' => 'project.manager',
					'members' => 2,
					'group' => 'User: Test User',
					'group_type' => 'user',
					'group_id' => null,
			      ),
*/			),
		);

		$this->assertEqual($list, $expected);
	}

	function testChatlistNotJson()
	{
		$this->Chat->params = Router::parse('chat/chatlist');

		$this->Chat->beforeFilter();
		$this->Chat->Component->startup($this->Chat);

		$this->Chat->RequestHandler = new ChatControllerMockRequestHandlerComponent();
		$this->Chat->RequestHandler->setReturnValue('prefers', false);
		$this->Chat->chatlist();

		$this->assertEqual($this->Chat->error, 'error404');
	}

	function testPopout()
	{
		$session = 'user:2';

		$this->Chat->params = Router::parse('chat/popout/' . $session);
		$this->Chat->beforeFilter();
		$this->Chat->Component->startup($this->Chat);

		$this->Chat->popout($session);
		$this->assertTrue(isset($this->Chat->viewVars['chat_session']));

		$result = $this->Chat->viewVars['chat_session'];
		$this->assertEqual($result, $session);
	}

	function testPopoutNullSession()
	{
		$session = null;

		$this->Chat->params = Router::parse('chat/popout/' . $session);
		$this->Chat->beforeFilter();
		$this->Chat->Component->startup($this->Chat);

		$this->Chat->popout($session);
		$this->assertEqual($this->Chat->error, 'missing_field');
	}

	function testLogsEmptyTableType() {
		try {
			$this->Chat->logs(null, 1);
			$this->assertEqual($this->Chat->error, 'missing_field');
		}

		catch(InvalidArgumentException $e) {
			$this->pass();
		}
	}

	function testLogsEmptyTableId() {
                        $this->Chat->logs('user', null);
        }
	
	function testLogsEmptyTableIdUserTableType() {
                try {
                        $this->Chat->logs('project', null);
                        $this->assertEqual($this->Chat->error, 'missing_field');
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

	function testLogsUser() {
		$this->Chat->RequestHandler = new ChatControllerMockRequestHandlerComponent();
		$this->Chat->RequestHandler->setReturnValue('prefers', true);
        
                $this->Chat->logs('user', 1);
        }

	function testLogsGroup() {
                        $this->Chat->logs('group', 1);
        }

	function testLogsProject() {
        //                $this->Chat->logs('project', 1);
        }

	function testLogsDefault() {
                try {
                        $this->Chat->logs('string', 1);
                        $this->assertEqual($this->Chat->error, 'access_denied');
                }

                catch(InvalidArgumentException $e) {
                        $this->pass();
                }
        }

/*
	function testHelpIndex() {
		$this->Chat->help_index();
	}

	function testHelpPidgin() {
		$this->Chat->help_pidgin();
	}
*/
	
	function endTest() {
		unset($this->Chat);
		ClassRegistry::flush();	
	}
}
?>
