/**
 * @module im/messenger/lib/counters/tab-counters/collab
 */
jn.define('im/messenger/lib/counters/tab-counters/collab', (require, exports, module) => {
	const { Type } = require('type');

	const { EventType, CounterType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { BaseTabCounters } = require('im/messenger/lib/counters/tab-counters/base');
	const { TabCounterState } = require('im/messenger/lib/counters/tab-counters/state');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('counters--collab');

	/**
	 * @deprecated
	 * @see MessengerCounters
	 * @class CollabCounters
	 */
	class CollabCounters extends BaseTabCounters
	{
		initCounters()
		{
			this.collabCounter = new TabCounterState();
		}

		subscribeEvents()
		{
			BX.addCustomEvent(EventType.counters.clearAll, this.#clearAllHandler);
		}

		/**
		 * @param {immobileTabChatLoadResult} data
		 */
		handleCountersGet(data)
		{
			const counters = data?.imCounters;
			if (!Type.isPlainObject(counters))
			{
				logger.error(`${this.getClassName()}.handleCountersGet`, counters);

				return;
			}

			logger.log(`${this.getClassName()}.handleCountersGet`, counters);

			this.mutedChatCollection = this.getMutedCollectionByImCounters(counters);

			counters.collabUnread.forEach((chatId) => {
				this.collabCounter.detail[`chat${chatId}`] = 1;
			});

			Object.keys(counters.collab).forEach((chatId) => {
				this.collabCounter.detail[`chat${chatId}`] = counters.collab[chatId];
			});

			this.collabCounter.value = counters.type.collab;
			this.update();
			this.sendCountersToCounterService(counters);
		}

		update()
		{
			this.clearUpdateTimeout();
			this.collabCounter.reset();

			const userId = serviceLocator.get('core').getUserId();

			this.collabCounter.value = this.store.getters['recentModel/getCollection']()
				.reduce((counter, item) => {
					delete this.collabCounter.detail[item.id];

					const dialog = this.store.getters['dialoguesModel/getById'](item.id);
					if (!dialog)
					{
						logger.error(`${this.getClassName()}.update: there is no dialog "${item.id}" in model`);

						return counter;
					}

					const isGroupChat = DialogHelper.isDialogId(dialog.dialogId);
					const isChatMuted = dialog && dialog.muteList.includes(userId);
					if (isGroupChat && !isChatMuted)
					{
						return counter + this.calculateChatCounter(item, dialog);
					}

					return counter;
				}, 0)
			;

			this.collabCounter.update();

			const counters = {
				collab: this.collabCounter.value,
			};

			logger.log(`${this.getClassName()}.update`, counters);

			BX.postComponentEvent('ImRecent::counter::list', [counters], 'im.navigation');
		}

		clearAll()
		{
			logger.log(`${this.getClassName()}.clearAll`);

			this.collabCounter.clearAll();
		}

		#clearAllHandler = () => {
			this.clearAll();
			this.update();
		};

		deleteCounterByChatId(chatId)
		{
			const counterId = this.getIdByChatId(chatId);
			if (!Type.isNil(this.collabCounter.detail[counterId]))
			{
				delete this.collabCounter.detail[counterId];
			}
		}

		updateCounterDetailByCounterState(counterState)
		{
			const {
				type,
				counter,
				chatId,
			} = counterState;

			if (this.mutedChatCollection.has(chatId))
			{
				return;
			}

			const counterId = this.getIdByChatId(chatId);

			if (type === CounterType.collab)
			{
				this.collabCounter.detail[counterId] = counter;
			}
		}
	}

	module.exports = { CollabCounters };
});
