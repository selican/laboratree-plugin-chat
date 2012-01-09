laboratree.chat = {};
/**
 * Global Variables
 */

/**
 * Client Identity
 * For Entity Capabilities: http://xmpp.org/extensions/xep-0115.html
 */
laboratree.chat.identity = {
	category: 'client',
	type: 'web',
	name: 'LaboratreeChat'
};

/**
 * Client Features
 * For Entity Capabilities: http://xmpp.org/extensions/xep-0115.html
 */
laboratree.chat.features = [
	'http://jabber.org/protocol/caps', //XEP-0115
	'http://jabber.org/protocol/chatstates', //XEP-0085
	'http://jabber.org/protocol/disco#info', //XEP-0030
	'http://jabber.org/protocol/disco#items', //XEP-0030
	'http://jabber.org/protocol/muc', //XEP-0045
	'urn:xmpp:avatar:data', //XEP-0084
	'urn:xmpp:avatar:metadata', //XEP-0084
	'urn:xmpp:chat:event+notify' // PEP Node for Chat Event
];

/**
 * Connection State
 * Variables to track the chat connection
 */
laboratree.chat.stat = Strophe.Status.DISCONNECTED;
laboratree.chat.auth = {};

/**
 * Reconnect Limiters
 * Count and Limit for Reconnect Attempts
 */
laboratree.chat.reconnectCount = 0;
laboratree.chat.reconnectLimit = 6;

/**
 * Popin/Out Timeouts
 * Timeout values for valid PEP messages
 */
laboratree.chat.popinTimeout = 5 * 1000; // 5 Seconds
laboratree.chat.popoutTimeout = 5 * 1000; // 5 Seconds
laboratree.chat.popoutText = null;

/**
 * Chat State Timeout
 * Timeout between sending composing and paused events
 */
laboratree.chat.chatStateTimeout = 3 * 1000; // 3 Seconds

/**
 * Panels Array
 * Associates Session to Panel
 */
laboratree.chat.panels = {};

/**
 * Record Types
 */

/**
 * List of all messages per session
 */
laboratree.chat.messageRecord = Ext.data.Record.create([
	'session',
	'name',
	'message',
	'timestamp'
]);

/**
 * List of all message IDs and Timestamps
 */
laboratree.chat.archiveRecord = Ext.data.Record.create([
	'id',
	'timestamp'
]);

/**
 * List of Multi-User Chat (MUC) Rooms
 */
laboratree.chat.mucUser = Ext.data.Record.create([
	'session',
	'name',
	'jid',
	'role'
]);

/**
 * Document Event Functions
 */
laboratree.chat.onLoad = function() {
	// Opera Fast Navigation Fix: http://www.opera.com/support/kb/view/827/
	if(history) {
		history.navigationMode = 'compatible';
	}
};

laboratree.chat.onUnload = function() {
	// We have to keep this here to work with browsers that don't support onbeforeunload events
	if(laboratree.chat.stat != Strophe.Status.DISCONNECTED) {
		laboratree.chat.onBeforeUnload();
	}

	// Save Connection to Server
	Ext.Ajax.request({
		url: laboratree.links.chat.save + '.json',
		params: laboratree.chat.auth,
		success: function(response, request) {

		},
		failure: function(response, request) {

		},
		scope: this
	});
};

laboratree.chat.onBeforeUnload = function() {
	laboratree.chat.stat = Strophe.Status.DISCONNECTED;

	if(laboratree.chat.type == 'window') {
		laboratree.chat.windowMgr.windowGroup.hideAll();

		// We pause the connection so we can get a reliable Request id (rid)
		laboratree.chat.bosh.connection.pause();
		laboratree.chat.auth.rid = parseInt(laboratree.chat.bosh.connection.rid, 10);

	} else if(laboratree.chat.type == 'popout') {
		laboratree.chat.bosh.connection.disconnect();
	}
};

/**
 * Initialize Global Chat Variables
 */
//laboratree.chat.sessionStore = null;
//laboratree.chat.listMenu  = null;
//laboratree.chat.windowMgr = null;
//laboratree.chat.popoutMgr = null;

laboratree.chat.showMenu = function() {
	var btn = Ext.get(laboratree.chat.div);
	if(btn) {
		// TODO: This throws errors, but works. Fix it.
		laboratree.chat.listMenu.show(btn, 'tl-bl', [-4, 0]);
	}
};

/**
 * JSONP Callback to Intialize Chat
 */
laboratree.chat.init = function(div, auth) {
	laboratree.chat.div = div;
	laboratree.chat.auth = auth;

	laboratree.chat.windowMgr = new laboratree.chat.WindowMgr();
	laboratree.chat.popoutMgr = new laboratree.chat.PopoutMgr();

	laboratree.chat.listMenu = new Ext.menu.Menu({
		height: 250,
		autoScroll: true,

		items: [{
			text: 'No Online Collaborators'
		}],
		
		listeners: {
			click: function(menu, item, e) {
				var record = laboratree.chat.sessionStore.getById(item.itemId);
				if(record) {
					laboratree.chat.windowMgr.openWindow(record.data.session);
				}
			},
			beforeshow: function(menu) {
				laboratree.chat.sessionStore.reload();
			}
		}
	});

	laboratree.chat.sessionStore = new Ext.data.JsonStore({
		url: laboratree.chat.auth.list,
		storeId: 'chat_store',
		root: 'rows',
		idProperty: 'session',
		fields: ['id', 'type', 'session', 'username', 'name'],

		listeners: {
			load: function(store, records, options) {
				if(laboratree.chat.type == 'window') {
					if(records.length > 0) {
						laboratree.chat.listMenu.removeAll();
						Ext.each(records, function(item, index, allItems) {
							laboratree.chat.listMenu.addMenuItem({
								cls: 'chat-menu-' + item.data.type,
								text: item.data.name,
								itemId: item.id
							});
						});
					}
				}
			}
		}
	});

	laboratree.chat.connect();
};

/**
 * JSONP Callback to Reinitialize Chat
 */
laboratree.chat.reinit = function(div, auth) {
	laboratree.chat.div = div;
	laboratree.chat.auth = auth;

	if(laboratree.chat.type == 'window') {
		if(laboratree.chat.windowMgr) {
			// Hide Open Chat Windows
			laboratree.chat.windowMgr.windowGroup.hideAll();
	
			// Remove Chat Button
			laboratree.chat.windowMgr.removeButton();
		}
	}

	laboratree.chat.connect();
};

/**
 * Chat Connect
 * Attaches to BOSH, Loads Chat List, and Setups up Windows/Popout.
 */
laboratree.chat.connect = function() {
	if(!laboratree.chat.auth) {
		laboratree.chat.stat = Strophe.Status.ERROR;
		return;
	}	

	if(laboratree.chat.auth.errors) {
		laboratree.chat.stat = Strophe.Status.ERROR;
		return;
	}

	laboratree.chat.bosh = new laboratree.chat.Bosh('/xmpp-httpbind');
	laboratree.chat.bosh.attach();

	laboratree.chat.sessionStore.load({
		scope: this,
		callback: function(records, options, success) {
			if(success) {
				if(laboratree.chat.type == 'window') {
					if(records.length > 0) {
						var sessions = Ext.state.Manager.get('chat-sessions', null);
						if(sessions) {
							var session;
							for(session in sessions) {
								if(sessions.hasOwnProperty(session)) {	
									var hidden = sessions[session];
									if(!hidden) {
										var record = laboratree.chat.sessionStore.getById(session);
										if(record) {
											laboratree.chat.windowMgr.openWindow(session);
										}
									}
								}
							}
						}
	
						laboratree.chat.windowMgr.addButton();
					}
				} else if(laboratree.chat.type == 'popout') {
					laboratree.chat.popoutMgr.makePopout(laboratree.chat.auth.session);
				}
			}
		}
	});
};

/**
 * Reconnect Function
 * Removes old chat code and adds new Connect Script
 */
