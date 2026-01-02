/**
 * @module im/messenger-v2/controller/recent/service/action/common
 */
jn.define('im/messenger-v2/controller/recent/service/action/common', (require, exports, module) => {
	const { isOnline } = require('device/connection');
	const { EventType } = require('im/messenger/const');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { BaseUiRecentService } = require('im/messenger-v2/controller/recent/service/base');
	const { handlers } = require('im/messenger-v2/controller/recent/service/action/lib/handler');

	/**
	 * @implements {IActionService}
	 * @class CommonActionService
	 */
	class CommonActionService extends BaseUiRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}

		async onUiReady(ui)
		{
			this.logger.log('onUiReady');
			ui?.on(EventType.recent.itemAction, this.onItemAction);
		}

		/**
		 * @param {ItemActionEventData} itemActionData
		 */
		onItemAction = async (itemActionData) => {
			this.logger.log('onItemAction', itemActionData);

			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const action = itemActionData.action?.identifier;
			const itemId = itemActionData.item.params?.id;
			if (!action || !itemId)
			{
				this.logger.error('onItemAction invalid event data:', action, itemId);

				return;
			}

			if (!handlers[action])
			{
				this.logger.error(`onItemAction unknown action: ${action}`);

				return;
			}

			try
			{
				await handlers[action]({ store: this.store, logger: this.logger, recentLocator: this.recentLocator }, itemId);
			}
			catch (error)
			{
				this.logger.error(`onItemAction error handling action "${action}":`, error);
			}
		};

		/**
		 * @return {MessengerCoreStore}
		 */
		get store()
		{
			return serviceLocator.get('core').getStore();
		}
	}

	module.exports = CommonActionService;
});
