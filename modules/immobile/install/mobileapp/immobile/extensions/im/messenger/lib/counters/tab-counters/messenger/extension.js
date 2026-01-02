/**
 * @module im/messenger/lib/counters/tab-counters/messenger
 */
jn.define('im/messenger/lib/counters/tab-counters/messenger', (require, exports, module) => {
	const { Type } = require('type');
	const {
		CounterType,
		EventType,
		DialogType,
		NavigationTabId,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { BaseTabCounters } = require('im/messenger/lib/counters/tab-counters/base');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const logger = getLoggerWithContext('counters--messenger', 'MessengerCounters');

	/**
	 * @class MessengerCounters
	 */
	class MessengerCounters extends BaseTabCounters
	{
		constructor()
		{
			super();

			this.subscribeStoreEvents();
			this.subscribeInitMessengerEvent();
		}

		initCounters()
		{
			this.notificationCounters = 0;
			/** @type {Record<string, Set<number>>} */
			this.unreadCollection = {
				chats: new Set(),
				copilot: new Set(),
				collab: new Set(),
			};

			this.lastCounters = {
				chats: null,
				openlines: null,
				copilot: null,
				collab: null,
				tasksTask: null,
			};
		}

		subscribeStoreEvents()
		{
			serviceLocator.get('core').getStoreManager()
				.on('counterModel/set', this.#setCounterHandler)
				.on('counterModel/delete', this.#deleteCounterHandler)
				.on('counterModel/setDisable', this.#setDisableHandler)
				.on('recentModel/update', this.#updateRecentHandler)
			;
		}

		update()
		{
			this.clearUpdateTimeout();

			const counters = {
				chats: 0,
				openlines: 0,
				copilot: 0,
				collab: 0,
				tasksTask: 0,
			};

			const counterList = this.store.getters['counterModel/getList']();
			const disabledCollection = new Set();
			for (const counterModelState of counterList)
			{
				if (counterModelState.disabled)
				{
					disabledCollection.add(counterModelState.chatId);
				}
			}

			for (const counterState of counterList)
			{
				if (counterState.disabled)
				{
					continue;
				}

				if (counterState.counter === 0)
				{
					continue;
				}

				if (counterState.type === CounterType.openline)
				{
					counters.openlines += counterState.counter;
					continue;
				}

				if (counterState.type === CounterType.copilot)
				{
					this.unreadCollection.copilot.delete(counterState.chatId);
					counters.copilot += counterState.counter;
					continue;
				}

				if (counterState.type === CounterType.collab)
				{
					this.unreadCollection.collab.delete(counterState.chatId);
					counters.collab += counterState.counter;
				}

				if (counterState.type === CounterType.tasksTask)
				{
					this.unreadCollection.tasksTask.delete(counterState.chatId);
					counters.tasksTask += counterState.counter;
					continue;
				}

				if (
					counterState.type === CounterType.comment
					&& disabledCollection.has(counterState.parentChatId)
				)
				{
					continue;
				}

				this.unreadCollection.chats.delete(counterState.chatId);
				counters.chats += counterState.counter;
			}

			const calculateUnreadCounters = (counter, chatId) => {
				if (disabledCollection.has(chatId))
				{
					return counter;
				}

				return counter + 1;
			};

			counters.chats += [...this.unreadCollection.chats.values()]
				.reduce((counter, chatId) => calculateUnreadCounters(counter, chatId), 0)
			;

			counters.copilot += [...this.unreadCollection.copilot.values()]
				.reduce((counter, chatId) => calculateUnreadCounters(counter, chatId), 0)
			;

			counters.collab += [...this.unreadCollection.collab.values()]
				.reduce((counter, chatId) => calculateUnreadCounters(counter, chatId), 0)
			;

			if (Type.isSet(this.unreadCollection.tasksTask))
			{
				counters.tasksTask += [...this.unreadCollection.tasksTask.values()]
					.reduce((counter, chatId) => calculateUnreadCounters(counter, chatId), 0)
				;
			}

			logger.log('update', counters);

			this.sendCountersToExternalComponents(counters);
			this.updateUi(counters);
		}

		sendCountersToExternalComponents(counters)
		{
			const communicationCounters = {
				...counters,
				chats: counters.chats - counters.copilot, // for legacy
				notifications: this.notificationCounters,
			};

			BX.postComponentEvent('ImRecent::counter::messages', [counters.chats], 'calls');
			BX.postComponentEvent('ImRecent::counter::list', [communicationCounters], 'communication');
		}

		updateUi(counters)
		{
			Object.entries(counters).forEach(([tabId, counter]) => {
				if (Type.isNumber(this.lastCounters[tabId]) && this.lastCounters[tabId] === counter)
				{
					return;
				}
				this.lastCounters[tabId] = counter;
				const uiTabId = this.#prepareTabId(tabId);

				tabs.updateItem(uiTabId, {
					counter,
					label: counter ? counter.toString() : '',
				});
			});
		}

		setNotificationCounters(counter)
		{
			this.notificationCounters = counter;
		}

		clearNotificationCounters()
		{
			this.notificationCounters = 0;
		}

		clearAll()
		{
			this.update();
		}

		deleteCounterByChatId(chatId)
		{}

		updateCounterDetailByCounterState(counterState)
		{}

		/**
		 * @param {immobileTabChatLoadResult} data
		 */
		async handleCountersGet(data)
		{
			const counters = data?.imCounters;

			if (!data || !Type.isPlainObject(counters))
			{
				logger.error(`${this.getClassName()}.handleCountersGet`, counters);

				return;
			}

			logger.log(`${this.getClassName()}.handleCountersGet`, counters);

			this.mutedChatCollection = this.getMutedCollectionByImCounters(counters);
			this.notificationCounters = counters.type.notify;

			this.unreadCollection = {
				chats: new Set(counters.chatUnread ?? []),
				copilot: new Set(counters.copilotUnread ?? []),
				collab: new Set(counters.collabUnread ?? []),
				tasksTask: new Set(counters.tasksTaskUnread ?? []),
			};

			try
			{
				await this.fillCounterStore(counters);
			}
			catch (error)
			{
				logger.error(error);
			}

			this.reloadNotifications();
		}

		reloadNotifications()
		{
			MessengerEmitter.emit(EventType.notification.reload);
		}

		async fillCounterStore(counters)
		{
			const counterStateList = this.#prepareInitialCounters(counters);

			await this.store.dispatch('counterModel/setList', {
				counterList: counterStateList,
			});
		}

		/**
		 * @param {MutationPayload<CounterSetData, CounterSetActions>} payload
		 */
		#setCounterHandler = ({ payload }) => {
			this.update();
		};

		/**
		 * @param {MutationPayload<CounterDeleteData, CounterDeleteActions>} payload
		 */
		#deleteCounterHandler = ({ payload }) => {
			if (payload.actionName === 'clear')
			{
				for (const unreadCollection of Object.values(this.unreadCollection))
				{
					unreadCollection.clear();
				}
			}
			this.update();
		};

		#setDisableHandler = ({ payload }) => {
			this.update();
		};

		/**
		 * @param {MutationPayload<RecentV2UpdateData, RecentV2UpdateActions>} payload
		 */
		#updateRecentHandler = ({ payload }) => {
			if (payload.actionName !== 'update')
			{
				return;
			}

			const { recentItemList } = payload.data;
			let hasUnreadCountersUpdated = false;

			for (const updatingRecentItem of recentItemList)
			{
				if (!Type.isBoolean(updatingRecentItem.fields.unread))
				{
					continue;
				}

				const chat = serviceLocator.get('core').getStore()
					.getters['dialoguesModel/getById'](updatingRecentItem.fields.id)
				;

				if (Type.isNil(chat))
				{
					continue;
				}
				hasUnreadCountersUpdated = true;

				this.#setToUnreadCollections(chat.chatId, chat.type, updatingRecentItem.fields.unread);
			}

			if (!hasUnreadCountersUpdated)
			{
				return;
			}

			this.update();
		};

		#setToUnreadCollections(chatId, chatType, unread)
		{
			const unreadCollectionList = this.#getUnreadCollectionsByChatType(chatType);

			for (const unreadCollection of unreadCollectionList)
			{
				if (unread)
				{
					unreadCollection.add(chatId);
				}
				else
				{
					unreadCollection.delete(chatId);
				}
			}
		}

		#prepareTabId(rawTabId)
		{
			if (rawTabId === CounterType.tasksTask)
			{
				return NavigationTabId.task;
			}

			return rawTabId;
		}

		/**
		 * @param type
		 * @return {Array<Set<number>>}
		 */
		#getUnreadCollectionsByChatType(type)
		{
			if (type === DialogType.lines)
			{
				return [];
			}

			if (type === DialogType.tasksTask)
			{
				return [this.unreadCollection.tasksTask];
			}

			const unreadCollections = [];
			if (type === DialogType.copilot)
			{
				unreadCollections.push(this.unreadCollection.copilot);
			}

			if (type === DialogType.collab)
			{
				unreadCollections.push(this.unreadCollection.collab);
			}

			unreadCollections.push(this.unreadCollection.chats);

			return unreadCollections;
		}

		/**
		 * @param {immobileTabsLoadCommonResult['imCounters']} counters
		 * @return {Array<CounterState>}
		 */
		#prepareInitialCounters(counters)
		{
			/**
			 * @type {CounterModelState}
			 */
			const chatCounters = Object.entries(counters.chat).map(([chatId, counter]) => {
				return {
					chatId: Number(chatId),
					parentChatId: 0,
					counter,
					type: CounterType.chat,
					disabled: false,
				};
			});

			const mutedCounters = Object.entries(counters.chatMuted).map(([chatId, counter]) => {
				return {
					chatId: Number(chatId),
					parentChatId: 0,
					counter,
					type: CounterType.chat,
					disabled: true,
				};
			});

			const collabCounters = Object.entries(counters.collab).map(([chatId, counter]) => {
				return {
					chatId: Number(chatId),
					parentChatId: 0,
					counter,
					type: CounterType.collab,
					disabled: false,
				};
			});

			const copilotCounters = Object.entries(counters.copilot).map(([chatId, counter]) => {
				return {
					chatId: Number(chatId),
					parentChatId: 0,
					counter,
					type: CounterType.copilot,
					disabled: false,
				};
			});

			let tasksTaskCounters = [];
			if (Type.isPlainObject(counters.tasksTask))
			{
				tasksTaskCounters = Object.entries(counters.tasksTask).map(([chatId, counter]) => {
					return {
						chatId: Number(chatId),
						parentChatId: 0,
						counter,
						type: CounterType.tasksTask,
						disabled: false,
					};
				});
			}

			const linesCounters = Object.entries(counters.lines).map(([chatId, counter]) => {
				return {
					chatId: Number(chatId),
					parentChatId: 0,
					counter,
					type: CounterType.openline,
					disabled: false,
				};
			});

			const commentCounters = Object.entries(counters.channelComment)
				.flatMap(([channelChatId, counterMap]) => {
					return Object.entries(counterMap).map(([commentChatId, counter]) => {
						return {
							chatId: Number(commentChatId),
							parentChatId: Number(channelChatId),
							counter,
							type: CounterType.comment,
						};
					});
				})
			;

			return [
				...chatCounters,
				...mutedCounters,
				...collabCounters,
				...copilotCounters,
				...linesCounters,
				...commentCounters,
				...tasksTaskCounters,
			];
		}
	}

	module.exports = { MessengerCounters };
});