laboratree.chat.reconnect = function() {
	/*
	if(laboratree.chat.reconnectCount > laboratree.chat.reconnectLimit)
	{
		if(laboratree.chat.windowMgr) {
			laboratree.chat.windowMgr.removeButton();
		}

		return;
	}


	laboratree.chat.reconnectCount += 1;
	*/

	laboratree.info('Reconnect');

	// Remove Connect Script Tag
	$('script', '#connect').remove();

	//laboratree.chat.bosh.connection.disconnect();
	//laboratree.chat.bosh.connection.reset();

	// Add New Connect Script Tag with Restart
	$('#connect').append('<script type="text/javascript" src="' + laboratree.chat.auth.connect + '?restart=1&amp;jsonp=laboratree.chat.reinit&amp;session=' + laboratree.chat.auth.session + '" />');
};

laboratree.chat.WindowMgr = function() {
	this.windows = {};

	this.windowGroup = new Ext.WindowGroup();

	this.linkTpl = new Ext.DomHelper.createTemplate('<li><a style="display: block;" id="chat-entry-{0}" href="#" onclick="laboratree.chat.windowMgr.toggleWindow(\'{0}\'); return false;">Chat: {1}</a></li>');
};

laboratree.chat.WindowMgr.prototype.toggleWindow = function(session, text) {
	if(!this.windows[session]) {
		this.openWindow(session, text);
	} else {
		if(this.windows[session].window.hidden) {
			laboratree.chat.windowMgr.windowGroup.hideAll();
			this.windows[session].window.show();
		} else {
			this.windows[session].window.hide();
		}
	}
};

laboratree.chat.WindowMgr.prototype.openWindow = function(session, text) {
	if(!this.windows[session]) {
		this.windows[session] = new laboratree.chat.Window(session);
	}
	this.windows[session].open(text);
};

laboratree.chat.WindowMgr.prototype.addButton = function() {
	/*
	var button = Ext.getCmp('chat-button');
	if(button) {
		return false;
	}
	*/

	var div = Ext.get(laboratree.chat.div);
	if(!div) {
		return false;
	}

	div.show();
	
	/*
	this.button = new Ext.Button({
		id: 'chat-button',
		renderTo: laboratree.chat.div,

		width: 104,

		text: 'Chat',
		menu: laboratree.chat.listMenu,
		menuAlign: 'bl-br'
	});
	*/
};

laboratree.chat.WindowMgr.prototype.removeButton = function() {
	var button = Ext.getCmp('chat-button');
	if(button && this.button) {
		this.button.destroy();
	}
};

laboratree.chat.Window = function(session) {
	this.session = session;

	this.node = laboratree.chat.sessionStore.getById(session);

	this.window = null;
	this.panel = null;
};

laboratree.chat.Window.prototype.open = function(text) {
	laboratree.chat.windowMgr.windowGroup.hideAll();

	var type = this.node.get('type');

	if(!this.window) {
		var tbar = Ext.get('tab-bar-bottom-entries');
		if(!tbar) {
			return;
		}

		var link = laboratree.chat.windowMgr.linkTpl.append('tab-bar-bottom-entries', [this.session, this.node.get('name'), type], true);
		if(!link) {
			return;
		}

		var first = link.first();
		if(!first) {
			return;
		}

		var position = first.getAnchorXY('bl');
		position[0] += 8;

		this.panel = new laboratree.chat.Panel(this.session, this.node);
		laboratree.chat.panels[this.session] = this.panel;

		var windows = {
			user: {
				id: 'chat_window_' + this.session,
				itemId: 'chat_window_' + this.session,

				session: this.session,

				manager: laboratree.chat.windowMgr.windowGroup,
				title: this.node.get('name'),
				layout: 'fit',
				cls: 'chat-window',
				width: 280,
				height: 300,
				plain: true,
				border: false,
				closable: false,
				draggable: false,
				resizable: false,
				x: position[0],
				y: position[1],

				items: [this.panel.panel],

				tools: [{
					id: 'unpin',
					qtip: 'Popout Chat Window',
					handler: function(event, toolEl, panel) {
						laboratree.chat.windowMgr.windows[panel.session].popout();
					}
				},{
					id: 'close',
					qtip: 'Close Chat Window',
					handler: function(event, toolEl, panel) {
						panel.hide(null, function() {
							laboratree.chat.session.setSession(panel.session, true);
						},this);
					}
				}],
				listeners: {
					hide: function(win) {
						laboratree.chat.bosh.inactive(win.session);
					}
				}
			},
			group: {
				id: 'chat_window_' + this.session,
				itemId: 'chat_window_' + this.session,

				session: this.session,

				manager: laboratree.chat.windowMgr.windowGroup,
				title: this.node.get('name'),
				layout: 'fit',
				cls: 'chat-window',
				width: 400,
				height: 270,
				plain: true,
				border: false,
				closable: false,
				draggable: false,
				resizable: false,
				x: position[0],
				y: position[1],

				items: [this.panel.panel],
	
				tools: [{
					id: 'unpin',
					qtip: 'Popout Chat Window',
					handler: function(e, toolEl, panel) {
						laboratree.chat.windowMgr.windows[panel.session].popout();
					}
				},{
					id: 'close',
					qtip: 'Close Chat Window',
					handler: function(e, toolEl, panel) {
						panel.hide(null, function() {
							laboratree.chat.session.setSession(panel.session, true);
						},this);
					}
				}],
				listeners: {
					hide: function(win) {
						laboratree.chat.bosh.inactive(win.session);
						laboratree.chat.bosh.leaveMuc(win.session);
					}
				}
			},
			project: {
				id: 'chat_window_' + this.session,
				itemId: 'chat_window_' + this.session,

				session: this.session,

				manager: laboratree.chat.windowMgr.windowGroup,
				title: this.node.get('name'),
				layout: 'fit',
				cls: 'chat-window',
				width: 400,
				height: 270,
				plain: true,
				border: false,
				closable: false,
				draggable: false,
				resizable: false,
				x: position[0],
				y: position[1],

				items: [this.panel.panel],
	
				tools: [{
					id: 'unpin',
					qtip: 'Popout Chat Window',
					handler: function(event, toolEl, panel) {
						laboratree.chat.windowMgr.windows[panel.session].popout();
					}
				},{
					id: 'close',
					qtip: 'Close Chat Window',
					handler: function(event, toolEl, panel) {
						panel.hide(null, function() {
							laboratree.chat.session.setSession(panel.session, true);
						},this);
					}
				}],
				listeners: {
					hide: function(win) {
						laboratree.chat.bosh.inactive(win.session);
						laboratree.chat.bosh.leaveMuc(win.session);
					}
				}
			}
		};

		var config = windows.user;
		if(windows[type]) {
			config = windows[type];
		}

		this.window = new Ext.Window(config);

		if(type == 'user') {
			laboratree.chat.bosh.requestArchiveList(this.session, null);
		}
	}

	if(type == 'group' || type == 'project') {
		laboratree.chat.bosh.joinMuc(this.session);
		laboratree.chat.bosh.updateMuc(this.session);
	}

	this.window.show(this);

	this.panel.scrollBottom();
	this.panel.setInputText(text);

	laboratree.chat.session.setSession(this.session, false);
};

laboratree.chat.Window.prototype.clearStatus = function() {
	if(!this.window) {
		return;
	}

	if(this.window.hidden) {
		return;
	}

	if(this.node) {
		if(this.node.get('type') == 'user') {
			this.window.setTitle(this.node.get('name'));
		}
	}
};

laboratree.chat.Window.prototype.isComposing = function() {
	if(!this.window) {
		return;
	}

	if(this.window.hidden) {
		return;
	}

	if(this.node) {
		if(this.node.get('type') == 'user') {
			this.window.setTitle(this.node.get('name') + ' - Typing');
		}
	}
};

laboratree.chat.Window.prototype.isPaused = function() {
	if(!this.window) {
		return;
	}

	if(this.window.hidden) {
		return;
	}

	if(this.node) {
		if(this.node.get('type') == 'user') {
			this.window.setTitle(this.node.get('name') + ' - Paused');
		}
	}
};

laboratree.chat.Window.prototype.popout = function() {
	laboratree.chat.windowMgr.windowGroup.hideAll();

	var type = this.node.get('type');
	if(type == 'group' || type == 'project') {
		laboratree.chat.bosh.leaveMuc(this.session);
	}

	laboratree.chat.popoutMgr.openPopout(this.session);
};

