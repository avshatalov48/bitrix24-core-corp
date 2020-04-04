/**
 * Bitrix Mobile App
 * Pull client
 *
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2019 Bitrix
 */

/**
 * After modify this script, copy to:
 * mobile/install/mobileapp/mobile/extensions/bitrix/pull/client/events/extension.js
 */

// start common code

var PullStatus = {
	Online: 'online',
	Offline: 'offline',
	Connecting: 'connect'
};

var SubscriptionType = {
	Server: 'server',
	Client: 'client',
	Online: 'online',
	Status: 'status',
	Revision: 'revision'
};

var CloseReasons = {
	NORMAL_CLOSURE : 1000,
	SERVER_DIE : 1001,
	CONFIG_REPLACED : 3000,
	CHANNEL_EXPIRED : 3001,
	SERVER_RESTARTED : 3002,
	CONFIG_EXPIRED : 3003,
	MANUAL : 3004,
};

class PullEvents
{
	constructor()
	{
		this._subscribers = {};
		this._eventListener = {};

		this.context = 'client';
	}

	/**
	 * Creates a subscription to incoming messages.
	 *
	 * @param {Object} params
	 * @param {string} [params.type] Subscription type (for possible values see SubscriptionType).
	 * @param {string} [params.command] command
	 * @param {string} [params.moduleId] Name of the module.
	 * @param {Function} params.callback Function, that will be called for incoming messages.
	 * @returns {Function} - Unsubscribe callback function
	 */
	subscribe(params = {})
	{
		if (!params)
		{
			console.error(Utils.getDateForLog() + ': Pull.subscribe: params for subscribe function is invalid. ');
			return function(){}
		}

		if (!Utils.isPlainObject(params))
		{
			return this.attachCommandHandler(params);
		}

		params.type = params.type || SubscriptionType.Server;

		let eventName = '';
		let eventType = params.type;

		if (
			eventType === SubscriptionType.Server
			|| eventType === SubscriptionType.Client
			|| eventType === SubscriptionType.Online
		)
		{
			if (eventType === SubscriptionType.Server)
			{
				eventName = typeof env !== 'undefined'? "onPullEvent-" + params.moduleId: "onPull-" + params.moduleId;
			}
			else if (eventType === SubscriptionType.Client)
			{
				eventName = typeof env !== 'undefined'? "onPullClientEvent-" + params.moduleId: "onPullClient-" + params.moduleId;
			}
			else if (eventType === SubscriptionType.Online)
			{
				eventName = typeof env !== 'undefined'? "onPullOnlineEvent": 'onPullOnline';
			}

			if (eventName && !this._eventListener[eventName])
			{
				this._eventListener[eventName] = true;

				if (typeof env !== 'undefined')
				{
					BX.addCustomEvent(eventName, (command, params, extra, moduleId) =>
					{
						if (eventType === SubscriptionType.Online)
						{
							moduleId = 'online';
						}
						this.emit({
							type : eventType,
							moduleId : moduleId,
							data: {
								command,
								params: Utils.clone(params),
								extra: Utils.clone(extra)
							}
						});
					});
				}
				else
				{
					this.receiveComponentEvent(eventName, data =>
					{
						if (eventType === SubscriptionType.Online)
						{
							data.module_id = 'online';
						}
						this.emit({
							type : eventType,
							moduleId : data.module_id,
							data: Utils.clone(data)
						});
					});
				}
			}
		}
		else if (eventType === SubscriptionType.Status)
		{
			eventName = 'onPullStatus';

			if (eventName && !this._eventListener[eventName])
			{
				this._eventListener[eventName] = true;

				if (typeof env !== 'undefined')
				{
					BX.addCustomEvent(eventName, (status) => {
						this.emit({
							type : eventType,
							data: {status}
						});
					});
				}
				else
				{
					this.receiveComponentEvent(eventName, data => {
						this.emit({
							type : eventType,
							data: Utils.clone(data)
						});
					});
				}
			}
		}


		/**
		 *  Dont modify following code, copy from pull/install/js/pull/client/pull.client.js: 'subscribe'
		 */
		params.command = params.command || null;

		if (params.type === SubscriptionType.Server || params.type === SubscriptionType.Client)
		{
			if (typeof (this._subscribers[params.type]) === 'undefined')
			{
				this._subscribers[params.type] = {};
			}
			if (typeof (this._subscribers[params.type][params.moduleId]) === 'undefined')
			{
				this._subscribers[params.type][params.moduleId] = {
					'callbacks': [],
					'commands': {},
				};
			}

			if (params.command)
			{
				if (typeof (this._subscribers[params.type][params.moduleId]['commands'][params.command]) === 'undefined')
				{
					this._subscribers[params.type][params.moduleId]['commands'][params.command] = [];
				}

				this._subscribers[params.type][params.moduleId]['commands'][params.command].push(params.callback);

				return function () {
					this._subscribers[params.type][params.moduleId]['commands'][params.command] = this._subscribers[params.type][params.moduleId]['commands'][params.command].filter(function(element) {
						return element !== params.callback;
					});
				}.bind(this);
			}
			else
			{
				this._subscribers[params.type][params.moduleId]['callbacks'].push(params.callback);

				return function () {
					this._subscribers[params.type][params.moduleId]['callbacks'] = this._subscribers[params.type][params.moduleId]['callbacks'].filter(function(element) {
						return element !== params.callback;
					});
				}.bind(this);
			}
		}
		else
		{
			if (typeof (this._subscribers[params.type]) === 'undefined')
			{
				this._subscribers[params.type] = [];
			}

			this._subscribers[params.type].push(params.callback);

			return function () {
				this._subscribers[params.type] = this._subscribers[params.type].filter(function(element) {
					return element !== params.callback;
				});
			}.bind(this);
		}
	};

