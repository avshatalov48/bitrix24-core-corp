/**
 * @requires BXCordovaPlugin
 */
(function (window)
{
	if (window.BX.MobileVoximplant || typeof window.BXCordovaPlugin === "undefined" ) return;

	BX.MobileVoximplant = {
		isConnected: false,
		isInitialized: false,
		isReady: false,
		plugin: new BXCordovaPlugin("BitrixVoximplant", false, false),
		listeners: {},
		internalListeners: {
			"onIncomingCall": function (data)
			{
				var incomingCall = BX.MobileVoximplant.call(data.displayName, data.videoCall, data.headers, data.callId);
				BX.MobileVoximplant.executeCallback(BX.MobileVoximplant.events.IncomingCall, {'call': incomingCall});
			},
			"onConnectionEstablished": function ()
			{
				BX.MobileVoximplant.isConnected = true;
				BX.MobileVoximplant.executeCallback(BX.MobileVoximplant.events.ConnectionEstablished);
			},
			"onConnectionClosed": function ()
			{
				BX.MobileVoximplant.isConnected = false;
				BX.MobileVoximplant.executeCallback(BX.MobileVoximplant.events.ConnectionClosed);
			},
			"onConnectionFailed": function (data)
			{
				BX.MobileVoximplant.isConnected = false;
				BX.MobileVoximplant.executeCallback(BX.MobileVoximplant.events.ConnectionFailed, data);
			},
			"onSDKReady": function ()
			{
				BX.MobileVoximplant.isReady = true;
				BX.MobileVoximplant.executeCallback(BX.MobileVoximplant.events.SDKReady);
			},
			"onAuthResult": function (data)
			{
				if (data.code == 401)
				{
					console.error("BX.MobileVoximplant.login(). It seams like login or one time password was not specified. " +
						"Use BX.MobileVoximplant.loginWithOneTimeKey to set session data");
				}

				BX.MobileVoximplant.executeCallback(BX.MobileVoximplant.events.AuthResult, data);
			}
		},
		executeCallback: function (name, data)
		{
			if (typeof BX.MobileVoximplant.listeners[name] == "function")
			{
				BX.MobileVoximplant.listeners[name](data);
			}
			else
			{
				console.log(name, data);
			}
		},
		getInstance: function ()
		{
			return this;
		},
		init: function ()
		{
			this.plugin.exec("init", {
				listeners: this.getListeners()
			});
		},
		/**
		 *
		 * @param {BX.MobileVoximplant.events} eventName
		 * @param handler
		 */
		addEventListener: function (eventName, handler)
		{
			this.listeners[eventName] = handler;
		},
		connected: function ()
		{
			return this.isConnected;
		},
		connect: function ()
		{
			BX.MobileVoximplant.addEventListener()
			this.callIfReady(function ()
			{
				BX.MobileVoximplant.plugin.exec("connect")
			});
		},
		disconnect: function ()
		{
			this.callIfReady(function ()
			{
				BX.MobileVoximplant.plugin.exec("disconnect")
			});
		},
		loginWithOneTimeKey: function (login, hash)
		{
			this.plugin.exec("loginWithOneTimeKey", {login: login, hash: hash});
		},
		login: function ()
		{
			this.callIfReady(function ()
			{
				BX.MobileVoximplant.plugin.exec("login")
			});
		},
		call: function (number, useVideo, callParams, callId)
		{
			return new BX.MobileVoximplantCall(number, useVideo, callParams, this.plugin, callId);
		},
		setOperatorACDStatus: function (onlineStatus)
		{
			this.plugin.exec("some");
		},
		/**
		 * @private
		 * @param {function} func function to be called
		 */
		callIfReady: function (func)
		{
			if (this.isReady)
			{
				func();
			}
			else
			{
				console.error("MobileVoximplant engine is not initialized. You should call BX.MobileVoximplant.init() before.")
			}
		},
		/**
		 * @private
		 * @returns {BX.MobileVoximplant.internalListeners|{onIncomingCall, onConnectionEstablished, onConnectionClosed, onConnectionFailed, onSDKReady, onAuthResult}}
		 */
		getListeners: function ()
		{
			if (this.isInitialized)
				return this.internalListeners;

			for (var key in this.events)
			{
				var eventName = this.events[key];
				if (typeof this.internalListeners[eventName] != "function")
				{
					var context = {
						handler: function (data)
						{
							BX.MobileVoximplant.executeCallback(this.eventName, data);
						},
						eventName: eventName
					};

					this.internalListeners[eventName] = BX.proxy(context.handler, context);
				}
			}

			this.isInitialized = true;

			return this.internalListeners;
		},
		/**
		 * @readonly
		 * @enum {string}
		 */
		events: {
			IncomingCall: "onIncomingCall",
			ConnectionEstablished: "onConnectionEstablished",
			ConnectionFailed: "onConnectionFailed",
			ConnectionClosed: "onConnectionClosed",
			AuthResult: "onAuthResult",
			MicAccessResult: "onMicAccessResult",
			NetStatsReceived: "onNetStatsReceived",
			SDKReady: "onSDKReady"
		}
	};


	BX.MobileVoximplantCall = function (number, useVideo, callParams, pluginObject, callId)
	{
		this.callState = BX.MobileVoximplantCall.states.DISCONNECTED;
		this.plugin = pluginObject;
		this.listeners = {};
		this.params = {
			callId: callId ? callId : null,
			phoneNumber: number,
			useVideo: ((typeof useVideo == "undefined") ? false : useVideo),
			callParams: JSON.stringify(callParams),
			onCreateCallback: BX.proxy(this.onCreate, this),
			listeners: {
				"onCallConnected": BX.proxy(this.onConnected, this),
				"onCallDisconnected": BX.proxy(this.onDisconnected, this),
				"onCallFailed": BX.proxy(this.onFailed, this),
				"onProgressToneStart": BX.proxy(this.onProgressToneStart, this),
				"onProgressToneStop": BX.proxy(this.onProgressToneStop, this)
			}
		}
	};

	BX.MobileVoximplantCall.prototype.addEventListener = function (eventName, handler)
	{
		this.listeners[eventName] = handler;
	};

	BX.MobileVoximplantCall.prototype.onCreate = function (result)
	{
		this.params.callId = result.callId;
	};


	BX.MobileVoximplantCall.prototype.start = function ()
	{
		if (this.params.callId != null)
			return;

		this.plugin.exec("createCallAndStart", this.params);
	};

	BX.MobileVoximplantCall.prototype.removeEventListener = function (eventName)
	{
		delete this.listeners[eventName];
	};

	BX.MobileVoximplantCall.prototype.answer = function ()
	{
		if (this.params.callId == null)
			return;

		this.plugin.exec("answer", this.params);
	};

	BX.MobileVoximplantCall.prototype.hangup = function ()
	{
		this.plugin.exec("hangup", this.params);
	};

	BX.MobileVoximplantCall.prototype.decline = function ()
	{
		this.plugin.exec("decline", this.params);
	};

	BX.MobileVoximplantCall.prototype.id = function ()
	{
		return this.params.callId
	};

	BX.MobileVoximplantCall.prototype.state = function ()
	{
		return this.callState;
	};

	BX.MobileVoximplantCall.prototype.muteMicrophone = function ()
	{
		this.plugin.exec("setMute", {mute: true});
	};

	BX.MobileVoximplantCall.prototype.unmuteMicrophone = function ()
	{
		this.plugin.exec("setMute", {mute: false});
	};
	/**
	 * Switches off/on loud speaker
	 * @param {boolean} enabled
	 */
	BX.MobileVoximplantCall.prototype.setUseLoudSpeaker = function (enabled)
	{
		this.plugin.exec("setUseLoudSpeaker", {enabled: (typeof enabled == "boolean" ? enabled : false)});
	};


	/**
	 * Sends instant message within this call
	 * @param {string} text text of message
	 * @param {object} [headers] headers of message
	 */
	BX.MobileVoximplantCall.prototype.sendMessage = function (text, headers)
	{
		var data = {
			callId: this.id(),
			text: text,
			headers: (typeof headers != "object") ? {} : headers
		};


		this.plugin.exec("sendMessage", data);
	};
	/**
	 * Sends DTMF digit in this call.
	 * @param {int} digit digit can be 0-9 for 0-9, 10 for * and 11 for #
	 */
	BX.MobileVoximplantCall.prototype.sendTone = function (digit)
	{
		this.plugin.exec("sendDTMF", {callId: this.id(), digit: digit});
	};

	BX.MobileVoximplantCall.prototype.onDisconnected = function ()
	{
		this.callState = BX.MobileVoximplantCall.states.DISCONNECTED;
		this.executeCallback(BX.MobileVoximplantCall.events.Disconnected);
	};

	BX.MobileVoximplantCall.prototype.onConnected = function ()
	{
		this.callState = BX.MobileVoximplantCall.states.CONNECTED;
		this.executeCallback(BX.MobileVoximplantCall.events.Connected);
	};

	BX.MobileVoximplantCall.prototype.onFailed = function (data)
	{
		this.callState = BX.MobileVoximplantCall.states.DISCONNECTED;
		this.executeCallback(BX.MobileVoximplantCall.events.Failed, data);
	};

	BX.MobileVoximplantCall.prototype.onProgressToneStart = function ()
	{
		this.callState = BX.MobileVoximplantCall.states.CONNECTING;
		this.executeCallback(BX.MobileVoximplantCall.events.ProgressToneStart);
	};

	BX.MobileVoximplantCall.prototype.onProgressToneStop = function ()
	{
		this.executeCallback(BX.MobileVoximplantCall.events.ProgressToneStop);
	};

	BX.MobileVoximplantCall.prototype.executeCallback = function (eventName, data)
	{
		if (typeof this.listeners[eventName] == "function")
		{
			return this.listeners[eventName](data);
		}
		else
		{
			console.log(eventName, data)
		}

	};


	BX.MobileVoximplantCall.events = {
		Connected: "onCallConnected",
		Disconnected: "onCallDisconnected",
		Failed: "onCallFailed",
		ProgressToneStart: "onProgressToneStart",
		ProgressToneStop: "onProgressToneStop"
	};

	BX.MobileVoximplantCall.states = {
		CONNECTED: "connected",
		DISCONNECTED: "disconnected",
		CONNECTING: "connecting"
	};


	BX.MobileCallUI = {
		events: {
			onHangup: "onHangupCallClicked",
			onSpeakerphoneChanged: "onSpeakerphoneCallClicked",
			onMuteChanged: "onMuteCallClicked",
			onPauseChanged: "onPauseCallClicked",
			onNumpadClicked: "onNumpadClicked",
			onFormFolded: "onFoldCallClicked",
			onFormExpanded: "onUnfoldIconClicked",
			onCloseClicked: "onCloseCallClicked",
			onSkipClicked: "onSkipCallClicked",
			onAnswerClicked: "onAnswerCallClicked",
			onNumpadClosed: "onNumpadClosed",
			onNumpadButtonClicked: "onNumpadButtonClicked",
			onPhoneNumberReceived: "onPhoneNumberReceived",
			onContactListChoose: "onContactListChoose",
			onContactListMenuChoose: "onContactListMenuChoose"
		},
		customEvents: {},
		listener: null,
		plugin: new BXCordovaPlugin("BXCallsCordovaPlugin"),
		init: function ()
		{
			this.form.init();
			this.plugin.exec("init", {
				eventListener: BX.proxy(this.onEvent, this),
				jsCallbackProvider: "window.BX.MobileCallUI.plugin"
			})
		},
		setListener: function (listener)
		{
			this.listener = listener;
		},
		showContactListMenu: function (data)
		{
			this.plugin.exec("showContactListMenu", {items: data});
		},
		onEvent: function (event)
		{
			if (typeof this.listener == "function")
			{
				this.listener(event.eventName, event.params);
			}
		},
		list: {
			show: function (data)
			{
				BX.MobileCallUI.plugin.exec("openContactList", data);
			},
			/**
			 * Shows context menu in the list
			 * @param  items array of menu items
			 *
			 * <pre>
			 * Item object format:
			 *
			 * {
			 *    title:{string},
			 *    sort: {integer},
			 *    params:{object}
			 * }
			 * </pre>
			 */
			showMenu: function (items)
			{
				var preparedItems = [];
				for (var key in items)
				{
					preparedItems[key] = items[key];
					if (typeof preparedItems[key]["params"] == "object")
					{
						preparedItems[key]["params"] = JSON.stringify(preparedItems[key]["params"]);
					}
				}


				BX.MobileCallUI.plugin.exec("showContactListMenu", {items: preparedItems});
			}
		},
		numpad: {
			show: function ()
			{
				BX.MobileCallUI.plugin.exec("showNumpad")
			},
			close: function (animated)
			{
				BX.MobileCallUI.plugin.exec("closeNumpad", {animated: (typeof animated == "boolean" ? animated : true)});
			}
		},
		form: {
			plugin: null,
			init: function ()
			{
				this.plugin = BX.MobileCallUI.plugin;
			},
			/**
			 * Creates and shows call form with init data
			 * @param {object} startData init data
			 * @example
			 *<pre>
			 *  BX.MobileCallUI.form.show(
			 *  {
			 *      headerLabels:{
			 *          // see the first argument of  BX.MobileCallUI.form.updateHeader
			 *      },
			 *      footerLabels:{
			 *           // see the first argument of BX.MobileCallUI.form.updateFooter
			 *      },
			 *      middleLabels:{
			 *           // see the first argument of BX.MobileCallUI.form.updateMiddle
			 *      },
			 *      middleButtons:{
			 *          // see the second argument of BX.MobileCallUI.form.updateMiddle
			 *      },
			 *      state: BX.CallForm.state.INCOMING,
			 *      avatarUrl: "http://mydomain/mypic.png",
			 * });
			 *
			 * </pre>
			 * @see BX.MobileCallUI.form.updateHeader
			 * @see BX.MobileCallUI.form.updateMiddle
			 * @see BX.MobileCallUI.form.updateFooter
			 * @see BX.MobileCallUI.form.state
			 *
			 */
			show: function (startData)
			{
				this.plugin.exec("show", startData);
			},
			/**
			 * Closes and destroys the form
			 */
			close: function ()
			{
				this.plugin.exec("close");
			},
			/**
			 * Sets labels in header of the form
			 * @param {object} labels - header labels description
			 * @config {object}  [firstHeader] first big header
			 * @config {object}  [secondHeader] second big header
			 * @config {object}  [firstSmallHeader] first small header
			 * @config {object}  [secondSmallHeader] second small header
			 * @config {object}  [thirdSmallHeader] third small header
			 * @param {string} [avatar] avatar url
			 */
			updateHeader: function (labels, avatar)
			{
				this.plugin.exec("updateHeader", {headerLabels: labels, avatarUrl: avatar});
			},

			/**
			 * Sets labels and buttons in middle area of call form
			 * @param labels {object} labels object description.
			 * @config {object}  [infoTitle]
			 * @config {object}  [infoHeader]
			 * @config {object}  [infoSum]
			 * @config {object}  [infoDesc]
			 * @config {object}  [imageStub]
			 * @param {object} buttons buttons object description
			 * @example
			 * <pre>
			 * Example of buttons object:
			 * {
			 *      button1:{text:"button title", textColor:"#f0f0f0", eventName:"onCreateSome", sort:100},
			 *      button2:{text:"button2 title", textColor:"#fb0000", eventName:"onCreateSome2", sort:200},
			 * }
			 * </pre>
			 */
			updateMiddle: function (labels, buttons)
			{
				this.plugin.exec("updateMiddle", {middleLabels: labels, middleButtons: buttons});
			},

			/**
			 * Set state of call. It changes bottom area of the form
			 * @param {string}  [state] state of control panel
			 * @param  {object}  labels description of labels in the bottom area of form
			 * @config {object}  [actionDoneHint] hint label below control panel
			 * @config {object}  [callStateLabel] label above control panel
			 * @see BX.MobileCallUI.form.state
			 */
			updateFooter: function (labels, state)
			{
				this.plugin.exec("updateFooter", {footerLabels: labels, state: state});
			},

			/**
			 * Minimizes the form if it's exists and expanded
			 */
			rollUp: function ()
			{
				this.plugin.exec("rollUp", {});
			},
			/**
			 * Expands the form if it's exists and folded
			 */
			expand: function ()
			{
				this.plugin.exec("expand", {});
			},
			/**
			 * Starts timer. The timer will be displayed below userpic
			 */
			startTimer: function ()
			{
				this.plugin.exec("startTimer", {});
			},
			/**
			 * Pauses timer. The timer will stay shown.
			 */
			pauseTimer: function ()
			{
				this.plugin.exec("pauseTimer", {});
			},
			/**
			 * Do the same as startTimer
			 */
			resumeTimer: function ()
			{
				this.plugin.exec("startTimer", {});
			},
			/**
			 * Stops timer. The timer will be hide.
			 */
			stopTimer: function ()
			{
				this.plugin.exec("stopTimer", {});
			},
			/**
			 * Cancels delayed closing
			 */
			cancelDelayedClosing: function ()
			{
				this.plugin.exec("cancelDelayedClosing", {});
			},
			/**
			 * Sets duration for delay closing of form
			 * @param {int} duration duration for delay closing in milliseconds
			 * @default 1000 milliseconds
			 */
			setCloseDurationDelay: function (duration)
			{
				this.plugin.exec("setCloseDurationDelay", {duration: duration});
			},
			/**
			 * Plays sound
			 * @param soundId
			 */
			playSound: function (soundId)
			{
				this.plugin.exec("playSound", {soundId: soundId});
			},
			stopSound:function()
			{
				this.plugin.exec("stopSound");
			},
			/**
			 * Stops playing sound
			 */
			stopSound: function ()
			{
				this.plugin.exec("stopSound");
			},
			/**
			 * Shows context menu
			 * @param  items array of menu items
			 *
			 * <pre>
			 * Item object format:
			 *
			 * {
			 *    title:{string},
			 *    sort: {integer},
			 *    eventName: {string}
			 * }
			 * </pre>
			 */
			showMenu: function (items)
			{
				this.plugin.exec("showMenu", {items: items});
			},
			sound: {
				INCOMING: "incoming",
				START_CALL: "startcall"
			},
			state: {
				INCOMING: "INCOMING",
				FINISHED: "FINISHED",
				STARTED: "STARTED",
				OUTGOING: "OUTGOING",
				WAITING: "WAITING",
				CALLBACK: "CALLBACK"
			},
			contentDesc: {
				text: "",
				textColor: "",
				display: "",
				sort: 1
			}
		}

	};


})(window);