laboratree.chat.PopoutMgr = function() {
	this.popouts = {};
};

laboratree.chat.PopoutMgr.prototype.openPopout = function(session) {

	this.node = laboratree.chat.sessionStore.getById(session);

	var type = this.node.get('type');

	var options = [
		'copyhistory=no',
		'directories=no',
		'location=no',
		'menubar=no',
		'personalbar=no',
		'dialog=yes',
		'resizable=no',
		'scrollbars=no',
		'status=no',
		'toolbar=no'
	];

	/* set height and width based on type */
	var width = 280; // width for user popout
	var height = 305; // height for user popout

	if (type != 'user') { // the popout is for a group or project
		width = 400;
		height = 275;
	}

	// append the width and height to the array of options
	options.push('width=' + width);
	options.push('height=' + height);

	if(!this.popouts[session] || this.popouts[session].closed) {
		this.popouts[session] = window.open(laboratree.chat.auth.popout + '/' + session, 'chat-window-' + session, options.join(','));
		if(!this.popouts[session]) {
			Ext.alert('Popup Blocker', 'You appear to be using pop-up blocking software. Please allow pop-ups from this location in order to use chat popouts.');
			return;
		}
	}

	this.popouts[session].focus();

	var text = '';
	if(laboratree.chat.panels[session]) {
		text = laboratree.chat.panels[session].input.getValue();
	}

	laboratree.chat.bosh.connection.addTimedHandler(3000, function() {
		laboratree.chat.bosh.sendPopoutNotification(session, text);

		return false;
	});
};

laboratree.chat.PopoutMgr.prototype.makePopout = function(session) {
	this.popouts[session] = new laboratree.chat.Popout(session);
};

laboratree.chat.Popout = function(session) {
	this.session = session;

	this.node = laboratree.chat.sessionStore.getById(session);

	this.popout = null;
	this.panel = null;

	var type = this.node.get('type');

	this.panel = new laboratree.chat.Panel(this.session, this.node);
	laboratree.chat.panels[this.session] = this.panel;

	var popouts = {
		user: {
			id: 'chat_popout_' + this.session,
			itemId: 'chat_popout_' + this.session,
			title: this.node.get('name'),
			layout: 'fit',
			cls: 'chat-popout',
			width: 280,
			height: 300,
			plain: true,
			border: false,

			session: this.session,

			items: [this.panel.panel],

			tools: [{
				id: 'pin',
				qtip: 'Popin Chat Window',
				handler: function(event, toolEl, panel) {
					var text = '';
					if(laboratree.chat.popoutMgr.popouts[panel.session]) {
						text = laboratree.chat.popoutMgr.popouts[panel.session].panel.input.getValue();
					}
					laboratree.chat.bosh.sendPopinNotification(panel.session, text, true);
				}
			},{
				id: 'close',
				qtip: 'Close Chat Popout',
				handler: function(event, toolEl, panel) {
					laboratree.chat.bosh.inactive(panel.session);
					window.close();
				}
			}]
		},
		group: {
			id: 'chat_popout_' + this.session,
			itemId: 'chat_popout_' + this.session,
			title: this.node.get('name'),
			layout: 'fit',
			cls: 'chat-popout',
			width: 400,
			height: 270,
			plain: true,
			border: false,

			session: this.session,

			items: [this.panel.panel],

			tools: [{
				id: 'pin',
				qtip: 'Popin Chat Window',
				handler: function(event, toolEl, panel) {
					var text = '';
					if(laboratree.chat.popoutMgr.popouts[panel.session]) {
						text = laboratree.chat.popoutMgr.popouts[panel.session].panel.input.getValue();
					}
					laboratree.chat.bosh.sendPopinNotification(panel.session, text, true);
				}
			},{
				id: 'close',
				qtip: 'Close Chat Popout',
				handler: function(event, toolEl, panel) {
					laboratree.chat.bosh.inactive(panel.session);
					laboratree.chat.bosh.leaveMuc(panel.session);
					window.close();
				}
			}]
		},
		project: {
			id: 'chat_popout_' + this.session,
			itemId: 'chat_popout_' + this.session,
			title: this.node.get('name'),
			layout: 'fit',
			cls: 'chat-popout',
			width: 400,
			height: 270,
			plain: true,
			border: false,

			session: this.session,

			items: [this.panel.panel],

			tools: [{
				id: 'pin',
				qtip: 'Popin Chat Window',
				handler: function(event, toolEl, panel) {
					var text = '';
					if(laboratree.chat.popoutMgr.popouts[panel.session]) {
						text = laboratree.chat.popoutMgr.popouts[panel.session].panel.input.getValue();
					}
					laboratree.chat.bosh.sendPopinNotification(panel.session, text, true);
				}
			},{
				id: 'close',
				qtip: 'Close Chat Popout',
				handler: function(event, toolEl, panel) {
					laboratree.chat.bosh.inactive(panel.session);
					laboratree.chat.bosh.leaveMuc(panel.session);
					window.close();
				}
			}]
		}
	};

	var config = popouts.user;
	if(popouts[type]) {
		config = popouts[type];
	}

	this.popout = new Ext.Panel(config);
	this.popout.render(document.body);

	if(type == 'user') {
		laboratree.chat.bosh.requestArchiveList(this.session, null);
	} else if(type == 'group' || type == 'project') {
		laboratree.chat.bosh.joinMuc(this.session);
		laboratree.chat.bosh.updateMuc(this.session);
	}

	this.panel.scrollBottom();

	window.title = this.node.get('name');

	laboratree.chat.session.setSession(this.session, false);
};

laboratree.chat.Popout.prototype.clearStatus = function() {
	if(this.node) {
		if(this.node.get('type') == 'user') {
			document.title = this.node.get('name');
		}
	}
};

laboratree.chat.Popout.prototype.isComposing = function() {
	if(this.node) {
		if(this.node.get('type') == 'user') {
			document.title = this.node.get('name') + ' - Typing';
		}
	}
};

laboratree.chat.Popout.prototype.isPaused = function() {
	if(this.node) {
		if(this.node.get('type') == 'user') {
			document.title = this.node.get('name') + ' - Paused';
		}
	}
};

