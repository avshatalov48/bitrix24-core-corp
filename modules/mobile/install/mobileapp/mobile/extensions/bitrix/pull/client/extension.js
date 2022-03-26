var REVISION = 3; // api revision - check module/pull/include.php

var CloseReasons = {
	CONFIG_REPLACED : 3000,
	CHANNEL_EXPIRED : 3001,
	SERVER_RESTARTED : 3002,
	CONFIG_EXPIRED : 3003,
	SERVER_DIE : 1001,
	CODE_1000 : 1000
};
var SystemCommands = {
	CHANNEL_EXPIRE : 0,
	CONFIG_EXPIRE : 1,
	SERVER_RESTART : 2
};
var PullStatus = {
	Online: 'online',
	Offline: 'offline',
	Connecting: 'connect'
};
var WebsocketReadyState = {
	0 : 'CONNECTING',
	1 : 'OPEN',
	2 : 'CLOSING',
	3 : 'CLOSED',
};
var SenderType = {
	Unknown: 0,
	Client: 1,
	Backend: 2
};

var isSecure = currentDomain.indexOf('https') == 0;

var CONFIG = {
	USER_ID: BX.componentParameters.get('USER_ID', 0),
	SITE_ID: BX.componentParameters.get('SITE_ID', 's1'),
	LANGUAGE_ID: BX.componentParameters.get('LANGUAGE_ID', 'en'),
	PULL_CONFIG: BX.componentParameters.get('PULL_CONFIG', {}),
};

var MAX_IDS_TO_STORE = 10;

// Protobuf message models
var Response = protobuf ? protobuf.roots['push-server']['Response'] : null;
var ResponseBatch = protobuf ? protobuf.roots['push-server']['ResponseBatch'] : null;
var Request = protobuf ? protobuf.roots['push-server']['Request'] : null;
var RequestBatch = protobuf ? protobuf.roots['push-server']['RequestBatch'] : null;
var IncomingMessagesRequest = protobuf ? protobuf.roots['push-server']['IncomingMessagesRequest'] : null;
var IncomingMessage = protobuf ? protobuf.roots['push-server']['IncomingMessage'] : null;
var Receiver = protobuf ? protobuf.roots['push-server']['Receiver'] : null;

/**
 * Interface for delegate of connector
 * @constructor
 */
var WebSocketConnectorDelegate = function ()
{
};
WebSocketConnectorDelegate.prototype = {
	getPath : function ()
	{ /** must be overridden in children class **/
	},
	updateConfig : function ()
	{ /** must be overridden in children class **/
	},
	onError : function ()
	{ /** must be overridden in children class **/
	},
	onClose : function ()
	{ /** must be overridden in children class **/
	},
	onMessage : function ()
	{ /** must be overridden in children class **/
	},
	onOpen : function ()
	{ /** must be overridden in children class **/
	},
};

/**
 * Class for settings of connector
 * @param initParams
 * @constructor
 */
var WebSocketConnectorParams = function (initParams)
{
	this.debug = false;
	this.attemptCount = 3;
	this.attemptInterval = 2000;
	this.betweenAttemptsInterval = 20000;

	this.disconnectOnBackground = true;

	if (initParams)
	{
		for (let key in initParams)
		{
			if (initParams.hasOwnProperty(key) && WebSocketConnectorParams.prototype.hasOwnProperty(key))
			{
				if (typeof this[key] === typeof initParams[key])
				{
					this[key] = initParams[key];
				}
				else
				{
					console.warn("WebSocketConnectorParams",
						"Parameter '" + key + "' must be " + typeof this[key] + ". Default value will be using (" + this[key] + ")");
				}
			}
			else
			{
				console.warn("WebSocketConnectorParams", "Unknown parameter '" + key + "'");
			}
		}
	}
};

/**
 * @param {WebSocketConnectorDelegate} delegate
 * @param {WebSocketConnectorParams} params
 * @constructor
 */
var WebSocketConnector = function (delegate, params)
{
	this.params = params;
	this.connectAttempt = 0;
	this.debug = BX.componentParameters.get('PULL_DEBUG_SOURCE', false);
	this.connecting = false;
	this._socket = null;
	this.delegate = delegate;
	this.offline = false;

	this.hasActiveCall = false;
	this.hasActiveTelephonyCall = false;

	Object.defineProperty(this, "socket", {
		set : (value) =>
		{
			if (this._socket && this._socket.readyState === 1)
			{
				this._socket.close(1000, "normal");
			}

			this._socket = value;
		},
		get : () => this._socket
	});

	BX.addCustomEvent("online", () => {
		this.offline = false;
		if(!Application.isBackground())
		{
			this.connect();
		}
	});
	BX.addCustomEvent("offline", () => {
		this.offline = true;
	});
	if (this.params.disconnectOnBackground)
	{
		BX.addCustomEvent("onAppActive", this.onAppActive.bind(this));
		BX.addCustomEvent("onAppPaused", this.onAppPaused.bind(this));
		BX.addCustomEvent("CallEvents::hasActiveCall", this.onActiveCallStateChange.bind(this))
		BX.addCustomEvent("CallEvents::hasActiveTelephonyCall", this.onActiveTelephonyCallStateChange.bind(this))
	}

	BX.addCustomEvent("onPullForceBackgroundConnect", () => {
		console.warn("Forced connection in background");
		if(Application.isBackground())
		{
			this.connect(true);
		}
		else
		{
			if (this.socket && this.socket.readyState === 1)
			{
				// already connected
				this.sendPullStatus(PullStatus.Online);
			}
		}
	});

	BX.addCustomEvent("onPullGetStatus", () => {
		BX.postWebEvent("onPullStatus", {status : this.currentStatus});
		BX.postComponentEvent("onPullStatus", [{status : this.currentStatus}]);
	})
};

