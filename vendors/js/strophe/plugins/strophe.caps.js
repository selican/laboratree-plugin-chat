/*
Plugin to implement the Entity Capabilities extension. http://xmpp.org/extensions/xep-0115.html
*/
Strophe.addConnectionPlugin('caps', {
	_connection: null,
	init: function(conn) {
		this._connection = conn;

		Strophe.addNamespace('CAPS', 'http://jabber.org/protocol/caps');

		Number.prototype.toHexStr = function() {
			var s="", v;
			for (var i=7; i>=0; i--) { v = (this>>>(i*4)) & 0xf; s += v.toString(16); }
			return s;
		}
	},
	presence: function(user, node, priority, identity, features, iq_handler_cb) {
		var ver = this.ver(identity, features);

		var pres = $pres({
			from: user
		}).c('c', {
			xmlns: Strophe.NS.CAPS,
			hash: 'sha-1',
			node: node,
			ver: ver
		});

		if(priority) {
			priority = parseInt(priority);
			if(!isNaN(priority)) {
				pres.up().c('priority', null).t(priority + '');
			}
		}

		if(iq_handler_cb) {
			this._connection.addHandler(function(stanza) {
				var type = stanza.getAttribute('type');
				if(type == 'get') {
					var query = stanza.getElementsByTagName('query');
					for(var i = 0; i < query.length; i++) {
						var xmlns = query[i].getAttribute('xmlns');

						if(xmlns && xmlns.match(Strophe.NS.DISCO_INFO)) {
							return iq_handler_cb(stanza);
						}
					}
				}

				return true;
			}, null, 'iq', null, null);
		}

		this._connection.send(pres);
	},
	verify: function(id, to, user, identity, features) {
		var iq = $iq({
			id: id,
			type: 'result',
			from: user,
			to: to
		}).c('query', {
			xmlns: Strophe.NS.DISCO_INFO
		}).c('identity', identity).up();

		for(var i = 0; i < features.length; i++) {
			iq.c('feature', {
				'var': features[i]
			}).up();
		}

		this._connection.send(iq);
	},
	ver: function(identity, features) {
		var ident = [
			identity.category + '/' + identity.type + '//' + identity.name
		];

		var list = ident.concat(features);
		var s = list.join('<') + '<';

		var sha1 = Crypto.SHA1(s, {
			asBytes: true
		});

		var base64 = Crypto.util.bytesToBase64(sha1);

		return base64;
	}
});
