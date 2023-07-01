/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/model/dialogues
 */
jn.define('im/messenger/model/dialogues', (require, exports, module) => {

	const { Type } = require('type');
	const { DialogType } = require('im/messenger/const');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { Color } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');

	const dialogState = {
		dialogId: '0',
		chatId: 0,
		type: DialogType.chat,
		name: '',
		description: '',
		avatar: '',
		color: Color.base,
		extranet: false,
		counter: 0,
		userCounter: 0,
		lastReadId: 0,
		markedId: 0,
		lastMessageId: 0,
		lastMessageViews: {
			countOfViewers: 0,
			firstViewer: null,
			messageId: 0
		},
		savedPositionMessageId: 0,
		managerList: [],
		readList: [],
		writingList: [],
		muteList: [],
		textareaMessage: '',
		quoteId: 0,
		owner: 0,
		entityType: '',
		entityId: '',
		dateCreate: null,
		public: {
			code: '',
			link: ''
		},
		inited: false,
		loading: false,
		hasPrevPage: false,
		hasNextPage: false,
	};

	const dialoguesModel = {
		namespaced: true,
		state: () => ({
			collection: {},
			saveDialogList: [],
			saveChatList: [],
		}),
		getters: {
			/** @function dialoguesModel/getById */
			getById: (state) => (id) => {
				return state.collection[id];
			},

			/** @function dialoguesModel/getByChatId */
			getByChatId: (state) => (chatId) => {
				chatId = Number.parseInt(chatId, 10);
				return Object.values(state.collection).find(item => {
					return item.chatId === chatId;
				});
			},

			/** @function dialoguesModel/getLastReadId */
			getLastReadId: (state) => (dialogId) => {
				if (!state.collection[dialogId])
				{
					return 0;
				}

				const {lastReadId, lastMessageId} = state.collection[dialogId];

				return lastReadId === lastMessageId ? 0 : lastReadId;
			},

			/** @function dialoguesModel/getInitialMessageId */
			getInitialMessageId: (state) => (dialogId) =>
			{
				if (!state.collection[dialogId])
				{
					return 0;
				}

				const {lastReadId, markedId} = state.collection[dialogId];
				if (markedId === 0)
				{
					return lastReadId;
				}

				return Math.min(lastReadId, markedId);
			},
		},
		actions: {
			/** @function dialoguesModel/set */
			set: (store, payload) =>
			{
				if (!Array.isArray(payload) && Type.isPlainObject(payload))
				{
					payload = [payload];
				}

				payload.map(element => {
					return validate(store, element);
				}).forEach(element => {
					const existingItem = store.state.collection[element.dialogId];
					if (existingItem)
					{
						store.commit('update', {
							dialogId: element.dialogId,
							fields: element
						});
					}
					else
					{
						store.commit('add', {
							dialogId: element.dialogId,
							fields: {...dialogState, ...element}
						});
					}
				});
			},

			/** @function dialoguesModel/add */
			add: (store, payload) =>
			{
				if (!Array.isArray(payload) && Type.isPlainObject(payload))
				{
					payload = [payload];
				}

				payload.map(element => {
					return validate(store, element);
				}).forEach(element => {
					const existingItem = store.state.collection[element.dialogId];
					if (!existingItem)
					{
						store.commit('add', {
							dialogId: element.dialogId,
							fields: {...dialogState, ...element},
						});
					}
				});
			},

			/** @function dialoguesModel/update */
			update: (store, payload) =>
			{
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				store.commit('update', {
					dialogId: payload.dialogId,
					fields: validate(store, payload.fields),
				});
			},

			/** @function dialoguesModel/delete */
			delete: (store, payload) =>
			{
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				store.commit('delete', payload.dialogId);
			},

			/** @function dialoguesModel/decreaseCounter */
			decreaseCounter: (store, payload) =>
			{
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				if (existingItem.counter === 100)
				{
					return true;
				}

				let decreasedCounter = existingItem.counter - payload.count;
				if (decreasedCounter < 0)
				{
					decreasedCounter = 0;
				}

				store.commit('update', {
					dialogId: payload.dialogId,
					fields: {
						counter: decreasedCounter,
						previousCounter: existingItem.counter
					}
				});
			},
		},
		mutations: {
			add: (state, payload) =>
			{
				state.collection[payload.dialogId] = payload.fields;
			},
			update: (state, payload) =>
			{
				state.collection[payload.dialogId] = {...state.collection[payload.dialogId], ...payload.fields};
			},
			delete: (state, payload) =>
			{
				delete state.collection[payload.dialogId];
			},
		}
	};

	function validate(store, fields)
	{
		const result = {};

		if (!Type.isUndefined(fields.dialog_id))
		{
			fields.dialogId = fields.dialog_id;
		}
		if (Type.isNumber(fields.dialogId) || Type.isStringFilled(fields.dialogId))
		{
			result.dialogId = fields.dialogId.toString();
		}

		if (!Type.isUndefined(fields.chat_id))
		{
			fields.chatId = fields.chat_id;
		}
		else if (!Type.isUndefined(fields.id))
		{
			fields.chatId = fields.id;
		}
		if (Type.isNumber(fields.chatId) || Type.isStringFilled(fields.chatId))
		{
			result.chatId = Number.parseInt(fields.chatId, 10);
		}

		if (Type.isStringFilled(fields.type))
		{
			result.type = fields.type.toString();
		}

		if (Type.isNumber(fields.quoteId))
		{
			result.quoteId = Number.parseInt(fields.quoteId, 10);
		}

		if (Type.isNumber(fields.counter) || Type.isStringFilled(fields.counter))
		{
			result.counter = Number.parseInt(fields.counter, 10);
		}

		if (!Type.isUndefined(fields.user_counter))
		{
			result.userCounter = fields.user_counter;
		}
		if (Type.isNumber(fields.userCounter) || Type.isStringFilled(fields.userCounter))
		{
			result.userCounter = Number.parseInt(fields.userCounter, 10);
		}

		if (!Type.isUndefined(fields.last_id))
		{
			fields.lastId = fields.last_id;
		}
		if (Type.isNumber(fields.lastId))
		{
			result.lastReadId = fields.lastId;
		}

		if (!Type.isUndefined(fields.marked_id))
		{
			fields.markedId = fields.marked_id;
		}
		if (Type.isNumber(fields.markedId))
		{
			result.markedId = fields.markedId;
		}

		if (!Type.isUndefined(fields.last_message_id))
		{
			fields.lastMessageId = fields.last_message_id;
		}
		if (Type.isNumber(fields.lastMessageId) || Type.isStringFilled(fields.lastMessageId))
		{
			result.lastMessageId = Number.parseInt(fields.lastMessageId, 10);
		}

		if (Type.isPlainObject(fields.last_message_views))
		{
			fields.lastMessageViews = fields.last_message_views;
		}
		if (Type.isPlainObject(fields.lastMessageViews))
		{
			result.lastMessageViews = prepareLastMessageViews(fields.lastMessageViews);
		}

		if (Type.isBoolean(fields.hasPrevPage))
		{
			result.hasPrevPage = fields.hasPrevPage;
		}

		if (Type.isBoolean(fields.hasNextPage))
		{
			result.hasNextPage = fields.hasNextPage;
		}

		if (!Type.isUndefined(fields.textareaMessage))
		{
			result.textareaMessage = fields.textareaMessage.toString();
		}

		if (!Type.isUndefined(fields.title))
		{
			fields.name = fields.title;
		}
		if (Type.isNumber(fields.name) || Type.isStringFilled(fields.name))
		{
			result.name = ChatUtils.htmlspecialcharsback(fields.name.toString());
		}

		if (!Type.isUndefined(fields.owner))
		{
			fields.ownerId = fields.owner;
		}
		if (Type.isNumber(fields.ownerId) || Type.isStringFilled(fields.ownerId))
		{
			result.owner = Number.parseInt(fields.ownerId, 10);
		}

		if (Type.isString(fields.avatar))
		{
			result.avatar = prepareAvatar(store, fields.avatar);
		}

		if (Type.isStringFilled(fields.color))
		{
			result.color = fields.color;
		}

		if (Type.isBoolean(fields.extranet))
		{
			result.extranet = fields.extranet;
		}

		if (!Type.isUndefined(fields.entity_type))
		{
			fields.entityType = fields.entity_type;
		}
		if (Type.isStringFilled(fields.entityType))
		{
			result.entityType = fields.entityType;
		}
		if (!Type.isUndefined(fields.entity_id))
		{
			fields.entityId = fields.entity_id;
		}
		if (Type.isNumber(fields.entityId) || Type.isStringFilled(fields.entityId))
		{
			result.entityId = fields.entityId.toString();
		}

		if (!Type.isUndefined(fields.date_create))
		{
			fields.dateCreate = fields.date_create;
		}
		if (!Type.isUndefined(fields.dateCreate))
		{
			result.dateCreate = DateHelper.cast(fields.dateCreate);
		}

		if (Type.isPlainObject(fields.public))
		{
			result.public = {};

			if (Type.isStringFilled(fields.public.code))
			{
				result.public.code = fields.public.code;
			}

			if (Type.isStringFilled(fields.public.link))
			{
				result.public.link = fields.public.link;
			}
		}

		// if (!Type.isUndefined(fields.readed_list))
		// {
		// 	fields.readList = fields.readed_list;
		// }
		// if (Type.isArray(fields.readList))
		// {
		// 	result.readList = this.prepareReadList(fields.readList);
		// }

		// if (!Type.isUndefined(fields.writing_list))
		// {
		// 	fields.writingList = fields.writing_list;
		// }
		// if (Type.isArray(fields.writingList))
		// {
		// 	result.writingList = this.prepareWritingList(fields.writingList);
		// }

		if (!Type.isUndefined(fields.manager_list))
		{
			fields.managerList = fields.manager_list;
		}
		if (Type.isArray(fields.managerList))
		{
			result.managerList = [];

			fields.managerList.forEach(userId =>
			{
				userId = Number.parseInt(userId, 10);
				if (userId > 0)
				{
					result.managerList.push(userId);
				}
			});
		}

		// if (!Type.isUndefined(fields.mute_list))
		// {
		// 	fields.muteList = fields.mute_list;
		// }
		// if (Type.isArray(fields.muteList) || Type.isPlainObject(fields.muteList))
		// {
		// 	result.muteList = this.prepareMuteList(fields.muteList);
		// }

		if (Type.isBoolean(fields.inited))
		{
			result.inited = fields.inited;
		}

		if (Type.isBoolean(fields.hasMoreUnreadToLoad))
		{
			result.hasMoreUnreadToLoad = fields.hasMoreUnreadToLoad;
		}

		if (Type.isString(fields.description))
		{
			result.description = fields.description;
		}

		return result;
	}

	function prepareLastMessageViews(rawLastMessageViews)
	{
		const {
			count_of_viewers: countOfViewers,
			first_viewers: rawFirstViewers,
			message_id: messageId
		} = rawLastMessageViews;

		let firstViewer;
		rawFirstViewers.forEach(rawFirstViewer => {
			if (rawFirstViewer.user_id === MessengerParams.getUserId())
			{
				return;
			}

			firstViewer = {
				userId: rawFirstViewer.user_id,
				userName: rawFirstViewer.user_name,
				date: DateHelper.cast(rawFirstViewer.date)
			};
		});

		if (countOfViewers > 0 && !firstViewer)
		{
			throw new Error('dialoguesModel: no first viewer for message');
		}

		return {
			countOfViewers,
			firstViewer,
			messageId
		};
	}

	function prepareAvatar(store, avatar)
	{
		let result = '';

		if (!avatar || avatar.endsWith('/js/im/images/blank.gif'))
		{
			result = '';
		}
		else if (avatar.startsWith('http'))
		{
			result = avatar;
		}
		else
		{
			result = store.rootState.applicationModel.common.host + avatar;
		}

		if (result)
		{
			result = encodeURI(result);
		}

		return result;
	}

	module.exports = { dialoguesModel };
});
