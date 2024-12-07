/**
 * @module navigator/more-tab
 */
jn.define('navigator/more-tab', (require, exports, module) => {
	const { BaseNavigator } = require('navigator/base');
	const { NOTIFICATION_EVENTS, SUBSCRIPTION_EVENTS } = require('navigator/more-tab/meta');

	/**
	 * @class MoreTabNavigator
	 */
	class MoreTabNavigator extends BaseNavigator
	{
		subscribeToPushNotifications(MoreTabMenu)
		{
			this.subscribeToTaskNotification(MoreTabMenu);
			this.subscribeToCrmNotification(MoreTabMenu);
			this.subscribeToInviteNotification();
		}

		unsubscribeFromPushNotifications()
		{
			BX.removeCustomEvent(NOTIFICATION_EVENTS.TASKS, this.onTaskNotification.bind(this));
			BX.removeCustomEvent(NOTIFICATION_EVENTS.CRM, this.onCrmNotification.bind(this));
			BX.removeCustomEvent(NOTIFICATION_EVENTS.INVITE, this.onInviteNotification.bind(this));
		}

		subscribeToTaskNotification(MoreTabMenu)
		{
			BX.addCustomEvent(NOTIFICATION_EVENTS.TASKS, this.onTaskNotification.bind(this, MoreTabMenu));
			this.onSubscribeToPushNotification(SUBSCRIPTION_EVENTS.TASKS);
		}

		onTaskNotification(MoreTabMenu)
		{
			if (!this.isActiveTab())
			{
				this.makeTabActive();
			}

			const taskMenuItem = MoreTabMenu.getItemById('tasks_tabs');
			if (taskMenuItem)
			{
				MoreTabMenu.triggerItemOnClick(taskMenuItem);
			}
			else
			{
				console.error('Task menu item not found');
			}
		}

		subscribeToCrmNotification(MoreTabMenu)
		{
			BX.addCustomEvent(NOTIFICATION_EVENTS.CRM, this.onCrmNotification.bind(this, MoreTabMenu));
			this.onSubscribeToPushNotification(SUBSCRIPTION_EVENTS.CRM);
		}

		onCrmNotification(MoreTabMenu)
		{
			if (!this.isActiveTab())
			{
				this.makeTabActive();
			}

			const crmMenuItem = MoreTabMenu.getItemById('crm_tabs');
			if (crmMenuItem)
			{
				MoreTabMenu.triggerItemOnClick(crmMenuItem);
			}
			else
			{
				console.error('CRM menu item not found');
			}
		}

		subscribeToInviteNotification()
		{
			BX.addCustomEvent(NOTIFICATION_EVENTS.INVITE, this.onInviteNotification.bind(this));

			this.onSubscribeToPushNotification(SUBSCRIPTION_EVENTS.INVITE);
		}

		async onInviteNotification()
		{
			const { openIntranetInviteWidget } = await requireLazy('intranet:invite-opener-new') || {};
			if (openIntranetInviteWidget)
			{
				this.makeTabActive();

				openIntranetInviteWidget({});
			}
			else
			{
				console.error('Invite opener not found');
			}
		}
	}

	module.exports = {
		MoreTabNavigator,
	};
});
