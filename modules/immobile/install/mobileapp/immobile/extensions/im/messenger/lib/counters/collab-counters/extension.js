/**
 * @module im/messenger/lib/counters/collab-counters
 */
jn.define('im/messenger/lib/counters/collab-counters', (require, exports, module) => {
	const { Type } = require('type');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { BaseCounters } = require('im/messenger/lib/counters/lib/base-counters');
	const { Counter } = require('im/messenger/lib/counters/lib/counter');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('counters--collab');

	/**
	 * @class CollabCounters
	 */
	class CollabCounters extends BaseCounters
	{
		initCounters()
		{
			this.collabCounter = new Counter();
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

			Object.keys(counters.collab).forEach((chatId) => {
				this.collabCounter.detail[`chat${chatId}`] = counters.collab[chatId];
			});

			this.collabCounter.value = counters.type.collab;
			this.update();
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

			this.collabCounter.reset();
		}
	}

	module.exports = { CollabCounters };
});
