/**
 * @module im/messenger/provider/push/message-handler/counter
 */
jn.define('im/messenger/provider/push/message-handler/counter', (require, exports, module) => {
	const { Type } = require('type');
	const { WaitingEntity } = require('im/messenger/const');
	const { BasePushMessageHandler } = require('im/messenger/provider/push/message-handler/base');
	const { getLogger } = require('im/messenger/lib/logger');
	const { CounterHelper } = require('im/messenger/lib/helper');

	const logger = getLogger('push-handler');

	/**
	 * @class CounterPushMessageHandler
	 */
	class CounterPushMessageHandler extends BasePushMessageHandler
	{
		/** @type {CounterStorageWriter} */
		#storageWriter;

		/**
		 * @param {CounterStorageWriter} storage
		 */
		constructor(storage)
		{
			super();

			this.#storageWriter = storage;
		}

		getHandlerId()
		{
			return WaitingEntity.push.messageHandler.counter;
		}

		filterMessageEvents(eventList)
		{
			return eventList.filter((event) => {
				const helper = this.getHelper(event);

				return !helper.isLines();
			});
		}

		async setUsers(users = [])
		{
			// override
		}

		async setFiles(files = [])
		{
			// override
		}

		async setRecent(recentItems = [])
		{
			// override
		}

		async setMessages(messages = [])
		{
			// override
		}

		/**
		 * @param {Array<RawChat>} dialogs
		 * @return {Promise<void>}
		 */
		async setDialogs(dialogs = [])
		{
			logger.log(`${this.className}.setDialogs`, dialogs);
			if (!Type.isArrayFilled(dialogs))
			{
				return;
			}

			/** @type {CounterStateCollection} */
			const counterCollection = {};

			dialogs.forEach((dialog) => {
				const chatId = dialog.chatId ?? dialog.id;

				counterCollection[chatId] = {
					chatId,
					counter: dialog.counter,
					parentChatId: 0,
					type: CounterHelper.getCounterTypeByDialogType(dialog.type),
				};
			});

			this.#storageWriter.setCollection(counterCollection)
				.catch((error) => {
					logger.error(`${this.className}.setDialogs error`, error);
				})
			;
		}
	}

	module.exports = { CounterPushMessageHandler };
});
