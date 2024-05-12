/**
 * @module im/messenger/lib/counters/chat-counters
 */
jn.define('im/messenger/lib/counters/chat-counters', (require, exports, module) => {
	const { BaseCounters } = require('im/messenger/lib/counters/lib/base-counters');
	const { Counter } = require('im/messenger/lib/counters/lib/counter');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { EventType } = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { Type } = require('type');
	const logger = LoggerManager.getInstance().getLogger('chat-counters');

	/**
	 * @class ChatCounters
	 */
	class ChatCounters extends BaseCounters
	{
		constructor()
		{
			super({ logger });
		}

		initCounters()
		{
			this.chatCounter = new Counter();
			this.openlinesCounter = new Counter();
			this.notificationCounter = new Counter();
		}

		handleCountersGet(response)
		{
			const error = response.error();
			if (error)
			{
				logger.error(`${this.getClassName()}.handleCountersGet`, error);

				return;
			}

			const counters = response.data();
			logger.log(`${this.getClassName()}.handleCountersGet`, counters);

			counters.chatUnread.forEach((chatId) => {
				this.chatCounter.detail[`chat${chatId}`] = 1;
			});

			Object.keys(counters.chat).forEach((chatId) => {
				this.chatCounter.detail[`chat${chatId}`] = counters.chat[chatId];
			});

			Object.keys(counters.lines).forEach((chatId) => {
				this.openlinesCounter.detail[`chat${chatId}`] = counters.lines[chatId];
			});

			this.chatCounter.value = counters.type.chat;
			this.openlinesCounter.value = counters.type.lines;
			this.notificationCounter.value = counters.type.notify;

			MessengerEmitter.emit(EventType.notification.reload);
			this.update();
		}

		update()
		{
			this.clearUpdateTimeout();

			this.chatCounter.reset();
			this.openlinesCounter.reset();

			const userId = MessengerParams.getUserId();

			this.chatCounter.value = this.store.getters['recentModel/getCollection']()
				.reduce((counter, item) => {
					const dialog = this.store.getters['dialoguesModel/getById'](item.id)
						|| this.store.getters['dialoguesModel/getByChatId'](item.id);

					if (!dialog)
					{
						logger.error(`${this.getClassName()}.update: there is no dialog "${item.id}" in model`);

						return counter;
					}

					if (dialog.chatId === 0)
					{
						logger.warn(`${this.getClassName()}.update: "${item.id}" fake dialog without chatId in model`, dialog);

						return counter;
					}

					if (Type.isUndefined(this.chatCounter.detail[item.id]) && !item.id.includes('chat'))
					{
						delete this.chatCounter.detail[`chat${dialog.chatId}`];
					}
					else
					{
						delete this.chatCounter.detail[item.id];
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

			logger.log(`${this.getClassName()}.update`, counters);

			BX.postComponentEvent('ImRecent::counter::messages', [this.chatCounter.value], 'calls');
			BX.postComponentEvent('ImRecent::counter::list', [counters], 'communication');
			BX.postComponentEvent('ImRecent::counter::list', [counters], 'im.navigation');
		}

		clearAll()
		{
			logger.log(`${this.getClassName()}.clearAll`);

			this.chatCounter.reset();
			this.openlinesCounter.reset();
			this.notificationCounter.reset();
		}
	}

	module.exports = { ChatCounters };
});
