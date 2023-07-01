/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/model/recent
 */
jn.define('im/messenger/model/recent', (require, exports, module) => {

	const { Type } = require('type');
	const { ChatTypes, MessageStatus } = require('im/messenger/const');
	const { RecentCache } = require('im/messenger/cache');
	const { DateHelper } = require('im/messenger/lib/helper');

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
	};

	const recentModel = {
		namespaced: true,
		state: () => ({
			collection: [],
			index: {},
		}),
		getters: {
			/** @function recentModel/getRecentPage */
			getRecentPage: (state) => (pageNumber, itemsPerPage) => {
				const list = [...state.collection];

				return list
					.splice((pageNumber - 1) * itemsPerPage, itemsPerPage)
					.sort(sortListByMessageDateWithPinned)
				;
			},

			/** @function recentModel/getTitleById */
			getTitleById: (state) => (id) => {
				return state.collection.find((item) => item.id == id).title;
			},

			/** @function recentModel/getById */
			getById: (state) => (id) => {
				return state.collection.find((item) => item.id == id);
			},

			/** @function recentModel/getUserList */
			getUserList: (state) => {
				return state.collection.filter(recentItem => recentItem.type === 'user').sort(sortListByMessageDate);
			},

			/** @function recentModel/getCollection */
			getCollection: (state) => {
				return state.collection;
			},

			/** @function recentModel/isEmpty */
			isEmpty: (state) => {
				return state.collection.length === 0;
			},
		},
		actions: {
			/** @function recentModel/setState */
			setState: (store, payload) =>
			{
				if (Type.isPlainObject(payload) && Type.isArrayFilled(payload.collection))
				{
					payload.collection =
						payload.collection
							.map(item => {
								item.writing = false;
								item.liked = false;

								return {
									...elementState,
									...validate(item),
								}
							})
							.filter(item => item.id !== 0)
					;

					store.commit('setState', payload);
				}
			},

			/** @function recentModel/set */
			set: (store, payload) =>
			{
				let result = [];

				if (Type.isArray(payload))
				{
					result = payload.map(recentItem => {
						return {
							...elementState,
							...validate(recentItem),
						};
					});
				}

				if (result.length === 0)
				{
					return false;
				}

				const newItems = [];
				const existingItems = [];

				payload.forEach(recentItem => {
					const existingItem = findItemById(store, recentItem.id);
					if (existingItem)
					{
						// if we already got chat - we should not update it with default user chat (unless it's an accepted invitation)
						const defaultUserElement = recentItem.options && recentItem.options.default_user_record && !recentItem.invited;
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
					store.commit('add', newItems);
				}

				if (existingItems.length > 0)
				{
					store.commit('update', existingItems);
				}
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

				store.commit('update', [{
					index: existingItem.index,
					fields: { liked },
				}]);
			},

			/** @function recentModel/delete */
			delete: (store, payload) =>
			{
				const existingItem = findItemById(store, payload.id);
				if (!existingItem)
				{
					return false;
				}

				store.commit('delete', {
					index: existingItem.index,
					id: payload.id,
				});
			},

			/** @function recentModel/clearAllCounters */
			clearAllCounters: (store, payload) =>
			{
				const updatedItems = [];

				store.state.collection.forEach((recentItem, index) => {
					recentItem.counter = 0;
					recentItem.unread = false;

					updatedItems.push({
						index,
						fields: recentItem,
					});
				})

				store.commit('update', updatedItems);
			},
		},
		mutations: {
			setState: (state, payload) => {
				state.collection = payload.collection;

				payload.collection.forEach(item => {
					if (!state.index[item.id])
					{
						state.index[item.id] = 1;
						return;
					}

					state.index[item.id]++;
				});
			},
			add: (state, payload) => {
				payload.forEach(item => {
					state.collection.push(item.fields);

					if (!state.index[item.fields.id])
					{
						state.index[item.fields.id] = 1;
						return;
					}

					state.index[item.fields.id]++;
				});

				//TODO: Crutch, remove when we figure out why the chats were duplicated and remove
				state.collection = state.collection.filter(item => {
					if (state.index[item.id] !== 1)
					{
						state.index[item.id]--;

						return false;
					}

					return true;
				});

				RecentCache.save(state);
			},
			update: (state, payload) => {
				payload.forEach(item => {
					state.collection[item.index] = {
						...state.collection[item.index],
						...item.fields,
					};
				});

				RecentCache.save(state);
			},
			delete: (state, payload) => {
				state.collection.splice(payload.index, 1);

				delete state.index[payload.id];

				RecentCache.save(state);
			},
		}
	};

	function validate(fields)
	{
		const result = {};

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

		result.date_update = DateHelper.cast(fields.date_update);

		//TODO: move part to file model
		result.message = fields.message || { id: 0 };
		if (result.message.id > 0)
		{
			result.message.date = DateHelper.cast(result.message.date);
		}

		//TODO: move to user and dialog model
		result.chat = fields.chat || { id: 0 };
		if (result.chat.id > 0)
		{
			result.chat.date_create = DateHelper.cast(result.chat.date_create);
		}

		result.user = fields.user || { id: 0 };
		if (result.user.id > 0)
		{
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

		return result;
	}

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
	}

	module.exports = { recentModel };
});
