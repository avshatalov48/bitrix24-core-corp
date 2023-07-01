/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/counters/counters
 */
jn.define('im/messenger/lib/counters/counters', (require, exports, module) => {

	const { core } = require('im/messenger/core');
	const { Logger } = require('im/messenger/lib/logger');
	const { Counter } = require('im/messenger/lib/counters/counter');
	const { restManager } = require('im/messenger/lib/rest-manager');
	const { RestMethod, EventType } = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { MessengerParams } = require('im/messenger/lib/params');

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

			restManager.on(RestMethod.imCountersGet, { JSON: 'Y' }, this.handleCountersGet.bind(this));
		}

		handleCountersGet(response)
		{
			const error = response.error();
			if (error)
			{
				Logger.error('Counters.handleCountersGet', error);

				return;
			}

			const counters = response.data();
			Logger.log('Counters.handleCountersGet', counters);

			this.chatCounter.detail = counters.dialog;
			this.openlinesCounter.detail = {};

			counters.dialogUnread.forEach((dialogId) =>
			{
				this.chatCounter.detail[dialogId] = 1;
			});

			counters.chatUnread.forEach((chatId) =>
			{
				this.chatCounter.detail['chat' + chatId] = 1;
			});

			Object.keys(counters.chat).forEach(chatId => {
				this.chatCounter.detail['chat' + chatId] = counters.chat[chatId];
			});

			Object.keys(counters.lines).forEach(chatId => {
				this.openlinesCounter.detail['chat' + chatId] = counters.lines[chatId];
			});

			this.chatCounter.value = counters.type.chat + counters.type.dialog;
			this.openlinesCounter.value = counters.type.lines;
			this.notificationCounter.value = counters.type.notify;

			MessengerEmitter.emit(EventType.notification.reload);

			this.update();
		}

		updateDelayed()
		{
			Logger.log('Counters.updateDelayed');

			if (!this.updateTimeout)
			{
				this.updateTimeout = setTimeout(() => this.update(), this.updateInterval);
			}
		}

		update()
		{
			Logger.info('Counters.update');
			this.clearUpdateTimeout();

			this.chatCounter.reset();
			this.openlinesCounter.reset();

			const userId = MessengerParams.getUserId();

			this.chatCounter.value =
				this.store.getters['recentModel/getCollection']
					.reduce((counter, item) => {
						delete this.chatCounter.detail[item.id];

						if (item.type === 'user')
						{
							return counter + this.calculateItemCounter(item);
						}

						if (item.type === 'chat' && !item.chat.mute_list[userId])
						{
							return counter + this.calculateItemCounter(item);
						}

						return counter;
					}, 0)
			;

			this.chatCounter.update();
			this.openlinesCounter.update();

			const counters = {
				'chats': this.chatCounter.value,
				'openlines': this.openlinesCounter.value,
				'notifications': this.notificationCounter.value,
			};

			BX.postComponentEvent('ImRecent::counter::messages', [this.chatCounter.value], 'calls');
			BX.postComponentEvent('ImRecent::counter::list', [counters], 'communication');
			BX.postComponentEvent('ImRecent::counter::list', [counters], 'im.navigation');
		}

		calculateItemCounter(item = {})
		{
			let counter = 0;
			if (item.counter && item.counter > 0)
			{
				counter = item.counter;
			}
			else if (item.unread)
			{
				counter = 1;
			}

			return counter;
		};

		clearUpdateTimeout()
		{
			clearTimeout(this.updateTimeout);
			this.updateTimeout = null;
		}

		clearAll()
		{
			Logger.log('Counters.clearAll');

			this.chatCounter.reset();
			this.openlinesCounter.reset();
			this.notificationCounter.reset();
		}
	}

	module.exports = {
		Counters: new Counters(),
	};
});
