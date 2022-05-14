/** @memberof BX.Crm.Timeline.Tools */
export const SmsWatcher =
{
	_pullTagName: 'MESSAGESERVICE',
	_pullInited: false,
	_listeners: {},
	initPull: function()
	{
		if (this._pullInited)
			return;

		BX.addCustomEvent("onPullEvent-messageservice", this.onPullEvent.bind(this));
		this.extendWatch();

		this._pullInited = true;
	},
	subscribeOnMessageUpdate: function(messageId, callback)
	{
		this.initPull();
		this._listeners[messageId] = callback;
	},
	fireExternalStatusUpdate: function(messageId, message)
	{
		const listener = this._listeners[messageId];
		if (listener)
		{
			listener(message);
		}
	},
	onPullEvent: function(command, params)
	{
		// console.log(command, params);
		if (command === 'message_update')
		{
			for (let i = 0; i < params.messages.length; ++i)
			{
				const message = params.messages[i];
				this.fireExternalStatusUpdate(message['ID'], message);
			}
		}
	},
	extendWatch: function()
	{
		if(BX.type.isFunction(BX.PULL))
		{
			BX.PULL.extendWatch(this._pullTagName);
			window.setTimeout(this.extendWatch.bind(this), 60000);
		}
	}
};