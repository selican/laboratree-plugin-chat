/*
Plugin to implement the Personal Eventing Protocol extension. http://xmpp.org/extensions/xep-0163.html
*/
Strophe.addConnectionPlugin('pep', {
	_connection: null,
	init: function(conn) {
		this._connection = conn;

		Strophe.addNamespace('PUBSUB', 'http://jabber.org/protocol/pubsub');
		Strophe.addNamespace('PUBSUB_EVENT', Strophe.NS.PUBSUB + '#event');
	},
	publish: function(user, node, item, item_id, msg_handler_cb, iq_handler_cb) {
		var id = this._connection.getUniqueId('pep');

		var item_attr = null;
		if(item_id) {
			item_attr = {
				id: item_id
			};
		}

		var iq = $iq({
			id: id,
			from: user,
			type: 'set',
		}).c('pubsub', {
			xmlns: Strophe.NS.PUBSUB
		}).c('publish', {
			node: node
		}).c('item', item_attr).cnode(item);

		if(msg_handler_cb) {
			this._connection.addHandler(function(stanza) {
				var events = stanza.getElementsByTagName('event');
				for(var i = 0; i < events.length; i++) {
					var xmlns = events[i].getAttribute('xmlns');
					if(xmlns && xmlns.match(Strophe.NS.PUBSUB_EVENT)) {
						return msg_handler_cb(stanza);
					}
				}

				return true;
			}, null, 'message', null, null);
		}

		if(iq_handler_cb) {
			this._connection.addHandler(function(stanza) {
				return iq_handler_cb(stanza);
			}, null, 'iq', null, id);
		}

		this._connection.send(iq);
	},
});
