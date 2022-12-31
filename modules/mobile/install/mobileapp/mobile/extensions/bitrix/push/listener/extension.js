(() => {

	const HANDLED_PUSH_COMMAND = 'CommonMobilePushEvent';
	const SHOW_NOTIFICATION_FOR_SECONDS = 10;

	include('InAppNotifier');

	class PushListener
	{
		constructor()
		{
			this.events = {};
			this.latestMessageId = null;
		}

		/**
		 * @param {string} messageType
		 * @param {Function} callback
		 */
		subscribe(messageType, callback)
		{
			if (!this.events[messageType])
			{
				this.events[messageType] = [];
			}

			const emptyFn = () => {};
			const handler = typeof callback === 'function' ? callback : emptyFn;

			this.events[messageType].push(handler);
		}

		/**
		 * @param {string} messageType
		 */
		unsubscribe(messageType)
		{
			delete this.events[messageType];
		}

		/**
		 * @param {Message} message
		 */
		handle(message)
		{
			if (this.messageAlreadyProcessed(message))
			{
				return;
			}

			if (!this.subscribedTo(message))
			{
				return;
			}

			this.latestMessageId = message.id;

			if (message instanceof ApplicationMessage && message.hasBody())
			{
				this.displayApplicationNotification(message);
			}
			else
			{
				this.executeCallbacks(message);
			}
		}

		/**
		 * @param {Message} message
		 * @returns {boolean}
		 */
		messageAlreadyProcessed(message)
		{
			return message.id === this.latestMessageId;
		}

		/**
		 * @param {Message} message
		 * @returns {boolean}
		 */
		subscribedTo(message)
		{
			const type = message.type;
			return this.events[type] && this.events[type].length;
		}

		/**
		 * @param {Message} message
		 */
		executeCallbacks(message)
		{
			const callbacks = this.events[message.type];
			if (callbacks && callbacks.length)
			{
				callbacks.forEach(callback => callback(message));
			}
		}

		/**
		 * @param {Message} message
		 */
		displayApplicationNotification(message)
		{
			InAppNotifier.setHandler(() => this.executeCallbacks(message));
			InAppNotifier.showNotification({
				title: message.title,
				backgroundColor: '#E6000000',
				message: message.body,
				time: SHOW_NOTIFICATION_FOR_SECONDS,
			});
		}
	}

	/**
	 * @class Message
	 */
	class Message
	{
		constructor({id, type, title, body, payload})
		{
			this.id = id;
			this.type = type;
			this.title = title;
			this.body = body;
			this.payload = payload;
		}

		/**
		 * @returns {boolean}
		 */
		hasBody()
		{
			return Boolean(this.body.length);
		}
	}

	/**
	 * @class ApplicationMessage
	 */
	class ApplicationMessage extends Message {}

	/**
	 * @class DeviceMessage
	 */
	class DeviceMessage extends Message {}

	const pushListener = new PushListener();

	BX.addCustomEvent('onPullEvent-mobile', (command, params) => {
		if (command === HANDLED_PUSH_COMMAND && params && params.message)
		{
			pushListener.handle(new ApplicationMessage(params.message));
		}
	});

	const onAppActive = () => {
		const push = Application.getLastNotification();
		if (push && push.params)
		{
			const pushParams = JSON.parse(push.params);
			if (pushParams && pushParams.command && pushParams.message && pushParams.command === HANDLED_PUSH_COMMAND)
			{
				const messageParams = JSON.parse(pushParams.message);
				if (messageParams)
				{
					pushListener.handle(new DeviceMessage(messageParams));
				}
			}
		}
	};

	BX.addCustomEvent('onAppActive', onAppActive);

	// fake timeout to wait subscribers on initialization
	setTimeout(() => onAppActive(), 100);

	/**
	 * @class PushListener
	 */
	this.PushListener = pushListener;

})();
