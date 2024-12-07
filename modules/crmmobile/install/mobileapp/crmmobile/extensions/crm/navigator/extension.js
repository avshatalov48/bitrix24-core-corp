/**
 * @module crm/navigator
 */
jn.define('crm/navigator', (require, exports, module) => {
	const { BaseNavigator } = require('navigator/base');
	const { NOTIFICATION_EVENTS, SUBSCRIPTION_EVENTS } = require('crm/navigator/meta');

	/**
	 * @class CrmNavigator
	 */
	class CrmNavigator extends BaseNavigator
	{
		constructor(props)
		{
			super(props);
			this.customSectionId = props.customSectionId;
		}

		subscribeToPushNotifications()
		{
			this.subscribeToCrmNotification();
		}

		unsubscribeFromPushNotifications()
		{
			BX.removeCustomEvent(NOTIFICATION_EVENTS.CRM, this.onCrmNotification.bind(this));
		}

		subscribeToCrmNotification()
		{
			BX.addCustomEvent(NOTIFICATION_EVENTS.CRM, this.onCrmNotification.bind(this));
			this.onSubscribeToPushNotification(SUBSCRIPTION_EVENTS.CRM);
		}

		onCrmNotification()
		{
			if (!Number.isInteger(this.customSectionId) && !this.isActiveTab())
			{
				this.makeTabActive();
			}
		}
	}

	module.exports = {
		CrmNavigator,
	};
});
