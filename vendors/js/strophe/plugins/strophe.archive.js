/*
Plugin to implement the Message Archive extension. http://xmpp.org/extensions/xep-0136.html
*/
Strophe.addConnectionPlugin('archive', {
	_connection: null,
	init: function(conn) {
		this._connection = conn;

		Strophe.addNamespace('ARCHIVE', 'urn:xmpp:archive');
		Strophe.addNamespace('RSM', 'http://jabber.org/protocol/rsm');
	},
	list: function(user, limit, start, after, iq_handler_cb) {
		if(!limit) {
			limit = 30;
		}

		var id = this._connection.getUniqueId('archive');

		var iq = $iq({
			type: 'get',
			id: id
		}).c('list', {
			xmlns: Strophe.NS.ARCHIVE,
			start: start,
			'with': user
		}).c('set', {
			xmlns: Strophe.NS.RSM
		}).c('max', null).t(limit + '');

		if(after) {
			iq.up();
			iq.c('after', null).t(after);
		}

		if(iq_handler_cb) {
			this._connection.addHandler(function(stanza) {
				var set = stanza.getElementsByTagName('set');	
				if(set.length > 0) {
					for(var i = 0; i < set.length; i++) {
						var xmlns = set[i].getAttribute('xmlns');

						if(xmlns && xmlns.match(Strophe.NS.RSM)) {
							return iq_handler_cb(stanza);
						}
					}
				}

				return true;
			}, null, 'iq', null, id);
		}

		this._connection.send(iq);
	},
	retrieve: function(user, limit, start, after, iq_handler_cb) {
		if(!limit) {
			limit = 30;
		}

		var id = this._connection.getUniqueId('archive');

		var iq = $iq({
			type: 'get',
			id: id
		}).c('retrieve', {
			xmlns: Strophe.NS.ARCHIVE,
			'with': user,
			start: start
		}).c('set', {
			xmlns: Strophe.NS.RSM
		}).c('max', null).t(limit + '');

		if(after) {
			iq.up();
			iq.c('after', null).t(after);
		}

		if(iq_handler_cb) {
			this._connection.addHandler(function(stanza) {
				var chat = stanza.getElementsByTagName('chat');	
				if(chat.length > 0) {
					for(var i = 0; i < chat.length; i++) {
						var xmlns = chat[i].getAttribute('xmlns');

						if(xmlns && xmlns.match(Strophe.NS.ARCHIVE)) {
							return iq_handler_cb(stanza);
						}
					}
				}

				return true;
			}, null, 'iq', null, id);
		}

		this._connection.send(iq);
	}
});
