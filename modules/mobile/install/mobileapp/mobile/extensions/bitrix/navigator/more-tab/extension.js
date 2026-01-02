/**
 * @module navigator/more-tab
 */
jn.define('navigator/more-tab', (require, exports, module) => {
	const { BaseNavigator } = require('navigator/base');
	const { NOTIFICATION_EVENTS, SUBSCRIPTION_EVENTS } = require('navigator/more-tab/meta');
	const { EntityReady } = require('entity-ready');
	const { AnalyticsEvent } = require('analytics');

	let TASKS_ROOT_COMPONENT_NAME = null;
	let TASKS_TABS_NAVIGATOR = null;
	try
	{
		TASKS_ROOT_COMPONENT_NAME = require('tasks/navigator/meta').TASKS_ROOT_COMPONENT_NAME;
		TASKS_TABS_NAVIGATOR = require('tasks/navigator/meta').TASKS_TABS_NAVIGATOR;
	}
	catch (e)
	{
		console.warn(e);
	}

	/**
	 * @class MoreTabNavigator
	 */
	class MoreTabNavigator extends BaseNavigator
	{
		subscribeToEvents(MoreTabMenu)
		{
			this.subscribeToTaskNotification(MoreTabMenu);
			this.subscribeToCrmNotification(MoreTabMenu);
			this.subscribeToInviteNotification();
			this.subscribeToOpenTasksTabs(MoreTabMenu);
		}

		unsubscribeFromEvents()
		{
			BX.removeCustomEvent(NOTIFICATION_EVENTS.TASKS, this.onTaskNotification.bind(this));
			BX.removeCustomEvent(NOTIFICATION_EVENTS.CRM, this.onCrmNotification.bind(this));
			BX.removeCustomEvent(NOTIFICATION_EVENTS.INVITE, this.onInviteNotification.bind(this));
			BX.removeCustomEvent(NOTIFICATION_EVENTS.INVITE, this.onInviteNotification.bind(this));

			if (TASKS_TABS_NAVIGATOR)
			{
				BX.removeCustomEvent(TASKS_TABS_NAVIGATOR.makeActive, this.openTaskTab.bind(this));
			}
		}

		subscribeToTaskNotification(MoreTabMenu)
		{
			BX.addCustomEvent(NOTIFICATION_EVENTS.TASKS, this.onTaskNotification.bind(this, MoreTabMenu));
			this.onSubscribeToPushNotification(SUBSCRIPTION_EVENTS.TASKS);
		}

		subscribeToOpenTasksTabs(MoreTabMenu)
		{
			if (TASKS_TABS_NAVIGATOR)
			{
				BX.addCustomEvent(TASKS_TABS_NAVIGATOR.makeActive, (data) => {
					this.openTaskTab.bind(this, MoreTabMenu)(data);
				});
			}
		}

		async openTaskTab(MoreTabMenu, tabId)
		{
			const taskMenuItem = MoreTabMenu.getItemById('tasks_tabs');
			if (taskMenuItem && TASKS_ROOT_COMPONENT_NAME && TASKS_TABS_NAVIGATOR)
			{
				if (!this.isActiveTab())
				{
					this.makeTabActive();
				}

				MoreTabMenu.triggerItemOnClick(taskMenuItem);
				await EntityReady.wait(TASKS_ROOT_COMPONENT_NAME);
				BX.postComponentEvent(TASKS_TABS_NAVIGATOR.makeTabActive, [tabId]);
			}
			else
			{
				console.error('Task menu item not found');
			}
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

				openIntranetInviteWidget({
					analytics: new AnalyticsEvent().setSection('marketing_push'),
				});
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
