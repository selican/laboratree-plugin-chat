<?php
class BoshComponent extends Object
{
	var $components = array(
		'Session',
	);

	var $bindurl = null;

	const HTTPBIND = 'http://jabber.org/protocol/httpbind';
	const XMPP = 'urn:xmpp:xbosh';
	const AUTH = 'urn:ietf:params:xml:ns:xmpp-sasl';
	const CLIENT = 'jabber:client';
	const BIND = 'urn:ietf:params:xml:ns:xmpp-bind';
	const SESSION = 'urn:ietf:params:xml:ns:xmpp-session';

	function initialize(&$controller, $settings = array())
	{
		$this->Controller =& $controller;
	}

	function startup(&$controller) {}

	/**
	 * Creates an XML element based on attributes and contents
	 *
	 * @param string $element  XML Element
	 * @param array  $attrs    XML Element Attributes
	 * @param string $contents XML Element Content
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return string XML Data
	 */
	function element($element, $attrs = array(), $contents = null)
	{
		if(empty($element) || !is_string($element))
		{
			throw new InvalidArgumentException('Invalid element');
		}

		if(!is_array($attrs))
		{
			throw new InvalidArgumentException('Invalid attrs');
		}

		if(!empty($contents) && !is_string($contents))
		{
			throw new InvalidArgumentException('Invalid contents');
		}

		$xml = '<' . $element;
		foreach($attrs as $key => $value)
		{
			$xml .= ' ' . $key . '="' . $value . '"';
		}

		if(empty($contents))
		{
			$xml .= '/>';
		}
		else
		{
			$xml .= '>';
			$xml .= $contents;
			$xml .= '</' . $element . '>';
		}

		return $xml;
	}

	/**
	 * Sends teh XML packet to the BOSH server
	 *
	 * @param string $xml     XML Packet
	 * @param string $bindurl BOSH Bind Url
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Response
	 */
	function send($xml, $bindurl = null)
	{
		if(empty($xml) || !is_string($xml))
		{
			throw new InvalidArgumentException('Invalid xml');
		}

		if(!empty($bindurl))
		{
			if(!is_string($bindurl))
			{
				throw new InvalidArgumentException('Invalid bindurl');
			}

			$this->bindurl = $bindurl;
		}

		if(empty($this->bindurl))
		{
			throw new InvalidArgumentException('Invalid bindurl');
		}

		/*
		 * I added the connect timeout setting to avoid
		 * hanging up the client on a bad connection.
		 * This value may need to be adjusted in the future
		 */
		$options = array(
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $xml,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: text/xml; charset=utf-8',
				'Accept: text/xml',
			),
		);

		$ch = curl_init($this->bindurl);
		if($ch === false)
		{
			return false;
		}

		$res = curl_setopt_array($ch, $options);
		if($res === false)
		{
			return false;
		}

		$response = curl_exec($ch);
		if($response === false)
		{
			return false;
		}

		curl_close($ch);

		return $response;
	}

	/**
	 * Connects to the BOSH service
	 *
	 * @param string $username Username
	 * @param string $password Password
	 * @param string $bindurl  BOSH Bind Url
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed Connection Data
	 */
	function connect($username, $password, $bindurl)
	{
		if(empty($username) || !is_string($username))
		{
			throw new InvalidArgumentException('Invalid username.');
		}

		if(empty($password) || !is_string($password))
		{
			throw new InvalidArgumentException('Invalid password.');
		}

		if(empty($bindurl) || !is_string($bindurl))
		{
			throw new InvalidArgumentException('Invalid bindurl.');
		}

		$this->bindurl = $bindurl;

		$jid = $username . '@' . Configure::read('Chat.domain');

		$connData = array(
			$jid,
			$username,
			$password,
		);

		$connString = implode("\0", $connData);
		$hash = base64_encode($connString);
		
		/* Handshake */
		$rid = rand();
		$body = $this->element('body', array(
			'rid' => $rid,
			'xmlns' => self::HTTPBIND,
			'to' => Configure::read('Chat.domain'),
			'xml:lang' => 'en',
			'wait' => 60,
			'hold' => 1,
			'content' => 'text/xml; charset=utf-8',
			'ver' => '1.6',
			'xmpp:version' => '1.0',
			'xmlns:xmpp' => self::XMPP,
		));
		$response = $this->send($body);
		if(!$response)
		{
			return false;
		}

		try
		{
			$xml = new SimpleXMLElement($response);
			if(!is_object($xml))
			{
				return false;
			}

			/* We have to leave this as an array reference */
			$sid = (string) $xml['sid'];
		}
		catch (Exception $e)
		{
			return false;
		}

		/* Authentication */
		$rid++;
		$auth = $this->element('auth', array(
			'xmlns' => self::AUTH,
			'mechanism' => 'PLAIN',
		), $hash);

		$body = $this->element('body', array(
			'rid' => $rid,
			'xmlns' => self::HTTPBIND,
			'sid' => $sid,
		), $auth);
		$response = $this->send($body);
		if(!$response)
		{
			return false;
		}

		/* Restart */
		$rid++;
		$body = $this->element('body', array(
			'rid' => $rid,
			'xmlns' => self::HTTPBIND,
			'sid' => $sid,
			'to' => Configure::read('Chat.domain'),
			'xml:lang' => 'en',
			'xmpp:restart' => 'true',
			'xmlns:xmpp' => self::XMPP,
		));
		$response = $this->send($body);
		if(!$response)
		{
			return false;
		}

		/* Bind */
		$rid++;
		$bind = $this->element('bind', array(
			'xmlns' => self::BIND,
		));

		$iq = $this->element('iq', array(
			'type' => 'set',
			'id' => '_bind_auth_2',
			'xmlns' => self::CLIENT,
		), $bind);

		$body = $this->element('body', array(
			'rid' => $rid,
			'xmlns' => self::HTTPBIND,
			'sid' => $sid,
		), $iq);
		$response = $this->send($body);
		if(!$response)
		{
			return false;
		}

		try
		{
			$xml = new SimpleXMLElement($response);
			if(!is_object($xml->iq->bind))
			{
				return false;
			}

			$jid = (string) $xml->iq->bind->jid;
		}
		catch (Exception $e)
		{
			return false;
		}

		/* Session */
		$rid++;
		$session = $this->element('session', array(
			'xmlns' => self::SESSION,
		));

		$iq = $this->element('iq', array(
			'type' => 'set',
			'id' => '_session_auth_2',
			'xmlns' => self::CLIENT,
		), $session);

		$body = $this->element('body', array(
			'rid' => $rid,
			'xmlns' => self::HTTPBIND,
			'sid' => $sid,
		), $iq);
		$response = $this->send($body);
		if(!$response)
		{
			return false;
		}

		$rid++;
		return array(
			'rid' => $rid,
			'sid' => $sid,
			'jid' => $jid,
		);
	}
}
?>
