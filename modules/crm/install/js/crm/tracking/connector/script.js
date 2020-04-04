;(function () {

	"use strict";

	function Connector(options)
	{
		this.addressee = options.addressee;
		this.responders = options.responders || {};

		this.belongTo = 'b24-portal-connector';
		this.queue = {};
		window.addEventListener('message', this.response.bind(this));
	}
	Connector.prototype = {
		request: function (window, action, data, callback)
		{
			var requestId = Math.random();
			this.queue[requestId] = {
				requestId: requestId,
				callback: callback,
				action: action,
				data: data
			};

			this.send(window, {
				requestId: requestId,
				action: action,
				data: data || {}
			});
		},
		send: function (window, data)
		{
			data = data || {};
			data.belongTo = this.belongTo;
			window.postMessage(JSON.stringify(data), this.addressee);
		},
		getDataFromEvent: function (event)
		{
			if (!event.source)
			{
				return false;
			}

			if (event.origin !== this.addressee)
			{
				return false;
			}

			try
			{
				var data = JSON.parse(event.data);
			}
			catch (e)
			{
				return false;
			}

			if (data.belongTo !== this.belongTo)
			{
				return false;
			}

			return data;
		},
		response: function (event)
		{
			var data = this.getDataFromEvent(event);
			if (!data)
			{
				return;
			}

			// it is response to our call
			if (data.responseId)
			{
				var request = this.queue[data.responseId];
				if (!request || !request.callback)
				{
					return;
				}

				request.callback.call(this, data.data, data);
				return;
			}

			// it is response to their call
			if (data.action)
			{
				var responder = this.responders[data.action];
				if (!responder)
				{
					return;
				}

				this.send(event.source, {
					responseId: data.requestId,
					data: responder.call(this, data.data)
				});
			}
		}
	};

	if (!window.b24Tracker) window.b24Tracker = {};
	b24Tracker.Connector = Connector;

})();