	extendWatch(tagId, force = false)
	{
		this.postComponentEvent("onPullExtendWatch", {id: tagId, force});
		return true;
	}

	clearWatch(tagId)
	{
		this.postComponentEvent("onPullClearWatch", {id: tagId});
		return true;
	}

	capturePullEvent(debugFlag = true)
	{
		if (this.debug === null)
		{
			console.warn('PullEvents.capturePullEvent: only commands from subscribed modules are logged.')
		}

		this.debug = !!debugFlag;

		console.log('PullEvents.capturePullEvent: logger turn '+(this.debug? 'on': 'off'))
	}

	getDebugInfo()
	{
		this.postComponentEvent("onPullGetDebugInfo");

		if (!this._eventListener["onPullGetDebugInfoResult"])
		{
			this._eventListener["onPullGetDebugInfoResult"] = true;
			this.receiveComponentEvent("onPullGetDebugInfoResult", data => {
				if (typeof data === 'string')
				{
					console.info(data)
				}
				else
				{
					console.info(data.text);
				}
			});
		}
	}

	/**
	 * @private
	 *
	 * @param eventName
	 * @param callback
	 */
	receiveComponentEvent(eventName, callback)
	{
		if (typeof BXMobileApp !== 'undefined' && typeof BXMobileApp.addCustomEvent !== 'undefined')
		{
			BXMobileApp.addCustomEvent(eventName, callback);
		}
		else
		{
			BX.addCustomEvent(eventName, callback);
		}
	}

	/**
	 * @private
	 *
	 * @param name
	 * @param params
	 */
	postComponentEvent(name, params = {})
	{
		if (typeof BX.postComponentEvent !== 'undefined')
		{
			BX.postComponentEvent(name, [params], "communication");
		}
		else
		{
			if (
				typeof window.app !== 'undefined'
				&& typeof window.app.enableInVersion !== 'undefined'
				&& typeof BXMobileApp !== 'undefined'
			)
			{
				if (window.app.enableInVersion(25))
				{
					BXMobileApp.Events.postToComponent(name, params, "communication");
				}
				else
				{
					BXMobileApp.onCustomEvent(name, params, true);
				}
			}
		}
	}

	/**
	 * @private
	 *
	 * @param handler
	 * @returns {Function}
	 */
	attachCommandHandler(handler)
	{
		/**
		 *  Dont modify this method, this is copy from pull/install/js/pull/client/pull.client.js: 'attachCommandHandler'
		 */
		if (typeof handler.getModuleId !== 'function' || typeof handler.getModuleId() !== 'string')
		{
			console.error(Utils.getDateForLog() + ': Pull.attachCommandHandler: result of handler.getModuleId() is not a string.');
			return function(){}
		}

		var type = SubscriptionType.Server;
		if (typeof handler.getSubscriptionType === 'function')
		{
			type = handler.getSubscriptionType();
		}

		this.subscribe({
			type: type,
			moduleId: handler.getModuleId(),
			callback: function(data)
			{
				var method = null;

				if (typeof handler.getMap === 'function')
				{
					let mapping = handler.getMap();
					if (mapping && typeof mapping === 'object')
					{
						if (typeof mapping[data.command] === 'function')
						{
							method = mapping[data.command].bind(handler)
						}
						else if (typeof mapping[data.command] === 'string' && typeof handler[mapping[data.command]] === 'function')
						{
							method = handler[mapping[data.command]].bind(handler);
						}
					}
				}

				if (!method)
				{
					var methodName = 'handle'+data.command.charAt(0).toUpperCase() + data.command.slice(1);
					if (typeof handler[methodName] === 'function')
					{
						method = handler[methodName].bind(handler);
					}
				}

				if (method)
				{
					if (this.debug && this.context !== 'master')
					{
						console.warn(Utils.getDateForLog() + ': Pull.attachCommandHandler: receive command', data);
					}
					method(data.params, data.extra, data.command);
				}
			}.bind(this)
		});
	};

