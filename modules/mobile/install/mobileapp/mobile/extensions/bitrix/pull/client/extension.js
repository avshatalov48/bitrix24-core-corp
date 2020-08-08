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
		BX.addCustomEvent("onAppActive", () =>
		{
			this.waitingConnectionAfterBackground = true;
			this.connect(true);
		});
		BX.addCustomEvent("onAppPaused", () => this.disconnect(1000, "App is in background"));
	}

	BX.addCustomEvent("onPullForceBackgroundConnect", () => {
		console.warn("Forced connection in background");
		if(Application.isBackground())
		{
			this.connect();
		}
	})
};

WebSocketConnector.prototype = {
	connectAttempt : 0,
	state : 0,
	connectionTimeoutId : null,
	connectionTimeoutTime : 0,
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

			this.createWebSocket()
		}, connectTimeout);
	},
	disconnect : function (code, message)
	{
		/**
		 * @var this.socket WebSocket
		 */
		if (this.socket !== null && this.socket.readyState === 1)
		{
			code = (code? code: 1000);
			this.socket.close(code, message);
		}
	},
	createWebSocket : function ()
	{
		let connectPath = this.delegate.getPath();
		if (connectPath)
		{
			this.socket = new WebSocket(connectPath);
			this.socket.onclose = this.onclose.bind(this);
			this.socket.onerror = this.onerror.bind(this);
			this.socket.onmessage = this.onmessage.bind(this);
			this.socket.onopen = this.onopen.bind(this);
		}
		else
		{
			this.delegate.updateConfig();
		}
	},
	sendPullStatus : function (status)
	{
		if(this.offlineTimeout)
		{
			clearTimeout(this.offlineTimeout);
			this.offlineTimeout = null;
		}
		BX.postWebEvent("onPullStatus", {status : status});
		BX.postComponentEvent("onPullStatus", [{status : status}]);
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

		this.sendPullStatus(PullStatus.Offline);

		this.delegate.onError.apply(this.delegate, [arguments[0], this.waitingConnectionAfterBackground]);
		this.waitingConnectionAfterBackground = false;
	},
	onmessage : function ()
	{
		console.log("WebSocket -> onmessage", this.debug? arguments: true);
		this.delegate.onMessage.apply(this.delegate, arguments);
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
		messageCount: 0
	};

	this.connector = new WebSocketConnector(this, new WebSocketConnectorParams());

	this.expireCheckInterval = 60000;
	this.expireCheckTimeoutId = null;
	this.watchTagsQueue = {};
	this.watchUpdateInterval = 1740000;
	this.watchForceUpdateInterval = 5000;
	this.configRequestAfterErrorInterval = 60000;

	BX.addCustomEvent("onPullGetDebugInfo", this.sendDebugInfo.bind(this));
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
Connection.prototype.parseResponse = function (response)
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

		let data = BX.parseJSON(dataArray[i]);

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
};
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
			BX.postComponentEvent('onPullClientEvent-' + moduleId, [command, message.params, message.extra, moduleId], true);
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