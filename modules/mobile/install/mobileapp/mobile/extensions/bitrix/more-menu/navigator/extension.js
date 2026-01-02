/**
 * @module more-menu/navigator
 */
jn.define('more-menu/navigator', (require, exports, module) => {
	const { BaseNavigator } = require('navigator/base');
	const { NOTIFICATION_EVENTS, SUBSCRIPTION_EVENTS } = require('navigator/more-tab/meta');
	const {
		handleItemClick,
	} = require('more-menu/utils');
	const { Type } = require('type');

	/**
	 * @class MenuNavigator
	 */
	class MenuNavigator extends BaseNavigator
	{
		/**
		 *
		 * @param props
		 * @param {array} props.menuList
		 */
		constructor(props)
		{
			super(props);

			this.menuList = Type.isArrayFilled(props.menuList) ? props.menuList : [];
		}

		updateMenuList(menuList)
		{
			if (Type.isArrayFilled(menuList))
			{
				this.menuList = menuList;
			}
			else
			{
				this.menuList = [];
			}
		}

		subscribeToEvents()
		{
			this.unsubscribeFromEvents();

			this.subscribeToTaskNotification();
			this.subscribeToCrmNotification();
			this.subscribeToInviteNotification();
		}

		unsubscribeFromEvents()
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

		async onTaskNotification()
		{
			if (!this.isActiveTab())
			{
				await this.makeTabActive();
			}
			const taskItem = this.getItemById('tasks');
			if (taskItem)
			{
				handleItemClick(taskItem);
			}
			else
			{
				console.error('Task item is not found in menu');
			}
		}

		subscribeToCrmNotification()
		{
			BX.addCustomEvent(NOTIFICATION_EVENTS.CRM, this.onCrmNotification.bind(this));
			this.onSubscribeToPushNotification(SUBSCRIPTION_EVENTS.CRM);
		}

		async onCrmNotification()
		{
			if (!this.isActiveTab())
			{
				await this.makeTabActive();
			}

			const crmMenuItem = this.getItemById('crm');
			if (crmMenuItem)
			{
				handleItemClick(crmMenuItem);
			}
			else
			{
				console.error('CRM menu item not found');
			}
		}

		getItemById(id)
		{
			for (const section of this.menuList)
			{
				const item = section?.items.find((sectionItem) => sectionItem?.id === id);
				if (item)
				{
					return item;
				}
			}

			return null;
		}

		subscribeToInviteNotification()
		{
			BX.addCustomEvent(NOTIFICATION_EVENTS.INVITE, this.onInviteNotification.bind(this));

			this.onSubscribeToPushNotification(SUBSCRIPTION_EVENTS.INVITE);
		}

		async onInviteNotification()
		{
			if (!this.isActiveTab())
			{
				await this.makeTabActive();
			}
			const usersItem = this.getItemById('users');
			if (usersItem)
			{
				const params = usersItem.params || {};

				handleItemClick({
					...usersItem,
					params: {
						openInviteOnMount: true,
						...params,
					},
				});
			}
			else
			{
				console.error('Users menu item not found');
			}
		}
	}

	module.exports = {
		MenuNavigator,
	};
});
