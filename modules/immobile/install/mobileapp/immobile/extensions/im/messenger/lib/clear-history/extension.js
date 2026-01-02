/**
 * @module im/messenger/lib/clear-history
 */
jn.define('im/messenger/lib/clear-history', (require, exports, module) => {
	const { TabCounters } = require('im/messenger/lib/counters/tab-counters');
	const { DialogHelper } = require('im/messenger/lib/helper/dialog');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessageDataProvider } = require('im/messenger/provider/data');
	const { recentDefaultElement } = require('im/messenger/model/recent');

	/**
	 * @class ClearChatHistory
	 */
	class ClearChatHistory
	{
		/**
		 * @param {DialogId} dialogId
		 */
		static async clear(dialogId)
		{
			const store = serviceLocator.get('core').getStore();
			await (new MessageDataProvider()).deleteByChat({ dialogId });

			await this.#updateRecentModel(store, dialogId);
			await this.#updateDialoguesModel(store, dialogId);

			const chatId = DialogHelper.getDialogModel(dialogId)?.chatId;
			if (chatId)
			{
				TabCounters.deleteCounterByChatId(chatId);
				TabCounters.update();
			}
		}

		static #updateDialoguesModel(store, dialogId)
		{
			return store.dispatch('dialoguesModel/update', {
				dialogId,
				fields: {
					counter: 0,
					lastMessageId: 0,
					lastMessageViews: {},
					tariffRestrictions: {
						isHistoryLimitExceeded: false,
					},
				},
			});
		}

		static #updateRecentModel(store, dialogId)
		{
			return store.dispatch('recentModel/update', [
				{
					dialogId,
					liked: false,
					message: recentDefaultElement.message,
					dateMessage: null,
				},
			]);
		}
	}

	module.exports = {
		ClearChatHistory,
	};
});