laboratree.chat.Panel = function(session, node) {
	this.session = session;
	this.node = node;

	this.composing = null;

	this.conversation = new Ext.data.ArrayStore({
		storeId: 'conversation_' + session,
		fields: ['session', 'name', 'message', 'timestamp']
	});

	this.archive = new Ext.data.ArrayStore({
		storeId: 'archive_' + session,
		idProperty: 'id',
		fields: ['id', 'timestamp']
	});

	this.mucUsers = new Ext.data.ArrayStore({
		storeId: 'muc_' + session,
		idProperty: 'session',
		fields: ['session', 'name', 'jid', 'role']
	});

	this.input = new Ext.form.TextArea({
		id: 'chat_input_' + this.session,
		anchor: '100%',

		session: session,

		flex: 1,

		fieldClass: 'chatinput',
		plain: true,
		shadow: false,
		value: laboratree.chat.popoutText,
		enableKeyEvents: true,
		
		listeners: {
			keyup: function(field, e) {
				var key = e.getKey();	
				if(key == 13) {
					var message = Ext.util.Format.trim(field.getValue());
					if(message.length > 0)
					{
						laboratree.chat.panels[field.session].send(message);
					}
					field.setValue('');
				} else {
					if(laboratree.chat.panels[field.session]) {
						if(!laboratree.chat.panels[field.session].composing) {
							laboratree.chat.panels[field.session].compose();
						}
					}
				}
			}
		}
	});

	this.conversationGrid = null;
	this.usersGrid = null;
	this.panel = null;

	var type = this.node.get('type');
	if(type == 'user') {
		this.conversationGrid =  new Ext.grid.GridPanel({
			id: 'chat_conversation_' + this.session,
			store: this.conversation,
			border: false,
			shadow: false,
			cls: 'chat-conversation',
			anchor: '100% -40',
		
			viewConfig: {
				forceFit: true,
				enableRowBody: true,
				getRowClass: function(record, rowIndex, p, store) {
					p.body = record.data.message;
					return 'x-grid3-row-with-body';
				}
			},
		
			columns: [{
				id: 'name',
				header: 'Name',
				dataIndex: 'name',
				renderer: laboratree.chat.render.name
			},{
				id: 'time',
				header: 'Time',
				width: 60,
				dataIndex: 'timestamp',
				renderer: laboratree.chat.render.time
			}]
		});
	
		this.panel = new Ext.Panel({
			id: 'chat_panel_' + this.session,
			layout: 'anchor',
			plain: true,

			session: this.session,
	
			items: [this.conversationGrid,{
				layout: 'hbox',
				anchor: '100%',
	
				height: 40,
	
				layoutConfig: {
					align: 'stretch',
					pack: 'start'
				},
	
				items: [this.input,{
					xtype: 'button',
					text: 'Send',

					session: this.session,
	
					width: 40,
	
					handler: function(button, e) {
						var field = laboratree.chat.panels[button.session].input;
						if(!field) {
							return;
						}

						var message = Ext.util.Format.trim(field.getValue());
						if(message.length > 0)
						{
							laboratree.chat.panels[button.session].send(message);
						}
						field.setValue('');
					}
				}]
			}]
		});
	} else if(type == 'group') {
		this.conversationGrid = new Ext.grid.GridPanel({
			id: 'chat_conversation_' + this.session,
			store: this.conversation,
		
			border: false,
			cls: 'chat-conversation',
	
			flex: 2,
	
			viewConfig: {
				forceFit: true,
				enableRowBody: true,
				getRowClass: function(record, rowIndex, p, store) {
					p.body = record.data.message;
					return 'x-grid3-row-with-body';
				}
			},
			
			columns: [{
				id: 'name',
				header: 'Name',
				dataIndex: 'name',
				renderer: laboratree.chat.render.name
			},{
				id: 'time',
				header: 'Time',
				width: 60,
				dataIndex: 'timestamp',
				renderer: laboratree.chat.render.time
			}]
		});
	
		this.usersGrid = new Ext.grid.GridPanel({
			id: 'chat_users_' + this.session,
			store: this.mucUsers,
		
			border: false,
			cls: 'chat-muc-users',
	
			flex: 1,
	
			viewConfig: {
				forceFit: true
			},
	
			columns: [{
				id: 'name',
				header: 'Name',
				dataIndex: 'name',
				renderer: laboratree.chat.render.name
			}]
		});
	
		this.panel = new Ext.Panel({
			id: 'chat_panel_' + this.session,
			layout: 'anchor',
			plain: true,

			session: this.session,
	
			items: [{
				id: 'chat-panel-hbox-' + this.session,
				anchor: '100% -40',
	
				layout: 'hbox',
				layoutConfig: {
					align: 'stretch',
					pack: 'start'
				},
	
				items: [this.conversationGrid,this.usersGrid]
			},{
				layout: 'hbox',

				height: 40,

				layoutConfig: {
					align: 'stretch',
					pack: 'start'
				},

				items: [this.input,{
					xtype: 'button',
					text: 'Send',

					session: this.session,
	
					width: 40,

					handler: function(button, e) {
						var field = laboratree.chat.panels[button.session].input;
						if(!field) {
							return;
						}
	
						var message = Ext.util.Format.trim(field.getValue());
						if(message.length > 0)
						{
							laboratree.chat.panels[button.session].send(field.getValue());
						}
						field.setValue('');
					}
				}]
			}]
		});
	} else if(type == 'project') {
		this.conversationGrid = new Ext.grid.GridPanel({
			id: 'chat_conversation_' + this.session,
			store: this.conversation,
		
			border: false,
			cls: 'chat-conversation',
	
			flex: 2,
	
			viewConfig: {
				forceFit: true,
				enableRowBody: true,
				getRowClass: function(record, rowIndex, p, store) {
					p.body = record.data.message;
					return 'x-grid3-row-with-body';
				}
			},
			
			columns: [{
				id: 'name',
				header: 'Name',
				dataIndex: 'name',
				renderer: laboratree.chat.render.name
			},{
				id: 'time',
				header: 'Time',
				width: 60,
				dataIndex: 'timestamp',
				renderer: laboratree.chat.render.time
			}]
		});
	
		this.usersGrid = new Ext.grid.GridPanel({
			id: 'chat_users_' + this.session,
			store: this.mucUsers,
		
			border: false,
			cls: 'chat-muc-users',
	
			flex: 1,
	
			viewConfig: {
				forceFit: true
			},
	
			columns: [{
				id: 'name',
				header: 'Name',
				dataIndex: 'name',
				renderer: laboratree.chat.render.name
			}]
		});
	
		this.panel = new Ext.Panel({
			id: 'chat_panel_' + this.session,
			layout: 'anchor',
			plain: true,

			session: this.session,
	
			items: [{
				id: 'chat-panel-hbox-' + this.session,
				anchor: '100% -40',
	
				layout: 'hbox',
				layoutConfig: {
					align: 'stretch',
					pack: 'start'
				},
	
				items: [this.conversationGrid,this.usersGrid]
			},{
				layout: 'hbox',

				height: 40,

				layoutConfig: {
					align: 'stretch',
					pack: 'start'
				},

				items: [this.input,{
					xtype: 'button',
					text: 'Send',

					session: this.session,
	
					width: 40,

					handler: function(button, e) {
						var field = laboratree.chat.panels[button.session].input;
						if(!field) {
							return;
						}
	
						var message = Ext.util.Format.trim(field.getValue());
						if(message.length > 0)
						{
							laboratree.chat.panels[button.session].send(field.getValue());
						}
						field.setValue('');
					}
				}]
			}]
		});
	}
};

laboratree.chat.Panel.prototype.send = function(message) {
	var type = 'chat';

	if(this.node) {
		type = (this.node.get('type') == 'user') ? 'chat' : 'groupchat';
	}

	var message_id = laboratree.chat.bosh.connection.getUniqueId('msg:' + this.session + ':' + laboratree.chat.auth.username);
	this.archiveMessage(message_id);

	var body = $msg({
		id: message_id,
		to: laboratree.chat.bosh.createJid(this.session),
		from: laboratree.chat.auth.jid,
		type: type
	}).c('body', null).t(message);

	var msg = body.up();
	msg.c('active', {
		xmlns: Strophe.NS.CHATSTATES
	});

	if(this.composing) {
		clearTimeout(this.composing);
		this.composing = null;
	}

	laboratree.chat.bosh.connection.send(body.tree());

	msg = body.up();
	this.addMessage(laboratree.chat.auth.name, msg);
};

laboratree.chat.Panel.prototype.addMessage = function(from, body, timestamp) {
	var newMsg = true;
	var total = this.conversation.getCount();

	if(total > 0)
	{
		var last = this.conversation.getAt(total - 1);
		if(last && last.data.name == from) {
			last.data.message += '<br />' + body;

			if(this.conversationGrid) {
				var view = this.conversationGrid.getView();
				if(view) {
					view.refresh();
				}
			}

			newMsg = false;
		}
	}

	if(newMsg) {
		var stamp = new Date();
		if(timestamp) {
			stamp = Date.parseDate(timestamp, 'c');
		}

		var msg = {
			session: this.session,
			name: from,
			message: body,
			timestamp: stamp.format('g:i a')
		};

		var msgRecord = new laboratree.chat.messageRecord(msg);
		this.conversation.add(msgRecord);
	}

	this.scrollBottom();
};

laboratree.chat.Panel.prototype.addConversation = function(conversation) {
	var idx;
	for(idx in conversation) {
		if(conversation.hasOwnProperty(idx)) {
			var msgRecord = new laboratree.chat.messageRecord(conversation[idx]);
			if(this.conversation) {
				this.conversation.add(msgRecord);
			}
		}
	}

	this.scrollBottom();
};

