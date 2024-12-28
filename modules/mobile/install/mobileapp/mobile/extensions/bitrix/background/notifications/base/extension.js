/**
 * @module background/notifications/base
 */
jn.define('background/notifications/base', (require, exports, module) => {
	const { PushListener } = require('push/listeners');

	/**
	 * @abstract
	 * @class BaseNotificationHandler
	 */
	class BaseNotificationHandler
	{
		constructor()
		{
			PushListener.subscribe(this.getNotificationType(), this.handleNotificationClick.bind(this));

			this.isSubscribed = false;
			this.emitNotificationOnSubscribe = false;
			this.emitNotificationMessage = null;

			if (this.getSubscriptionEventName())
			{
				BX.addCustomEvent(this.getSubscriptionEventName(), this.onSubscribeToPushNotification.bind(this));
			}
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		getNotificationType()
		{
			console.error('Must be implemented');
		}

		/**
		 * @param {object} message
		 */
		handleNotificationClick(message)
		{
			if (this.isSubscribed)
			{
				this.sendAnalytics(message);
				BX.postComponentEvent(this.getNotificationEventName(), [message]);
			}
			else
			{
				this.emitNotificationOnSubscribe = true;
				this.emitNotificationMessage = message;
			}
		}

		sendAnalytics(message)
		{
			const analytics = this.getAnalytics(message);
			if (analytics)
			{
				analytics.send();
			}
		}

		getAnalytics(message)
		{
			return null;
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		getNotificationEventName()
		{
			console.error('Must be implemented');
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		getSubscriptionEventName()
		{
			console.error('Must be implemented');
		}

		onSubscribeToPushNotification()
		{
			if (!this.isSubscribed)
			{
				this.isSubscribed = true;

				if (this.emitNotificationOnSubscribe)
				{
					this.sendAnalytics(this.emitNotificationMessage);
					BX.postComponentEvent(this.getNotificationEventName(), [this.emitNotificationMessage]);

					this.emitNotificationOnSubscribe = false;
					this.emitNotificationMessage = null;
				}
			}
		}
	}

	module.exports = {
		BaseNotificationHandler,
	};
});