WebSocketConnector.prototype = {
	connectAttempt : 0,
	state : 0,
	connectionTimeoutId : null,
	connectionTimeoutTime : 0,
	currentStatus : 'offline',
	connect : function (force)
	{
		if (!force)
		{
			if (this.socket && this.socket.readyState === 1)
			{
				console.warn("WebSocketConnector.connect: " +
					"WebSocket is already connected. Use second argument to force reconnect");
				return;
			}

			if (this.connectionTimeoutId && this.connectionTimeoutTime+60000 > (new Date()).getTime())
			{
				console.warn("WebSocketConnector.connect:" +
					" Connection will be established soon, but not now. Use second argument to force connection.");
				return;
			}
		}

		clearTimeout(this.connectionTimeoutId);

		let connectTimeout = 0;
		switch (this.connectAttempt)
		{
			case 0:
			{
				this.connectAttempt++;
				break;
			}
			case (this.params.attemptCount):
			{
				this.connectAttempt = 0;
				connectTimeout = this.params.betweenAttemptsInterval;
				break;
			}
			default:
			{
				this.connectAttempt++;
				connectTimeout = this.params.attemptInterval;
				break;
			}
		}

		this.sendPullStatus(PullStatus.Connecting);

		this.connectionTimeoutTime = (new Date()).getTime();
		this.connectionTimeoutId = setTimeout(() =>
		{
			this.connectionTimeoutId = null;
			this.connectionTimeoutTime = 0;

			let connectPath = this.delegate.getPath();
			if (connectPath)
			{
				this.createWebSocket(connectPath)
			}
			else
			{
				this.delegate.updateConfig();
			}
		}, connectTimeout);
	},
	disconnect : function (code, message)
	{
		console.trace("disconnect", code, message)
		/**
		 * @var this.socket WebSocket
		 */
		if (this.socket !== null && this.socket.readyState === 1)
		{
			code = (code? code: 1000);
			this.socket.close(code, message);
		}
	},
	createWebSocket : function (connectPath)
	{
		this.socket = new WebSocket(connectPath);
		this.socket.binaryType = 'arraybuffer';

		this.socket.onclose = this.onclose.bind(this);
		this.socket.onerror = this.onerror.bind(this);
		this.socket.onmessage = this.onmessage.bind(this);
		this.socket.onopen = this.onopen.bind(this);
	},
	sendPullStatus : function (status, additional)
	{
		if (!additional)
		{
			additional = {};
		}
		this.currentStatus = status;
		if(this.offlineTimeout)
		{
			clearTimeout(this.offlineTimeout);
			this.offlineTimeout = null;
		}
		BX.postWebEvent("onPullStatus", {status : status, additional: additional});
		BX.postComponentEvent("onPullStatus", [{status : status, additional: additional}]);
	},
	/**
	 * Sends some data to the server via websocket connection.
	 * @param {ArrayBuffer} buffer Data to send.
	 * @return {boolean}
	 */
	send: function (buffer)
	{
		if(!this.socket || this.socket.readyState !== 1)
		{
			console.error("Send error: WebSocket is not connected");
			return false;
		}

		this.socket.send(new Uint8Array(buffer));
	},
	onopen : function ()
	{
		this.state = "connected";
		this.connectAttempt = 0;
		console.info("WebSocket -> onopen");

		this.sendPullStatus(PullStatus.Online);

		this.delegate.onOpen.apply(this.delegate, arguments);
	},
	onclose : function ()
	{
		console.info("WebSocket -> onclose", arguments);

		if(
			arguments[0].code !== CloseReasons.CONFIG_EXPIRED
			&& arguments[0].code !== CloseReasons.CHANNEL_EXPIRED
			&& arguments[0].code !== CloseReasons.CONFIG_REPLACED
		)
		{
			this.sendPullStatus(PullStatus.Offline);
		}
		else
		{
			this.offlineTimeout = setTimeout(() => this.sendPullStatus(PullStatus.Offline), 5000);
		}

		this.delegate.onClose.apply(this.delegate, [arguments[0], this.waitingConnectionAfterBackground]);
		this.waitingConnectionAfterBackground = false;
	},
	onerror : function ()
	{
		console.error("WebSocket -> onerror", arguments);

		this.sendPullStatus(PullStatus.Offline, {isError: true});

		this.delegate.onError.apply(this.delegate, [arguments[0], this.waitingConnectionAfterBackground]);
		this.waitingConnectionAfterBackground = false;
	},
	onmessage : function ()
	{
		console.log("WebSocket -> onmessage", this.debug? arguments: true);
		this.delegate.onMessage.apply(this.delegate, arguments);
	},
	onActiveCallStateChange : function (hasActiveCall)
	{
		this.hasActiveCall = hasActiveCall;
		if (!this.hasActiveCall && !this.hasActiveTelephonyCall && Application.isBackground())
		{
			this.disconnect(1000, "App is in background")
		}
	},
	onActiveTelephonyCallStateChange : function (hasActiveTelephonyCall)
	{
		console.warn('onActiveTelephonyCallStateChange', hasActiveTelephonyCall);
		this.hasActiveTelephonyCall = hasActiveTelephonyCall;
		if (!this.hasActiveCall && !this.hasActiveTelephonyCall && Application.isBackground())
		{
			this.disconnect(1000, "App is in background")
		}
	},
	onAppPaused : function ()
	{
		console.warn("onAppPaused; this.hasActiveCall: " + (this.hasActiveCall ? "true" : "false") + "; this.hasActiveTelephonyCall: " + (this.hasActiveTelephonyCall ? "true" : "false"));
		if (!this.hasActiveCall && !this.hasActiveTelephonyCall)
		{
			this.disconnect(1000, "App is in background")
		}
	},
	onAppActive : function ()
	{
		console.warn("onAppActive; this.hasActiveCall: " + (this.hasActiveCall ? "true" : "false") + "; this.hasActiveTelephonyCall: " + (this.hasActiveTelephonyCall ? "true" : "false"));
		this.waitingConnectionAfterBackground = true;
		this.connect(!this.hasActiveCall && !this.hasActiveTelephonyCall);
	}
};