	/**
	 * @private
	 *
	 * @param params
	 * @returns {boolean}
	 */
	emit(params)
	{
		/**
		 *  Dont modify this method, this is copy from pull/install/js/pull/client/pull.client.js: 'emit'
		 */
		params = params || {};

		if (params.type === SubscriptionType.Server || params.type === SubscriptionType.Client)
		{
			if (typeof (this._subscribers[params.type]) === 'undefined')
			{
				this._subscribers[params.type] = {};
			}
			if (typeof (this._subscribers[params.type][params.moduleId]) === 'undefined')
			{
				this._subscribers[params.type][params.moduleId] = {
					'callbacks': [],
					'commands': {},
				};
			}

			if (this._subscribers[params.type][params.moduleId]['callbacks'].length > 0)
			{
				this._subscribers[params.type][params.moduleId]['callbacks'].forEach(function(callback){
					callback(params.data, {type: params.type, moduleId: params.moduleId});
				});
			}

			if (
				this._subscribers[params.type][params.moduleId]['commands'][params.data.command]
				&& this._subscribers[params.type][params.moduleId]['commands'][params.data.command].length > 0)
			{
				this._subscribers[params.type][params.moduleId]['commands'][params.data.command].forEach(function(callback){
					callback(params.data.params, params.data.extra, params.data.command, {type: params.type, moduleId: params.moduleId});
				});
			}

			return true;
		}
		else
		{
			if (typeof (this._subscribers[params.type]) === 'undefined')
			{
				this._subscribers[params.type] = [];
			}

			if (this._subscribers[params.type].length <= 0)
			{
				return true;
			}

			this._subscribers[params.type].forEach(function(callback){
				callback(params.data, {type: params.type});
			});

			return true;
		}
	}
}

var Utils =
{
	isArray(item)
	{
		return item && Object.prototype.toString.call(item) === "[object Array]";
	},
	isDomNode: function(item) {
		return item && typeof (item) == "object" && "nodeType" in item;
	},
	isDate: function(item) {
		return item && Object.prototype.toString.call(item) === "[object Date]";
	},
	clone(obj, bCopyObj)
	{
		let _obj, i, l;
		if (bCopyObj !== false)
			bCopyObj = true;

		if (obj === null)
			return null;

		if (this.isDomNode(obj))
		{
			_obj = obj.cloneNode(bCopyObj);
		}
		else if (typeof obj == 'object')
		{
			if (this.isArray(obj))
			{
				_obj = [];
				for (i=0,l=obj.length;i<l;i++)
				{
					if (typeof obj[i] == "object" && bCopyObj)
						_obj[i] = this.clone(obj[i], bCopyObj);
					else
						_obj[i] = obj[i];
				}
			}
			else
			{
				_obj =  {};
				if (obj.constructor)
				{
					if (this.isDate(obj))
						_obj = new Date(obj);
					else
						_obj = new obj.constructor();
				}

				for (i in obj)
				{
					if (!obj.hasOwnProperty(i))
					{
						continue;
					}
					if (typeof obj[i] == "object" && bCopyObj)
						_obj[i] = this.clone(obj[i], bCopyObj);
					else
						_obj[i] = obj[i];
				}
			}

		}
		else
		{
			_obj = obj;
		}

		return _obj;
	},
	isPlainObject(item)
	{
		if(!item || typeof(item) !== "object" || item.nodeType)
		{
			return false;
		}

		let hasProp = Object.prototype.hasOwnProperty;
		try
		{
			if (item.constructor && !hasProp.call(item, "constructor") && !hasProp.call(item.constructor.prototype, "isPrototypeOf") )
			{
				return false;
			}
		}
		catch (e)
		{
			return false;
		}

		let key;
		for (key in item)
		{
		}
		return typeof(key) === "undefined" || hasProp.call(item, key);
	},
	lpad(str, length, chr)
	{
		str = str.toString();
		chr = chr || ' ';

		if(str.length > length)
		{
			return str;
		}

		let result = '';
		for(var i = 0; i < length - str.length; i++)
		{
			result += chr;
		}

		return result + str;
	},
	getDateForLog()
	{
		let d = new Date();
		return d.getFullYear() + "-" + Utils.lpad(d.getMonth(), 2, '0') + "-" + Utils.lpad(d.getDate(), 2, '0') + " " + Utils.lpad(d.getHours(), 2, '0') + ":" + Utils.lpad(d.getMinutes(), 2, '0');
	},
};

// end common code

var PULL = new PullEvents;

PullEvents.PullStatus = PullStatus;
PullEvents.SubscriptionType = SubscriptionType;
PullEvents.CloseReasons = CloseReasons;

export {PULL, PullEvents as PullClient, PullEvents}