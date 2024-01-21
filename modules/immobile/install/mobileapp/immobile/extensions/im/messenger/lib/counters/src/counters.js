/**
 * @module im/messenger/lib/counters/counters
 */
jn.define('im/messenger/lib/counters/counters', (require, exports, module) => {
	const { core } = require('im/messenger/core');
	const { Counter } = require('im/messenger/lib/counters/counter');
	const { restManager } = require('im/messenger/lib/rest-manager');
	const { RestMethod, EventType } = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const logger = LoggerManager.getInstance().getLogger('counters');

	/**
	 * @class Counters
	 */
	class Counters
	{
		constructor()
		{
			this.store = core.getStore();
			this.updateTimeout = null;
			this.updateInterval = 300;

			this.chatCounter = new Counter();
			this.openlinesCounter = new Counter();
			this.notificationCounter = new Counter();
		}

		initRequests()
		{
			restManager.on(RestMethod.imCountersGet, { JSON: 'Y' }, this.handleCountersGet.bind(this));
		}

		handleCountersGet(response)
		{
			const error = response.error();
			if (error)
			{
				logger.error('Counters.handleCountersGet', error);

				return;
			}

			const counters = response.data();
			logger.log('Counters.handleCountersGet', counters);

			this.chatCounter.detail = counters.dialog;
			this.openlinesCounter.detail = {};

			counters.dialogUnread.forEach((dialogId) => {
				this.chatCounter.detail[dialogId] = 1;
			});

			counters.chatUnread.forEach((chatId) => {
				this.chatCounter.detail[`chat${chatId}`] = 1;
			});

			Object.keys(counters.chat).forEach((chatId) => {
				this.chatCounter.detail[`chat${chatId}`] = counters.chat[chatId];
			});

			Object.keys(counters.lines).forEach((chatId) => {
				this.openlinesCounter.detail[`chat${chatId}`] = counters.lines[chatId];
			});

			this.chatCounter.value = counters.type.chat + counters.type.dialog;
			this.openlinesCounter.value = counters.type.lines;
			this.notificationCounter.value = counters.type.notify;

			MessengerEmitter.emit(EventType.notification.reload);

			this.update();
		}

		updateDelayed()
		{
			logger.log('Counters.updateDelayed');

			if (!this.updateTimeout)
			{
				this.updateTimeout = setTimeout(() => this.update(), this.updateInterval);
			}
		}

		update()
		{
			this.clearUpdateTimeout();

			this.chatCounter.reset();
			this.openlinesCounter.reset();

			const userId = MessengerParams.getUserId();

			this.chatCounter.value = this.store.getters['recentModel/getCollection']()
				.reduce((counter, item) => {
					delete this.chatCounter.detail[item.id];

					const dialog = this.store.getters['dialoguesModel/getById'](item.id);
					if (!dialog)
					{
						logger.error(`Counters.update: there is no dialog "${item.id}" in model`);

						return counter;
					}

					if (DialogHelper.isChatId(dialog.dialogId))
					{
						return counter + this.calculateItemCounter(item, dialog);
					}

					if (
						DialogHelper.isDialogId(dialog.dialogId)
						&& !(dialog && dialog.muteList.includes(userId)))
					{
						return counter + this.calculateItemCounter(item, dialog);
					}

					return counter;
				}, 0)
			;
			this.chatCounter.update();
			this.openlinesCounter.update();

			const counters = {
				chats: this.chatCounter.value,
				openlines: this.openlinesCounter.value,
				notifications: this.notificationCounter.value,
			};

			logger.log('Counters.update', counters);

			BX.postComponentEvent('ImRecent::counter::messages', [this.chatCounter.value], 'calls');
			BX.postComponentEvent('ImRecent::counter::list', [counters], 'communication');
			BX.postComponentEvent('ImRecent::counter::list', [counters], 'im.navigation');
		}

		/**
		 * @param {RecentModelState} recentItem
		 * @param {DialoguesModelState} dialogItem
		 * @return {number}
		 */
		calculateItemCounter(recentItem = {}, dialogItem = {})
		{
			let counter = 0;
			if (dialogItem.counter && dialogItem.counter > 0)
			{
				counter = dialogItem.counter;
			}
			else if (recentItem.unread)
			{
				counter = 1;
			}

			return counter;
		}

		clearUpdateTimeout()
		{
			clearTimeout(this.updateTimeout);
			this.updateTimeout = null;
		}

		clearAll()
		{
			logger.log('Counters.clearAll');

			this.chatCounter.reset();
			this.openlinesCounter.reset();
			this.notificationCounter.reset();
		}
	}

	module.exports = {
		Counters: new Counters(),
	};
});
