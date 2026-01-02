/**
 * @module im/messenger/lib/notifier
 */
jn.define('im/messenger/lib/notifier', (require, exports, module) => {
	/* global InAppNotifier, include  */
	const { Type } = require('type');
	const { transparent } = require('utils/color');

	const { Theme } = require('im/lib/theme');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const {
		EventType,
		RecentTab,
		NavigationTabId,
	} = require('im/messenger/const');
	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Feature } = require('im/messenger/lib/feature');
	const { RecentManager } = require('im/messenger-v2/controller/recent/manager');

	// TODO: MessengerV2 move to helper
	const RecentTabByNavigationTab = {
		[NavigationTabId.chats]: RecentTab.chat,
		[NavigationTabId.copilot]: RecentTab.copilot,
		[NavigationTabId.collab]: RecentTab.collab,
		[NavigationTabId.channel]: RecentTab.openChannel,
		[NavigationTabId.task]: RecentTab.tasksTask,
	};

	/**
	 * @class Notifier
	 */
	class Notifier
	{
		constructor()
		{
			include('InAppNotifier');

			this.delayShow = {};
			this.visibilityManager = VisibilityManager.getInstance();

			this.isInitialized = !Type.isUndefined(InAppNotifier);
			if (this.isInitialized)
			{
				InAppNotifier.setHandler((data) => {
					if (data && data.dialogId)
					{
						if (data.dialogId === 'notify')
						{
							MessengerEmitter.emit(EventType.messenger.openNotifications);

							return;
						}

						MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId: data.dialogId });
					}
				});
			}
		}

		/**
		 * Sends an in-app notification
		 *
		 * @param {Object} options
		 * @param {string} options.dialogId
		 * @param {string} options.title
		 * @param {string} options.text
		 * @param {object} options.recentConfig
		 * @param {string} [options.avatar]
		 * @param delay
		 *
		 * @returns {Promise<boolean>} has a notification been sent
		 */
		async notify(options, delay = true)
		{
			if (!this.isInitialized || !options.dialogId)
			{
				return false;
			}

			clearTimeout(this.delayShow[options.dialogId]);
			if (delay !== false)
			{
				this.delayShow[options.dialogId] = setTimeout(
					() => this.notify(options, false),
					1500,
				);

				return true;
			}

			/** @type NavigationContext * */
			const navigationContext = await PageManager.getNavigator().getNavigationContext();
			if (navigationContext.isTabActive)
			{
				if (Feature.isMessengerV2Enabled)
				{
					if (Type.isPlainObject(options.recentConfig) && Type.isArrayFilled(options.recentConfig.sections))
					{
						const sections = options.recentConfig.sections;
						const tabId = RecentManager.getInstance().getActiveRecentId();
						const currentRecentTab = RecentTabByNavigationTab[tabId];
						if (sections.includes(currentRecentTab))
						{
							return false;
						}
					}
				}
				else
				{
					const activeTabInfo = await this.visibilityManager.getActiveTabInfo();
					if (activeTabInfo.componentCode === MessengerParams.getComponentCode())
					{
						return false;
					}
				}
			}

			const isDialogVisible = await this.visibilityManager.checkIsDialogVisible({
				dialogId: options.dialogId,
			});
			if (isDialogVisible)
			{
				return false;
			}

			this.showNotification(options);

			return true;
		}

		showNotification(options)
		{
			const notification = {
				title: jnComponent.convertHtmlEntities(options.title),
				backgroundColor: transparent(Theme.colors.baseBlackFixed, 0.8),
				message: jnComponent.convertHtmlEntities(options.text),
				data: options,
			};

			if (options.avatar)
			{
				notification.imageUrl = options.avatar;
			}

			InAppNotifier.showNotification(notification);
		}
	}

	module.exports = {
		Notifier: new Notifier(),
	};
});
