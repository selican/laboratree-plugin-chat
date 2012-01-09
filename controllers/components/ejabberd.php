<?php
class EjabberdComponent extends Object
{
	var $server;
	var $user;
	var $pass;
	var $host;
	var $service;

	function initialize(&$controller)
	{
		$this->Controller =& $controller;

		$this->server = Configure::read('Ejabberd.server');
		$this->user = Configure::read('Ejabberd.username');
		$this->pass = Configure::read('Ejabberd.password');

		$this->host = Configure::read('Chat.domain');
		$this->service = 'chat.' . $this->host;
	}

	/**
	 * Register User on Ejabberd Server. Unused.
	 *
	 * @param string $username Username
	 * @param string $password Password
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function register($username, $password)
	{
		if(empty($username) || !is_string($username))
		{
			throw new InvalidArgumentException('Invalid username.');
		}

		if(empty($password) || !is_string($password))
		{
			throw new InvalidArgumentException('Invalid password.');
		}

		$params = array(
			'user' => $username,
			'host' => $this->host,
			'password' => $password,
		);

		return $this->_send('register', $params);
	}

	/**
	 * Unregistered User on Ejabberd Server. Unused.
	 *
	 * @param string $username Username
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function unregister($username)
	{
		if(empty($username) || !is_string($username))
		{
			throw new InvalidArgumentException('Invalid username.');
		}

		$params = array(
			'user' => $username,
			'host' => $this->host,
		);

		return $this->_send('unregister', $params);
	}

	/**
	 * Changes User Password on Ejabberd Server. Unused.
	 *
	 * @param string $username Username
	 * @param string $password New Password
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function change_password($username, $password)
	{
		if(empty($username) || !is_string($username))
		{
			throw new InvalidArgumentException('Invalid username.');
		}

		if(empty($password) || !is_string($password))
		{
			throw new InvalidArgumentException('Invalid password.');
		}

		$params = array(
			'user' => $username,
			'host' => $this->host,
			'newpass' => $password,
		);

		return $this->_send('change_password', $params);
	}

	/**
	 * Adds a User to a Roster
	 *
	 * @param string $user  Username of Roster Owner
	 * @param string $other Username of User to Add to Roster
	 * @param string $nick  Nickname of User to Add to Roster
	 * @param string $group Group on Roster to Add To
	 * @param string $subs  Subscriptions
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function add_rosteritem($user, $other, $nick, $group, $subs)
	{
		if(empty($user) || !is_string($user))
		{
			throw new InvalidArgumentException('Invalid user.');
		}

		if(empty($other) || !is_string($other))
		{
			throw new InvalidArgumentException('Invalid other.');
		}

		if(empty($nick) || !is_string($nick))
		{
			throw new InvalidArgumentException('Invalid nick.');
		}

		if(empty($group) || !is_string($group))
		{
			throw new InvalidArgumentException('Invalid group.');
		}

		if(empty($subs) || !is_string($subs) || !in_array($subs, array('none', 'from', 'to', 'both')))
		{
			throw new InvalidArgumentException('Invalid subs.');
		}

		$params = array(
			'localuser' => $user,
			'localserver' => $this->host,
			'user' => $other,
			'server' => $this->host,
			'nick' => $nick,
			'group' => $group,
			'subs' => $subs,
		);

		return $this->_send('add_rosteritem', $params);
	}

	/**
	 * Deletes User from Roster
	 *
	 * @param string $user  Username of Roster Owner
	 * @param string $other Username of User to Remove
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function delete_rosteritem($user, $other)
	{
		if(empty($user) || !is_string($user))
		{
			throw new InvalidArgumentException('Invalid user.');
		}

		if(empty($other) || !is_string($other))
		{
			throw new InvalidArgumentException('Invalid other.');
		}

		$params = array(
			'localuser' => $user,
			'localserver' => $this->host,
			'user' => $other,
			'server' => $this->host,
		);

		return $this->_send('delete_rosteritem', $params);
	}

	/**
	 * Create Shared Roster Gorup
	 *
	 * @param string $group       Shared Roster Group
	 * @param string $name        Name of Shared Roster Group
	 * @param string $description Description of Shared Roster Group
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function srg_create($group, $name, $description)
	{
		if(empty($group) || !is_string($group))
		{
			throw new InvalidArgumentException('Invalid group.');
		}

		if(empty($name) || !is_string($name))
		{
			throw new InvalidArgumentException('Invalid name.');
		}

		if(empty($description) || !is_string($description))
		{
			throw new InvalidArgumentException('Invalid description.');
		}

		$params = array(
			'group' => $group,
			'host' => $this->host,
			'name' => $name,
			'description' => $description,
			'display' => $group,
		);

		return $this->_send('srg_create', $params);
	}

	/**
	 * Deleted Shared Roster Group
	 *
	 * @param string $group Shared Roster Group
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function srg_delete($group)
	{
		if(empty($group) || !is_string($group))
		{
			throw new InvalidArgumentException('Invalid group.');
		}

		$params = array(
			'group' => $group,
			'host' => $this->host,
		);

		return $this->_send('srg_delete', $params);
	}

	/**
	 * Add User to Shared Roster Group
	 *
	 * @param string $user  Username
	 * @param string $group Shared Roster Group
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function srg_user_add($user, $group)
	{
		if(empty($user) || !is_string($user))
		{
			throw new InvalidArgumentException('Invalid user.');
		}

		if(empty($group) || !is_string($group))
		{
			throw new InvalidArgumentException('Invalid group.');
		}

		$params = array(
			'user' => $user,
			'host' => $this->host,
			'group' => $group,
			'grouphost' => $this->host,
		);

		return $this->_send('srg_user_add', $params);
	}

	/**
	 * Delete User from Shared Roster Group
	 *
	 * @param string $user  Username
	 * @param string $group Shared Roster Group
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function srg_user_del($user, $group)
	{
		if(empty($user) || !is_string($user))
		{
			throw new InvalidArgumentException('Invalid user.');
		}

		if(empty($group) || !is_string($group))
		{
			throw new InvalidArgumentException('Invalid group.');
		}

		$params = array(
			'user' => $user,
			'host' => $this->host,
			'group' => $group,
			'grouphost' => $this->host,
		);

		return $this->_send('srg_user_del', $params);
	}

	/**
	 * Sets the Name for a Shared Roster Group
	 *
	 * @param string $group Shared Roster Group
	 * @param string $name  Name
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function srg_set_name($group, $name)
	{
		if(empty($group) || !is_string($group))
		{
			throw new InvalidArgumentException('Invalid group.');
		}

		if(empty($name) || !is_string($name))
		{
			throw new InvalidArgumentException('Invalid name.');
		}

		$params = array(
			'group' => $group,
			'grouphost' => $this->host,
			'name' => $name,
		);

		return $this->_send('srg_set_name', $params);
	}

	/**
	 * Sets teh Description for a Shared Roster Group
	 *
	 * @param string $group       Shared Roster Group
	 * @param string $description Description
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function srg_set_description($group, $description)
	{
		if(empty($group) || !is_string($group))
		{
			throw new InvalidArgumentException('Invalid group.');
		}

		if(empty($description) || !is_string($description))
		{
			throw new InvalidArgumentException('Invalid description.');
		}

		$params = array(
			'group' => $group,
			'grouphost' => $this->host,
			'description' => $description,
		);

		return $this->_send('srg_set_description', $params);
	}

	/**
	 * Creates a MUC room
	 *
	 * @param string $name Room Name
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function create_room($name)
	{
		if(empty($name) || !is_string($name))
		{
			throw new InvalidArgumentException('Invalid name.');
		}

		$params = array(
			'name' => $name,
			'service' => $this->service,
			'host' => $this->host,
		);

		return $this->_send('create_room', $params);
	}

	/**
	 * Destroys a MUC room
	 *
	 * @param string $name Room Name
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function destroy_room($name)
	{
		if(empty($name) || !is_string($name))
		{
			throw new InvalidArgumentException('Invalid name.');
		}

		$params = array(
			'name' => $name,
			'service' => $this->service,
			'host' => $this->host,
		);

		return $this->_send('destroy_room', $params);
	}

	/**
	 * Changes a MUC room option
	 *
	 * @param string $name   Room Name
	 * @param string $option Room Option
	 * @param string $value  Option Value
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function change_room_option($name, $option, $value)
	{
		if(empty($name) || !is_string($name))
		{
			throw new InvalidArgumentException('Invalid name.');
		}

		if(empty($option) || !is_string($option))
		{
			throw new InvalidArgumentException('Invalid option.');
		}

		if(empty($value) || !is_string($value))
		{
			throw new InvalidArgumentException('Invalid value.');
		}

		$params = array(
			'name' => $name,
			'service' => $this->service,
			'option' => $option,
			'value' => $value,
		);

		return $this->_send('change_room_option', $params);
	}

	/**
	 * Sets a MUC room Persistent option
	 *
	 * @param string  $name  Room Name
	 * @param boolean $value Persistance
	 *
	 * @throw InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function set_persistent($name, $value = true)
	{
		if(empty($name) || !is_string($name))
		{
			throw new InvalidArgumentException('Invalid name.');
		}

		if(!is_bool($value))
		{
			throw new InvalidArgumentException('Invalid value.');
		}

		$value = (!$value) ? 'false' : 'true';
		return $this->change_room_option($name, 'persistent', $value);
	}

	/**
	 * Sets a MUC room Logging option
	 *
	 * @param string  $name  Room Name
	 * @param boolean $value Logging
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function set_logging($name, $value = true)
	{
		if(empty($name) || !is_string($name))
		{
			throw new InvalidArgumentException('Invalid name.');
		}

		if(!is_bool($value))
		{
			throw new InvalidArgumentException('Invalid value.');
		}

		$value = (!$value) ? 'false' : 'true';
		return $this->change_room_option($name, 'logging', $value);
	}

	/**
	 * Set a MUC room Members Only option
	 *
	 * @param string  $name  Room Name
	 * @param boolean $value Members Only
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function set_members_only($name, $value = true)
	{
		if(empty($name) || !is_string($name))
		{
			throw new InvalidArgumentException('Invalid name.');
		}

		if(!is_bool($value))
		{
			throw new InvalidArgumentException('Invalid value.');
		}

		$value = (!$value) ? 'false' : 'true';
		return $this->change_room_option($name, 'members_only', $value);
	}

	/**
	 * Sets a User's MUC room affiliation
	 *
	 * @param string $name       Room Name
	 * @param string $username   Username
	 * @param string $affliation Room Affliation
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function set_room_affiliation($name, $username, $affiliation = 'member')
	{
		if(empty($name) || !is_string($name))
		{
			throw new InvalidArgumentException('Invalid name.');
		}

		if(empty($username) || !is_string($username))
		{
			throw new InvalidArgumentException('Invalid username.');
		}

		if(empty($affiliation) || !in_array($affiliation, array('owner', 'admin', 'member', 'outcast', 'none')))
		{
			throw new InvalidArgumentException('Invalid affiliation.');
		}

		$jid = $username . '@' . $this->host;

		$params = array(
			'name' => $name,
			'service' => $this->service,
			'jid' => $jid,
			'affiliation' => $affiliation,
		);

		return $this->_send('set_room_affiliation', $params);
	}

	/** 
	 * Sets a MUC room's Password Protected option
	 *
	 * @param string  $name  Room Name
	 * @param boolean $value Password Protected
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function set_password_protected($name, $value = true)
	{
		if(empty($name) || !is_string($name))
		{
			throw new InvalidArgumentException('Invalid name.');
		}

		if(!is_bool($value))
		{
			throw new InvalidArgumentException('Invalid value.');
		}

		$value = (!$value) ? 'false' : 'true';
		return $this->change_room_option($name, 'password_protected', $value);
	}

	/**
	 * Sets a MUC room's Password Option
	 *
	 * @param string $name  Room Name
	 * @param string $value Room Password
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function set_password($name, $value)
	{
		if(empty($name) || !is_string($name))
		{
			throw new InvalidArgumentException('Invalid name.');
		}

		if(empty($value) || !is_string($value))
		{
			throw new InvalidArgumentException('Invalid value.');
		}

		return $this->change_room_option($name, 'password', $value);
	}

	/**
	 * Sends a Command to the Ejabberd Server
	 *
	 * @internal
	 *
	 * @param string $command EjabberdCTL Command
	 * @param array  $params  Command Parameters
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Ejabberd Response
	 */
	function _send($command, $params = array())
	{
		if(!Configure::read('Chat.enabled'))
		{
			return true;
		}

		if(empty($command) || !is_string($command))
		{
			throw new InvalidArgumentException('Invalid command.');
		}

		if(!is_array($params))
		{
			throw new InvalidArgumentException('Invalid params.');
		}

		$request = xmlrpc_encode_request($command, $params, array('encoding' => 'utf-8'));

		$headers = array(
			'User-Agent: XMLRPC::Client mod_xmlrpc',
			'Content-Type: text/xml',
			'Content-Length: ' . strlen($request),
		);

		$context = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => implode("\r\n", $headers),
				'content' => $request,
			),
		));

		$file = file_get_contents($this->server, false, $context);
		if($file === false)
		{
			return false;
		}

		$response = xmlrpc_decode($file);

		if(!is_array($response))
		{
			return false;
		}

		if(!xmlrpc_is_fault($response))
		{
			return $response;
		}

		return false;
	}
}
?>
