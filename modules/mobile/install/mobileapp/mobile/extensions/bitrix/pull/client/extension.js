(function ()
{
	const REVISION = 3; // api revision - check module/pull/include.php

	const CloseReasons = {
		CONFIG_REPLACED: 3000,
		CHANNEL_EXPIRED: 3001,
		SERVER_RESTARTED: 3002,
		CONFIG_EXPIRED: 3003,
		SERVER_DIE: 1001,
		CODE_1000: 1000
	};
	const SystemCommands = {
		CHANNEL_EXPIRE: 0,
		CONFIG_EXPIRE: 1,
		SERVER_RESTART: 2
	};
	const PullStatus = {
		Online: 'online',
		Offline: 'offline',
		Connecting: 'connect'
	};
	const WebsocketReadyState = {
		0: 'CONNECTING',
		1: 'OPEN',
		2: 'CLOSING',
		3: 'CLOSED',
	};
	const SenderType = {
		Unknown: 0,
		Client: 1,
		Backend: 2
	};

	const isSecure = currentDomain.indexOf('https') === 0;

	const CONFIG = {
		USER_ID: BX.componentParameters.get('USER_ID', 0),
		SITE_ID: BX.componentParameters.get('SITE_ID', 's1'),
		LANGUAGE_ID: BX.componentParameters.get('LANGUAGE_ID', 'en'),
		PULL_CONFIG: BX.componentParameters.get('PULL_CONFIG', {}),
	};

	const MAX_IDS_TO_STORE = 10;

// Protobuf message models
	const Response = protobuf ? protobuf.roots['push-server']['Response'] : null;
	const ResponseBatch = protobuf ? protobuf.roots['push-server']['ResponseBatch'] : null;
	const Request = protobuf ? protobuf.roots['push-server']['Request'] : null;
	const RequestBatch = protobuf ? protobuf.roots['push-server']['RequestBatch'] : null;
	const IncomingMessagesRequest = protobuf ? protobuf.roots['push-server']['IncomingMessagesRequest'] : null;
	const IncomingMessage = protobuf ? protobuf.roots['push-server']['IncomingMessage'] : null;
	const Receiver = protobuf ? protobuf.roots['push-server']['Receiver'] : null;

	const JSON_RPC_VERSION = "2.0"
	const JSON_RPC_PING = "ping"
	const JSON_RPC_PONG = "pong"

	const PING_TIMEOUT = 5;

	const RpcError = {
		Parse: {code: -32700, message: "Parse error"},
		InvalidRequest: {code: -32600, message: "Invalid Request"},
		MethodNotFound: {code: -32601, message: "Method not found"},
		InvalidParams: {code: -32602, message: "Invalid params"},
		Internal: {code: -32603, message: "Internal error"},
	};

	const RpcMethod = {
		Publish: "publish",
		Subscribe: "subscribe",
	}

	const InternalChannel = {
		StatusChange: "internal:user_status",
	}

	/**
	 * Interface for delegate of connector
	 */
	class WebSocketConnectorDelegate
	{
		getPath()
		{ /** must be overridden in children class **/
		}

		isBinary()
		{ /** must be overridden in children class **/
		}

		updateConfig()
		{ /** must be overridden in children class **/
		}

		onError()
		{ /** must be overridden in children class **/
		}

		onClose()
		{ /** must be overridden in children class **/
		}

		onMessage()
		{ /** must be overridden in children class **/
		}

		onOpen()
		{ /** must be overridden in children class **/
		}
	}

	/**
	 * Class for settings of connector
	 * @param initParams
	 * @constructor
	 */
	class WebSocketConnectorParams
	{
		constructor(initParams)
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
		}
	}

	class WebSocketConnector
	{
		/**
		 * @param {WebSocketConnectorDelegate} delegate
		 * @param {WebSocketConnectorParams} params
		 */
		constructor(delegate, params)
		{
			this.connectAttempt = 0;
			this.state = 0;
			this.connectionTimeoutId = null;
			this.connectionTimeoutTime = 0;
			this.currentStatus = 'offline';

			this.params = params;
			this.connectAttempt = 0;
			this.debug = BX.componentParameters.get('PULL_DEBUG_SOURCE', false);
			this.connecting = false;
			this._socket = null;
			this.delegate = delegate;
			this.offline = false;

			this.hasActiveCall = false;
			this.hasActiveTelephonyCall = false;

			BX.addCustomEvent("online", () =>
			{
				this.offline = false;
				if (!Application.isBackground())
				{
					this.connect();
				}
			});
			BX.addCustomEvent("offline", () =>
			{
				this.offline = true;
			});
			if (this.params.disconnectOnBackground)
			{
				BX.addCustomEvent("onAppActive", this.onAppActive.bind(this));
				BX.addCustomEvent("onAppPaused", this.onAppPaused.bind(this));
				BX.addCustomEvent("CallEvents::hasActiveCall", this.onActiveCallStateChange.bind(this))
				BX.addCustomEvent("CallEvents::hasActiveTelephonyCall", this.onActiveTelephonyCallStateChange.bind(this))
			}

			BX.addCustomEvent("onPullForceBackgroundConnect", () =>
			{
				console.warn("Forced connection in background");
				if (Application.isBackground())
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

			BX.addCustomEvent("onPullGetStatus", () =>
			{
				BX.postWebEvent("onPullStatus", {status: this.currentStatus});
				BX.postComponentEvent("onPullStatus", [{status: this.currentStatus}]);
			})
		}

		get socket()
		{
			return this._socket
		}

		set socket(value)
		{
			if (this._socket && this._socket.readyState === 1)
			{
				this._socket.close(1000, "normal");
			}

			this._socket = value;
		}

		isConnected()
		{
			return this.socket && (this.socket.readyState === 1);
		}

		connect(force)
		{
			if (!force)
			{
				if (this.isConnected())
				{
					console.warn("WebSocketConnector.connect: " +
						"WebSocket is already connected. Use second argument to force reconnect");
					return;
				}

				if (this.connectionTimeoutId && this.connectionTimeoutTime + 60000 > (new Date()).getTime())
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
					this.createWebSocket(connectPath, this.delegate.isBinary())
				}
				else
				{
					this.delegate.updateConfig();
				}
			}, connectTimeout);
		}

		disconnect(code, message)
		{
			console.trace("disconnect", code, message)
			/**
			 * @var this.socket WebSocket
			 */
			if (this.socket !== null && this.socket.readyState === 1)
			{
				code = (code ? code : 1000);
				this.socket.close(code, message);
			}
		}

		createWebSocket(connectPath, binary)
		{
			this.socket = new WebSocket(connectPath);
			if (binary)
			{
				this.socket.binaryType = 'arraybuffer';
			}

			this.socket.onclose = this.onclose.bind(this);
			this.socket.onerror = this.onerror.bind(this);
			this.socket.onmessage = this.onmessage.bind(this);
			this.socket.onopen = this.onopen.bind(this);
		}

		sendPullStatus(status, additional)
		{
			if (!additional)
			{
				additional = {};
			}
			this.currentStatus = status;
			if (this.offlineTimeout)
			{
				clearTimeout(this.offlineTimeout);
				this.offlineTimeout = null;
			}

			BX.postWebEvent("onPullStatus", {status: status, additional: additional});
			BX.postComponentEvent("onPullStatus", [{status: status, additional: additional}]);
		}

		/**
		 * Sends some data to the server via websocket connection.
		 * @param {ArrayBuffer} buffer Data to send.
		 * @return {boolean}
		 */
		send(buffer)
		{
			if (!this.socket || this.socket.readyState !== 1)
			{
				console.error("Send error: WebSocket is not connected");
				return false;
			}

			if (buffer instanceof ArrayBuffer)
			{
				this.socket.send(new Uint8Array(buffer));
			}
			else
			{
				this.socket.send(buffer);
			}

			return true;
		}

		onopen()
		{
			this.state = "connected";
			this.connectAttempt = 0;
			console.info("WebSocket -> onopen");

			this.sendPullStatus(PullStatus.Online);

			this.delegate.onOpen.apply(this.delegate, arguments);
		}

		onclose()
		{
			console.info("WebSocket -> onclose", arguments);

			if (
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
		}

		onerror()
		{
			console.error("WebSocket -> onerror", arguments);

			this.sendPullStatus(PullStatus.Offline, {isError: true});

			this.delegate.onError.apply(this.delegate, [arguments[0], this.waitingConnectionAfterBackground]);
			this.waitingConnectionAfterBackground = false;
		}

		onmessage()
		{
			this.delegate.onMessage.apply(this.delegate, arguments);
		}

		onActiveCallStateChange(hasActiveCall)
		{
			this.hasActiveCall = hasActiveCall;
			if (!this.hasActiveCall && !this.hasActiveTelephonyCall && Application.isBackground())
			{
				this.disconnect(1000, "App is in background")
			}
		}

		onActiveTelephonyCallStateChange(hasActiveTelephonyCall)
		{
			console.warn('onActiveTelephonyCallStateChange', hasActiveTelephonyCall);
			this.hasActiveTelephonyCall = hasActiveTelephonyCall;
			if (!this.hasActiveCall && !this.hasActiveTelephonyCall && Application.isBackground())
			{
				this.disconnect(1000, "App is in background")
			}
		}

		onAppPaused()
		{
			console.warn("onAppPaused; this.hasActiveCall: " + (this.hasActiveCall ? "true" : "false") + "; this.hasActiveTelephonyCall: " + (this.hasActiveTelephonyCall ? "true" : "false"));
			if (!this.hasActiveCall && !this.hasActiveTelephonyCall)
			{
				this.disconnect(1000, "App is in background")
			}
		}

		onAppActive()
		{
			console.warn("onAppActive; this.hasActiveCall: " + (this.hasActiveCall ? "true" : "false") + "; this.hasActiveTelephonyCall: " + (this.hasActiveTelephonyCall ? "true" : "false"));
			this.waitingConnectionAfterBackground = true;
			this.connect(!this.hasActiveCall && !this.hasActiveTelephonyCall);
		}
	}

	/**
	 *
	 * @param config
	 * @constructor
	 */
	class Connection extends WebSocketConnectorDelegate
	{
		constructor(config)
		{
			config = config || CONFIG.PULL_CONFIG;

			super(config);

			this.config = {
				channels: {},
				server: {
					timeShift: 0
				},
				debug: {
					log: BX.componentParameters.get('PULL_DEBUG', true),
					logFunction: BX.componentParameters.get('PULL_DEBUG_FUNCTION', false)
				},
				clientId: null,
				jwt: null,
				actual: false,
			};
			this.session = {
				mid: null,
				tag: null,
				time: null,
				lastId: 0,
				history: {},
				lastMessageIds: [],
				messageCount: 0
			};

			this.connector = new WebSocketConnector(this, new WebSocketConnectorParams());
			this.channelManager = new ChannelManager();

			this.jsonRpcAdapter = new JsonRpc({
				connector: this.connector,
				handlers: {
					"incoming.message": this.handleRpcIncomingMessage.bind(this),
				}
			});

			this.expireCheckInterval = 60000;
			this.expireCheckTimeoutId = null;
			this.watchTagsQueue = {};
			this.watchUpdateInterval = 1740000;
			this.watchForceUpdateInterval = 5000;
			this.configRequestAfterErrorInterval = 60000;

			BX.addCustomEvent("Pull::internalRpcRequest", this.onInternalRpcRequest.bind(this));
			BX.addCustomEvent("onPullGetDebugInfo", this.sendDebugInfo.bind(this));
			BX.addCustomEvent("onPullGetPublishingState", this.sendPublishingState.bind(this));
			BX.addCustomEvent("onPullSetPublicIds", this.setPublicIds.bind(this));
			BX.addCustomEvent("onPullSendMessage", this.sendMessage.bind(this));
			BX.addCustomEvent("onPullExtendWatch", data => this.extendWatch(data.id, data.force));
			BX.addCustomEvent("onPullClearWatch", data => this.clearWatchTag(data.id));
			BX.addCustomEvent("onUpdateServerTime", this.updateTimeShift.bind(this));

			this.onPingTimeoutHandler = this.onPingTimeout.bind(this);

			this.internalRpcHandlers = {
				'getDebugInfo': () => this.getDebugInfo(),
				'getUsersLastSeen': ({userList}) => this.getUsersLastSeen(userList),
			}

			if (config)
			{
				this.setConfig(config);
			}
		}

		get socket()
		{
			return this.connector.socket;
		}

		/**
		 * Configuration
		 */
		start()
		{
			this.connect();
			this.updateWatch();
			this.checkChannelExpire();
		}

		setConfig(config)
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

			this.config.jwt = config.jwt || '';
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
				console.warn('Connection.setConfig: reload scripts because revision up (' + REVISION + ' -> ' + config.api.revision_mobile + ')');
				CONFIG.PULL_CONFIG.api.revision_mobile = config.api.revision_mobile;
				BX.componentParameters.set('PULL_CONFIG', CONFIG.PULL_CONFIG);

				reloadAllScripts();
				return false;
			}

			return true;
		}

		getChannel(type)
		{
			return this.config.channels[type];
		}

		isChannelsExpired()
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
					console.info("Connection.isChannelsExpired: " + type + " channel was expired.");
					result = true;
					break;
				}
			}

			return result;
		}

		checkChannelExpire()
		{
			clearTimeout(this.expireCheckTimeoutId);
			this.expireCheckTimeoutId = setTimeout(this.checkChannelExpire.bind(this), this.expireCheckInterval);
			if (this.isChannelsExpired())
			{
				this.connector.disconnect(CloseReasons.CONFIG_EXPIRED, "channel was expired");
			}
		}

		configRequest()
		{
			let promise = new BX.Promise();
			BX.rest.callBatch({
				serverTime: ['server.time'],
				configGet: ['pull.config.get', {'CACHE': 'N'}],
			}, (result) =>
			{
				// serverTime block
				if (result.serverTime && !result.serverTime.error())
				{
					this.config.server.timeShift = Math.floor((new Date().getTime() - new Date(result.serverTime.data()).getTime()) / 1000);
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
		}

		updateConfig()
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
					this.updateConfigTimeout = setTimeout(() =>
					{
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
		}

		getServerVersion()
		{
			return (this.config && this.config.server) ? this.config.server.version : 0;
		}

		isPublishingEnabled()
		{
			if (!this.isProtobufSupported())
			{
				return false;
			}

			return (this.config && this.config.server && this.config.server.publish_enabled === true);
		}

		sendPublishingState()
		{
			BX.postComponentEvent("onPullPublishingState", [this.isPublishingEnabled()]);
		}

		isProtobufSupported()
		{
			return (this.getServerVersion() > 3 && ("protobuf" in window));
		}

		isJsonRpc()
		{
			return (this.getServerVersion() >= 5);
		}

		handleRpcIncomingMessage(messageFields)
		{
			console.log("incoming message: ", messageFields);

			this.session.mid = messageFields.mid;
			let body = messageFields.body;

			if (!messageFields.body.extra)
			{
				body.extra = {};
			}
			body.extra.sender = messageFields.sender;

			if ("user_params" in messageFields && BX.type.isPlainObject(messageFields.user_params))
			{
				Object.assign(body.params, messageFields.user_params)
			}

			if ("dictionary" in messageFields && BX.type.isPlainObject(messageFields.dictionary))
			{
				Object.assign(body.params, messageFields.dictionary)
			}

			if (this.checkDuplicate(messageFields.mid))
			{
				this.addMessageToStat(body);
				this.trimDuplicates();
				this.broadcastMessage(body)
			}

			return {};
		}

		onJsonRpcPing()
		{
			this.updatePingWaitTimeout();
			this.connector.send(JSON_RPC_PONG)
		}

		handleIncomingEvents(events)
		{
			let messages = [];
			if (events.length === 0)
			{
				this.session.mid = null;
				return;
			}

			for (let i = 0; i < events.length; i++)
			{
				let event = events[i];
				this.updateSessionFromEvent(event);
				if (event.mid && !this.checkDuplicate(event.mid))
				{
					continue;
				}

				this.addMessageToStat(event.text);
				messages.push(event.text);
			}
			this.trimDuplicates();
			this.broadcastMessages(messages);
		}

		updateSessionFromEvent(event)
		{
			this.session.mid = event.mid || null;
			this.session.tag = event.tag || null;
			this.session.time = event.time || null;
		}

		checkDuplicate(mid)
		{
			if (this.session.lastMessageIds.includes(mid))
			{
				console.warn("Duplicate message " + mid + " skipped");
				return false;
			}
			else
			{
				console.log("Event " + mid + " received");
				this.session.lastMessageIds.push(mid);
				return true;
			}
		}

		trimDuplicates()
		{
			if (this.session.lastMessageIds.length > MAX_IDS_TO_STORE)
			{
				this.session.lastMessageIds = this.session.lastMessageIds.slice(-MAX_IDS_TO_STORE);
			}
		}

		addMessageToStat(message)
		{
			if (!this.session.history[message.module_id])
			{
				this.session.history[message.module_id] = {};
			}
			if (!this.session.history[message.module_id][message.command])
			{
				this.session.history[message.module_id][message.command] = 0;
			}
			this.session.history[message.module_id][message.command]++;

			this.session.messageCount++;
		}

		extractMessages(pullEvent)
		{
			if (pullEvent instanceof ArrayBuffer)
			{
				return this.extractProtobufMessages(pullEvent);
			}
			else if (Utils.isNotEmptyString(pullEvent))
			{
				return this.extractPlainTextMessages(pullEvent)
			}
		}

		extractProtobufMessages(pullEvent)
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
						} catch (e)
						{
							console.error("Pull: Could not parse message body", e);
							continue;
						}

						if (!messageFields.extra)
						{
							messageFields.extra = {}
						}
						messageFields.extra.sender = {
							type: message.sender.type
						};

						if (message.sender.id instanceof Uint8Array)
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
			} catch (e)
			{
				console.error("Pull: Could not parse message", e)
			}
			return result;
		}

		extractPlainTextMessages(pullEvent)
		{
			let result = [];
			let dataArray = pullEvent.match(/#!NGINXNMS!#(.*?)#!NGINXNME!#/gm);
			if (dataArray === null)
			{
				text = "\n========= PULL ERROR ===========\n" +
					"Error type: parseResponse error parsing message\n" +
					"\n" +
					"Data string: " + pullEvent + "\n" +
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
				} catch (e)
				{
					continue;
				}

				result.push(data);
			}
			return result;
		}

		/**
		 * Converts message id from byte[] to string
		 * @param {Uint8Array} encodedId
		 * @return {string}
		 */
		decodeId(encodedId)
		{
			if (!(encodedId instanceof Uint8Array))
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
		}

		/**
		 * Converts message id from hex-encoded string to byte[]
		 * @param {string} id Hex-encoded string.
		 * @return {Uint8Array}
		 */
		encodeId(id)
		{
			if (!id)
			{
				return new Uint8Array();
			}

			let result = [];
			for (let i = 0; i < id.length; i += 2)
			{
				result.push(parseInt(id.substr(i, 2), 16));
			}

			return new Uint8Array(result);
		}

		broadcastMessages(messages)
		{
			messages.forEach(message => this.broadcastMessage(message));
		}

		broadcastMessage(message)
		{
			if (this.config.debug.log)
			{
				console.log("Connection.broadcastMessage: receive message");
				console.log(message);
			}

			let moduleId = message.module_id = message.module_id.toLowerCase();
			let command = message.command;

			if (!message.extra)
			{
				message.extra = {};
			}

			if (message.extra.server_time_unix)
			{
				message.extra.server_time_ago = (((new Date()).getTime() - (message.extra.server_time_unix * 1000)) / 1000) - this.config.server.timeShift;
				message.extra.server_time_ago = message.extra.server_time_ago > 0 ? message.extra.server_time_ago : 0;
			}

			if (message.extra.sender && message.extra.sender.type === SenderType.Client)
			{
				BX.postComponentEvent('onPullClientEvent-' + moduleId, [command, message.params, message.extra, moduleId]);
				BX.postWebEvent('onPullClient-' + moduleId, {
					command: message.command,
					params: message.params,
					extra: message.extra,
					module_id: message.module_id,
				}, true);
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
							console.info("Connection.broadcastMessages: new config for " + message.params.channel.type + " channel set:\n", this.config.channels[message.params.channel.type]);
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
				BX.postWebEvent('onPullOnline', {
					command: message.command,
					params: message.params,
					extra: message.extra
				}, true);
			}
			else
			{
				BX.postComponentEvent('onPullEvent-' + message.module_id, [message.command, message.params, message.extra, message.module_id]);
				BX.postWebEvent('onPull-' + message.module_id, {
					command: message.command,
					params: message.params,
					extra: message.extra,
					module_id: message.module_id
				}, true);
				BX.postWebEvent('onPull', {
					module_id: message.module_id,
					command: message.command,
					params: message.params,
					extra: message.extra
				}, true);

				if (this.config.debug.logFunction)
				{
					let text = "Connection.broadcastMessages: get commands for use in console\n" +
						"==== for native component ==\n" +
						'BX.postComponentEvent("onPullEvent-' + message.module_id + '", ["' + message.command + '", ' + JSON.stringify(message.params) + ', ' + JSON.stringify(message.extra) + ']);' + "\n" +
						"\n" +
						"==== for mobile browser ==\n" +
						'BX.postWebEvent("onPull-' + message.module_id + '", {command: "' + message.command + '", params: ' + JSON.stringify(message.params) + ', extra: ' + JSON.stringify(message.extra) + ']}, true);' + "\n" +
						'BX.postWebEvent("onPull", {module_id: "' + message.module_id + '", command: "' + message.command + '", params: ' + JSON.stringify(message.params) + ', extra: ' + JSON.stringify(message.extra) + ']}, true);';
					console.info(text);
				}
			}

			const messageRevision = Number(message.extra.revision_mobile)
			if (messageRevision > REVISION)
			{
				console.warn('Connection.broadcastMessage: reload scripts because revision up (' + REVISION + ' -> ' + messageRevision + ')');
				CONFIG.PULL_CONFIG.api.revision_mobile = messageRevision;
				BX.componentParameters.set('PULL_CONFIG', CONFIG.PULL_CONFIG);

				reloadAllScripts();
				return false;
			}
		}

		extendWatch(tag, force)
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
		}

		updateWatch(force)
		{
			clearTimeout(this.watchUpdateTimeout);
			this.watchUpdateTimeout = setTimeout(() =>
			{
				let watchTags = [];
				for (let tagId in this.watchTagsQueue)
				{
					if (this.watchTagsQueue.hasOwnProperty(tagId))
					{
						watchTags.push(tagId);
					}
				}
				if (watchTags.length > 0)
				{
					console.info("Connection.updateWatch: send request for extend", watchTags);
					BX.rest.callMethod('pull.watch.extend', {'TAGS': watchTags}).then((result) =>
					{
						let updatedTags = result.data();
						console.info("Connection.updateWatch: extend tags result", updatedTags);
						for (let tagId in updatedTags)
						{
							if (updatedTags.hasOwnProperty(tagId) && !updatedTags[tagId])
							{
								this.clearWatchTag(tagId);
							}
						}
						this.updateWatch();
					}).catch(() =>
					{
						this.updateWatch();
					})
				}
				else if (force)
				{
					console.info("Connection.updateWatch: nothing to update");
				}
			}, force ? this.watchForceUpdateInterval : this.watchUpdateInterval);
		}

		clearWatchTag(tagId)
		{
			delete this.watchTagsQueue[tagId];
		}

		updateTimeShift(serverTime)
		{
			if (!serverTime)
			{
				return false;
			}

			let timeShift = Math.floor((new Date().getTime() - new Date(serverTime).getTime()) / 1000);
			if (this.config.server.timeShift != timeShift)
			{
				console.warn('Connection.updateServerTime: time shift is changed (' + this.config.server.timeShift + ' -> ' + timeShift + ')');
				this.config.server.timeShift = timeShift;
			}

			return true;
		}

		/**
		 *
		 * @param {object[]} publicIds
		 * @param {integer} publicIds.user_id
		 * @param {string} publicIds.public_id
		 * @param {string} publicIds.signature
		 * @param {Date} publicIds.start
		 * @param {Date} publicIds.end
		 */
		setPublicIds(publicIds)
		{
			return this.channelManager.setPublicIds(publicIds);
		}

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
		sendMessage(users, moduleId, command, params, expiry)
		{
			const message = {
				userList: users,
				body: {
					module_id: moduleId,
					command: command,
					params: params,
				},
				expiry: expiry
			};

			if (this.isJsonRpc())
			{
				return this.jsonRpcAdapter.executeOutgoingRpcCommand(RpcMethod.Publish, message);
			}
			else
			{

				return this.sendMessageBatch([message]);
			}
		}

		/**
		 * Sends batch of messages to the multiple public channels.
		 *
		 * @param {object[]} messageBatch Array of messages to send.
		 * @param  {int[]} messageBatch.userList User ids the message receivers.
		 * @param {string} messageBatch.moduleId Name of the module to receive message,
		 * @param {string} messageBatch.command Command name.
		 * @param {object} messageBatch.params Command parameters.
		 * @param {integer} [messageBatch.expiry] Message expiry time in seconds.
		 * @return {BX.Promise<bool>}
		 */
		sendMessageBatch(messageBatch)
		{
			if (!this.isPublishingEnabled())
			{
				console.error('Client publishing is not supported or is disabled');
				return false;
			}

			if (this.isJsonRpc())
			{
				let rpcRequest = this.jsonRpcAdapter.createPublishRequest(messageBatch);
				return this.connector.send(JSON.stringify(rpcRequest));
			}
			else
			{
				let userIds = {};
				for (let i = 0; i < messageBatch.length; i++)
				{
					if (messageBatch[i].userList)
					{
						for (let j = 0; j < messageBatch[i].userList.length; j++)
						{
							userIds[messageBatch[i].userList[j]] = true;
						}
					}
				}
				this.channelManager.getPublicIds(Object.keys(userIds)).then((publicIds) =>
				{
					return this.connector.send(this.encodeMessageBatch(messageBatch, publicIds));
				})
			}
		}

		subscribeUserStatusChange()
		{
			return this.executeSubscribeCommand([InternalChannel.StatusChange]);
		}

		executeSubscribeCommand(channelList)
		{
			return this.jsonRpcAdapter.executeOutgoingRpcCommand(RpcMethod.Subscribe, {channelList});
		}

		/**
		 * Returns "last seen" time in seconds for the users. Result format: Object{userId: int}
		 * If the user is currently connected - will return 0.
		 * If the user if offline - will return diff between current timestamp and last seen timestamp in seconds.
		 * If the user was never online - the record for user will be missing from the result object.
		 *
		 * @param {integer[]} userList Optional. If empty - returns all known to the server host users.
		 * @returns {Promise}
		 */
		getUsersLastSeen(userList)
		{
			return this.jsonRpcAdapter.executeOutgoingRpcCommand("getUsersLastSeen", {
				userList: userList
			});
		};

		encodeMessageBatch(messageBatch, publicIds)
		{
			let messages = [];
			messageBatch.forEach((messageFields) =>
			{
				let message = IncomingMessage.create({
					receivers: this.createMessageReceivers(messageFields.userList, publicIds),
					body: JSON.stringify(messageFields.body),
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
		}

		createMessageReceivers(users, publicIds)
		{
			let result = [];
			for (let i = 0; i < users.length; i++)
			{
				let userId = users[i];
				if (!publicIds[userId] || !publicIds[userId].publicId)
				{
					throw new Error('Could not determine public id for user ' + userId);
				}

				result.push(Receiver.create({
					id: this.encodeId(publicIds[userId].publicId),
					signature: this.encodeId(publicIds[userId].signature)
				}))
			}
			return result;
		}

		/**
		 * WebSocketConnectorDelegate methods
		 */
		onMessage(message)
		{
			let data = message.data;
			if (this.isJsonRpc())
			{
				(data === JSON_RPC_PING) ? this.onJsonRpcPing() : this.jsonRpcAdapter.parseJsonRpcMessage(data);
			}
			else
			{
				const events = this.extractMessages(data);
				this.handleIncomingEvents(events);
			}
		}

		onError(error, waitingRestore)
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
		}

		onClose(event, waitingRestore)
		{
			let reason = event.reason;
			let code = event.code;

			this.clearPingWaitTimeout();
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
					console.error('Connection.onClose: unexpected connection close (wait restore: ' + (waitingRestore ? 'Y' : 'N') + ')', event)
				}
			}
		}

		updatePingWaitTimeout()
		{
			clearTimeout(this.pingWaitTimeout);
			this.pingWaitTimeout = setTimeout(this.onPingTimeoutHandler, PING_TIMEOUT * 2 * 1000)
		}

		clearPingWaitTimeout()
		{
			clearTimeout(this.pingWaitTimeout);
			this.pingWaitTimeout = null;
		}

		onPingTimeout()
		{
			this.pingWaitTimeout = null;
			if (!this.connector.isConnected())
			{
				return;
			}

			console.warn("No pings are received in " + PING_TIMEOUT * 2 + " seconds. Reconnecting")
			this.disconnect("1000", "connection stuck");
			this.connector.connect(true);
		}

		getPath()
		{
			if (!this.config.actual)
			{
				return '';
			}

			let path = isSecure ? this.config.server.websocket_secure : this.config.server.websocket;
			if (!path)
			{
				return '';
			}

			if (typeof (this.config.jwt) == 'string' && this.config.jwt !== '')
			{
				path = path + '?token=' + this.config.jwt;
			}
			else
			{
				let channels = [];
				for (let type in this.config.channels)
				{
					if (!this.config.channels.hasOwnProperty(type))
					{
						continue;
					}
					channels.push(this.config.channels[type].id);
				}

				path = path + '?CHANNEL_ID=' + channels.join('/');
			}

			if (this.session.mid)
			{
				path = path + "&mid=" + this.session.mid;
			}
			if (this.session.tag)
			{
				path = path + "&tag=" + this.session.tag;
			}
			if (this.session.time)
			{
				path = path + "&time=" + this.session.time;
			}
			if (this.config.server.mode === "shared")
			{
				path = path + "&clientId=" + this.config.clientId;
			}

			if (this.isJsonRpc())
			{
				path = path + "&jsonRpc=true";
			}
			else if (this.isProtobufSupported())
			{
				path = path + "&binaryMode=true";
			}

			return path;
		}

		isBinary()
		{
			return this.isProtobufSupported() && !this.isJsonRpc();
		}

		/**
		 * Connection methods
		 */
		connect(force)
		{
			if (this.config.actual)
			{
				this.connector.connect(force);
			}
			else
			{
				this.updateConfig();
			}
		}

		disconnect(code, message)
		{
			this.clearPingWaitTimeout();
			this.connector.disconnect(code, message)
		}

		/**
		 * Debug methods
		 */
		getServerStatus()
		{
			console.info('Connection.getServerStatus: server is ' + (this.config.server.server_enabled ? 'enabled' : 'disabled'));
		}

		capturePullEvent(status)
		{
			if (typeof (status) == 'undefined')
			{
				status = !this.config.debug.log;
			}

			console.info('Connection.capturePullEvent: capture "Pull Event" ' + (status ? 'enabled' : 'disabled'));
			this.config.debug.log = !!status;

			BX.componentParameters.set('PULL_DEBUG', this.config.debug.log);
		}

		capturePullEventSource(status)
		{
			if (typeof (status) == 'undefined')
			{
				status = !this.connector.debug;
			}

			console.info('Connection.capturePullEventSource: capture "Pull Event Source" ' + (status ? 'enabled' : 'disabled'));
			this.connector.debug = !!status;

			BX.componentParameters.set('PULL_DEBUG_SOURCE', this.connector.debug);
		}

		capturePullEventFunction(status)
		{
			if (typeof (status) == 'undefined')
			{
				status = !this.config.debug.logFunction;
			}

			console.info('Connection.capturePullEventFunction: capture "Pull Event Function"  ' + (status ? 'enabled' : 'disabled'));
			this.config.debug.logFunction = !!status;

			BX.componentParameters.set('PULL_DEBUG_FUNCTION', this.config.debug.logFunction);
		}

		getDebugInfo(logToConsole)
		{
			logToConsole = !!logToConsole;

			let watchTags = [];
			for (let tagId in this.watchTagsQueue)
			{
				if (this.watchTagsQueue.hasOwnProperty(tagId))
				{
					watchTags.push(tagId);
				}
			}

			let text = "Connection.getDebugInfo:\n" +
				"================================\n" +
				"Revision: " + (REVISION) + "\n" +
				"UserId: " + CONFIG.USER_ID + " " + (CONFIG.USER_ID > 0 ? '' : '(guest)') + "\n" +
				"Queue Server: " + (this.config.server.server_enabled ? 'Y' : 'N') + "\n" +
				"\n" +
				"WebSocket status: " + (this.connector.socket ? WebsocketReadyState[this.connector.socket.readyState] : WebsocketReadyState[3]) + "\n" +
				"WebSocket try number: " + (this.connector.connectAttempt) + "\n" +
				"WebSocket path: " + this.getPath() + "\n" +
				"\n" +
				"Config state: " + (this.config.actual ? 'OK' : 'WAITING UPDATE') + "\n" +
				"Last message: " + (this.session.lastId > 0 ? this.session.lastId : '-') + "\n" +
				"Time last connect: " + (this.session.time) + "\n" +
				"Session message count: " + (this.session.messageCount) + "\n" +
				"Current time shift: " + (this.config.server.timeShift) + "\n\n" +
				"== Config channels ==\n" + JSON.stringify(this.config.channels) + "\n\n" +
				"== Config server ==\n" + JSON.stringify(this.config.server) + "\n\n" +
				"== Session == \n" + JSON.stringify(this.session) + "\n\n" +
				"Watch tags: \n" + JSON.stringify(watchTags) + "\n" +
				"================================";

			if (logToConsole)
			{
				console.info(text);
			}
			else
			{
				return text;
			}
		}

		onInternalRpcRequest(e)
		{
			const {id, method, params} = e;

			if (!this.internalRpcHandlers.hasOwnProperty(method))
			{
				console.error("Unknown internal RPC method", method);
				return;
			}

			const result = this.internalRpcHandlers[method].call(this, params, id);
			if (result instanceof Promise)
			{
				result.then(successResult => {
					BX.postComponentEvent('Pull::internalRpcResponse', [{id, result: successResult}]);
				}).catch(error => {
					BX.postComponentEvent('Pull::internalRpcResponse', [{id, error}]);
				})
			}
			else
			{
				BX.postComponentEvent('Pull::internalRpcResponse', [{id, result}]);
			}
		}

		sendDebugInfo()
		{
			let text = this.getDebugInfo(false);

			BX.postComponentEvent('onPullGetDebugInfoResult', [text]);
			BX.postWebEvent('onPullGetDebugInfoResult', {text}, true);
		}

		getSessionHistory()
		{
			let text = "Connection.getSessionHistory:\n" +
				"===================\n" +
				"Message received: " + this.session.messageCount + "\n" +
				"===================";

			for (let moduleId in this.session.history)
			{
				if (!this.session.history.hasOwnProperty(moduleId))
				{
					continue;
				}
				text = text + "\n" + moduleId + "\n";
				for (let commandName in this.session.history[moduleId])
				{
					if (!this.session.history[moduleId].hasOwnProperty(commandName))
					{
						continue;
					}
					text = text + ' | --- ' + commandName + ': ' + this.session.history[moduleId][commandName] + "\n";
				}
			}

			text = text + "===================";
			console.info(text);
		}
	}

	class ChannelManager
	{
		constructor()
		{
			this.publicIds = {};
		}

		/**
		 *
		 * @param {Array} users Array of user ids.
		 * @return {Promise}
		 */
		getPublicIds(users)
		{
			return new Promise((resolve) =>
			{
				let result = {};
				let now = new Date();
				let unknownUsers = [];

				for (let i = 0; i < users.length; i++)
				{
					let userId = users[i];
					if (this.publicIds[userId] && this.publicIds[userId]['end'] > now)
					{
						result[userId] = this.publicIds[userId];
					}
					else
					{
						unknownUsers.push(userId);
					}
				}

				if (unknownUsers.length === 0)
				{
					return resolve(result);
				}

				BX.rest.callMethod("pull.channel.public.list", {users: unknownUsers}).then((response) =>
				{
					if (response.error())
					{
						return resolve({});
					}
					let data = response.data();
					this.setPublicIds(Object.values(data));
					unknownUsers.forEach(userId => result[userId] = this.publicIds[userId]);

					resolve(result);
				});
			})
		}

		/**
		 *
		 * @param {object[]} publicIds
		 * @param {integer} publicIds.user_id
		 * @param {string} publicIds.public_id
		 * @param {string} publicIds.signature
		 * @param {Date} publicIds.start
		 * @param {Date} publicIds.end
		 */
		setPublicIds(publicIds)
		{
			for (let i = 0; i < publicIds.length; i++)
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
		}
	}

	class JsonRpc
	{
		constructor(options)
		{
			this.idCounter = 0;
			this.handlers = {};
			this.rpcResponseAwaiters = new Map();

			this.connector = options.connector;
			if (BX.type.isPlainObject(options.handlers))
			{
				for (let method in options.handlers)
				{
					this.handle(method, options.handlers[method]);
				}
			}
		}

		/**
		 * @param {string} method
		 * @param {function} handler
		 */
		handle(method, handler)
		{
			this.handlers[method] = handler;
		}

		/**
		 * Sends RPC command to the server.
		 *
		 * @param {string} method Method name
		 * @param {object} params
		 * @param {int} timeout
		 * @returns {Promise}
		 */
		executeOutgoingRpcCommand(method, params, timeout)
		{
			if (!timeout)
			{
				timeout = 5;
			}
			return new Promise((resolve, reject) =>
			{
				const request = this.createRequest(method, params);
				console.warn("send ", request)

				if (!this.connector.send(JSON.stringify(request)))
				{
					reject(new ErrorNotConnected('websocket is not connected'));
				}

				const t = setTimeout(() => {
					this.rpcResponseAwaiters.delete(request.id);
					reject(new ErrorTimeout('no response'));
				}, timeout * 1000);
				this.rpcResponseAwaiters.set(request.id, {resolve, reject, timeout: t});
			})
		}

		/**
		 * Executes array or rpc commands. Returns array of promises, each promise will be resolved individually.
		 *
		 * @param {JsonRpcRequest[]} batch
		 * @returns {Promise[]}
		 */
		executeOutgoingRpcBatch(batch)
		{
			let requests = [];
			let promises = [];
			batch.forEach(({method, params, id}) =>
			{
				const request = this.createRequest(method, params, id);
				requests.push(request);
				promises.push(new Promise((resolve, reject) => this.rpcResponseAwaiters.set(request.id, {
					resolve,
					reject
				})));
			});

			this.connector.send(JSON.stringify(requests));
			return promises;
		}

		processRpcResponse(response)
		{
			if ("id" in response && this.rpcResponseAwaiters.has(response.id))
			{
				const awaiter = this.rpcResponseAwaiters.get(response.id)
				if ("result" in response)
				{
					awaiter.resolve(response.result)
				}
				else if ("error" in response)
				{
					awaiter.reject(response.error)
				}
				else
				{
					awaiter.reject(new Error("wrong response structure"))
				}

				clearTimeout(awaiter.timeout)
				this.rpcResponseAwaiters.delete(response.id)
			}
			else
			{
				console.error("Received rpc response with unknown id " + response.id, response)
			}
		}

		parseJsonRpcMessage(message)
		{
			let decoded
			try
			{
				decoded = JSON.parse(message);
			} catch (e)
			{
				console.error(PullUtils.getDateForLog() + ": Pull: Could not decode json rpc message", e);
			}

			if (BX.type.isArray(decoded))
			{
				return this.executeIncomingRpcBatch(decoded);
			}
			else if (PullUtils.isJsonRpcRequest(decoded))
			{
				return this.executeIncomingRpcCommand(decoded);
			}
			else if (PullUtils.isJsonRpcResponse(decoded))
			{
				return this.processRpcResponse(decoded);
			}
			else
			{
				console.error(PullUtils.getDateForLog() + ": Pull: unknown rpc packet", decoded);
			}
		}

		/**
		 * Executes RPC command, received from the server
		 *
		 * @param {string} method
		 * @param {object} params
		 * @returns {object}
		 */
		executeIncomingRpcCommand({method, params})
		{
			if (method in this.handlers)
			{
				return this.handlers[method].call(this, params)
			}

			return {
				"error": RpcError.MethodNotFound
			}
		}

		executeIncomingRpcBatch(batch)
		{
			let result = [];
			for (let command of batch)
			{
				if ("jsonrpc" in command)
				{
					if ("method" in command)
					{
						let commandResult = this.executeIncomingRpcCommand(command)
						if (commandResult)
						{
							commandResult["jsonrpc"] = JSON_RPC_VERSION;
							commandResult["id"] = command["id"];

							result.push(commandResult)
						}
					}
					else
					{
						this.processRpcResponse(command)
					}
				}
				else
				{
					console.error(PullUtils.getDateForLog() + ": Pull: unknown rpc command in batch", command);
					result.push({
						"jsonrpc": "2.0",
						"error": RpcError.InvalidRequest,
					})
				}
			}

			return result;
		}

		nextId()
		{
			return ++this.idCounter;
		}

		createPublishRequest(messageBatch)
		{
			let result = messageBatch.map(message => this.createRequest('publish', message));

			if (result.length === 0)
			{
				return result[0]
			}

			return result;
		}

		createRequest(method, params, id)
		{
			if (!id)
			{
				id = this.nextId()
			}

			return {
				jsonrpc: JSON_RPC_VERSION,
				method: method,
				params: params,
				id: id
			}
		}
	}

	const PullUtils = {
		getDateForLog ()
		{
			const d = new Date();

			return d.getFullYear() + "-" + PullUtils.lpad(d.getMonth(), 2, '0') + "-" + PullUtils.lpad(d.getDate(), 2, '0') + " " + PullUtils.lpad(d.getHours(), 2, '0') + ":" + PullUtils.lpad(d.getMinutes(), 2, '0');
		},

		lpad (str, length, chr)
		{
			str = str.toString();
			chr = chr || ' ';

			if (str.length > length)
			{
				return str;
			}

			let result = '';
			for (let i = 0; i < length - str.length; i++)
			{
				result += chr;
			}

			return result + str;
		},

		isJsonRpcRequest (item)
		{
			return (
				typeof (item) === "object"
				&& item
				&& "jsonrpc" in item
				&& BX.type.isNotEmptyString(item.jsonrpc)
				&& "method" in item
				&& BX.type.isNotEmptyString(item.method)
			);
		},

		isJsonRpcResponse (item)
		{
			return (
				typeof (item) === "object"
				&& item
				&& "jsonrpc" in item
				&& BX.type.isNotEmptyString(item.jsonrpc)
				&& "id" in item
				&& (
					"result" in item
					|| "error" in item
				)
			);
		},
	}

	class ErrorNotConnected extends Error
	{
		constructor(message)
		{
			super(message);
			this.name = 'ErrorNotConnected';
		}
	}

	class ErrorTimeout extends Error
	{
		constructor(message)
		{
			super(message);
			this.name = 'ErrorTimeout';
		}
	}

	window.Connection = Connection;
	window.CONFIG = CONFIG;
})();