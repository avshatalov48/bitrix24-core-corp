/* eslint-disable no-param-reassign */
/**
 * @module im/messenger-v2/model/recent/model
 */
jn.define('im/messenger-v2/model/recent/model', (require, exports, module) => {
	const { Type } = require('type');
	const { Uuid } = require('utils/uuid');

	const {
		DialogType,
		RecentTab,
		NavigationTabId,
	} = require('im/messenger/const');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { ModelUtils } = require('im/messenger/lib/utils');
	const { MessengerMutationHandlersWaiter } = require('im/messenger/lib/state-manager/vuex-manager/mutation-handlers-waiter');
	const { recentDefaultElement } = require('im/messenger/model/recent/default-element');
	const { searchModel } = require('im/messenger/model/recent/search/model');

	const { normalize } = require('im/messenger-v2/model/recent/normalizer');

	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('model--recent');

	const MutationSetCollectionIdByTab = {
		[NavigationTabId.chats]: 'setChatIdCollection',
		[NavigationTabId.copilot]: 'setCopilotIdCollection',
		[NavigationTabId.channel]: 'setChannelIdCollection',
		[NavigationTabId.collab]: 'setCollabIdCollection',
		[NavigationTabId.task]: 'setTaskIdCollection',
	};

	const MutationHideCollectionIdByTab = {
		[NavigationTabId.chats]: 'deleteFromChatIdCollection',
		[NavigationTabId.copilot]: 'deleteFromCopilotIdCollection',
		[NavigationTabId.channel]: 'deleteFromChannelIdCollection',
		[NavigationTabId.collab]: 'deleteFromCollabIdCollection',
		[NavigationTabId.task]: 'deleteFromTaskIdCollection',
	};

	const CollectionByTab = {
		[NavigationTabId.chats]: 'chatIdCollection',
		[NavigationTabId.copilot]: 'copilotIdCollection',
		[NavigationTabId.channel]: 'channelIdCollection',
		[NavigationTabId.collab]: 'collabIdCollection',
		[NavigationTabId.task]: 'taskIdCollection',
	};

	// TODO: MessengerV2 move to helper
	const NavigationTabByRecentTab = {
		[RecentTab.chat]: NavigationTabId.chats,
		[RecentTab.copilot]: NavigationTabId.copilot,
		[RecentTab.collab]: NavigationTabId.collab,
		[RecentTab.openChannel]: NavigationTabId.channel,
		[RecentTab.tasksTask]: NavigationTabId.task,
	};

	const RECENT_MODEL_NAME = 'recentModel';
	const FIRST_PAGE_SIZE = 50;

	/** @type {RecentMessengerModelV2} */
	const recentModel = {
		namespaced: true,
		state: () => ({
			collection: {},
			chatIdCollection: new Set(),
			copilotIdCollection: new Set(),
			channelIdCollection: new Set(),
			collabIdCollection: new Set(),
			taskIdCollection: new Set(),
		}),
		modules: {
			searchModel,
		},
		getters: {
			/**
			 * @function recentModel/getById
			 * @return {?RecentModelState}
			 */
			getById: (state) => (id) => {
				return state.collection[String(id)];
			},

			/**
			 * @typedef {Function} recentModelGetByChatId
			 * @param {number} chatId
			 * @return {?RecentModelState}
			 * @alias recentModel/getByChatId
			 */
			/**
			 * @return {recentModelGetByChatId}
			 */
			getByChatId: (state, getters, rootState, rootGetters) => (chatId) => {
				const dialogModel = rootGetters['dialoguesModel/getByChatId'](chatId);

				return state.collection[dialogModel?.dialogId];
			},

			/**
			 * @function recentModel/getChatIdCollection
			 * @return {Set<string>}
			 */
			getChatIdCollection: (state) => () => {
				return new Set(state.chatIdCollection);
			},

			/**
			 * @function recentModel/getChatFirstPage
			 * @return {Array<RecentModelState>}
			 */
			getChatFirstPage: (state, getters) => () => {
				return getters.getFirstPageByIdCollection(state.chatIdCollection);
			},

			/**
			 * @function recentModel/getCopilotIdCollection
			 * @return {Set<string>}
			 */
			getCopilotIdCollection: (state) => () => {
				return new Set(state.copilotIdCollection);
			},

			/**
			 * @function recentModel/getCopilotFirstPage
			 * @return {Array<RecentModelState>}
			 */
			getCopilotFirstPage: (state, getters) => () => {
				return getters.getFirstPageByIdCollection(state.copilotIdCollection);
			},

			/**
			 * @function recentModel/getCollabIdCollection
			 * @return {Set<string>}
			 */
			getCollabIdCollection: (state) => () => {
				return new Set(state.collabIdCollection);
			},

			/**
			 * @function recentModel/getCollabFirstPage
			 * @return {Array<RecentModelState>}
			 */
			getCollabFirstPage: (state, getters) => () => {
				return getters.getFirstPageByIdCollection(state.collabIdCollection);
			},

			/**
			 * @function recentModel/getChannelIdCollection
			 * @return {Set<string>}
			 */
			getChannelIdCollection: (state) => () => {
				return new Set(state.channelIdCollection);
			},

			/**
			 * @function recentModel/getChannelFirstPage
			 * @return {Array<RecentModelState>}
			 */
			getChannelFirstPage: (state, getters) => () => {
				return getters.getFirstPageByIdCollection(state.channelIdCollection, sortListByMessageDate);
			},

			/**
			 * @function recentModel/getTaskIdCollection
			 * @return {Set<string>}
			 */
			getTaskIdCollection: (state) => () => {
				return new Set(state.taskIdCollection);
			},

			/**
			 * @function recentModel/getTaskFirstPage
			 * @return {Array<RecentModelState>}
			 */
			getTaskFirstPage: (state, getters) => () => {
				return getters.getFirstPageByIdCollection(state.taskIdCollection);
			},

			/**
			 * @function recentModel/getFirstPageByIdCollection
			 * @return {Array<RecentModelState>}
			 */
			getFirstPageByIdCollection: (state, getters, rootState, rootGetters) => (idCollection, sortFunction) => {
				const items = [];
				for (const dialogId of idCollection)
				{
					if (Type.isStringFilled(dialogId))
					{
						const item = state.collection[dialogId];
						if (item)
						{
							items.push(item);
						}
					}
				}
				const sorter = sortFunction?.(rootGetters) ?? sortByAggregatedActivityDateWithPinned(rootGetters);

				return items.sort(sorter).slice(0, FIRST_PAGE_SIZE);
			},

			/**
			 * @function recentModel/getChatCollection
			 * @return {Array<RecentModelState>}
			 */
			getChatCollection: (state) => () => {
				return [...state.chatIdCollection]
					.filter((dialogId) => {
						return Type.isStringFilled(dialogId);
					})
					.map((id) => {
						return state.collection[id];
					})
					.sort(sortByAggregatedActivityDate);
			},

			/**
			 * @function recentModel/getCollection
			 * @return {Array<RecentModelState>}
			 */
			getCollection: (state) => () => {
				return Object.values(state.collection).map((item) => ({ ...item }));
			},

			/**
			 * @function recentModel/getByIdList
			 * @return {Array<RecentModelState>}
			 */
			getByIdList: (state) => (idList) => {
				return Object.values(state.collection)
					.filter((recentItem) => idList.includes(recentItem.id))
				;
			},

			/**
			 * @typedef {Function} getByChatIdList
			 * @param {Array<number>} chatIdList
			 * @return {Array<RecentModelState>}
			 * @alias recentModel/getByChatIdList
			 */
			/**
			 * @return {getByChatIdList}
			 */
			getByChatIdList: (state, getters, rootState, rootGetters) => (chatIdList) => {
				if (!Type.isArrayFilled(chatIdList))
				{
					return [];
				}

				const recentList = [];
				Object.values(state.collection).forEach((recentItem) => {
					const dialogModel = rootGetters['dialoguesModel/getById'](recentItem.id);

					if (chatIdList.includes(dialogModel?.chatId))
					{
						recentList.push(recentItem);
					}
				});

				return recentList;
			},

			/**
			 * @function recentModel/getTabsContainsItem
			 * @return {Array<string>} // value NavigationTabId properties
			 */
			getTabsContainsItem: (state) => (id) => {
				return [
					state.chatIdCollection.has(id) ? NavigationTabId.chats : null,
					state.copilotIdCollection.has(id) ? NavigationTabId.copilot : null,
					state.channelIdCollection.has(id) ? NavigationTabId.channel : null,
					state.collabIdCollection.has(id) ? NavigationTabId.collab : null,
					state.taskIdCollection.has(id) ? NavigationTabId.task : null,
				].filter(Boolean);
			},

			/**
			 * @function recentModel/getCollectionSizeByTabId
			 * @return {number|null}
			 */
			getCollectionSizeByTabId: (state) => (tabId) => {
				return state[CollectionByTab[tabId]]?.size;
			},

			/**
			 * @function recentModel/getSortedCollection
			 * @return {Array<RecentModelState>}
			 */
			getSortedCollection: (state, getters) => () => {
				const collectionAsArray = getters.getCollection().filter(
					(item) => Boolean(item.message.id),
				);

				return [...collectionAsArray].sort((a, b) => {
					return b.message.date - a.message.date;
				});
			},

			/**
			 * @function recentModel/getUserList
			 * @return {Array<RecentModelState>}
			 */
			getUserList: (state, getters, rootState, rootGetters) => () => {
				return Object.keys(state.collection).filter((dialogId) => {
					return !dialogId.startsWith('chat') && rootGetters['usersModel/getById'](dialogId);
				}).sort(sortByLastActivityDateOnly);
			},

			/**
			 * @typedef {Function} recentModelHasItemInTab
			 * @param {string} dialogId
			 * @param {string} navigationTabId
			 * @return {boolean}
			 * @alias recentModel/hasItemInTab
			 */
			/**
			 * @return {recentModelHasItemInTab}
			 */
			hasItemInTab: (state) => (dialogId, navigationTabId) => {
				if (!Object.values(NavigationTabId).includes(navigationTabId))
				{
					logger.error('recentModel/hasItemInTab invalid navigationTabId', navigationTabId);

					return false;
				}

				if (navigationTabId === NavigationTabId.chats)
				{
					return state.chatIdCollection.has(dialogId);
				}

				if (navigationTabId === NavigationTabId.copilot)
				{
					return state.copilotIdCollection.has(dialogId);
				}

				if (navigationTabId === NavigationTabId.channel)
				{
					return state.channelIdCollection.has(dialogId);
				}

				if (navigationTabId === NavigationTabId.collab)
				{
					return state.collabIdCollection.has(dialogId);
				}

				if (navigationTabId === NavigationTabId.task)
				{
					return state.taskIdCollection.has(dialogId);
				}

				return false;
			},

			/**
			 * @function recentModel/needsBirthdayPlaceholder
			 * @return {boolean}
			 */
			needsBirthdayPlaceholder: (state, getters, rootState, rootGetters) => (dialogId) => {
				const currentItem = getters.getById(dialogId);
				if (!currentItem)
				{
					return false;
				}

				const dialog = rootGetters['dialoguesModel/getById'](dialogId);
				if (!dialog || (dialog.type !== DialogType.user && dialog.type !== DialogType.private))
				{
					return false;
				}

				const hasBirthday = rootGetters['usersModel/hasBirthday'](dialogId);
				if (!hasBirthday)
				{
					return false;
				}

				const hasMessage = Uuid.isV4(currentItem.message.id) || currentItem.message.id > 0;
				const hasTodayMessage = hasMessage && DateFormatter.isToday(currentItem.message.date);

				return !hasTodayMessage && dialog.counter === 0;
			},

			/**
			 * @function recentModel/needsBirthdayIcon
			 * @return {boolean}
			 */
			needsBirthdayIcon: (state, getters, rootState, rootGetters) => (dialogId) => {
				const currentItem = getters.getById(dialogId);
				if (!currentItem)
				{
					return false;
				}

				const dialog = rootGetters['dialoguesModel/getById'](dialogId);
				if (!dialog || (dialog.type !== DialogType.user && dialog.type !== DialogType.private))
				{
					return false;
				}

				return rootGetters['usersModel/hasBirthday'](dialogId);
			},

			/**
			 * @function recentModel/needsVacationIcon
			 * @return {boolean}
			 */
			needsVacationIcon: (state, getters, rootState, rootGetters) => (dialogId) => {
				const currentItem = getters.getById(dialogId);
				if (!currentItem)
				{
					return false;
				}

				const dialog = rootGetters['dialoguesModel/getById'](dialogId);
				if (!dialog || (dialog.type !== DialogType.user && dialog.type !== DialogType.private))
				{
					return false;
				}

				return rootGetters['usersModel/hasVacation'](dialogId);
			},
		},
		actions: {
			/** @function recentModel/setChat */
			setChat: async (store, payload) => {
				const { itemList } = ModelUtils.normalizeItemListPayload(payload);
				const itemIds = itemList.map((item) => String(item.id || item.dialogId));
				store.commit('setChatIdCollection', {
					actionName: 'setChat',
					data: {
						itemIds,
					},
				});

				await store.dispatch('set', payload);
			},

			/** @function recentModel/setCopilot */
			setCopilot: async (store, payload) => {
				const { itemList } = ModelUtils.normalizeItemListPayload(payload);
				const itemIds = itemList.map((item) => String(item.id || item.dialogId));
				store.commit('setCopilotIdCollection', {
					actionName: 'setCopilot',
					data: {
						itemIds,
					},
				});

				await store.dispatch('set', payload);
			},

			/** @function recentModel/setChannel */
			setChannel: async (store, payload) => {
				const { itemList } = ModelUtils.normalizeItemListPayload(payload);
				const itemIds = itemList.map((item) => String(item.id || item.dialogId));
				store.commit('setChannelIdCollection', {
					actionName: 'setChannel',
					data: {
						itemIds,
					},
				});

				await store.dispatch('set', payload);
			},

			/** @function recentModel/setCollab */
			setCollab: async (store, payload) => {
				const { itemList } = ModelUtils.normalizeItemListPayload(payload);
				const itemIds = itemList.map((item) => String(item.id || item.dialogId));
				store.commit('setCollabIdCollection', {
					actionName: 'setCollab',
					data: {
						itemIds,
					},
				});

				await store.dispatch('set', payload);
			},

			/** @function recentModel/setTask */
			setTask: async (store, payload) => {
				const { itemList } = ModelUtils.normalizeItemListPayload(payload);
				const itemIds = itemList.map((item) => String(item.id || item.dialogId));
				store.commit('setTaskIdCollection', {
					actionName: 'setTask',
					data: {
						itemIds,
					},
				});

				await store.dispatch('set', payload);
			},

			/** @function recentModel/setFirstPageByTab */
			setFirstPageByTab: async (store, payload) => {
				const { tab, itemList } = payload;
				const navTab = getNavigationTabId(tab);
				const itemIds = itemList.map((item) => String(item.id || item.dialogId));
				if (!CollectionByTab[navTab])
				{
					logger.error('RecentModel.setFirstPageByTab unknown tab:', navTab);

					return;
				}

				const actionName = 'setFirstPageByTab';
				await store.dispatch('set', { itemList, actionName });

				store.commit('storeIdCollection', {
					actionName,
					data: {
						tab: navTab,
						itemIds,
					},
				});
			},

			/** @function recentModel/setByNavigationTabs */
			setByNavigationTabs: async (store, payload) => {
				const {
					tabs,
					actionName = 'setByNavigationTabs',
				} = payload;
				let { itemList } = payload;

				if (!Type.isArrayFilled(tabs))
				{
					return;
				}

				if (!Type.isArray(itemList))
				{
					itemList = [itemList];
				}
				const itemIds = itemList.map((item) => String(item.id || item.dialogId));

				tabs.forEach((tab) => {
					const mutation = MutationSetCollectionIdByTab[tab];
					if (!mutation)
					{
						logger.error('RecentModel.setByRecentConfigTabs invalid tab:', tab);

						return;
					}

					store.commit(mutation, {
						actionName,
						data: {
							itemIds,
						},
					});
				});

				await store.dispatch('set', [...new Set(itemList)]);
			},

			/** @function recentModel/setByRecentConfigTabs */
			setByRecentConfigTabs: async (store, payload) => {
				const tabs = payload.tabs;
				if (!Type.isArrayFilled(tabs))
				{
					return;
				}

				let itemList = payload.itemList;
				if (!Type.isArray(itemList))
				{
					itemList = [itemList];
				}

				await store.dispatch('setByNavigationTabs', {
					tabs: tabs.map((tab) => getNavigationTabId(tab)),
					itemList,
					actionName: 'setByRecentConfigTabs',
				});
			},

			/** @function recentModel/setGroupCollection */
			setGroupCollection: async (store, payload) => {
				const groups = payload.groups;
				const tabs = Object.keys(groups);
				if (tabs.length === 0)
				{
					return;
				}

				const itemList = [];
				tabs.forEach((tab) => {
					const navTab = getNavigationTabId(tab);
					const mutation = MutationSetCollectionIdByTab[navTab];
					const items = groups[tab];
					if (!Type.isArrayFilled(items))
					{
						logger.log('RecentModel.setGroupCollection skipped by items is empty for:', tab);

						return;
					}

					if (!mutation)
					{
						logger.error('RecentModel.setGroupCollection invalid tab:', tab);

						return;
					}

					itemList.push(...items);
					const itemIds = items.map((item) => String(item.id || item.dialogId));

					store.commit(mutation, {
						actionName: 'setGroupCollection',
						data: {
							itemIds,
						},
					});
				});

				if (!Type.isArrayFilled(itemList))
				{
					return;
				}

				await store.dispatch('set', [...new Set(itemList)]);
			},

			/** @function recentModel/set */
			set: async (store, payload) => {
				/**
				 * @type {Array<RecentModelState>}
				 */
				const result = [];
				const { itemList, actionName = 'set' } = ModelUtils.normalizeItemListPayload(payload);

				if (Type.isArray(itemList))
				{
					itemList.forEach((recentItem) => {
						if (Type.isPlainObject(recentItem))
						{
							checkUploadingState(store, recentItem);

							result.push(normalize(recentItem));
						}
					});
				}

				const mutationWaiter = new MessengerMutationHandlersWaiter(RECENT_MODEL_NAME, actionName);
				const { newItems, existingItems } = splitItemsByExistence(store, result);
				if (newItems.length > 0)
				{
					mutationWaiter.addMutation('add');

					store.commit('add', {
						actionName,
						actionUid: mutationWaiter.actionUid,
						data: {
							recentItemList: newItems,
						},
					});
				}

				if (existingItems.length > 0)
				{
					mutationWaiter.addMutation('update');

					store.commit('update', {
						actionName,
						actionUid: mutationWaiter.actionUid,
						data: {
							recentItemList: existingItems,
						},
					});
				}

				await mutationWaiter.waitComplete();
			},

			/** @function recentModel/delete */
			delete: (store, payload) => {
				const existingItem = store.state.collection[payload.id];
				if (!existingItem)
				{
					return;
				}
				const actionName = payload.actionName || 'delete';

				store.commit('deleteFromChatIdCollection', { data: { id: existingItem.id }, actionName });
				store.commit('deleteFromCopilotIdCollection', { data: { id: existingItem.id }, actionName });
				store.commit('deleteFromChannelIdCollection', { data: { id: existingItem.id }, actionName });
				store.commit('deleteFromCollabIdCollection', { data: { id: existingItem.id }, actionName });
				store.commit('deleteFromTaskIdCollection', { data: { id: existingItem.id }, actionName });

				store.commit('delete', {
					actionName,
					data: {
						id: existingItem.id,
					},
				});
			},

			/** @function recentModel/deleteFromModel */
			deleteFromModel: async (store, payload) => {
				const existingItem = store.state.collection[payload.id];
				if (!existingItem)
				{
					return;
				}
				const actionName = payload.actionName || 'deleteFromModel';

				await store.dispatch('delete', { id: existingItem.id, actionName });
			},

			/** @function recentModel/deleteOpenChannel */
			deleteOpenChannel: (store, payload) => {
				const existingItem = store.state.collection[payload.id];
				if (!existingItem)
				{
					return;
				}
				const actionName = payload.actionName || 'deleteOpenChannel';

				store.commit('deleteFromChatIdCollection', { data: { id: existingItem.id }, actionName });
			},

			/** @function recentModel/hideByNavigationTabs */
			hideByNavigationTabs: (store, payload) => {
				const {
					id,
					fromTabs, // TODO fromTabs
					actionName = 'hideByNavigationTabs',
				} = payload;
				const existingItem = store.state.collection[id];
				if (!existingItem)
				{
					return;
				}

				if (!Type.isArrayFilled(fromTabs))
				{
					return;
				}

				fromTabs.forEach((tabId) => {
					const mutation = MutationHideCollectionIdByTab[tabId];
					if (!Type.isStringFilled(mutation))
					{
						logger.log(`hide action. unknown tabId: ${tabId}. skip `, tabId);

						return;
					}

					store.commit(mutation, {
						actionName,
						data: {
							id,
						},
					});
				});
			},

			/** @function recentModel/hideByRecentConfigTabs */
			hideByRecentConfigTabs: async (store, payload) => {
				const { id, fromTabs } = payload;
				const existingItem = store.state.collection[id];
				if (!existingItem)
				{
					return;
				}

				if (!Type.isArrayFilled(fromTabs))
				{
					return;
				}

				await store.dispatch('hideByNavigationTabs', {
					id,
					actionName: 'hideByRecentConfigTabs',
					fromCollections: fromTabs.map((tabId) => getNavigationTabId(tabId)),
				});
			},

			/** @function recentModel/update */
			update: (store, payload) => {
				/** @type {Array<Partial<RecentModelState>>} */
				const result = [];

				if (Type.isArray(payload))
				{
					payload.forEach((recentItem) => {
						if (Type.isPlainObject(recentItem))
						{
							result.push(normalize(recentItem));
						}
					});
				}

				if (result.length === 0)
				{
					return;
				}

				const existingItems = [];
				result.forEach((item) => {
					const existingItem = store.state.collection[item.id];

					if (!existingItem)
					{
						return;
					}

					existingItems.push({
						fields: item,
					});
				});

				if (existingItems.length === 0)
				{
					return;
				}

				store.commit('update', {
					actionName: 'update',
					data: {
						recentItemList: existingItems,
					},
				});
			},

			/** @function recentModel/like */
			like: (store, payload) => {
				const { id, messageId, liked } = payload;

				const existingItem = store.state.collection[id];
				if (!existingItem)
				{
					return;
				}

				if (
					!(Type.isUndefined(messageId) && liked === false)
					&& existingItem.message.id !== Number(messageId)
				)
				{
					return;
				}

				store.commit('update', {
					actionName: 'like',
					data: {
						recentItemList: [{ fields: { id, liked } }],
					},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<RecentV2SetIdCollectionData, RecentV2SetIdCollectionActions>} payload
			 */
			setChatIdCollection: (state, payload) => {
				logger.warn('RecentModel.setChatIdCollection', payload);
				const { data } = payload;
				data.itemIds.forEach((dialogId) => {
					state.chatIdCollection.add(dialogId);
				});
			},
			/**
			 * @param state
			 * @param {MutationPayload<RecentV2SetIdCollectionData, RecentV2SetIdCollectionActions>} payload
			 */
			setCopilotIdCollection: (state, payload) => {
				logger.warn('RecentModel.setCopilotIdCollection', payload);
				const { data } = payload;
				data.itemIds.forEach((dialogId) => {
					state.copilotIdCollection.add(dialogId);
				});
			},
			/**
			 * @param state
			 * @param {MutationPayload<RecentV2StoreIdCollectionData, RecentV2StoreIdCollectionActions>} payload
			 */
			storeIdCollection: (state, payload) => {
				logger.warn('RecentModel.storeIdCollection', payload);

				const { data } = payload;

				state[CollectionByTab[data.tab]] = new Set(data.itemIds);
			},

			/**
			 * @param state
			 * @param {MutationPayload<RecentV2SetIdCollectionData, RecentV2SetIdCollectionActions>} payload
			 */
			setChannelIdCollection: (state, payload) => {
				logger.warn('RecentModel.setChannelIdCollection', payload);
				const { data } = payload;
				data.itemIds.forEach((dialogId) => {
					state.channelIdCollection.add(dialogId);
				});
			},
			/**
			 * @param state
			 * @param {MutationPayload<RecentV2SetIdCollectionData, RecentV2SetIdCollectionActions>} payload
			 */
			setCollabIdCollection: (state, payload) => {
				logger.warn('RecentModel.setCollabIdCollection', payload);
				const { data } = payload;
				data.itemIds.forEach((dialogId) => {
					state.collabIdCollection.add(dialogId);
				});
			},
			/**
			 * @param state
			 * @param {MutationPayload<RecentV2SetIdCollectionData, RecentV2SetIdCollectionActions>} payload
			 */
			setTaskIdCollection: (state, payload) => {
				logger.warn('RecentModel.setTaskIdCollection', payload);
				const { data } = payload;
				data.itemIds.forEach((dialogId) => {
					state.taskIdCollection.add(dialogId);
				});
			},
			/**
			 * @param state
			 * @param {MutationPayload<RecentV2DeleteData, RecentDeleteActions>} payload
			 */
			deleteFromChatIdCollection: (state, payload) => {
				logger.warn('RecentModel.deleteFromChatIdCollection', payload);
				state.chatIdCollection.delete(payload.data.id);
			},
			/**
			 * @param state
			 * @param {MutationPayload<RecentV2DeleteData, RecentDeleteActions>} payload
			 */
			deleteFromCopilotIdCollection: (state, payload) => {
				logger.warn('RecentModel.deleteFromCopilotIdCollection', payload);
				state.copilotIdCollection.delete(payload.data.id);
			},
			/**
			 * @param state
			 * @param {MutationPayload<RecentV2DeleteData, RecentDeleteActions>} payload
			 */
			deleteFromChannelIdCollection: (state, payload) => {
				logger.warn('RecentModel.deleteFromChannelIdCollection', payload);
				state.channelIdCollection.delete(payload.data.id);
			},
			/**
			 * @param state
			 * @param {MutationPayload<RecentV2DeleteData, RecentDeleteActions>} payload
			 */
			deleteFromCollabIdCollection: (state, payload) => {
				logger.warn('RecentModel.deleteFromCollabIdCollection', payload);
				state.collabIdCollection.delete(payload.data.id);
			},
			/**
			 * @param state
			 * @param {MutationPayload<RecentV2DeleteData, RecentDeleteActions>} payload
			 */
			deleteFromTaskIdCollection: (state, payload) => {
				logger.warn('RecentModel.deleteFromTaskIdCollection', payload);
				state.taskIdCollection.delete(payload.data.id);
			},

			/**
			 * @param state
			 * @param {MutationPayload<RecentAddData, RecentAddActions>} payload
			 */
			add: (state, payload) => {
				logger.warn('RecentModel.add', payload);

				const {
					recentItemList,
				} = payload.data;

				recentItemList.forEach((item) => {
					state.collection[item.fields.id] = {
						...recentDefaultElement,
						...item.fields,
					};
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<RecentV2UpdateData, RecentV2UpdateActions>} payload
			 */
			update: (state, payload) => {
				logger.warn('RecentModel.update', payload);
				const {
					recentItemList,
				} = payload.data;

				recentItemList.forEach((item) => {
					const currentElement = state.collection[item.fields.id];

					item.fields.message = { ...currentElement?.message, ...item.fields.message };
					item.fields.options = { ...currentElement?.options, ...item.fields.options };

					state.collection[item.fields.id] = {
						...state.collection[item.fields.id],
						...item.fields,
					};
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<RecentV2DeleteData, RecentDeleteActions>} payload
			 */
			delete: (state, payload) => {
				const { id } = payload.data;
				logger.warn('RecentModel.delete', id);
				delete state.collection[id];
			},
		},
	};

	/**
	 * @param {string} tab
	 * @returns {string}
	 */
	function getNavigationTabId(tab)
	{
		if (NavigationTabByRecentTab[tab])
		{
			return NavigationTabByRecentTab[tab];
		}

		return tab;
	}

	function checkUploadingState(store, recentItem)
	{
		const existingItem = store.state.collection[recentItem.id];
		if (!existingItem)
		{
			return;
		}

		if (recentItem.message?.uuid && recentItem.message?.uuid === existingItem.element?.uploadingState?.message?.id)
		{
			recentItem.uploadingState = null;
		}
	}

	function splitItemsByExistence(store, items)
	{
		const newItems = [];
		const existingItems = [];

		items.forEach((recentItem) => {
			const existingItem = store.state.collection[recentItem.id];
			if (existingItem)
			{
				// if we already got chat, we should not upd ate it
				// with default user chat (unless it's an accepted invitation)
				const defaultUserElement = (
					recentItem.options
					&& recentItem.options.defaultUserRecord
					&& !recentItem.invitation
				);

				if (defaultUserElement)
				{
					return;
				}

				existingItems.push({
					fields: recentItem,
				});
			}
			else
			{
				newItems.push({
					fields: recentItem,
				});
			}
		});

		return { newItems, existingItems };
	}

	/**
	 * @param {MessengerStore<RecentMessengerModelV2>['rootGetters']} rootGetters
	 * @returns {(a: RecentModelState, b: RecentModelState) => number}
	 */
	const sortByAggregatedActivityDateWithPinned = (rootGetters) => (a, b) => {
		if (!a.pinned && b.pinned)
		{
			return 1;
		}

		if (a.pinned && !b.pinned)
		{
			return -1;
		}

		const aLastActivityDate = getLastActivityDate(a, rootGetters);
		const bLastActivityDate = getLastActivityDate(b, rootGetters);

		return bLastActivityDate - aLastActivityDate;
	};

	/**
	 * @param {MessengerStore<RecentMessengerModelV2>['rootGetters']} rootGetters
	 * @returns {(a: RecentModelState, b: RecentModelState) => number}
	 */
	const sortByAggregatedActivityDate = (rootGetters) => (a, b) => {
		const aLastActivityDate = getLastActivityDate(a, rootGetters);
		const bLastActivityDate = getLastActivityDate(b, rootGetters);

		return bLastActivityDate - aLastActivityDate;
	};

	/**
	 * @returns {(a: RecentModelState, b: RecentModelState) => number}
	 */
	const sortListByMessageDate = () => (a, b) => {
		if (a.message?.date && b.message?.date)
		{
			const timestampA = new Date(a.message.date).getTime();
			const timestampB = new Date(b.message.date).getTime();

			return timestampB - timestampA;
		}

		return 0;
	};

	/**
	 * @returns {(a: RecentModelState, b: RecentModelState) => number}
	 */
	const sortByLastActivityDateOnly = () => (a, b) => {
		if (a.lastActivityDate && b.lastActivityDate)
		{
			const timestampA = new Date(a.lastActivityDate).getTime();
			const timestampB = new Date(b.lastActivityDate).getTime();

			return timestampB - timestampA;
		}

		return 0;
	};

	/**
	 * @param {RecentModelState} item
	 * @param {MessengerStore<RecentMessengerModelV2>['rootGetters']} [rootGetters]
	 * @returns {Date}
	 */
	const getLastActivityDate = (item, rootGetters = null) => {
		const getTime = (date) => date?.getTime() || new Date(0).getTime();

		const recentLastActivityDate = item.lastActivityDate;
		const recentMessageDate = item.message?.date;

		let recent = recentMessageDate;
		const isChannel = DialogHelper.createByDialogId(item.id)?.isChannel;
		const shouldUseActivityDate = Type.isDate(recentLastActivityDate) && recentLastActivityDate > recentMessageDate;

		if (isChannel && shouldUseActivityDate)
		{
			recent = recentLastActivityDate;
		}

		const draftDate = rootGetters?.['draftModel/getById'](item.id)?.lastActivityDate;
		const uploadingDate = item.uploadingState?.lastActivityDate;

		return new Date(Math.max(
			getTime(draftDate),
			getTime(uploadingDate),
			getTime(recent),
		));
	};

	module.exports = { recentModel };
});
