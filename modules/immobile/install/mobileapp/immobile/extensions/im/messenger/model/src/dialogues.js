/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/model/dialogues
 */
jn.define('im/messenger/model/dialogues', (require, exports, module) => {

	const { Type } = jn.require('type');
	const { DateHelper } = jn.require('im/messenger/lib/helper');

	const dialogState = {
		dialogId: '0',
		chatId: 0,
		counter: 0,
		userCounter: 0,
		messageCount: 0,
		unreadId: 0,
		lastMessageId: 0,
		managerList: [],
		readedList: [],
		writingList: [],
		muteList: [],
		textareaMessage: '',
		quoteId: 0,
		editId: 0,
		init: false,
		name: '',
		owner: 0,
		extranet: false,
		avatar: '',
		color: '#17A3EA',
		type: 'chat',
		entityType: '',
		entityId: '',
		entityData1: '',
		entityData2: '',
		entityData3: '',
		dateCreate: new Date(),
		restrictions: {
			avatar: true,
			extend: true,
			leave: true,
			leaveOwner: true,
			rename: true,
			send: true,
			userList: true,
			mute: true,
			call: true,
		},
		public: {
			code: '',
			link: ''
		}
	}

	const dialoguesModel = {
		namespaced: true,
		state: () => ({
			collection: {},
			saveDialogList: [],
			saveChatList: [],
		}),
		getters: {
			getById: (state) => (id) => {
				return state.collection[id];
			},
		},
		actions: {
			set: (store, payload) =>
			{
				if (!(payload instanceof Array))
				{
					payload = [payload];
				}

				payload = payload.map(dialog => {
					return {
						...validate(dialog),
						...{ init: true },
					};
				});

				store.commit('set', payload);
			},
			delete: (store, payload) =>
			{
				const existingItem = store.state.collection[payload.id];
				if (!existingItem)
				{
					return false;
				}

				store.commit('delete', { id: payload.id });
			},
		},
		mutations: {
			set: (state, payload) =>
			{
				for (const element of payload)
				{
					state.collection[element.dialogId] = {
						...dialogState,
						...state.collection[element.dialogId],
						...element
					};
				}
			},
			delete: (state, payload) => {
				delete state.collection[payload.id];
			},
		}
	};

	function validate(fields, options = {})
	{
		const result = {};

		//options.host = options.host || this.getState().host;

		if (!Type.isUndefined(fields.dialog_id))
		{
			fields.dialogId = fields.dialog_id;
		}
		if (Type.isNumber(fields.dialogId) || Type.isString(fields.dialogId))
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
		if (Type.isNumber(fields.chatId) || Type.isString(fields.chatId))
		{
			result.chatId = parseInt(fields.chatId);
		}
		if (Type.isNumber(fields.quoteId))
		{
			result.quoteId = parseInt(fields.quoteId);
		}
		if (Type.isNumber(fields.editId))
		{
			result.editId = parseInt(fields.editId);
		}

		if (Type.isNumber(fields.counter) || Type.isString(fields.counter))
		{
			result.counter = parseInt(fields.counter);
		}

		if (Type.isNumber(fields.user_counter) || Type.isString(fields.user_counter))
		{
			result.userCounter = parseInt(fields.user_counter);
		}
		if (Type.isNumber(fields.userCounter) || Type.isString(fields.userCounter))
		{
			result.userCounter = parseInt(fields.userCounter);
		}

		if (typeof fields.message_count === "number" || typeof fields.message_count === "string")
		{
			result.messageCount = parseInt(fields.message_count);
		}
		if (typeof fields.messageCount === "number" || typeof fields.messageCount === "string")
		{
			result.messageCount = parseInt(fields.messageCount);
		}

		if (typeof fields.unread_id !== 'undefined')
		{
			fields.unreadId = fields.unread_id;
		}
		if (typeof fields.unreadId === "number" || typeof fields.unreadId === "string")
		{
			result.unreadId = parseInt(fields.unreadId);
		}

		if (typeof fields.last_message_id !== 'undefined')
		{
			fields.lastMessageId = fields.last_message_id;
		}
		if (typeof fields.lastMessageId === "number" || typeof fields.lastMessageId === "string")
		{
			result.lastMessageId = parseInt(fields.lastMessageId);
		}

		if (typeof fields.readed_list !== 'undefined')
		{
			fields.readedList = fields.readed_list;
		}
		if (typeof fields.readedList !== 'undefined')
		{
			result.readedList = [];

			if (fields.readedList instanceof Array)
			{
				fields.readedList.forEach(element =>
				{
					let record = {};
					if (typeof element.user_id !== 'undefined')
					{
						element.userId = element.user_id;
					}
					if (typeof element.user_name !== 'undefined')
					{
						element.userName = element.user_name;
					}
					if (typeof element.message_id !== 'undefined')
					{
						element.messageId = element.message_id;
					}

					if (!element.userId || !element.userName || !element.messageId)
					{
						return false;
					}

					record.userId = parseInt(element.userId);
					record.userName = element.userName.toString();
					record.messageId = parseInt(element.messageId);

					record.date = DateHelper.toDate(element.date);

					result.readedList.push(record);
				})
			}
		}

		if (typeof fields.writing_list !== 'undefined')
		{
			fields.writingList = fields.writing_list;
		}
		if (typeof fields.writingList !== 'undefined')
		{
			result.writingList = [];

			if (fields.writingList instanceof Array)
			{
				fields.writingList.forEach(element =>
				{
					let record = {};

					if (!element.userId)
					{
						return false;
					}

					record.userId = parseInt(element.userId);
					record.userName = element.userName;//Utils.text.htmlspecialcharsback(element.userName);

					result.writingList.push(record);
				})
			}
		}

		if (typeof fields.manager_list !== 'undefined')
		{
			fields.managerList = fields.manager_list;
		}
		if (typeof fields.managerList !== 'undefined')
		{
			result.managerList = [];

			if (fields.managerList instanceof Array)
			{
				fields.managerList.forEach(userId =>
				{
					userId = parseInt(userId);
					if (userId > 0)
					{
						result.managerList.push(userId);
					}
				});
			}
		}

		if (typeof fields.mute_list !== 'undefined')
		{
			fields.muteList = fields.mute_list;
		}
		if (typeof fields.muteList !== 'undefined')
		{
			result.muteList = [];

			if (fields.muteList instanceof Array)
			{
				fields.muteList.forEach(userId =>
				{
					userId = parseInt(userId);
					if (userId > 0)
					{
						result.muteList.push(userId);
					}
				});
			}
			else if (typeof fields.muteList === 'object')
			{
				Object.entries(fields.muteList).forEach(entry => {
					if (entry[1] === true)
					{
						const userId = parseInt(entry[0]);
						if (userId > 0)
						{
							result.muteList.push(userId);
						}
					}
				});
			}
		}

		if (typeof fields.textareaMessage !== 'undefined')
		{
			result.textareaMessage = fields.textareaMessage.toString();
		}

		if (typeof fields.title !== 'undefined')
		{
			fields.name = fields.title;
		}
		if (typeof fields.name === "string" || typeof fields.name === "number")
		{
			result.name = fields.name.toString();//Utils.text.htmlspecialcharsback(fields.name.toString());
		}

		if (typeof fields.owner !== 'undefined')
		{
			fields.ownerId = fields.owner;
		}
		if (typeof fields.ownerId === "number" || typeof fields.ownerId === "string")
		{
			result.ownerId = parseInt(fields.ownerId);
		}

		if (typeof fields.extranet === "boolean")
		{
			result.extranet = fields.extranet;
		}

		if (typeof fields.avatar === 'string')
		{
			let avatar;

			if (!fields.avatar || fields.avatar.endsWith('/js/im/images/blank.gif'))
			{
				avatar = '';
			}
			else if (fields.avatar.startsWith('http'))
			{
				avatar = fields.avatar;
			}
			else
			{
				avatar = options.host + fields.avatar;
			}

			if (avatar)
			{
				result.avatar = encodeURI(avatar);
			}
		}

		if (typeof fields.color === "string")
		{
			result.color = fields.color.toString();
		}

		if (typeof fields.type === "string")
		{
			result.type = fields.type.toString();
		}

		if (typeof fields.entity_type !== 'undefined')
		{
			fields.entityType = fields.entity_type;
		}
		if (typeof fields.entityType === "string")
		{
			result.entityType = fields.entityType.toString();
		}
		if (typeof fields.entity_id !== 'undefined')
		{
			fields.entityId = fields.entity_id;
		}
		if (typeof fields.entityId === "string" || typeof fields.entityId === "number")
		{
			result.entityId = fields.entityId.toString();
		}

		if (typeof fields.entity_data_1 !== 'undefined')
		{
			fields.entityData1 = fields.entity_data_1;
		}
		if (typeof fields.entityData1 === "string")
		{
			result.entityData1 = fields.entityData1.toString();
		}

		if (typeof fields.entity_data_2 !== 'undefined')
		{
			fields.entityData2 = fields.entity_data_2;
		}
		if (typeof fields.entityData2 === "string")
		{
			result.entityData2 = fields.entityData2.toString();
		}

		if (typeof fields.entity_data_3 !== 'undefined')
		{
			fields.entityData3 = fields.entity_data_3;
		}
		if (typeof fields.entityData3 === "string")
		{
			result.entityData3 = fields.entityData3.toString();
		}

		if (typeof fields.date_create !== 'undefined')
		{
			fields.dateCreate = fields.date_create;
		}

		if (typeof fields.dateCreate !== "undefined")
		{
			result.dateCreate = DateHelper.toDate(fields.dateCreate);
		}

		if (typeof fields.dateLastOpen !== "undefined")
		{
			result.dateLastOpen = DateHelper.toDate(fields.dateLastOpen);
		}

		if (typeof fields.restrictions === 'object' && fields.restrictions)
		{
			result.restrictions = {};

			if (typeof fields.restrictions.avatar === 'boolean')
			{
				result.restrictions.avatar = fields.restrictions.avatar;
			}

			if (typeof fields.restrictions.extend === 'boolean')
			{
				result.restrictions.extend = fields.restrictions.extend;
			}

			if (typeof fields.restrictions.leave === 'boolean')
			{
				result.restrictions.leave = fields.restrictions.leave;
			}

			if (typeof fields.restrictions.leave_owner === 'boolean')
			{
				result.restrictions.leaveOwner = fields.restrictions.leave_owner;
			}

			if (typeof fields.restrictions.rename === 'boolean')
			{
				result.restrictions.rename = fields.restrictions.rename;
			}

			if (typeof fields.restrictions.send === 'boolean')
			{
				result.restrictions.send = fields.restrictions.send;
			}

			if (typeof fields.restrictions.user_list === 'boolean')
			{
				result.restrictions.userList = fields.restrictions.user_list;
			}

			if (typeof fields.restrictions.mute === 'boolean')
			{
				result.restrictions.mute = fields.restrictions.mute;
			}

			if (typeof fields.restrictions.call === 'boolean')
			{
				result.restrictions.call = fields.restrictions.call;
			}
		}

		if (typeof fields.public === 'object' && fields.public)
		{
			result.public = {};

			if (typeof fields.public.code === 'string')
			{
				result.public.code = fields.public.code;
			}

			if (typeof fields.public.link === 'string')
			{
				result.public.link = fields.public.link;
			}
		}

		return result;
	}

	module.exports = { dialoguesModel };
});