laboratree.chat.Panel.prototype.addMucUser = function(jid, role) {
	var user = laboratree.chat.bosh.parseJid(jid);
	if(!user) {
		return false;
	}

	var name = user.resource;

	var userSession = 'user:' + name;

	var record = this.mucUsers.getById(userSession);
	if(record) {
		return false;
	}

	var mucUser = new laboratree.chat.mucUser({
		session: userSession,
		name: name,
		jid: jid,
		role: role
	}, userSession);

	this.mucUsers.add(mucUser);

	if(this.usersGrid) {
		var view = this.usersGrid.getView();
		if(view) {
			view.refresh();
		}
	}

	return true;
};

laboratree.chat.Panel.prototype.removeMucUser = function(jid) {
	var user = laboratree.chat.bosh.parseJid(jid);
	if(!user) {
		return false;
	}

	var userSession = 'user:' + user.node;

	var record = this.mucUsers.getById(userSession);
	if(record) {
		this.mucUsers.remove(record);

		if(this.usersGrid) {
			var view = this.usersGrid.getView();
			if(view) {
				view.refresh();
			}
		}
	}

	return true;
};

laboratree.chat.Panel.prototype.archiveMessage = function(message_id) {
	var local = new Date();
	var utc = local.toUTC();
	var timestamp = utc.format('Y-m-d\\TH:i:s\\Z');

	var archive = {
		id: message_id,
		timestamp: timestamp
	};

	var archiveRecord = new laboratree.chat.archiveRecord(archive, message_id);
	if(this.archive) {
		this.archive.add(archiveRecord);
	}
};

laboratree.chat.Panel.prototype.scrollBottom = function() {
	if(this.conversationGrid) {
		var view = this.conversationGrid.getView();
		if(view) {
			var count = this.conversation.getCount();
			if(count > 0) {
				view.focusRow(count - 1);
			}
		}
	}

	if(this.input) {
		this.input.focus();
	}
};

laboratree.chat.Panel.prototype.setInputText = function(text) {
	if(text) {
		this.input.setValue(text);
	}
};

laboratree.chat.Panel.prototype.compose = function() {
	if(this.node) {
		if(this.node.get('type') == 'user') {
			laboratree.chat.bosh.composing(this.session);
			this.composing = setTimeout("laboratree.chat.panels['" + this.session + "'].pause();", laboratree.chat.chatStateTimeout);
		}
	}
};

laboratree.chat.Panel.prototype.pause = function() {
	if(this.node) {
		if(this.node.get('type') == 'user') {
			laboratree.chat.bosh.paused(this.session);
			this.composing = null;
		}
	}
};

/**
 * Render Functions
 */
laboratree.chat.render = {};

/**
 * Render Chat Name in Conversation
 */
laboratree.chat.render.name = function(value, p, record) {
	return String.format('<div style="font-weight: bold;">{0}</div>', value);
};

/**
 * Render Time in Conversation
 */
laboratree.chat.render.time = function(value, p, record) {
	return String.format('<div style="text-align: right;">{0}</div>', value);
};

/**
 * Session Class
 * Convenience class to handle setting and saving chat sessions
 */
laboratree.chat.Session = function() {
	Ext.state.Manager.setProvider(new Ext.state.CookieProvider({
		expires: new Date(new Date().getTime() + (1000 * 60 *30)) // 30 minutes
	}));
};

laboratree.chat.Session.prototype.getSession = function(session) {
	var sessions = Ext.state.Manager.get('chat-sessions', null);
	if(!sessions) {
		return true;
	}

	if(sessions.hasOwnProperty(session)) {
		return sessions[session];
	}

	return true;
};

laboratree.chat.Session.prototype.setSession = function(session, hidden) {
	var sessions = Ext.state.Manager.get('chat-sessions', null);
	if(!sessions) {
		sessions = {};
	}
		
	sessions[session] = hidden;

	Ext.state.Manager.set('chat-sessions', sessions);
};

laboratree.chat.Bosh = function(bindurl) {
	//Strophe.log = laboratree.log;

	Strophe.addNamespace('AVATAR_DATA', 'urn:xmpp:avatar:data');
	Strophe.addNamespace('AVATAR_METADATA', 'urn:xmpp:avatar:metadata');

	this.limit = 30;
	this.connection = new Strophe.Connection(bindurl);
};

laboratree.chat.Bosh.prototype.createJid = function(session, resource) {
	var jid = session;

	var node = laboratree.chat.sessionStore.getById(session);
	if(node) {
		var type = node.get('type');

		if(type == 'user') {
			jid = node.get('username') + '@' + laboratree.chat.auth.domain;
		} else if(type == 'group' || type == 'project') {
			jid = type + '_' + node.get('id') + '@' + 'chat.' + laboratree.chat.auth.domain;
		}
	}

	if(resource) {
		jid += '/' + resource;
	}

	return jid;
};

laboratree.chat.Bosh.prototype.parseJid = function(jid) {
	if(!jid) {
		return null;
	}

	var node = Strophe.getNodeFromJid(jid);
	var domain = Strophe.getDomainFromJid(jid);
	var resource = Strophe.getResourceFromJid(jid);
	var bare = Strophe.getBareJidFromJid(jid);

	return {
		node: node,
		domain: domain,
		resource: resource,
		jid: jid,
		bare: bare
	};
};

/**
 * Presence and Entity Capabilties
 */
laboratree.chat.Bosh.prototype.sendPresence = function(priority) {
	this.connection.caps.presence(laboratree.chat.auth.jid, 'http://laboratree.selican.com', priority, laboratree.chat.identity, laboratree.chat.features, laboratree.chat.bosh.onDiscoInfo);
};

laboratree.chat.Bosh.prototype.onDiscoInfo = function(iq) {
	laboratree.info('DISCO INFO');
	laboratree.info(iq);

	var id = iq.getAttribute('id');
	var from = iq.getAttribute('from');

	laboratree.chat.bosh.connection.caps.verify(id, from, laboratree.chat.auth.jid, laboratree.chat.identity, laboratree.chat.features);

	return true;
};

/**
 * Attach to BOSH Connection
 */
laboratree.chat.Bosh.prototype.attach = function() {
	this.connection.attach(laboratree.chat.auth.jid, laboratree.chat.auth.sid, laboratree.chat.auth.rid, this.onAttach);
};

laboratree.chat.Bosh.prototype.onAttach = function(stat) {
	// 'this' is the Strophe Connection
	if(stat == Strophe.Status.ATTACHED) {
		laboratree.chat.stat = Strophe.Status.ATTACHED;
		laboratree.chat.session = new laboratree.chat.Session();

		this.addHandler(laboratree.chat.bosh.onPresence, null, 'presence', null, null, null);
		this.addHandler(laboratree.chat.bosh.onIq, null, 'iq', null, null, null);
		this.addHandler(laboratree.chat.bosh.onMessage, null, 'message', null, null, null);

		/* Chat Message/Chat State Handler */
		this.addHandler(function(stanza) {
			var type = stanza.getAttribute('type');
			if(type && type == 'chat') {
				var bodies = stanza.getElementsByTagName('body');
				if(bodies.length > 0) {
					return laboratree.chat.bosh.onChatMessage(stanza);
				} else {
					return laboratree.chat.bosh.onChatState(stanza);
				}
			}

			return true;
		}, null, 'message', null, null, null);

		/* PEP Message Handler */
		this.addHandler(function(stanza) {
			var events = stanza.getElementsByTagName('event');
			var i;
			for(i = 0; i < events.length; i++) {
				var xmlns = events[i].getAttribute('xmlns');
				if(xmlns && xmlns.match(Strophe.NS.PUBSUB_EVENT)) {
					return laboratree.chat.bosh.onPEPMessage(stanza);
				}
			}

			return true;
		}, null, 'message', null, null, null);

		laboratree.chat.bosh.sendPresence(laboratree.chat.priority);
		laboratree.chat.bosh.sendAvatar();
	} else if(stat == Strophe.Status.DISCONNECTING) {
		laboratree.chat.stat = Strophe.Status.DISCONNECTED;
		laboratree.chat.reconnect();
	}
};

/**
 * Generic Stanza Handlers
 */
