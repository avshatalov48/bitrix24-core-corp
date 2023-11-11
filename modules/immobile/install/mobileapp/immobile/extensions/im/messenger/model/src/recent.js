/* eslint-disable no-param-reassign */
/**
 * @module im/messenger/model/recent
 */
jn.define('im/messenger/model/recent', (require, exports, module) => {
	const { Type } = require('type');
	const { ChatTypes, MessageStatus } = require('im/messenger/const');
	const { RecentCache } = require('im/messenger/cache');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { searchModel } = require('im/messenger/model/recent/search');
	const { Logger } = require('im/messenger/lib/logger');

	const elementState = {
		id: 0,
		type: ChatTypes.chat,
		avatar: '',
		color: '#048bd0',
		title: '',
		counter: 0,
		pinned: false,
		liked: false,
		message: {
			id: 0,
			text: '',
			date: new Date(),
			senderId: 0,
			status: MessageStatus.received,
		},
		chat_id: 0,
		chat: {},
		user: { id: 0 },
		writing: false,
		unread: false,
		options: {},
		invitation: {
			isActive: false,
			originator: 0,
			canResend: false,
		},
	};

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
					.sort(sortListByMessageDateWithPinned)
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
			getUserList: (state) => () => {
				return state.collection.filter((recentItem) => recentItem.type === 'user').sort(sortListByMessageDate);
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
		},
		actions: {
			/** @function recentModel/setState */
			setState: (store, payload) => {
				if (Type.isPlainObject(payload) && Type.isArrayFilled(payload.collection))
				{
					payload.collection = payload.collection
						.map((item) => {
							item.writing = false;
							item.liked = false;

							return {
								...elementState,
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

			/** @function recentModel/clearAllCounters */
			clearAllCounters: (store, payload) => {
				const updatedItems = [];

				store.state.collection.forEach((recentItem, index) => {
					if (recentItem.counter === 0 && recentItem.unread === false)
					{
						return;
					}

					recentItem.counter = 0;
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
			 * @param {MutationPayload} payload
			 */
			setState: (state, payload) => {
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
			 * @param {MutationPayload} payload
			 */
			add: (state, payload) => {
				Logger.warn('RecentModel.addMutation', payload);
				const {
					recentItemList,
				} = payload.data;

				recentItemList.forEach((item) => {
					state.collection.push({
						...elementState,
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

				RecentCache.save(state);
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			update: (state, payload) => {
				Logger.warn('RecentModel.updateMutation', payload);
				const {
					recentItemList,
				} = payload.data;

				recentItemList.forEach((item) => {
					const currentElement = state.collection[item.index];

					item.message = { ...currentElement.message, ...item.message };
					item.options = { ...currentElement.options, ...item.options };

					state.collection[item.index] = {
						...state.collection[item.index],
						...item.fields,
					};
				});

				RecentCache.save(state);
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			delete: (state, payload) => {
				const {
					id,
					index,
				} = payload.data;

				state.collection.splice(index, 1);

				delete state.index[id];

				RecentCache.save(state);
			},
		},
	};

	/**
	 * @param fields
	 * @return {Partial<RecentModelState>}
	 */
	function validate(fields)
	{
		const result = {
			options: {},
		};

		if (Type.isNumber(fields.id) || Type.isStringFilled(fields.id))
		{
			result.id = fields.id.toString();
		}

		if (Type.isStringFilled(fields.type))
		{
			result.type = fields.type;
		}

		if (Type.isStringFilled(fields.avatar))
		{
			result.avatar = fields.avatar;
		}

		if (Type.isStringFilled(fields.color))
		{
			result.color = fields.color;
		}

		if (Type.isNumber(fields.title) || Type.isStringFilled(fields.title))
		{
			result.title = fields.title.toString();
		}

		if (Type.isNumber(fields.counter) || Type.isStringFilled(fields.counter))
		{
			result.counter = Number.parseInt(fields.counter, 10);
		}

		if (Type.isBoolean(fields.pinned))
		{
			result.pinned = fields.pinned;
		}

		if (Type.isBoolean(fields.liked))
		{
			result.liked = fields.liked;
		}

		if (Type.isBoolean(fields.writing))
		{
			result.writing = fields.writing;
		}

		if (Type.isNumber(fields.chat_id) || Type.isStringFilled(fields.chat_id))
		{
			result.chat_id = Number.parseInt(fields.chatId, 10);
		}

		if (Type.isBoolean(fields.unread))
		{
			result.unread = fields.unread;
		}

		if (!Type.isUndefined(fields.date_update))
		{
			result.date_update = DateHelper.cast(fields.date_update, null);
		}

		// TODO: move part to file model

		if (Type.isPlainObject(fields.message))
		{
			result.message = fields.message;
			if (result.message.id > 0)
			{
				result.message.date = DateHelper.cast(result.message.date);
			}
		}

		// TODO: move to user and dialog model
		if (Type.isPlainObject(fields.chat))
		{
			result.chat = fields.chat;
			result.chat.date_create = DateHelper.cast(result.chat.date_create);
		}

		if (Type.isPlainObject(fields.user))
		{
			result.user = fields.user;

			result.user.last_activity_date = DateHelper.cast(result.user.last_activity_date);
			if (result.user.mobile_last_date)
			{
				result.user.mobile_last_date = DateHelper.cast(result.user.mobile_last_date);
			}
			else
			{
				result.user.mobile_last_date = new Date(0);
			}

			if (!result.user.status)
			{
				result.user.status = '';
			}
		}

		if (Type.isPlainObject(fields.invited))
		{
			result.invitation = {
				isActive: true,
				originator: fields.invited.originator_id,
				canResend: fields.invited.can_resend,
			};
			result.options.defaultUserRecord = true;
		}
		else if (fields.invited === false)
		{
			result.invitation = {
				isActive: false,
				originator: 0,
				canResend: false,
			};
			result.options.defaultUserRecord = true;
		}
		else if (Type.isPlainObject(fields.invitation))
		{
			result.invitation = fields.invitation;
			result.options.defaultUserRecord = true;
		}

		if (Type.isPlainObject(fields.options))
		{
			if (!result.options)
			{
				result.options = {};
			}

			if (Type.isBoolean(fields.options.default_user_record))
			{
				fields.options.defaultUserRecord = fields.options.default_user_record;
			}

			if (Type.isBoolean(fields.options.defaultUserRecord))
			{
				result.options.defaultUserRecord = fields.options.defaultUserRecord;
			}

			if (Type.isBoolean(fields.options.birthdayPlaceholder))
			{
				result.options.birthdayPlaceholder = fields.options.birthdayPlaceholder;
			}
		}

		return result;
	}

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

	function sortListByMessageDate(a, b)
	{
		if (a.message && b.message)
		{
			const timestampA = new Date(a.message.date).getTime();
			const timestampB = new Date(b.message.date).getTime();

			return timestampB - timestampA;
		}

		return 0;
	}

	function sortListByMessageDateWithPinned(a, b)
	{
		if (!a.pinned && b.pinned)
		{
			return 1;
		}

		if (a.pinned && !b.pinned)
		{
			return -1;
		}

		if (a.message && b.message)
		{
			const timestampA = new Date(a.message.date).getTime();
			const timestampB = new Date(b.message.date).getTime();

			return timestampB - timestampA;
		}

		return 0;
	}

	module.exports = { recentModel };
});
