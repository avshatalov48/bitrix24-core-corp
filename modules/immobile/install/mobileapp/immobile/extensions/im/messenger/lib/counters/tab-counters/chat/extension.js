/**
 * @module im/messenger/lib/counters/tab-counters/chat
 */
jn.define('im/messenger/lib/counters/tab-counters/chat', (require, exports, module) => {
	const { EntityReady } = require('entity-ready');
	const { Type } = require('type');

	const {
		EventType,
		ComponentCode,
		CounterType,
	} = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { BaseTabCounters } = require('im/messenger/lib/counters/tab-counters/base');
	const { TabCounterState } = require('im/messenger/lib/counters/tab-counters/state');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { DialogHelper } = require('im/messenger/lib/helper');

	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('chat-counters');

	/**
	 * @deprecated
	 * @see MessengerCounters
	 * @class ChatCounters
	 */
	class ChatCounters extends BaseTabCounters
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

		get userId()
		{
			return serviceLocator.get('core').getUserId();
		}

		initCounters()
		{
			this.chatCounter = new TabCounterState();
			this.collabCounter = new TabCounterState();
			this.copilotCounter = new TabCounterState();
			this.openlinesCounter = new TabCounterState();
			this.notificationCounter = new TabCounterState();
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

			this.mutedChatCollection = this.getMutedCollectionByImCounters(counters);

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
				counters.collabUnread.forEach((chatId) => {
					this.collabCounter.detail[`chat${chatId}`] = 1;
				});

				Object.keys(counters.collab).forEach((chatId) => {
					this.collabCounter.detail[`chat${chatId}`] = counters.collab[chatId];
				});
			}

			if (!this.isCopilotLaunched)
			{
				counters.copilotUnread?.forEach((chatId) => {
					this.copilotCounter.detail[`chat${chatId}`] = 1;
				});

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

			this.sendCountersToCounterService(counters);
		}

		update()
		{
			this.clearUpdateTimeout();

			this.chatCounter.reset();
			this.openlinesCounter.reset();
			this.collabCounter.reset();
			this.copilotCounter.reset();

			this.chatCounter.value = this.getRecentCollection()
				.reduce((counter, item) => {
					const dialog = this.getDialog(item);
					if (!this.validateDialog(item, dialog))
					{
						return counter;
					}

					this.deleteCounterDetails(this.chatCounter, item, dialog.chatId);

					if (Type.isArray(dialog.muteList) && !dialog.muteList.includes(this.userId))
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
				this.collabCounter.value = this.getRecentCollection()
					.reduce((counter, item) => {
						const dialog = this.getDialog(item);
						if (!this.validateDialog(item, dialog))
						{
							return counter;
						}

						const isCollab = DialogHelper.createByModel(dialog)?.isCollab;
						if (!isCollab)
						{
							return counter;
						}

						this.deleteCounterDetails(this.collabCounter, item, dialog.chatId);

						return this.calculateCounter(counter, item, dialog);
					}, 0)
				;
				this.collabCounter.update();
				counters.collab = this.collabCounter.value;
			}

			if (!this.isCopilotLaunched)
			{
				this.copilotCounter.value = this.getRecentCollection()
					.reduce((counter, item) => {
						const dialog = this.getDialog(item);
						if (!this.validateDialog(item, dialog))
						{
							return counter;
						}

						const isCopilot = DialogHelper.createByModel(dialog)?.isCopilot;
						if (!isCopilot)
						{
							return counter;
						}

						this.deleteCounterDetails(this.copilotCounter, item, dialog.chatId);

						return this.calculateCounter(counter, item, dialog);
					}, 0)
				;

				this.copilotCounter.update();
				counters.copilot = this.copilotCounter.value;
			}

			logger.log(`${this.getClassName()}.update`, counters);

			const communicationCounters = { ...counters };
			communicationCounters.copilot = 0;

			BX.postComponentEvent('ImRecent::counter::messages', [this.chatCounter.value], 'calls');
			BX.postComponentEvent('ImRecent::counter::list', [communicationCounters], 'communication');
			BX.postComponentEvent('ImRecent::counter::list', [counters], 'im.navigation');
		}

		calculateCounter(counter, item, dialog)
		{
			const isChatMuted = dialog && dialog.muteList.includes(this.userId);
			if (!isChatMuted)
			{
				return counter + this.calculateChatCounter(item, dialog);
			}

			return counter;
		}

		deleteCounterDetails(counter, item, chatId) {
			const key = Type.isUndefined(counter.detail[item.id]) && !item.id.includes('chat')
				? `chat${chatId}`
				: item.id;

			// eslint-disable-next-line no-param-reassign
			delete counter.detail[key];
		}

		getDialog(item)
		{
			return this.store.getters['dialoguesModel/getById'](item.id)
				|| this.store.getters['dialoguesModel/getByChatId'](item.id);
		}

		validateDialog(item, dialog)
		{
			if (!dialog)
			{
				logger.error(`${this.getClassName()}.update: there is no dialog "${item.id}" in model`);

				return false;
			}

			if (dialog.chatId === 0)
			{
				logger.warn(
					`${this.getClassName()}.update: "${item.id}" fake dialog without chatId in model`,
					dialog,
				);

				return false;
			}

			return true;
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

			this.chatCounter.clearAll();
			this.openlinesCounter.clearAll();
			// this.notificationCounter.reset();

			if (!this.isCollabLaunched)
			{
				this.collabCounter.clearAll();
			}

			if (!this.isCopilotLaunched)
			{
				this.copilotCounter.clearAll();
			}

			MessengerEmitter.broadcast(EventType.counters.clearAll);
			this.update();
		}

		deleteCounterByChatId(chatId)
		{
			const counterId = this.getIdByChatId(chatId);
			if (!Type.isNil(this.chatCounter.detail[counterId]))
			{
				delete this.chatCounter.detail[counterId];
			}

			if (!Type.isNil(this.collabCounter.detail[counterId]))
			{
				delete this.collabCounter.detail[counterId];
			}

			if (!Type.isNil(this.copilotCounter.detail[counterId]))
			{
				delete this.copilotCounter.detail[counterId];
			}

			if (!Type.isNil(this.openlinesCounter.detail[counterId]))
			{
				delete this.openlinesCounter.detail[counterId];
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
				if (!this.isCollabLaunched)
				{
					this.collabCounter.detail[counterId] = counter;
				}

				this.chatCounter.detail[counterId] = counter;

				return;
			}

			if (type === CounterType.copilot)
			{
				if (!this.isCopilotLaunched)
				{
					this.copilotCounter.detail[counterId] = counter;
				}

				this.chatCounter.detail[counterId] = counter;

				return;
			}

			if (type === CounterType.comment)
			{
				// comments counters are not stored in TabCounterState
				return;
			}

			if (type === CounterType.openline)
			{
				this.openlinesCounter.detail[counterId] = counter;

				return;
			}

			this.chatCounter.detail[counterId] = counter;
		}

		setNotificationCounters(counter)
		{
			this.notificationCounter.value = counter;
		}

		clearNotificationCounters()
		{
			this.notificationCounter.value = 0;
		}
	}

	module.exports = { ChatCounters };
});
