/**
 * Plugin to implement the MUC extension. http://xmpp.org/extensions/xep-0045.html
 */
Strophe.addConnectionPlugin('muc', {
	_connection: null,
	/**
	 * Initialize MUC Plugin
	 * Set Connection and Add Namespaces
	 * @param Connection conn Strophe Connection
	 */
	init: function(conn) {
		this._connection = conn;

		Strophe.addNamespace('MUC_USER', Strophe.NS.MUC + '#user');
		Strophe.addNamespace('MUC_ADMIN', Strophe.NS.MUC + "#admin");
		Strophe.addNamespace('JABBER_DELAY', 'jabber:x:delay');
		Strophe.addNamespace('XMPP_DELAY', 'urn:xmpp:delay');
		Strophe.addNamespace('CHATSTATES', 'http://jabber.org/protocol/chatstates');
	},
	/**
	 * Join MUC Room
	 * @param string   room            MUC Room
	 * @param string   user            User
	 * @param string   since           Room History Start
	 * @param function msg_handler_cb  Message Handler Callback
	 * @param function pres_handler_cb Presence Handler Callback
	*/
	join: function(room, user, since, msg_handler_cb, pres_handler_cb) {
		var pres = $pres({
			from: user,
			to: room,
		}).c('x', {
			xmlns: Strophe.NS.MUC
		}).c('history', {
			since: since
		});

		if(msg_handler_cb) {
			this._connection.addHandler(function(stanza) {
				var from = stanza.getAttribute('from');

				/* Separate Room from Nickname */
				var name = from.split('/');

				/* Separate Room from Nickname */
				var muc = room.split('/');

				if(name[0] == muc[0]) {
					return msg_handler_cb(stanza);
				}

				return true;
			}, null, 'message', null, null, null);
		}

		if(pres_handler_cb) {
			this._connection.addHandler(function(stanza) {
				var x = stanza.getElementsByTagName('x');
				if(x.length > 0) {
					for(var i = 0; i < x.length; i++) {
						var xmlns = x[i].getAttribute('xmlns');
						
						if(xmlns && xmlns.match(Strophe.NS.MUC))
						{
							return pres_handler_cb(stanza);
						}
					}
				}

				return true;
			}, null, 'presence', null, null, null);
		}

		this._connection.send(pres);
	},
	/**
	 * Leave MUC Room
	 * @param string   room            MUC Room
	 * @param string   user            User
	 * @param function pres_handler_cb Presence Handler Callback
	 */
	leave: function(room, user, pres_handler_cb) {
		var id = this._connection.getUniqueId('muc');

		var pres = $pres({
			id: id,
			type: 'unavailable',
			from: user,
			to: room
		}).c('x',{
			xmlns: Strophe.NS.MUC
		});

		if(pres_handler_cb) {
			this._connection.addHandler(function(stanza) {
				return pres_handler_cb(stanza);
			}, null, 'presence', null, id, null);
		}

		this._connection.send(pres);
		this._connection.flush();
	},
	/**
	 * Send Message to MUC Room
	 * @param string room    MUC Room
	 * @param string user    User
	 * @param string message Message
	 */
	message: function(room, user, message) {
		var id = this._connection.getUniqueId('muc');

		var msg = $msg({
			id: id,
			to: room,
			from: user,
			type: 'groupchat'
		}).c('body',{
			xmlns: Strophe.NS.CLIENT
		}).t(message);

		msg.up().c('active', {
			xmlns: Stophe.NS.CHATSTATES
		});

		this._connection.send(msg);

		return id;
	},
	/**
	 * Update MUC Room
	 * @param string   room MUC Room
	 * @param string   user User
	 * @param function iq_handler_cb IQ Handler Callback
	 */
	update: function(room, user, iq_handler_cb) {
		var id = this._connection.getUniqueId('muc');

		var iq = $iq({
			id: id,
			from: user,
			to: room,
			type: 'get'
		}).c('query',{
			xmlns: Strophe.NS.DISCO_ITEMS
		});
			
		this._connection.send(iq);

		if(iq_handler_cb) {
			this._connection.addHandler(function(stanza) {
				return iq_handler_cb(stanza);
			}, null, 'iq', null, id, null);
		}
	}
});
