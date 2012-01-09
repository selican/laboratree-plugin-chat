<?php
class ChatController extends ChatAppController {
	var $name = 'Chat';

	var $uses = array(
		'User',
		'Group',
		'Project',
	);

	var $components = array(
		'Auth',
		'Security',
		'Session',
		'Cookie',
		'RequestHandler',
	);

	function beforeFilter()
	{
		$this->Cookie->name = 'chat';
		$this->Cookie->time = '30 minutes';

		$this->Security->validatePost = false;
		parent::beforeFilter();
	}

	/**
	 * Returns a JSONP callback to Initialize the Chat BOSH connection
	 */
	function connect()
	{
		if(!$this->RequestHandler->prefers('js'))
		{
			$this->cakeError('error404');
			return;
		}

		$restart = false;
		if(isset($this->params['url']['restart']))
		{
			$restart = true;
		}

		$jsonp = 'laboratree.chat.init';
		if(isset($this->params['url']['jsonp']))
		{
			$jsonp = $this->params['url']['jsonp'];
		}

		$session = null;
		if(isset($this->params['url']['session']))
		{
			$session = $this->params['url']['session'];
		}

		$find = array(
			'conditions' => array(
				'User.id' => $this->Session->read('Auth.User.id'),
			),
			'fields' => array(
				'User.id',
				'User.password',
				'User.name',
				'User.username',
				'User.picture',
			),
			'recursive' => -1,
		);

		$user = $this->User->find('first', $find);
		if(empty($user))
		{
			$this->cakeError('internal_error', array('action' => 'Connect', 'resource' => 'Chat'));
			return;
		}
		
		$connection = $this->Cookie->read('connection');

		$this->Cookie->del('connection');

		if(empty($connection) || $restart)
		{
			$connection = $this->Bosh->connect($user['User']['username'], $user['User']['password'], Configure::read('Chat.boshUrl'));
		}

		if(empty($connection))
		{
			$this->cakeError('internal_error', array('action' => 'Connect', 'resource' => 'Chat'));
			return;
		}
		
		$response = array(
			'name' => $user['User']['name'],
			'username' => $user['User']['username'],
			'domain' => Configure::read('Chat.domain'),
			'connect' => Configure::read('Chat.connectUrl'),
			'popout' => Router::url('/chat/popout'),
			'list' => Router::url('/chat/chatlist.json'),
			'session' => $session,
			'avatar' => array(
				'height' => 50,
				'width' => 50,
				'type' => 'image/png',
			),
		);

		$picture = IMAGES . 'users/default_small.png';
		if(!empty($user['User']['picture']))
		{
			$filename = IMAGES . 'users/' . $user['User']['picture'] . '_thumb.png';
			if(file_exists($filename))
			{
				$picture = $filename;
			}
		}

		$data = file_get_contents($picture);

		$response['avatar']['data'] = base64_encode($data);
		$response['avatar']['size'] = filesize($picture);
		$response['avatar']['sha1'] = sha1($data);

		$response = array_merge($connection, $response);

		$this->set('jsonp', $jsonp);
		$this->set('response', $response);
	}

	/**
	 * Saves the Current Chat Connection
	 */
	function save()
	{
		if(!$this->RequestHandler->prefers('json'))
		{
			$this->cakeError('error404');
			return;
		}

		$this->Cookie->write('connection', $this->params['form']);

		$response = array(
			'success' => true
		);

		$this->set('response', $response);
	}

	/**
	 * Returns a list of Colleagues, Groups, and Projects for the Chat Dropdown
	 */
	function chatlist()
	{
		if(!$this->RequestHandler->prefers('json'))
		{
			$this->cakeError('error404');
			return;
		}

		$since = date('Y-m-d H:i:s', strtotime('now - 600 seconds'));
		try {
			$colleagues = $this->User->colleagues($this->Session->read('Auth.User.id'), null, $since);
		} catch(Exception $e) {
			$this->cakeError('internal_error', array('action' => 'Retrieve', 'resource' => 'Colleagues'));
			return;
		}

		/* TODO: There has to be a way to make this work with MySQL instead of in PHP */
		$now = date('U');
		for($i = 0; $i < sizeof($colleagues); $i++)
		{
			$activity = date('U', strtotime($colleagues[$i]['User']['activity']));
			if($now - $activity >= 600)
			{
				unset($colleagues[$i]);
			}
		}

		try {
			$colleagues = $this->User->toNodes($colleagues);
		} catch(Exception $e) {
			$this->cakeError('internal_error', array('action' => 'Convert', 'resource' => 'Colleagues'));
			return;
		}

		try {
			$groups = $this->GroupsUsers->groups($this->Session->read('Auth.User.id'));
			$groups = $this->Group->toNodes($groups);
		} catch(Exception $e) {
			$this->cakeError('internal_error', array('action' => 'Retrieve', 'resource' => 'Groups'));
			return;
		}

		try {
			$projects = $this->ProjectsUsers->projects($this->Session->read('Auth.User.id'));
			$projects = $this->Project->toNodes($projects);
		} catch(Exception $e) {
			$this->cakeError('internal_error', array('action' => 'Retrieve', 'resource' => 'Projects'));
			return;
		}

		$list = array(
			'rows' => array_merge($colleagues, $groups, $projects),
		);

		usort($list['rows'], array($this, '_chatlist_sort'));
		
		$this->set('list', $list);
	}

	/**
	 * Sorts the Chat List
	 *
	 * @internal
	 *
	 * @param array $a Chat List Entry
	 * @param array $b Chat List Entry
	 *
	 * @return integer Sort Priority
	 */
	function _chatlist_sort($a, $b)
	{
		return strcasecmp($a['name'], $b['name']);
	}

	/**
	 * Creates a Popout Chat Window
	 *
	 * @param string $session Chat Session ID
	 */
	function popout($session = '')
	{
		$this->layout = 'chat_popout';

		if(empty($session))
		{
			$this->cakeError('missing_field', array('field' => 'Session'));
			return;
		}

		if(!is_string($session))
		{
			$this->cakeError('invalid_field', array('field' => 'Session'));
			return;
		}

		$this->set('chat_session', $session);
	}

	/**
	 * Help for Pidgin
	 */
	function help_pidgin()
	{
		$this->pageTitle = 'Help - Chat - Pidgin';
		$this->set('pageName', 'Pidgin - Chat - Help');
	}

	/* TODO: Create help functions for chat */
}
?>