/**
 *
 * @param config
 * @constructor
 */
function Connection(config)
{
	config = config || CONFIG.PULL_CONFIG;

	this.config = {
		channels : {},
		server : {
			timeShift: 0
		},
		debug: {
			log: BX.componentParameters.get('PULL_DEBUG', true),
			logFunction: BX.componentParameters.get('PULL_DEBUG_FUNCTION', false)
		},
		clientId: null,
		actual: false,
	};
	this.session = {
		mid : null,
		tag : null,
		time : null,
		lastId: 0,
		history: {},
		lastMessageIds: [],
		messageCount: 0
	};

	this.connector = new WebSocketConnector(this, new WebSocketConnectorParams());
	this.channelManager = new ChannelManager();

	this.expireCheckInterval = 60000;
	this.expireCheckTimeoutId = null;
	this.watchTagsQueue = {};
	this.watchUpdateInterval = 1740000;
	this.watchForceUpdateInterval = 5000;
	this.configRequestAfterErrorInterval = 60000;

	BX.addCustomEvent("onPullGetDebugInfo", this.sendDebugInfo.bind(this));
	BX.addCustomEvent("onPullGetPublishingState", this.sendPublishingState.bind(this));
	BX.addCustomEvent("onPullSetPublicIds", this.setPublicIds.bind(this));
	BX.addCustomEvent("onPullSendMessageBatch", this.sendMessageBatch.bind(this));
	BX.addCustomEvent("onPullExtendWatch", data => this.extendWatch(data.id, data.force));
	BX.addCustomEvent("onPullClearWatch", data => this.clearWatchTag(data.id));
	BX.addCustomEvent("onUpdateServerTime", this.updateTimeShift.bind(this));

	Object.defineProperty(this, "socket", {
		get : () => this.connector.socket
	});

	if (config)
	{
		this.setConfig(config);
	}
}

Connection.prototype = Object.create(WebSocketConnectorDelegate.prototype);
Connection.prototype.constructor = Connection;

/**
 * Configuration
 */

