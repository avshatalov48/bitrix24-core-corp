/**
 * @module im/messenger/lib/counters/chat-counters
 */
jn.define('im/messenger/lib/counters/chat-counters', (require, exports, module) => {
	const { EntityReady } = require('entity-ready');
	const { Type } = require('type');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { BaseCounters } = require('im/messenger/lib/counters/lib/base-counters');
	const { Counter } = require('im/messenger/lib/counters/lib/counter');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { EventType, ComponentCode } = require('im/messenger/const');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('chat-counters');

	/**
	 * @class ChatCounters
	 */
	class ChatCounters extends BaseCounters
	{
		constructor()
		{
			super({ logger });
		}

		get isCollabLaunched()
		{
			return EntityReady.isReady(`${ComponentCode.imCollabMessenger}::launched`);
		}

		get isCopilotLaunched()
		{
			return EntityReady.isReady(`${ComponentCode.imCopilotMessenger}::launched`);
		}

		get isChannelLaunched()
		{
			return EntityReady.isReady(`${ComponentCode.imChannelMessenger}::launched`);
		}

		initCounters()
		{
			this.chatCounter = new Counter();
			this.collabCounter = new Counter();
			this.copilotCounter = new Counter();
			this.openlinesCounter = new Counter();
			this.notificationCounter = new Counter();
		}

		/**
		 * @param {immobileTabChatLoadResult} data
		 */
		handleCountersGet(data)
		{
			const counters = data?.imCounters;

			if (!data || !Type.isPlainObject(counters))
			{
				logger.error(`${this.getClassName()}.handleCountersGet`, counters);

				return;
			}

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
			this.setCommentCounters(counters.channelComment);

			if (!this.isCollabLaunched)
			{
				Object.keys(counters.collab).forEach((chatId) => {
					this.collabCounter.detail[`chat${chatId}`] = counters.collab[chatId];
				});
			}

			if (!this.isCopilotLaunched)
			{
				Object.keys(counters.copilot).forEach((chatId) => {
					this.copilotCounter.detail[`chat${chatId}`] = counters.copilot[chatId];
				});
			}

			if (!this.isChannelLaunched)
			{
				const channelComment = data?.imCounters?.channelComment;

				if (channelComment)
				{
					this.store.dispatch('commentModel/setCounters', channelComment);
				}
			}

			MessengerEmitter.emit(EventType.notification.reload);
			this.update();
		}

		update()
		{
			this.clearUpdateTimeout();

			this.chatCounter.reset();
			this.openlinesCounter.reset();
			this.collabCounter.reset();
			this.copilotCounter.reset();

			const userId = serviceLocator.get('core').getUserId();

			this.chatCounter.value = this.store.getters['recentModel/getCollection']()
				.reduce((counter, item) => {
					const dialog = this.store.getters['dialoguesModel/getById'](item.id)
						|| this.store.getters['dialoguesModel/getByChatId'](item.id)
					;

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
						return counter + this.calculateChatCounter(item, dialog);
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

			if (!this.isCollabLaunched)
			{
				this.collabCounter.value = this.store.getters['recentModel/getCollection']()
					.reduce((counter, item) => {
						const dialog = this.store.getters['dialoguesModel/getById'](item.id)
							|| this.store.getters['dialoguesModel/getByChatId'](item.id)
						;

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

						const isCollab = DialogHelper.createByModel(dialog)?.isCollab;
						if (!isCollab)
						{
							return counter;
						}

						if (Type.isUndefined(this.collabCounter.detail[item.id]) && !item.id.includes('chat'))
						{
							delete this.collabCounter.detail[`chat${dialog.chatId}`];
						}
						else
						{
							delete this.collabCounter.detail[item.id];
						}

						const isChatMuted = dialog && dialog.muteList.includes(userId);
						if (!isChatMuted)
						{
							return counter + this.calculateChatCounter(item, dialog);
						}

						return counter;
					}, 0)
				;

				counters.collab = this.collabCounter.value;

				this.collabCounter.update();
			}

			if (!this.isCopilotLaunched)
			{
				this.copilotCounter.update();
				counters.copilot = this.copilotCounter.value;
			}

			logger.log(`${this.getClassName()}.update`, counters);

			BX.postComponentEvent('ImRecent::counter::messages', [this.chatCounter.value], 'calls');
			BX.postComponentEvent('ImRecent::counter::list', [counters], 'communication');
			BX.postComponentEvent('ImRecent::counter::list', [counters], 'im.navigation');
		}

		/**
		 * @param {Record<number, Record<number, number>>} countersCollection
		 */
		setCommentCounters(countersCollection)
		{
			if (!countersCollection)
			{
				return;
			}

			// erase invalid local counters
			Object.entries(countersCollection).forEach(([channelChatId, countersMap]) => {
				const channelCountersCollection = this.store.getters['commentModel/getChannelCounterCollection'](channelChatId);

				Object.keys(channelCountersCollection).forEach((commentChatId) => {
					if (!countersCollection[channelChatId][commentChatId])
					{
						countersCollection[channelChatId][commentChatId] = 0;
					}
				});
			});

			this.store.dispatch('commentModel/setCounters', countersCollection);
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
