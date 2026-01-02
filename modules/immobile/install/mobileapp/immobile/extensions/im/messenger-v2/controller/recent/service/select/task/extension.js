/**
 * @module im/messenger-v2/controller/recent/service/select/task
 */
jn.define('im/messenger-v2/controller/recent/service/select/task', (require, exports, module) => {
	const { EventType } = require('im/messenger/const');
	const { BaseUiRecentService } = require('im/messenger-v2/controller/recent/service/base');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { openTaskComments } = require('im/messenger/lib/integration/tasksmobile/comments/opener');

	/**
	 * @implements {ISelectService}
	 * @class TaskSelectService
	 */
	class TaskSelectService extends BaseUiRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}

		/**
		 * @private
		 * @return {MessengerCoreStore}
		 */
		get store()
		{
			return serviceLocator.get('core').getStore();
		}

		async onUiReady(ui)
		{
			this.logger.log('onUiReady');
			ui?.on(EventType.recent.itemSelected, this.onItemSelected);
		}

		/**
		 * @param {ItemSelectedEventData} itemData
		 */
		onItemSelected = async (itemData) => {
			void this.openTaskComments(itemData.id);
		};

		async openTaskComments(dialogId)
		{
			try
			{
				const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
				void openTaskComments(dialog.chatId, Number(dialog.entityId));
			}
			catch (error)
			{
				this.logger.error('openDialog error', error);
			}
		}
	}

	module.exports = TaskSelectService;
});
