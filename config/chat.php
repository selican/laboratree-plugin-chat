<?php
	Configure::write('Chat.enabled', true);
	Configure::write('Chat.connectUrl', 'http://SITE.selican.dyndns.org/chat/connect.js'); // CHANGE ME
	Configure::write('Chat.boshUrl', 'http://hoth:7650/http-bind');
	Configure::write('Chat.domain', 'hoth');

	Configure::write('Ejabberd.server', 'http://localhost:4560');
	Configure::write('Ejabberd.username', '');
	Configure::write('Ejabberd.password', '');
?>