laboratree.chat.Bosh.prototype.onMessage = function(message) {
	laboratree.info('MESSAGE');
	laboratree.info(message);
	// Nothing
	return true;

	var id = message.getAttribute('id');
	var type = message.getAttribute('type');

	return true;
};

laboratree.chat.Bosh.prototype.onIq = function(iq) {
	laboratree.info('IQ');
	laboratree.info(iq);
	// Nothing
	return true;

	var id = iq.getAttribute('id');
	var type = iq.getAttribute('type');
	var from = laboratree.chat.bosh.parseJid(iq.getAttribute('from'));

	return true;
};

laboratree.chat.Bosh.prototype.onPresence = function(presence) {
	laboratree.info('PRESENCE');
	laboratree.info(presence);
	// Nothing
	return true;

	var from = laboratree.chat.bosh.parseJid(presence.getAttribute('from'));
	if(!from) {
		return true;
	}

	var type = presence.getAttribute('type');

	var x = presence.getElementsByTagName('x');
	var i;
	for(i = 0; i < x.length; i++) {
		var xmlns = x[i].getAttribute('xmlns');
	}

	return true;
};

/**
 * Chat Message
 */
laboratree.chat.Bosh.prototype.onChatMessage = function(message) {
	laboratree.info('CHAT MESSAGE');
	laboratree.info(message);

	var id = message.getAttribute('id');

	var from = laboratree.chat.bosh.parseJid(message.getAttribute('from'));
	if(!from) {
		return true;
	}

	var session = 'user:' + from.node;

	laboratree.chat.windowMgr.openWindow(session);

	/* Message SHOULD be in chat history */
	if(!laboratree.chat.windowMgr.windows[session]) {
		return true;
	}

	var name = from.node;
	var node = laboratree.chat.sessionStore.getById(session);
	if(node) {
		name = node.get('name');
	}

	var bodies = message.getElementsByTagName('body');
	var i;
	for(i = 0; i < bodies.length; i++) {
		var body = Strophe.getText(bodies[i]);
		if(body) {
			if(laboratree.chat.panels[session]) {
				/* Check to see if we have received this message already */
				if(laboratree.chat.panels[session].archive) {
					var archive = laboratree.chat.panels[session].archive.getById(id);
					if(archive) {
						return true;
					}
				}

				laboratree.chat.panels[session].archiveMessage(id);
				laboratree.chat.panels[session].addMessage(name, body);
			}
		}
	}

	if(laboratree.chat.type == 'window') {
		if(laboratree.chat.windowMgr.windows[session]) {
			laboratree.chat.windowMgr.windows[session].clearStatus();
		}
	} else if(laboratree.chat.type == 'popout') {
		if(laboratree.chat.popoutMgr.popouts[session]) {
			laboratree.chat.popoutMgr.popouts[session].clearStatus();
		}
	}

	return true;
};


/**
 * User Avatar
 * User Avatar Send and Handler
 */
laboratree.chat.Bosh.prototype.sendAvatar = function() {
	var id = laboratree.chat.auth.avatar.sha1;

	var item = Strophe.xmlElement('data', [
		['xmlns', Strophe.NS.AVATAR_DATA]
	], laboratree.chat.auth.avatar.data);

	laboratree.chat.bosh.connection.pep.publish(laboratree.chat.auth.jid, 'urn:xmpp:avatar:data', item, id, null, laboratree.chat.bosh.onAvatarUpdate);
};

laboratree.chat.Bosh.prototype.onAvatarUpdate = function(iq) {
	var id = laboratree.chat.auth.avatar.sha1;

	var item = Strophe.xmlElement('metadata', [
		['xmlns', Strophe.NS.AVATAR_METADATA]
	]);

	var info = Strophe.xmlElement('info', [
		['id', id],
		['bytes', laboratree.chat.auth.avatar.size],
		['height', laboratree.chat.auth.avatar.height],
		['width', laboratree.chat.auth.avatar.width],
		['type', laboratree.chat.auth.avatar.type]
	]);

	item.appendChild(info);

	laboratree.chat.bosh.connection.pep.publish(laboratree.chat.auth.jid, 'urn:xmpp:avatar:metadata', item, id);

	return true;
};

/**
 * Message Archive
 * Message Archive Request and Handlers
 */
laboratree.chat.Bosh.prototype.requestArchiveList = function(session, after) {
	var local = new Date();
	var utc = local.toUTC();
	var startDate = utc.add(Date.HOUR, -3);
	var start = startDate.format('Y-m-d\\TH:i:s\\Z');

	var user = laboratree.chat.bosh.createJid(session);
	this.connection.archive.list(user, laboratree.chat.bosh.limit, start, after, laboratree.chat.bosh.onArchiveList);
};

laboratree.chat.Bosh.prototype.requestArchive = function(session, start, to, after) {
	this.connection.archive.retrieve(to, laboratree.chat.bosh.limit, start, after, laboratree.chat.bosh.onArchiveRequest);
};

laboratree.chat.Bosh.prototype.onArchiveList = function(iq) {
	laboratree.info('ARCHIVE LIST');
	laboratree.info(iq);

	var first = null;
	var after = null;
	var index = 0;
	var count = 0;
	var last = 0;

	var sets = iq.getElementsByTagName('set');
	var i;
	for(i = 0; i < sets.length; i++) {
		var firsts = sets[i].getElementsByTagName('first');
		var j;
		for(j = 0; j < firsts.length; j++) {
			first = Strophe.getText(firsts[j]);

			var indexAttr = parseInt(firsts[j].getAttribute('index'), 10);
			if(indexAttr) {
				index = indexAttr;
			}
		}

		var lasts = sets[i].getElementsByTagName('last');
		for(j = 0; j < lasts.length; j++) {
			last = Strophe.getText(lasts[j]);
		}

		var counts = sets[i].getElementsByTagName('count');
		for(j = 0; j < counts.length; j++) {
			count = parseInt(Strophe.getText(counts[j]), 10);
		}

		if((index + laboratree.chat.bosh.limit) < count) {
			after = last;
		}
	}

	var chats = iq.getElementsByTagName('chat');
	for(i = 0; i < chats.length; i++) {
		var user = chats[i].getAttribute('with');
		if(!user) {
			continue;
		}

		var start = chats[i].getAttribute('start');
		if(!start) {
			continue;
		}

		var jid = laboratree.chat.bosh.parseJid(user);
		if(!jid) {
			continue;
		}

		var session = 'user:' + jid.node;

		laboratree.chat.bosh.requestArchive(session, start, user, null);

		if(after) {
			laboratree.chat.bosh.requestArchiveList(session, after);
		}
	}

	return true;
};

laboratree.chat.Bosh.prototype.onArchiveRequest = function(iq) {
	laboratree.info('ARCHIVE REQUEST');
	laboratree.info(iq);

	var first = null;
	var after = null;
	var index = 0;
	var count = 0;
	var last = 0;

	var sets = iq.getElementsByTagName('set');
	var i;
	var j;
	for(i = 0; i < sets.length; i++) {
		var firsts = sets[i].getElementsByTagName('first');
		for(j = 0; j < firsts.length; j++) {
			first = Strophe.getText(firsts[j]);

			var indexAttr = parseInt(firsts[j].getAttribute('index'), 10);
			if(indexAttr) {
				index = indexAttr;
			}
		}

		var lasts = sets[i].getElementsByTagName('last');
		for(j = 0; j < lasts.length; j++) {
			last = Strophe.getText(lasts[j]);
		}

		var counts = sets[i].getElementsByTagName('count');
		for(j = 0; j < counts.length; j++) {
			count = parseInt(Strophe.getText(counts[j]), 10);
		}

		if((index + laboratree.chat.bosh.limit) < count) {
			after = last;
		}
	}

	var chats = iq.getElementsByTagName('chat');
	for(i = 0; i < chats.length; i++) {
		var user = chats[i].getAttribute('with');
		if(!user) {
			continue;
		}

		var start = chats[i].getAttribute('start');
		if(!start) {
			continue;
		}

		start = Date.parseDate(start, 'c');
		if(!start) {
			continue;
		}

		var jid = laboratree.chat.bosh.parseJid(user);
		if(!jid) {
			continue;
		}

		var session = 'user:' + jid.node;

		if(chats[i].hasChildNodes && chats[i].childNodes.length > 0) {
			var conversation = [];

			for(j = 0; j < chats[i].childNodes.length; j++) {
				var item = chats[i].childNodes[j];

				var secs = item.getAttribute('secs');
				if(!secs) {
					continue;
				}

				var stamp = start.add(Date.SECOND, secs);
				var timestamp = stamp.format('g:i a');

				var bodies = item.getElementsByTagName('body');
				if(bodies.length < 1) {
					continue;
				}

				var message = Strophe.getText(bodies[0]);

				var name = null;
				if(item.tagName == 'from') {
					var node = laboratree.chat.sessionStore.getById(session);
					if(node) {
						name = node.get('name');
					}
				} else if(item.tagName == 'to') {
					name = laboratree.chat.auth.name;
				}

				if(!name) {
					continue;
				}

				var msg = {
					session: session,
					name: name,
					message: message,
					timestamp: timestamp
				};

				conversation.push(msg);
			}

			laboratree.chat.panels[session].addConversation(conversation);

			if(after) {
				laboratree.chat.bosh.requestArchive(session, start, user, after);
			}
		}
	}

	return true;
};

