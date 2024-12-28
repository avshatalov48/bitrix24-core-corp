/* eslint-disable no-param-reassign */
/* eslint-disable es/no-optional-chaining */
/**
 * @module im/messenger/model/recent
 */
jn.define('im/messenger/model/recent', (require, exports, module) => {
	const { Type } = require('type');
	const { Uuid } = require('utils/uuid');

	const {
		ChatTypes,
		MessageStatus,
	} = require('im/messenger/const');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { searchModel } = require('im/messenger/model/recent/search');
	const { validate } = require('im/messenger/model/validators/recent');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--recent');

	const recentDefaultElement = Object.freeze({
		id: 0,
		message: {
			id: 0,
			senderId: 0,
			date: new Date(),
			status: MessageStatus.received,
			subTitleIcon: '',
			sending: false,
			text: '',
			params: {
				withFile: false,
				withAttach: false,
			},
		},
		dateMessage: null,
		lastActivityDate: new Date(),
		unread: false,
		pinned: false,
		liked: false,
		invitation: {
			isActive: false,
			originator: 0,
			canResend: false,
		},
		options: {},
		uploadingState: null,
	});

	/** @type {RecentMessengerModel} */
	const recentModel = {
		namespaced: true,
		state: () => ({
			collection: [],
			index: {},
		}),
		modules: {
			searchModel,
		},
		getters: {
			/**
			 * @function recentModel/getRecentPage
			 * @return {RecentModelState[]}
			 */
			getRecentPage: (state) => (pageNumber, itemsPerPage) => {
				const list = [...state.collection];

				return list
					.splice((pageNumber - 1) * itemsPerPage, itemsPerPage)
					.sort(sortListByLastActivityDateWithPinned)
				;
			},

			/**
			 * @function recentModel/getById
			 * @return {?RecentModelState}
			 */
			getById: (state) => (id) => {
				return state.collection.find((item) => item.id == id);
			},

			/**
			 * @function recentModel/getUserList
			 * @return {RecentModelState[]}
			 */
			getUserList: (state, getters, rootState, rootGetters) => () => {
				return state.collection.filter((recentItem) => {
					return !recentItem.id.startsWith('chat') && rootGetters['usersModel/getById'](recentItem.id);
				})
					.sort(sortListByLastActivityDate);
			},

			/**
			 * @function recentModel/getSortedCollection
			 * @return {RecentModelState[]}
			 */
			getSortedCollection: (state) => () => {
				const collectionAsArray = Object.values(state.collection).filter((item) => {
					const isBirthdayPlaceholder = item.options.birthdayPlaceholder;
					const isInvitedUser = item.options.defaultUserRecord;

					return !isBirthdayPlaceholder && !isInvitedUser && item.message.id;
				});

				return [...collectionAsArray].sort((a, b) => {
					return b.message.date - a.message.date;
				});
			},

			/**
			 * @function recentModel/getCollection
			 * @return {RecentModelState[]}
			 */
			getCollection: (state) => () => {
				return state.collection;
			},

			/**
			 * @function recentModel/isEmpty
			 * @return {boolean}
			 */
			isEmpty: (state) => () => {
				return state.collection.length === 0;
			},

			/**
			 * @function recentModel/needsBirthdayPlaceholder
			 * @return {boolean}
			 */
			needsBirthdayPlaceholder: (state, getters, rootState, rootGetters) => (dialogId) => {
				const currentItem = rootGetters['recentModel/getById'](dialogId);
				if (!currentItem)
				{
					return false;
				}

				const dialog = rootGetters['dialoguesModel/getById'](dialogId);
				if (!dialog || dialog.type !== ChatTypes.user)
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
				const currentItem = rootGetters['recentModel/getById'](dialogId);
				if (!currentItem)
				{
					return false;
				}

				const dialog = rootGetters['dialoguesModel/getById'](dialogId);
				if (!dialog || dialog.type !== ChatTypes.user)
				{
					return false;
				}

				return rootGetters['usersModel/hasBirthday'](dialogId);
			},

			/**
			 * @function recentModel/needsVacationPlaceholder
			 * @return {boolean}
			 */
			needsVacationPlaceholder: (state, getters, rootState, rootGetters) => (dialogId) => {
				const currentItem = rootGetters['recentModel/getById'](dialogId);
				if (!currentItem)
				{
					return false;
				}

				const dialog = rootGetters['dialoguesModel/getById'](dialogId);
				if (!dialog || dialog.type !== ChatTypes.user)
				{
					return false;
				}

				const hasVacation = rootGetters['usersModel/hasVacation'](dialogId);
				if (!hasVacation)
				{
					return false;
				}

				const hasMessage = Uuid.isV4(currentItem.message.id) || currentItem.message.id > 0;
				const hasTodayMessage = hasMessage && DateFormatter.isToday(currentItem.message.date);

				return !hasTodayMessage && dialog.counter === 0;
			},

			/**
			 * @function recentModel/needsVacationIcon
			 * @return {boolean}
			 */
			needsVacationIcon: (state, getters, rootState, rootGetters) => (dialogId) => {
				const currentItem = rootGetters['recentModel/getById'](dialogId);
				if (!currentItem)
				{
					return false;
				}

				const dialog = rootGetters['dialoguesModel/getById'](dialogId);
				if (!dialog || dialog.type !== ChatTypes.user)
				{
					return false;
				}

				return rootGetters['usersModel/hasVacation'](dialogId);
			},
		},
		actions: {
			/** @function recentModel/setState */
			setState: (store, payload) => {
				if (Type.isPlainObject(payload) && Type.isArrayFilled(payload.collection))
				{
					payload.collection = payload.collection
						.map(/** @param {RecentModelState} item */(item) => {
							item.liked = false;

							return {
								...recentDefaultElement,
								...validate(item),
							};
						})
						.filter((item) => item.id !== 0)
					;

					store.commit('setState', {
						actionName: 'setState',
						data: {
							collection: payload.collection,
						},
					});
				}
			},

			/** @function recentModel/set */
			set: (store, payload) => {
				/**
				 * @type {Array<RecentModelState>}
				 */
				const result = [];

				if (Type.isArray(payload))
				{
					payload.forEach((recentItem) => {
						if (Type.isPlainObject(recentItem))
						{
							result.push(validate(recentItem));
						}
					});
				}

				if (result.length === 0)
				{
					return false;
				}

				const newItems = [];
				const existingItems = [];

				result.forEach((recentItem) => {
					const existingItem = findItemById(store, recentItem.id);
					if (existingItem)
					{
						// if we already got chat - we should not update it
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
							index: existingItem.index,
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

				if (newItems.length > 0)
				{
					store.commit('add', {
						actionName: 'set',
						data: {
							recentItemList: newItems,
						},
					});
				}

				if (existingItems.length > 0)
				{
					store.commit('update', {
						actionName: 'set',
						data: {
							recentItemList: existingItems,
						},
					});
				}

				return true;
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
							result.push(validate(recentItem));
						}
					});
				}

				if (result.length === 0)
				{
					return false;
				}

				const existingItems = [];
				result.forEach((item) => {
					const existingItem = findItemById(store, item.id);

					if (!existingItem)
					{
						return;
					}

					existingItems.push({
						index: existingItem.index,
						fields: item,
					});
				});

				if (existingItems.length === 0)
				{
					return false;
				}

				store.commit('update', {
					actionName: 'update',
					data: {
						recentItemList: existingItems,
					},
				});

				return true;
			},

			/** @function recentModel/like */
			like: (store, payload) => {
				const { id, messageId, liked } = payload;

				const existingItem = findItemById(store, id);
				if (!existingItem)
				{
					return;
				}

				if (
					!(Type.isUndefined(messageId) && liked === false)
					&& existingItem.element.message.id !== Number(messageId)
				)
				{
					return;
				}

				const recentItemForUpdate = {
					index: existingItem.index,
					fields: { liked },
				};

				store.commit('update', {
					actionName: 'like',
					data: {
						recentItemList: [recentItemForUpdate],
					},
				});
			},

			/** @function recentModel/delete */
			delete: (store, payload) => {
				const existingItem = findItemById(store, payload.id);
				if (!existingItem)
				{
					return false;
				}

				store.commit('delete', {
					actionName: 'delete',
					data: {
						index: existingItem.index,
						id: payload.id,
					},
				});

				return true;
			},

			/**
			 * @function recentModel/deleteFromModel
			 * @description use if you need to remove unnecessary elements for example drawn from the cache,
			 * but not delete them from the database
			 */
			deleteFromModel: (store, payload) => {
				const existingItem = findItemById(store, payload.id);
				if (!existingItem)
				{
					return false;
				}

				store.commit('delete', {
					actionName: 'deleteFromModel',
					data: {
						index: existingItem.index,
						id: payload.id,
					},
				});

				return true;
			},

			/** @function recentModel/clearAllCounters */
			clearAllCounters: (store, payload) => {
				const updatedItems = [];

				store.state.collection.forEach((recentItem, index) => {
					if (recentItem.unread === false)
					{
						return;
					}

					recentItem.unread = false;

					updatedItems.push({
						index,
						fields: recentItem,
					});
				});

				if (updatedItems.length > 0)
				{
					store.commit('update', {
						actionName: 'clearAllCounters',
						data: {
							recentItemList: updatedItems,
						},
					});
				}
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<RecentSetStateData, RecentSetStateActions>} payload
			 */
			setState: (state, payload) => {
				logger.warn('RecentModel.setState', payload);
				const {
					collection,
				} = payload.data;

				state.collection = collection;

				collection.forEach((item) => {
					if (!state.index[item.id])
					{
						state.index[item.id] = 1;

						return;
					}

					state.index[item.id]++;
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<RecentAddData, RecentAddActions>} payload
			 */
			add: (state, payload) => {
				logger.warn('RecentModel.addMutation', payload);
				const {
					recentItemList,
				} = payload.data;

				recentItemList.forEach((item) => {
					state.collection.push({
						...recentDefaultElement,
						...item.fields,
					});

					if (!state.index[item.fields.id])
					{
						state.index[item.fields.id] = 1;

						return;
					}

					state.index[item.fields.id]++;
				});

				// TODO: Crutch, remove when we figure out why the chats were duplicated and remove
				state.collection = state.collection.filter((item) => {
					if (state.index[item.id] !== 1)
					{
						state.index[item.id]--;

						return false;
					}

					return true;
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<RecentUpdateData, RecentUpdateActions>} payload
			 */
			update: (state, payload) => {
				logger.warn('RecentModel.updateMutation', payload);
				const {
					recentItemList,
				} = payload.data;

				recentItemList.forEach((item) => {
					const currentElement = state.collection[item.index];

					item.fields.message = { ...currentElement?.message, ...item.fields.message };
					item.fields.options = { ...currentElement?.options, ...item.fields.options };

					state.collection[item.index] = {
						...state.collection[item.index],
						...item.fields,
					};
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<RecentDeleteData, RecentDeleteActions>} payload
			 */
			delete: (state, payload) => {
				const {
					id,
					index,
				} = payload.data;

				state.collection.splice(index, 1);

				delete state.index[id];
			},
		},
	};

	/**
	 *
	 * @param store
	 * @param id
	 * @return {{index: number, element: RecentModelState}|boolean}
	 */
	function findItemById(store, id)
	{
		const result = {};

		const elementIndex = store.state.collection.findIndex((element, index) => {
			return element.id.toString() === id.toString();
		});

		if (elementIndex !== -1)
		{
			result.index = elementIndex;
			result.element = store.state.collection[elementIndex];

			return result;
		}

		return false;
	}

	function sortListByLastActivityDate(a, b)
	{
		if (a.lastActivityDate && b.lastActivityDate)
		{
			const timestampA = new Date(a.lastActivityDate).getTime();
			const timestampB = new Date(b.lastActivityDate).getTime();

			return timestampB - timestampA;
		}

		return 0;
	}

	/**
	 * @param {RecentModelState} a
	 * @param {RecentModelState} b
	 * @returns {number}
	 */
	function sortListByLastActivityDateWithPinned(a, b)
	{
		if (!a.pinned && b.pinned)
		{
			return 1;
		}

		if (a.pinned && !b.pinned)
		{
			return -1;
		}

		const aLastActivityDate = a.uploadingState?.lastActivityDate > a.lastActivityDate
			? a.uploadingState.lastActivityDate
			: a.lastActivityDate
		;
		const bLastActivityDate = b.uploadingState?.lastActivityDate > b.lastActivityDate
			? b.uploadingState.lastActivityDate
			: b.lastActivityDate
		;

		if (aLastActivityDate && bLastActivityDate)
		{
			const timestampA = new Date(aLastActivityDate).getTime();
			const timestampB = new Date(bLastActivityDate).getTime();

			return timestampB - timestampA;
		}

		return 0;
	}

	module.exports = { recentModel, recentDefaultElement };
});
