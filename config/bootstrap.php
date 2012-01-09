<?php
	/* Define Constants */
	if(!defined('CHAT_APP'))
	{
		define('CHAT_APP', APP . DS . 'plugins' . DS . 'chat');
	}

	if(!defined('CHAT_CONFIGS'))
	{
		define('CHAT_CONFIGS', CHAT_APP . DS . 'config');
	}

	/* Include Config File */
	require_once(CHAT_CONFIGS . DS . 'chat.php');

	/* Setup Permissions */
	try {
		$parent = $this->addPermission('chat', 'Chat');

		$this->addPermission('chat.logs', 'View Chat Logs', 1, $parent);

		$this->addPermissionDefaults(array(
			'group' => array(
				'chat' => array(
					'Administrator' => 1,
					'Manager' => 1,
					'Member' => 1,
				),
			),
			'project' => array(
				'chat' => array(
					'Administrator' => 1,
					'Manager' => 1,
					'Member' => 1,
				),
			),
		));
	} catch(Exception $e) {
		// TODO: Do something
	}

	/* Add Listeners */
	try {
		$this->addListener('chat', 'group.add', function($id, $name) {
			App::import('Core', 'Controller');
			$controller = new Controller();

			App::import('Component', 'Chat.Ejabberd');
			$ejabberd = new EjabberdComponent();
			$ejabberd->initialize($controller);

			/* Create MUC Room */
			$keyword = 'group_' . $id;
			if($ejabberd->create_room($keyword) === false)
			{
				throw new RuntimeException('Unable to create room');
			}

			/* TODO: Check output of these functions */
			$ejabberd->set_persistent($keyword);
			$ejabberd->set_logging($keyword);
			$ejabberd->set_members_only($keyword);

			/* Create Shared Roster Group */
			if($ejabberd->srg_create($keyword, $name, 'Group: ' . $name) === false)
			{
				throw new RuntimeException('Unable to create shared roster group');
			}
		});

		$this->addListener('chat', 'group.edit', function($id, $oldName, $newName) {
			if($oldName == $newName)
			{
				return true;
			}

			App::import('Core', 'Controller');
			$controller = new Controller();

			App::import('Component', 'Chat.Ejabberd');
			$ejabberd = new EjabberdComponent();
			$ejabberd->initialize($controller);

			$keyword = 'group_' . $id;
			try {
				$ejabberd->srg_set_name($keyword, 'Group: ' . $newName);
			} catch(Exception $e) {
				throw new RuntimeException('Unable to set shared roster group name');
			}

			try {
				$ejabberd->srg_set_description($keyword, $newName);
			}
			catch(Exception $e)
			{
				throw new RuntimeException('Unable to set shared roster group description');
			}
		});

		$this->addListener('chat', 'group.delete', function($id, $name) {
			App::import('Core', 'Controller');
			$controller = new Controller();

			App::import('Component', 'Chat.Ejabberd');
			$ejabberd = new EjabberdComponent();
			$ejabberd->initialize($controller);

			$keyword = 'group_' . $id;
			try {
				$ejabberd->destroy_room($keyword);
			} catch(Exception $e) {
				throw new RuntimeException('Unable to destroy MUC room');
			}

			try {
				$ejabberd->srg_delete($keyword);
			}
			catch(Exception $e)
			{
				throw new RuntimeException('Unable to delete shared roster group');
			}
		});

		$this->addListener('chat', 'group.adduser', function($id, $user_id, $username, $name) {
			App::import('Core', 'Controller');
			$controller = new Controller();

			App::import('Component', 'Chat.Ejabberd');
			$ejabberd = new EjabberdComponent();
			$ejabberd->initialize($controller);

			$keyword = 'group_' . $id;
			try {
				$ejabberd->srg_user_add($username, $keyword);
			} catch(Exception $e) {
				throw new RuntimeException('Unable to add user to shared roster group');
			}

			try {
				$ejabberd->set_room_affiliation($keyword, $username, 'member');
			} catch(Exception $e) {
				throw new RuntimeException('Unable to set room affiliation');
			}
		});

		$this->addListener('chat', 'group.removeuser', function($id, $user_id, $username, $name) {
			App::import('Core', 'Controller');
			$controller = new Controller();

			App::import('Component', 'Chat.Ejabberd');
			$ejabberd = new EjabberdComponent();
			$ejabberd->initialize($controller);

			$keyword = 'group_' . $id;
			try {
				$ejabberd->srg_user_del($username, $keyword);
			} catch(Exception $e) {
				throw new RuntimeException('Unable to remove user from shared roster group');
			}
			
			try {
				$ejabberd->set_room_affiliation($keyword, $username, 'none');
			} catch(Exception $e) {
				throw new RuntimeException('Unable to set room affiliation');
			}
		});

		$this->addListener('chat', 'project.add', function($id, $name) {
			App::import('Core', 'Controller');
			$controller = new Controller();

			App::import('Component', 'Chat.Ejabberd');
			$ejabberd = new EjabberdComponent();
			$ejabberd->initialize($controller);

			/* Create MUC Room */
			$keyword = 'project_' . $id;
			if($ejabberd->create_room($keyword) === false)
			{
				throw new RuntimeException('Unable to create room');
			}

			/* TODO: Check output of these functions */
			$ejabberd->set_persistent($keyword);
			$ejabberd->set_logging($keyword);
			$ejabberd->set_members_only($keyword);

			/* Create Shared Roster Project */
			if($ejabberd->srg_create($keyword, $name, 'Project: ' . $name) === false)
			{
				throw new RuntimeException('Unable to create shared roster project');
			}
		});

		$this->addListener('chat', 'project.edit', function($id, $oldName, $newName) {
			App::import('Core', 'Controller');
			$controller = new Controller();

			App::import('Component', 'Chat.Ejabberd');
			$ejabberd = new EjabberdComponent();
			$ejabberd->initialize($controller);

			if($oldName == $newName)
			{
				return true;
			}

			$keyword = 'project_' . $id;
			try {
				$ejabberd->srg_set_name($keyword, 'Project: ' . $newName);
			} catch(Exception $e) {
				throw new RuntimeException('Unable to set shared roster project name');
			}

			try {
				$ejabberd->srg_set_description($keyword, $newName);
			}
			catch(Exception $e)
			{
				throw new RuntimeException('Unable to set shared roster project description');
			}
		});

		$this->addListener('chat', 'project.delete', function($id, $name) {
			App::import('Core', 'Controller');
			$controller = new Controller();

			App::import('Component', 'Chat.Ejabberd');
			$ejabberd = new EjabberdComponent();
			$ejabberd->initialize($controller);

			$keyword = 'project_' . $id;
			try {
				$ejabberd->destroy_room($keyword);
			} catch(Exception $e) {
				throw new RuntimeException('Unable to destroy MUC room');
			}

			try {
				$ejabberd->srg_delete($keyword);
			}
			catch(Exception $e)
			{
				throw new RuntimeException('Unable to delete shared roster project');
			}
		});

		$this->addListener('chat', 'project.adduser', function($id, $user_id, $username, $name) {
			App::import('Core', 'Controller');
			$controller = new Controller();

			App::import('Component', 'Chat.Ejabberd');
			$ejabberd = new EjabberdComponent();
			$ejabberd->initialize($controller);

			$keyword = 'project_' . $id;
			try {
				$ejabberd->srg_user_add($username, $keyword);
			} catch(Exception $e) {
				throw new RuntimeException('Unable to add user to shared roster project');
			}
			
			try {
				$ejabberd->set_room_affiliation($keyword, $username, 'member');
			} catch(Exception $e) {
				throw new RuntimeException('Unable to set room affiliation');
			}
		});

		$this->addListener('chat', 'project.removeuser', function($id, $user_id, $username, $name) {
			App::import('Core', 'Controller');
			$controller = new Controller();

			App::import('Component', 'Chat.Ejabberd');
			$ejabberd = new EjabberdComponent();
			$ejabberd->initialize($controller);

			$keyword = 'project_' . $id;
			try {
				$ejabberd->srg_user_del($username, $keyword);
			} catch(Exception $e) {
				throw new RuntimeException('Unable to remove user from shared roster project');
			}
			
			try {
				$ejabberd->set_room_affiliation($keyword, $username, 'none');
			} catch(Exception $e) {
				throw new RuntimeException('Unable to set room affiliation');
			}
		});
	} catch(Exception $e) {
		// TODO: Do something
	}
?>