/**
 * Popin/Popout
 * Popin/Poput Notificatin and Handling Functions using PEP.
 */
laboratree.chat.Bosh.prototype.sendPopinNotification = function(session, text) {
	var local = new Date();
	var utc = local.toUTC();
	var timestamp = utc.format('Y-m-d\\TH:i:s\\Z');

	var item = Strophe.xmlElement('event', [
		['type', 'popin'],
		['session', session],
		['timestamp', timestamp]
	], text);

	laboratree.chat.bosh.connection.pep.publish(laboratree.chat.auth.jid, 'urn:xmpp:chat:event', item, 'current', null, laboratree.chat.bosh.onPopinNotification);
};

laboratree.chat.Bosh.prototype.sendPopoutNotification = function(session, text) {
	if(!session) {
		return;
	}

	var local = new Date();
	var utc = local.toUTC();
	var timestamp = utc.format('Y-m-d\\TH:i:s\\Z');

	var item = Strophe.xmlElement('event', [
		['type', 'popout'],
		['session', session],
		['timestamp', timestamp]
	], text);

	laboratree.chat.bosh.connection.pep.publish(laboratree.chat.auth.jid, 'urn:xmpp:chat:event', item, 'current', null, laboratree.chat.bosh.onPopoutNotification);
};

laboratree.chat.Bosh.prototype.onPopoutNotification = function(iq) {
	//This should only be bound and exist within the Main window
	return true;
};

laboratree.chat.Bosh.prototype.onPopinNotification = function(iq) {
	//This should only be bound and exist within the Popout window
	window.close();
};

laboratree.chat.Bosh.prototype.onPEPMessage = function(message) {
	var from = message.getAttribute('from');
	if(!from) {
		return true;
	}

	var parsed = laboratree.chat.bosh.parseJid(laboratree.chat.auth.jid);
	if(!parsed) {
		return true;
	}

	if(from != parsed.bare) {
		return true;
	}

	var events = message.getElementsByTagName('event');
	var i;
	for(i = 0; i < events.length; i++) {
		var items = events[i].getElementsByTagName('items');
		var j;
		for(j = 0; j < items.length; j++) {
			var node = items[j].getAttribute('node');
			var item = items[j].getElementsByTagName('item');

			switch(node) {
				case 'urn:xmpp:chat:event':
					var k;
					for(k = 0; k < item.length; k++) {
						var e = item[k].getElementsByTagName('event');
						var l;
						for(l = 0; l < e.length; l++) {
							var type = e[l].getAttribute('type');
							var session;
							var now;
							var stamp;
							var timestamp;
							var text;

							switch(type) {
								case 'popout':
									if(laboratree.chat.type == 'window') {
										return true;
									}

									session = e[l].getAttribute('session');

									now = new Date();

									stamp = e[l].getAttribute('timestamp');
									if(!stamp) {
										return true;
									}

									timestamp = Date.parseDate(stamp, 'c');

									if(now.getElapsed(timestamp) > laboratree.chat.popoutTimeout) {
										return true;
									}

									text = Strophe.getText(e[l]);

									laboratree.chat.popoutText = text;
									if(laboratree.chat.panels[session]) {
										laboratree.chat.panels[session].input.setValue(text);
									}
									break;
								case 'popin':
									if(laboratree.chat.type == 'popout') {
										return true;
									}

									session = e[l].getAttribute('session');

									now = new Date();

									stamp = e[l].getAttribute('timestamp');
									if(!stamp) {
										return true;
									}

									
									timestamp = Date.parseDate(stamp, 'c');

									if(now.getElapsed(timestamp) > laboratree.chat.popinTimeout) {
										return true;
									}

									text = Strophe.getText(e[l]);

									laboratree.chat.bosh.requestArchiveList(session, null);
									laboratree.chat.windowMgr.openWindow(session, text);
									break;
							}
						}
					}
					break;
			}
		}
	}

	return true;
};

/**
 * Multi-User Chat (MUC)
 * MUC Functions and Handlers
 */
laboratree.chat.Bosh.prototype.joinMuc = function(session) {
	var local = new Date();
	var utc = local.toUTC();
	var startDate = utc.add(Date.HOUR, -3);
	var since = startDate.format('Y-m-d\\TH:i:s\\Z');

	var room = laboratree.chat.bosh.createJid(session, laboratree.chat.auth.name);
	this.connection.muc.join(room, laboratree.chat.auth.jid, since, laboratree.chat.bosh.onMucMessage, laboratree.chat.bosh.onMucPresence);
};

laboratree.chat.Bosh.prototype.updateMuc = function(session) {
	var room = laboratree.chat.bosh.createJid(session);
	this.connection.muc.update(room, laboratree.chat.auth.jid, laboratree.chat.bosh.onMucUpdate);
};

laboratree.chat.Bosh.prototype.leaveMuc = function(session) {
	var room = laboratree.chat.bosh.createJid(session ,laboratree.chat.auth.name);
	this.connection.muc.leave(room, laboratree.chat.auth.jid);
};

laboratree.chat.Bosh.prototype.onMucMessage = function(message) {
	laboratree.info('MUC MESSAGE');
	laboratree.info(message);

	var id = message.getAttribute('id');

	var from = laboratree.chat.bosh.parseJid(message.getAttribute('from'));
	if(!from) {
		return true;
	}

	var session = 'group:' + from.node;
	var userSession = 'user:' + from.resource;

	var name = from.resource;
	var node = laboratree.chat.sessionStore.getById(userSession);
	if(node) {
		name = node.get('name');
	}

	var timestamp = null;

	var delays = message.getElementsByTagName('delay');
	var i;
	for(i = 0; i < delays.length; i++) {
		timestamp = delays[i].getAttribute('stamp');
	}

	var bodies = message.getElementsByTagName('body');
	for(i = 0; i < bodies.length; i++) {
		var body = Strophe.getText(bodies[i]);
		if(body) {
			/*
			var x = message.getElementsByTagName('x');
			for(var j = 0; j < x.length; j++) {
				var xmlns = x[j].getAttribute('xmlns');

				if(xmlns && (xmlns.match(Strophe.NS.JABBER_DELAY) || xmlns.match(Strophe.NS.XMPP_DELAY))) {
					timestamp = x[j].getAttribute('stamp');
				}
			}
			*/

			if(laboratree.chat.panels[session]) {
				/* Check to see if we have received this message already */
				var archive = laboratree.chat.panels[session].archive.getById(id);
				if(archive) {
					return true;
				}

				laboratree.chat.panels[session].archiveMessage(id);
				laboratree.chat.panels[session].addMessage(name, body, timestamp);
			}
		}
	}

	return true;
};

