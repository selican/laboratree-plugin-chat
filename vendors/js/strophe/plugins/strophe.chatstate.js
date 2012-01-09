/*
Plugin to implement the Chat State Notifications extension. http://xmpp.org/extensions/xep-0085.html
*/
Strophe.addConnectionPlugin('chatstate', {
	_connection: null,
	init: function(conn) {
		this._connection = conn;

		Strophe.addNamespace('CHATSTATES', 'http://jabber.org/protocol/chatstates');
	},
	active: function(to, user) {
		var id = this._connection.getUniqueId('chatstate');

		var msg = $msg({
			id: id,
			to: to,
			from: user,
			type: 'chat'
		}).c('active', {
			xmlns: Strophe.NS.CHATSTATES
		});

		this._connection.send(msg);
	},
	inactive: function(to, user) {
		var id = this._connection.getUniqueId('chatstate');

		var msg = $msg({
			id: id,
			to: to,
			from: user,
			type: 'chat'
		}).c('inactive', {
			xmlns: Strophe.NS.CHATSTATES
		});

		this._connection.send(msg);
	},
	gone: function(to, user) {
		var id = this._connection.getUniqueId('chatstate');

		var msg = $msg({
			id: id,
			to: to,
			from: user,
			type: 'chat'
		}).c('gone', {
			xmlns: Strophe.NS.CHATSTATES
		});

		this._connection.send(msg);
	},
	composing: function(to, user) {
		var id = this._connection.getUniqueId('chatstate');

		var msg = $msg({
			id: id,
			to: to,
			from: user,
			type: 'chat'
		}).c('composing', {
			xmlns: Strophe.NS.CHATSTATES
		});

		this._connection.send(msg);
	},
	paused: function(to, user) {
		var id = this._connection.getUniqueId('chatstate');

		var msg = $msg({
			id: id,
			to: to,
			from: user,
			type: 'chat'
		}).c('paused', {
			xmlns: Strophe.NS.CHATSTATES
		});

		this._connection.send(msg);
	}
});