Connection.prototype.start = function ()
{
	this.connect();
	this.updateWatch();
	this.checkChannelExpire();
};
Connection.prototype.setConfig = function (config)
{
	let isUpdated = false;

	for (let type in config.channels)
	{
		if (config.channels.hasOwnProperty(type))
		{
			this.config.channels[type] = config.channels[type];
			CONFIG.PULL_CONFIG.channels[type] = config.channels[type];
			isUpdated = true;
		}
	}

	for (let configId in config.server)
	{
		if (config.server.hasOwnProperty(configId))
		{
			this.config.server[configId] = config.server[configId];
			CONFIG.PULL_CONFIG.server[configId] = config.server[configId];
			isUpdated = true;
		}
	}

	if (config.clientId != this.config.clientId)
	{
		this.config.clientId = config.clientId;
		CONFIG.PULL_CONFIG.clientId = config.clientId;
		isUpdated = true;
	}
	if (!isUpdated)
	{
		console.warn("Connection.setConfig: nothing to update\n", this.config);
		return false;
	}

	BX.componentParameters.set('PULL_CONFIG', this.config);

	console.info("Connection.setConfig: new config set\n", this.config);
	this.config.actual = true;
	this.sendPublishingState();

	if (
		typeof config.api != 'undefined'
		&& parseInt(config.api.revision_mobile) > REVISION
	)
	{
		console.warn('Connection.setConfig: reload scripts because revision up ('+REVISION+' -> '+config.api.revision_mobile+')');
		CONFIG.PULL_CONFIG.api.revision_mobile = config.api.revision_mobile;
		BX.componentParameters.set('PULL_CONFIG', CONFIG.PULL_CONFIG);

		reloadAllScripts();
		return false;
	}

	return true;
};
Connection.prototype.getChannel = function (type)
{
	return this.config.channels[type];
};
Connection.prototype.isChannelsExpired = function ()
{
	let result = false;
	for (let type in this.config.channels)
	{
		if (!this.config.channels.hasOwnProperty(type))
		{
			continue;
		}
		if (new Date(this.config.channels[type].end).getTime() <= new Date().getTime())
		{
			this.config.actual = false;
			console.info("Connection.isChannelsExpired: "+type+" channel was expired.");
			result = true;
			break;
		}
	}

	return result;
};
Connection.prototype.checkChannelExpire = function ()
{
	clearTimeout(this.expireCheckTimeoutId);
	this.expireCheckTimeoutId = setTimeout(this.checkChannelExpire.bind(this), this.expireCheckInterval);
	if (this.isChannelsExpired())
	{
		this.connector.disconnect(CloseReasons.CONFIG_EXPIRED, "channel was expired");
	}
};
Connection.prototype.configRequest = function ()
{
	let promise = new BX.Promise();
	BX.rest.callBatch({
		serverTime : ['server.time'],
		configGet : ['pull.config.get', {'CACHE': 'N'}],
	}, (result) => {
		// serverTime block
		if (result.serverTime && !result.serverTime.error())
		{
			this.config.server.timeShift = Math.floor((new Date().getTime() - new Date(result.serverTime.data()).getTime())/1000);
		}

		if (result.configGet.error())
		{
			promise.reject(result.configGet);
		}
		else if (result.configGet)
		{
			promise.fulfill(result.configGet)
		}
	});

	return promise;
};
Connection.prototype.updateConfig = function ()
{
	clearTimeout(this.updateConfigTimeout);
	let updateConfigPromise = new BX.Promise();

	console.info("Connection.updateConfig: request new config");

	this.configRequest()
		.catch((result) =>
		{
			this.connector.sendPullStatus(PullStatus.Offline);

			let error = result.error();
			if (error.status == 0)
			{
				console.error("Connection.updateConfig: connection error, we will be check again soon", error.ex);
			}
			else
			{
				console.error("Connection.updateConfig: we have some problems with config, we will be check again soon\n", result.answer);
			}
			this.config.actual = false;
			this.updateConfigTimeout = setTimeout(() => {
				this.updateConfig();
			}, this.configRequestAfterErrorInterval);

			updateConfigPromise.reject(result.answer)
		})
		.then((result) =>
		{
			this.setConfig(result.data());
			this.connect(true);

			updateConfigPromise.fulfill(result.data());
		});

	return updateConfigPromise;
};
Connection.prototype.getServerVersion = function()
{
	return (this.config && this.config.server) ? this.config.server.version : 0;
};
Connection.prototype.isPublishingEnabled = function ()
{
	if(!this.isProtobufSupported())
	{
		return false;
	}

	return (this.config && this.config.server && this.config.server.publish_enabled === true);
};
Connection.prototype.sendPublishingState = function ()
{
	BX.postComponentEvent("onPullPublishingState", [this.isPublishingEnabled()]);
};
Connection.prototype.isProtobufSupported = function()
{
	return (this.getServerVersion() > 3 && ("protobuf" in window));
};
Connection.prototype.parseResponse = function (response)
{
	var events = this.extractMessages(response);
	var messages = [];
	if (events.length === 0)
	{
		this.session.mid = null;
		return;
	}

	for (var i = 0; i < events.length; i++)
	{
		var event = events[i];

		if (event.mid && this.session.lastMessageIds.includes(event.mid))
		{
			console.warn("Duplicate message " + event.mid + " skipped");
			continue;
		}

		this.session.mid = event.mid || null;
		this.session.tag = event.tag || null;
		this.session.time = event.time || null;

		messages.push(event.text);

		if (!this.session.history[event.text.module_id])
		{
			this.session.history[event.text.module_id] = {};
		}
		if (!this.session.history[event.text.module_id][event.text.command])
		{
			this.session.history[event.text.module_id][event.text.command] = 0;
		}
		this.session.history[event.text.module_id][event.text.command]++;
		this.session.messageCount++;
	}

	if (this.session.lastMessageIds.length > MAX_IDS_TO_STORE)
	{
		this.session.lastMessageIds = this.session.lastMessageIds.slice( - MAX_IDS_TO_STORE);
	}
	this.broadcastMessages(messages);
};
Connection.prototype.extractMessages = function (pullEvent)
{
	if(pullEvent instanceof ArrayBuffer)
	{
		return this.extractProtobufMessages(pullEvent);
	}
	else if(Utils.isNotEmptyString(pullEvent))
	{
		return this.extractPlainTextMessages(pullEvent)
	}
};
Connection.prototype.extractProtobufMessages = function(pullEvent)
{
	let result = [];
	try
	{
		let responseBatch = ResponseBatch.decode(new Uint8Array(pullEvent));
		for (let i = 0; i < responseBatch.responses.length; i++)
		{
			let response = responseBatch.responses[i];
			if (response.command != "outgoingMessages")
			{
				continue;
			}

			let messages = responseBatch.responses[i].outgoingMessages.messages;
			for (let m = 0; m < messages.length; m++)
			{
				let message = messages[m];
				let messageFields;
				try
				{
					messageFields = JSON.parse(message.body)
				}
				catch (e)
				{
					console.error("Pull: Could not parse message body", e);
					continue;
				}

				if(!messageFields.extra)
				{
					messageFields.extra = {}
				}
				messageFields.extra.sender = {
					type: message.sender.type
				};

				if(message.sender.id instanceof Uint8Array)
				{
					messageFields.extra.sender.id = this.decodeId(message.sender.id)
				}

				let compatibleMessage = {
					mid: this.decodeId(message.id),
					text: messageFields
				};

				result.push(compatibleMessage);
			}
		}
	}
	catch(e)
	{
		console.error("Pull: Could not parse message", e)
	}
	return result;
};
Connection.prototype.extractPlainTextMessages = function(pullEvent)
{
	let result = [];
	let dataArray = pullEvent.match(/#!NGINXNMS!#(.*?)#!NGINXNME!#/gm);
	if (dataArray === null)
	{
		text = "\n========= PULL ERROR ===========\n"+
			"Error type: parseResponse error parsing message\n"+
			"\n"+
			"Data string: " + pullEvent + "\n"+
			"================================\n\n";
		console.warn(text);
		return result;
	}
	for (let i = 0; i < dataArray.length; i++)
	{
		dataArray[i] = dataArray[i].substring(12, dataArray[i].length - 12);
		if (dataArray[i].length <= 0)
		{
			continue;
		}

		try
		{
			var data = JSON.parse(dataArray[i])
		}
		catch(e)
		{
			continue;
		}

		result.push(data);
	}
	return result;
};
/**
 * Converts message id from byte[] to string
 * @param {Uint8Array} encodedId
 * @return {string}
 */
Connection.prototype.decodeId = function(encodedId)
{
	if(!(encodedId instanceof Uint8Array))
	{
		throw new Error("encodedId should be an instance of Uint8Array");
	}

	var result = "";
	for (var i = 0; i < encodedId.length; i++)
	{
		var hexByte = encodedId[i].toString(16);
		if (hexByte.length === 1)
		{
			result += '0';
		}
		result += hexByte;
	}
	return result;
};
/**
 * Converts message id from hex-encoded string to byte[]
 * @param {string} id Hex-encoded string.
 * @return {Uint8Array}
 */
Connection.prototype.encodeId = function(id)
{
	if (!id)
	{
		return new Uint8Array();
	}

	var result = [];
	for (var i = 0; i < id.length; i += 2)
	{
		result.push(parseInt(id.substr(i, 2), 16));
	}

	return new Uint8Array(result);
};
/*Connection.prototype.parseResponse = function (response)
{
	let dataArray = response.match(/#!NGINXNMS!#(.*?)#!NGINXNME!#/gm);
	if (dataArray === null)
	{
		return false;
	}

	let messages = [];
	for (let i = 0; i < dataArray.length; i++)
	{
		dataArray[i] = dataArray[i].substring(12, dataArray[i].length - 12);
		if (dataArray[i].length <= 0)
		{
			continue;
		}

		let data = JSON.parse(dataArray[i]);

		this.session.lastId = data.id;

		if (data.mid)
		{
			this.session.mid = data.mid;
		}
		if (data.tag)
		{
			this.session.tag = data.tag;
		}
		if (data.time)
		{
			this.session.time = data.time;
		}
		messages.push(data.text);

		if (!this.session.history[data.text.module_id])
		{
			this.session.history[data.text.module_id] = {};
		}
		if (!this.session.history[data.text.module_id][data.text.command])
		{
			this.session.history[data.text.module_id][data.text.command] = 0;
		}
		this.session.history[data.text.module_id][data.text.command]++;
		this.session.messageCount++;
	}
	try
	{
		this.broadcastMessages(messages);
	}
	catch(e)
	{
		let text = "Connection.parseResponse:\n" +
			"========= PULL ERROR ===========\n" +
			"Error type: broadcastMessages execute error\n" +
			"\n" +
			"Data array: " + JSON.stringify(messages) + "\n" +
			"Catch error: " + JSON.stringify(e) + "\n" +
			"================================\n\n";
		console.error(text);
	}
};*/
Connection.prototype.broadcastMessages = function (messages)
{
	if (this.config.debug.log)
	{
		console.log("Connection.broadcastMessages: receive "+(messages.length == 1? "message": "messages")+":");
		messages.forEach(function (message) {console.log(message);});
	}

	let messageRevision = false;

	messages.forEach(function (message)
	{
		let moduleId = message.module_id = message.module_id.toLowerCase();
		let command = message.command;


		if(!message.extra)
		{
			message.extra = {};
		}

		if(message.extra.server_time_unix)
		{
			message.extra.server_time_ago = (((new Date()).getTime() - (message.extra.server_time_unix * 1000)) / 1000) - this.config.server.timeShift;
			message.extra.server_time_ago = message.extra.server_time_ago > 0 ? message.extra.server_time_ago : 0;
		}

		if(message.extra.sender && message.extra.sender.type === SenderType.Client)
		{
			BX.postComponentEvent('onPullClientEvent-' + moduleId, [command, message.params, message.extra, moduleId]);
			BX.postWebEvent('onPullClient-' + moduleId, {command : message.command, params : message.params, extra : message.extra, module_id : message.module_id, }, true);
		}
		else if (moduleId === 'pull')
		{
			switch (SystemCommands[command.toUpperCase()])
			{
				case SystemCommands.CHANNEL_EXPIRE:
				{
					if (command == 'channel_expire' && message.params.action == 'reconnect')
					{
						this.config.channels[message.params.channel.type] = message.params.new_channel;
						console.info("Connection.broadcastMessages: new config for "+message.params.channel.type+" channel set:\n", this.config.channels[message.params.channel.type]);
						this.connector.disconnect(CloseReasons.CONFIG_REPLACED, "config was replaced");
					}
					else
					{
						this.connector.disconnect(CloseReasons.CHANNEL_EXPIRED, "channel expired");
					}
					break;
				}
				case SystemCommands.CONFIG_EXPIRE:
				{
					this.connector.disconnect(CloseReasons.CHANNEL_EXPIRED, "channel expired");
					break;
				}
				case SystemCommands.SERVER_RESTART:
				{
					this.connector.disconnect(CloseReasons.SERVER_RESTARTED, "server was restarted");
					break;
				}
				default://
			}
		}
		else if (moduleId === 'online')
		{
			BX.postComponentEvent('onPullOnlineEvent', [message.command, message.params, message.extra]);
			BX.postWebEvent('onPullOnline', {command : message.command, params : message.params, extra : message.extra}, true);
		}
		else
		{
			BX.postComponentEvent('onPullEvent-' + message.module_id, [message.command, message.params, message.extra, message.module_id]);
			BX.postWebEvent('onPull-' + message.module_id, {command : message.command, params : message.params, extra : message.extra, module_id : message.module_id}, true);
			BX.postWebEvent('onPull', {module_id : message.module_id, command : message.command, params : message.params, extra : message.extra}, true);

			if (this.config.debug.logFunction)
			{
				let text = "Connection.broadcastMessages: get commands for use in console\n"+
					"==== for native component ==\n"+
					'BX.postComponentEvent("onPullEvent-'+message.module_id+'", ["'+message.command+'", '+JSON.stringify(message.params)+', '+JSON.stringify(message.extra)+']);'+"\n"+
					"\n"+
					"==== for mobile browser ==\n"+
					'BX.postWebEvent("onPull-'+message.module_id+'", {command: "'+message.command+'", params: '+JSON.stringify(message.params)+', extra: '+JSON.stringify(message.extra)+']}, true);'+"\n"+
					'BX.postWebEvent("onPull", {module_id: "'+message.module_id+'", command: "'+message.command+'", params: '+JSON.stringify(message.params)+', extra: '+JSON.stringify(message.extra)+']}, true);';
				console.info(text);
			}
		}

		if (parseInt(message.extra.revision_mobile) > REVISION)
		{
			messageRevision = message.extra.revision_mobile;
		}
	}, this);

	if (messageRevision)
	{
		console.warn('Connection.broadcastMessages: reload scripts because revision up ('+REVISION+' -> '+messageRevision+')');
		CONFIG.PULL_CONFIG.api.revision_mobile = messageRevision;
		BX.componentParameters.set('PULL_CONFIG', CONFIG.PULL_CONFIG);

		reloadAllScripts();
		return false;
	}
};
Connection.prototype.extendWatch = function (tag, force)
{
	if (!tag || this.watchTagsQueue[tag])
	{
		return false;
	}
	console.info("Connection.extendWatch: add new tag", tag);
	this.watchTagsQueue[tag] = true;
	if (force)
	{
		this.updateWatch(force);
	}
};
Connection.prototype.updateWatch = function (force)
{
	clearTimeout(this.watchUpdateTimeout);
	this.watchUpdateTimeout = setTimeout(() =>
	{
		let watchTags = [];
		for(let tagId in this.watchTagsQueue)
		{
			if(this.watchTagsQueue.hasOwnProperty(tagId))
			{
				watchTags.push(tagId);
			}
		}
		if (watchTags.length > 0)
		{
			console.info("Connection.updateWatch: send request for extend", watchTags);
			BX.rest.callMethod('pull.watch.extend', {'TAGS': watchTags}).then((result) => {
				let updatedTags = result.data();
				console.info("Connection.updateWatch: extend tags result", updatedTags);
				for (let tagId in updatedTags)
				{
					if(updatedTags.hasOwnProperty(tagId) && !updatedTags[tagId])
					{
						this.clearWatchTag(tagId);
					}
				}
				this.updateWatch();
			}).catch(() => {
				this.updateWatch();
			})
		}
		else if (force)
		{
			console.info("Connection.updateWatch: nothing to update");
		}
	}, force? this.watchForceUpdateInterval: this.watchUpdateInterval);
};
Connection.prototype.clearWatchTag = function (tagId)
{
	delete this.watchTagsQueue[tagId];
};

Connection.prototype.updateTimeShift = function(serverTime)
{
	if (!serverTime)
		return false;

	let timeShift = Math.floor((new Date().getTime() - new Date(serverTime).getTime())/1000);
	if (this.config.server.timeShift != timeShift)
	{
		console.warn('Connection.updateServerTime: time shift is changed ('+this.config.server.timeShift+' -> '+timeShift+')');
		this.config.server.timeShift = timeShift;
	}

	return true;
};

/**
 *
 * @param {object[]} publicIds
 * @param {integer} publicIds.user_id
 * @param {string} publicIds.public_id
 * @param {string} publicIds.signature
 * @param {Date} publicIds.start
 * @param {Date} publicIds.end
 */
Connection.prototype.setPublicIds = function(publicIds)
{
	return this.channelManager.setPublicIds(publicIds);
};

/**
 * Send single message to the specified public channel.
 *
 * @param {integer[]} users User ids the message receivers.
 * @param {string} moduleId Name of the module to receive message,
 * @param {string} command Command name.
 * @param {object} params Command parameters.
 * @param {integer} [expiry] Message expiry time in seconds.
 * @return {BX.Promise<bool>}
 */
Connection.prototype.sendMessage = function(users, moduleId, command, params, expiry)
{
	return this.sendMessageBatch([{
		users: users,
		moduleId: moduleId,
		command: command,
		params: params,
		expiry: expiry
	}]);
};

/**
 * Sends batch of messages to the multiple public channels.
 *
 * @param {object[]} messageBatch Array of messages to send.
 * @param  {int[]} messageBatch.users User ids the message receivers.
 * @param {string} messageBatch.moduleId Name of the module to receive message,
 * @param {string} messageBatch.command Command name.
 * @param {object} messageBatch.params Command parameters.
 * @param {integer} [messageBatch.expiry] Message expiry time in seconds.
 * @return {BX.Promise<bool>}
 */
Connection.prototype.sendMessageBatch = function(messageBatch)
{
	if(!this.isPublishingEnabled())
	{
		console.error('Client publishing is not supported or is disabled');
		return false;
	}

	let userIds = {};
	for(let i = 0; i < messageBatch.length; i++)
	{
		for(let j = 0; j < messageBatch[i].users.length; j++)
		{
			userIds[messageBatch[i].users[j]] = true;
		}
	}

	this.channelManager.getPublicIds(Object.keys(userIds)).then((publicIds) =>
	{
		let buffer = this.encodeMessageBatch(messageBatch, publicIds);
		this.connector.send(buffer);
	})
};

Connection.prototype.encodeMessageBatch = function(messageBatch, publicIds)
{
	let messages = [];
	messageBatch.forEach((messageFields) =>
	{
		let messageBody = {
			module_id: messageFields.moduleId,
			command: messageFields.command,
			params: messageFields.params
		};
		let message = IncomingMessage.create({
			receivers: this.createMessageReceivers(messageFields.users, publicIds),
			body: JSON.stringify(messageBody),
			expiry: messageFields.expiry || 0
		});
		messages.push(message);
	});

	let requestBatch = RequestBatch.create({
		requests: [{
			incomingMessages: {
				messages: messages
			}
		}]
	});

	return RequestBatch.encode(requestBatch).finish();
};

Connection.prototype.createMessageReceivers = function(users, publicIds)
{
	let result = [];
	for(let i = 0; i < users.length; i++)
	{
		let userId = users[i];
		if(!publicIds[userId] || !publicIds[userId].publicId)
		{
			throw new Error('Could not determine public id for user ' + userId);
		}

		result.push(Receiver.create({
			id: this.encodeId(publicIds[userId].publicId),
			signature: this.encodeId(publicIds[userId].signature)
		}))
	}
	return result;
};

/**
 * WebSocketConnectorDelegate methods
 */
Connection.prototype.onMessage = function (message)
{
	this.parseResponse(message.data);
};
Connection.prototype.onError = function (error, waitingRestore)
{
	if (error['HTTPResponseStatusCode'] == 400)
	{
		this.config.actual = false;
	}
	if (waitingRestore)
	{
		BX.postComponentEvent("failRestoreConnection");
	}

	this.connector.connect();
};
Connection.prototype.onClose = function (event, waitingRestore)
{
	let reason = event.reason;
	let code = event.code;

	switch (code)
	{
		case CloseReasons.CHANNEL_EXPIRED:
		case CloseReasons.CONFIG_EXPIRED:
		case CloseReasons.SERVER_RESTARTED:
		{
			this.updateConfig();
			break;
		}
		case CloseReasons.CONFIG_REPLACED:
		{
			this.connect();
			break;
		}
		case CloseReasons.SERVER_DIE:
		{
			this.updateConfig();
			break;
		}
		case CloseReasons.CODE_1000:
		{
			// mb offline or reload
			break;
		}
		default:
		{
			console.error('Connection.onClose: unexpected connection close (wait restore: '+(waitingRestore? 'Y': 'N')+')', event)
		}
	}
};
Connection.prototype.getPath = function ()
{
	if (!this.config.actual)
	{
		return '';
	}

	let path = isSecure? this.config.server.websocket_secure: this.config.server.websocket;
	if (!path)
	{
		return '';
	}

	let channels = [];
	for (let type in this.config.channels)
	{
		if (!this.config.channels.hasOwnProperty(type))
		{
			continue;
		}
		channels.push(this.config.channels[type].id);
	}

	path = path+'?CHANNEL_ID='+channels.join('/');

	if (this.session.mid)
	{
		path = path+"&mid=" + this.session.mid;
	}
	if (this.session.tag)
	{
		path = path+"&tag=" + this.session.tag;
	}
	if (this.session.time)
	{
		path = path+"&time=" + this.session.time;
	}
	if (this.config.server.mode === "shared")
	{
		path = path+"&clientId=" + this.config.clientId;
	}
	if (this.isProtobufSupported())
	{
		path = path+"&binaryMode=true";
	}

	return path;
};

/**
 * Connection methods
 */
Connection.prototype.connect = function (force)
{
	if (this.config.actual)
	{
		this.connector.connect(force);
	}
	else
	{
		this.updateConfig();
	}
};
Connection.prototype.disconnect = function (code, message)
{
	this.connector.disconnect(code, message)
};

/**
 * Debug methods
 */

Connection.prototype.getServerStatus = function ()
{
	console.info('Connection.getServerStatus: server is '+(this.config.server.server_enabled? 'enabled': 'disabled'));
};

Connection.prototype.capturePullEvent = function (status)
{
	if (typeof(status) == 'undefined')
	{
		status = !this.config.debug.log;
	}

	console.info('Connection.capturePullEvent: capture "Pull Event" '+(status? 'enabled': 'disabled'));
	this.config.debug.log = !!status;

	BX.componentParameters.set('PULL_DEBUG', this.config.debug.log);
};

Connection.prototype.capturePullEventSource = function (status)
{
	if (typeof(status) == 'undefined')
	{
		status = !this.connector.debug;
	}

	console.info('Connection.capturePullEventSource: capture "Pull Event Source" '+(status? 'enabled': 'disabled'));
	this.connector.debug = !!status;

	BX.componentParameters.set('PULL_DEBUG_SOURCE', this.connector.debug);
};

Connection.prototype.capturePullEventFunction = function (status)
{
	if (typeof(status) == 'undefined')
	{
		status = !this.config.debug.logFunction;
	}

	console.info('Connection.capturePullEventFunction: capture "Pull Event Function"  '+(status? 'enabled': 'disabled'));
	this.config.debug.logFunction = !!status;

	BX.componentParameters.set('PULL_DEBUG_FUNCTION', this.config.debug.logFunction);
};

Connection.prototype.getDebugInfo = function (logToConsole)
{
	logToConsole = !!logToConsole;

	let watchTags = [];
	for(let tagId in this.watchTagsQueue)
	{
		if(this.watchTagsQueue.hasOwnProperty(tagId))
		{
			watchTags.push(tagId);
		}
	}

	let text = "Connection.getDebugInfo:\n" +
		"================================\n"+
		"Revision: "+(REVISION)+"\n"+
		"UserId: "+CONFIG.USER_ID+" "+(CONFIG.USER_ID>0?'': '(guest)')+"\n"+
		"Queue Server: "+(this.config.server.server_enabled? 'Y': 'N')+"\n"+
		"\n"+
		"WebSocket status: "+(this.connector.socket? WebsocketReadyState[this.connector.socket.readyState]: WebsocketReadyState[3])+"\n"+
		"WebSocket try number: "+(this.connector.connectAttempt)+"\n"+
		"WebSocket path: "+this.getPath()+"\n"+
		"\n"+
		"Config state: "+(this.config.actual? 'OK': 'WAITING UPDATE')+"\n"+
		"Last message: "+(this.session.lastId > 0? this.session.lastId: '-')+"\n"+
		"Time last connect: "+(this.session.time)+"\n"+
		"Session message count: "+(this.session.messageCount)+"\n"+
		"Current time shift: "+(this.config.server.timeShift)+"\n\n"+
		"== Config channels ==\n"+JSON.stringify(this.config.channels)+"\n\n"+
		"== Config server ==\n"+JSON.stringify(this.config.server)+"\n\n"+
		"== Session == \n"+JSON.stringify(this.session)+"\n\n"+
		"Watch tags: \n"+JSON.stringify(watchTags)+"\n"+
		"================================";

	if (logToConsole)
	{
		console.info(text);
	}
	else
	{
		return text;
	}
};

Connection.prototype.sendDebugInfo = function ()
{
	let text = this.getDebugInfo(false);

	BX.postComponentEvent('onPullGetDebugInfoResult', [text]);
	BX.postWebEvent('onPullGetDebugInfoResult', {text}, true);
};

Connection.prototype.getSessionHistory = function ()
{
	let text = "Connection.getSessionHistory:\n" +
		"===================\n"+
		"Message received: "+this.session.messageCount+"\n"+
		"===================";

	for(let moduleId in this.session.history)
	{
		if (!this.session.history.hasOwnProperty(moduleId))
		{
			continue;
		}
		text = text + "\n" + moduleId + "\n";
		for(let commandName in this.session.history[moduleId])
		{
			if (!this.session.history[moduleId].hasOwnProperty(commandName))
			{
				continue;
			}
			text = text + ' | --- '+commandName+': '+this.session.history[moduleId][commandName]+"\n";
		}
	}

	text = text + "===================";
	console.info(text);
};

var ChannelManager = function ()
{
	this.publicIds = {};
};
/**
 *
 * @param {Array} users Array of user ids.
 * @return {BX.Promise}
 */
ChannelManager.prototype.getPublicIds = function(users)
{
	return new Promise((resolve) =>
	{
		let result = {};
		let now = new Date();
		let unknownUsers = [];

		for(let i = 0; i < users.length; i++)
		{
			let userId = users[i];
			if(this.publicIds[userId] && this.publicIds[userId]['end'] > now)
			{
				result[userId] = this.publicIds[userId];
			}
			else
			{
				unknownUsers.push(userId);
			}
		}

		if(unknownUsers.length === 0)
		{
			return resolve(result);
		}

		BX.rest.callMethod("pull.channel.public.list", {users: unknownUsers}).then((response) =>
		{
			if(response.error())
			{
				return resolve({});
			}
			let data = response.data();
			this.setPublicIds(Object.values(data));
			unknownUsers.forEach(userId => result[userId] = this.publicIds[userId]);

			resolve(result);
		});
	})
};
/**
 *
 * @param {object[]} publicIds
 * @param {integer} publicIds.user_id
 * @param {string} publicIds.public_id
 * @param {string} publicIds.signature
 * @param {Date} publicIds.start
 * @param {Date} publicIds.end
 */
ChannelManager.prototype.setPublicIds = function(publicIds)
{
	for(let i = 0; i < publicIds.length; i++)
	{
		let publicIdDescriptor = publicIds[i];
		let userId = publicIdDescriptor.user_id;
		this.publicIds[userId] = {
			userId: userId,
			publicId: publicIdDescriptor.public_id,
			signature: publicIdDescriptor.signature,
			start: new Date(publicIdDescriptor.start),
			end: new Date(publicIdDescriptor.end)
		}
	}
};