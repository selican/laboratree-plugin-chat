<?php echo $html->docType('xhtml-trans'); ?>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" dir="ltr">
	<head>
		<title><?php echo Configure::read('Site.name') . ' Chat'; ?></title>
		<?php
			echo $html->charset('utf-8');
			echo $html->css('ext-all.css');
			echo $html->css('chat.css');

			echo $javascript->link('jquery/jquery-1.3.2.min.js');
	
			if(Configure::read('debug') > 0)
			{
				echo $javascript->link('extjs/adapter/ext/ext-base-debug.js');
				echo $javascript->link('extjs/ext-all-debug.js');
			}
			else
			{
				echo $javascript->link('extjs/adapter/ext/ext-base.js');
				echo $javascript->link('extjs/ext-all.js');
			}

			echo $javascript->link('cryptojs/crypto/crypto-min.js');
			echo $javascript->link('cryptojs/crypto-sha1/crypto-sha1.js');
			
			echo $javascript->link('strophe/strophe.js');
			echo $javascript->link('strophe/plugins/strophe.caps.js');
			echo $javascript->link('strophe/plugins/strophe.muc.js');
			echo $javascript->link('strophe/plugins/strophe.archive.js');
			echo $javascript->link('strophe/plugins/strophe.pep.js');
			echo $javascript->link('strophe/plugins/strophe.chatstate.js');

			echo $javascript->link('laboratree.js');
			echo $javascript->link('chat.js');

			echo $scripts_for_layout;
		?>
	</head>
	<body><?php echo $content_for_layout; ?></body>
</html>