laboratree.chat.Bosh.prototype.onMucUpdate = function(iq) {
	laboratree.info(iq);

	var from = laboratree.chat.bosh.parseJid(iq.getAttribute('from'));
	if(!from) {
		return true;
	}

	var session = 'group:' + from.node;

	var queries = iq.getElementsByTagName('query');
	var i;
	for(i = 0; i < queries.length; i++) {
		var xmlns = queries[i].getAttribute('xmlns');

		if(xmlns && xmlns.match(Strophe.NS.DISCO_ITEMS)) {
			var items = queries[i].getElementsByTagName('item');
			var j;
			for(j = 0; j < items.length; j++) {
				var jid = items[j].getAttribute('jid');
				var name = items[j].getAttribute('name');

				laboratree.chat.panels[session].addMucUser(jid, null);
			}
		}
	}

	return true;
};

laboratree.chat.Bosh.prototype.onMucPresence = function(presence) {
	laboratree.info('onMucPresence');
	laboratree.info(presence);

	var jid = presence.getAttribute('from');
	if(!jid) {
		return true;
	}

	var from = laboratree.chat.bosh.parseJid(jid);
	if(!from) {
		return true;
	}

	var session = 'group:' + from.node;

	var type = presence.getAttribute('type');

	var x = presence.getElementsByTagName('x');
	var i;
	for(i = 0; i < x.length; i++) {
		var xmlns = x[i].getAttribute('xmlns');

		if(xmlns && xmlns.match(Strophe.NS.MUC_USER)) {
			var items = x[i].getElementsByTagName('item');
			var j;
			for(j = 0; j < items.length; j++) {
				//var jid = items[j].getAttribute('jid');
				var affiliation = items[j].getAttribute('affiliation');
				var role = items[j].getAttribute('role');

				if(type && type == 'unavailable') {
					laboratree.chat.panels[session].removeMucUser(jid);
				} else {
					laboratree.chat.panels[session].addMucUser(jid, role);
				}
			}
		}
	}

	return true;
};

/**
 * Chat State
 */
laboratree.chat.Bosh.prototype.active = function(session) {
	var to = laboratree.chat.bosh.createJid(session);
	this.connection.chatstate.active(to, laboratree.chat.auth.jid);
};

laboratree.chat.Bosh.prototype.composing = function(session) {
	var to = laboratree.chat.bosh.createJid(session);
	this.connection.chatstate.composing(to, laboratree.chat.auth.jid);
};

laboratree.chat.Bosh.prototype.paused = function(session) {
	var to = laboratree.chat.bosh.createJid(session);
	this.connection.chatstate.paused(to, laboratree.chat.auth.jid);
};

laboratree.chat.Bosh.prototype.inactive = function(session) {
	var to = laboratree.chat.bosh.createJid(session);
	this.connection.chatstate.inactive(to, laboratree.chat.auth.jid);
};

laboratree.chat.Bosh.prototype.gone = function(session) {
	var to = laboratree.chat.bosh.createJid(session);
	this.connection.chatstate.gone(to, laboratree.chat.auth.jid);
};

laboratree.chat.Bosh.prototype.onChatState = function(message) {
	laboratree.info('CHAT STATE');
	laboratree.info(message);

	var id = message.getAttribute('id');

	var from = laboratree.chat.bosh.parseJid(message.getAttribute('from'));
	if(!from) {
		return true;
	}

	var session = 'user:' + from.node;

	var composing = message.getElementsByTagName('composing');
	if(composing.length > 0) {
		if(laboratree.chat.type == 'window') {
			if(laboratree.chat.windowMgr.windows[session]) {
				laboratree.chat.windowMgr.windows[session].isComposing();
			}
		} else if(laboratree.chat.type == 'popout') {
			if(laboratree.chat.popoutMgr.popouts[session]) {
				laboratree.chat.popoutMgr.popouts[session].isComposing();
			}
		}
	}

	var paused = message.getElementsByTagName('paused');
	if(paused.length > 0) {
		if(laboratree.chat.type == 'window') {
			if(laboratree.chat.windowMgr.windows[session]) {
				laboratree.chat.windowMgr.windows[session].isPaused();
			}
		} else if(laboratree.chat.type == 'popout') {
			if(laboratree.chat.popoutMgr.popouts[session]) {
				laboratree.chat.popoutMgr.popouts[session].isPaused();
			}
		}
	}

	var active = message.getElementsByTagName('active');
	if(active.length > 0) {
		if(laboratree.chat.type == 'window') {
			if(laboratree.chat.windowMgr.windows[session]) {
				laboratree.chat.windowMgr.windows[session].clearStatus();
			}
		} else if(laboratree.chat.type == 'popout') {
			if(laboratree.chat.popoutMgr.popouts[session]) {
				laboratree.chat.popoutMgr.popouts[session].clearStatus();
			}
		}
	}

	return true;
};

if(window.addEventListener) {
	window.addEventListener('load', laboratree.chat.onLoad, false);
	window.addEventListener('unload', laboratree.chat.onUnload, false);
	window.addEventListener('beforeunload', laboratree.chat.onBeforeUnload, false);
} else if(window.attachEvent) {
	window.attachEvent('onload', laboratree.chat.onLoad);
	window.attachEvent('onunload', laboratree.chat.onUnload);
	window.attachEvent('onbeforeunload', laboratree.chat.onBeforeUnload);
} else {
	laboratree.chat.onLoad();
}

laboratree.chat.makeLogs = function(div, data_url) {
	laboratree.chat.logs = new laboratree.chat.Logs(div, data_url);
};

laboratree.chat.Logs = function(div, data_url) {
	Ext.QuickTips.init();

	this.div = div;
	this.data_url = data_url;

	this.dateStore = new Ext.data.GroupingStore({
		autoLoad: true,
		url: data_url,
		reader: new Ext.data.JsonReader({
			root: 'dates',
			fields: [
				'id', 'date', 'name'
			]
		}),
		groupField: 'date'
	});

	this.logStore = new Ext.data.JsonStore({
		root: 'logs',
		url: data_url,

		fields: [
			'id', 'date', 'name', 'message'
		]
	});

	this.dateGrid = new Ext.grid.GridPanel({
		id: 'dates',
		width: 250,
		stripeRows: true,
		loadMask: {msg: 'Loading...'},


		store: this.dateStore,

		autoExpandColumn: 'date',
		cm: new Ext.grid.ColumnModel({
			columns: [{
				id: 'name',
				header: 'Name',
				dataIndex: 'name',
				renderer: laboratree.chat.render.logs.day.name
			},{
				id: 'date',
				header: 'Date',
				dataIndex: 'date',
				hidden: true
			}]
		}),

		view: new Ext.grid.GroupingView({
			forceFit: true,
			showGroupName: false
		})
	});

	this.logGrid =  new Ext.grid.GridPanel({
		id: 'logs',
		store: this.logStore,

		flex: 1,
		
		viewConfig: {
			forceFit: true,
			enableRowBody: true,
			getRowClass: function(record, rowIndex, p, store) {
				p.body = record.data.message;
				return 'x-grid3-row-with-body';
			}
		},
		
		columns: [{
			id: 'name',
			header: 'Name',
			dataIndex: 'name',
			renderer: laboratree.chat.render.logs.log.name
		},{
			id: 'time',
			header: 'Time',
			width: 60,
			dataIndex: 'timestamp',
			renderer: laboratree.chat.render.logs.log.time
		}]
	});

	this.dashboard = new Ext.Panel({
		id: 'dashboard',
		title: 'Chat Logs',
		height: 400,
		width: 720,

		renderTo: div,

		layout: 'hbox',
		layoutConfig: {
			align: 'stretch',
			pack: 'start'
		},

		items: [this.dateGrid, this.logGrid]
	});
};

laboratree.chat.render.logs = {};
laboratree.chat.render.logs.day = {};
laboratree.chat.render.logs.day.name = function(value, p, record) {
	return String.format('<a href="#" onclick="laboratree.chat.log.loadLog(\'{0}\'); return false;" title="{1}">{1}</a>', record.id, value);
};

laboratree.chat.render.logs.log = {};
laboratree.chat.render.logs.log.name = function(value, p, record) {
	return String.format('<div style="font-weight: bold;">{0}</div>', value);
};

laboratree.chat.render.logs.log.time = function(value, p, record) {
	return String.format('<div style="text-align: right;">{0}</div>', value);
};
