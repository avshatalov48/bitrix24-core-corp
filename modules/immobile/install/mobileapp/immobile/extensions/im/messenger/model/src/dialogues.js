/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/dialogues
 */
jn.define('im/messenger/model/dialogues', (require, exports, module) => {
	const { Type } = require('type');
	const { DialogType, UserRole } = require('im/messenger/const');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { Color } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { clone } = require('utils/object');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--dialogues');

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
		participants: [],
		lastLoadParticipantId: 0,
		lastReadId: 0,
		markedId: 0,
		lastMessageId: 0,
		lastMessageViews: {
			countOfViewers: 0,
			firstViewer: null,
			messageId: 0,
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
			link: '',
		},
		inited: false,
		loading: false,
		hasPrevPage: false,
		hasNextPage: false,
		diskFolderId: 0,
		role: UserRole.guest,
		permissions: {
			manageUsersAdd: UserRole.none,
			manageUsersDelete: UserRole.none,
			manageUi: UserRole.none,
			manageSettings: UserRole.none,
			canPost: UserRole.none,
		},
	};

	const dialoguesModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/**
			 * @function dialoguesModel/getById
			 * @return {?DialoguesModelState}
			 */
			getById: (state) => (id) => {
				return state.collection[id];
			},

			/** @function dialoguesModel/getByIdList */
			getByIdList: (state, getters) => (idList) => {
				if (!Type.isArrayFilled(idList))
				{
					return [];
				}

				const dialogList = [];
				idList.forEach((id) => {
					const dialog = getters.getById(id);
					if (dialog)
					{
						dialogList.push(dialog);
					}
				});

				return dialogList;
			},

			/** @function dialoguesModel/getCollectionByIdList */
			getCollectionByIdList: (state, getters) => (idList) => {
				if (!Type.isArrayFilled(idList))
				{
					return [];
				}

				const collection = {};
				idList.forEach((id) => {
					const dialog = getters.getById(id);
					if (dialog)
					{
						collection[id] = dialog;
					}
				});

				return collection;
			},

			/**
			 * @function dialoguesModel/getByChatId
			 * @return {?DialoguesModelState}
			 */
			getByChatId: (state) => (chatId) => {
				chatId = Number.parseInt(chatId, 10);

				return Object.values(state.collection).find((item) => {
					return item.chatId === chatId;
				});
			},

			/**
			 * @function dialoguesModel/getLastReadId
			 * @return {number}
			 */
			getLastReadId: (state) => (dialogId) => {
				if (!state.collection[dialogId])
				{
					return 0;
				}

				const { lastReadId, lastMessageId } = state.collection[dialogId];

				return lastReadId === lastMessageId ? 0 : lastReadId;
			},

			/**
			 * @function dialoguesModel/getInitialMessageId
			 * @return {number}
			 */
			getInitialMessageId: (state) => (dialogId) => {
				if (!state.collection[dialogId])
				{
					return 0;
				}

				const { lastReadId, markedId } = state.collection[dialogId];
				if (markedId === 0)
				{
					return lastReadId;
				}

				return Math.min(lastReadId, markedId);
			},
		},
		actions: {
			/** @function dialoguesModel/setState */
			setState: (store, payload) => {
				Object.entries(payload.collection).forEach(([key, value]) => {
					payload.collection[key].writingList = [];
				});

				store.commit('setState', {
					actionName: 'setState',
					data: {
						collection: payload.collection,
					},
				});
			},

			/** @function dialoguesModel/set */
			set: (store, payload) => {
				if (!Array.isArray(payload) && Type.isPlainObject(payload))
				{
					payload = [payload];
				}

				payload.map((element) => {
					return validate(store, element);
				}).forEach((element) => {
					/** @type {DialoguesModelState} */
					const existingItem = store.state.collection[element.dialogId];
					if (existingItem)
					{
						store.commit('update', {
							actionName: 'set',
							data: {
								dialogId: element.dialogId,
								fields: element,
							},
						});
					}
					else
					{
						store.commit('add', {
							actionName: 'set',
							data: {
								dialogId: element.dialogId,
								fields: { ...dialogState, ...element },
							},
						});
					}
				});
			},

			/** @function dialoguesModel/add */
			add: (store, payload) => {
				if (!Array.isArray(payload) && Type.isPlainObject(payload))
				{
					payload = [payload];
				}

				payload.map((element) => {
					return validate(store, element);
				}).forEach((element) => {
					const existingItem = store.state.collection[element.dialogId];
					if (!existingItem)
					{
						store.commit('add', {
							actionName: 'add',
							data: {
								dialogId: element.dialogId,
								fields: { ...dialogState, ...element },
							},
						});
					}
				});
			},

			/** @function dialoguesModel/update */
			update: (store, payload) => {
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				store.commit('update', {
					actionName: 'update',
					data: {
						dialogId: payload.dialogId,
						fields: validate(store, payload.fields),
					},
				});

				return true;
			},

			/** @function dialoguesModel/updateWritingList */
			updateWritingList: (store, payload) => {
				const existingItem = store.state.collection[String(payload.dialogId)];

				if (!existingItem)
				{
					return false;
				}

				const oldWritingList = clone(existingItem.writingList);
				let newWritingList = clone(oldWritingList);
				let isHasChange = false;
				payload.fields.writingList.forEach((user) => {
					const userId = user.userId;
					const isWriting = user.isWriting;

					const userIndex = oldWritingList.findIndex((user) => user.userId === userId);
					if (userIndex !== -1 && !isWriting)
					{
						isHasChange = true;
						newWritingList = newWritingList.filter((el, index) => index !== userIndex);
					}

					if (userIndex === -1)
					{
						isHasChange = true;
						newWritingList.push({ ...user });
					}
				});

				const validateList = validate(store, { writingList: newWritingList });
				if (isHasChange)
				{
					store.commit('update', {
						actionName: 'updateWritingList',
						data: {
							dialogId: payload.dialogId,
							fields: validateList,
						},
					});
				}

				return true;
			},

			/** @function dialoguesModel/delete */
			delete: (store, payload) => {
				store.commit('delete', {
					actionName: 'delete',
					data: {
						dialogId: payload.dialogId,
					},
				});

				return true;
			},

			/** @function dialoguesModel/decreaseCounter */
			decreaseCounter: (store, payload) => {
				/** @type {DialoguesModelState} */
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				// for fix race condition
				if (payload.lastId)
				{
					if (existingItem.lastReadId === payload.lastId && payload.count !== existingItem.counter)
					{
						store.commit('update', {
							actionName: 'decreaseCounter',
							data: {
								dialogId: payload.dialogId,
								fields: {
									counter: payload.count,
									previousCounter: existingItem.counter,
								},
							},
						});

						return true;
					}

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

				if (decreasedCounter === existingItem.counter)
				{
					return false;
				}

				store.commit('update', {
					actionName: 'decreaseCounter',
					data: {
						dialogId: payload.dialogId,
						fields: {
							counter: decreasedCounter,
							previousCounter: existingItem.counter,
						},
					},
				});

				return true;
			},

			/** @function dialoguesModel/updateUserCounter */
			updateUserCounter(store, payload)
			{
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				if (existingItem.userCounter === payload.userCounter)
				{
					return false;
				}

				store.commit('update', {
					actionName: 'updateUserCounter',
					data: {
						dialogId: payload.dialogId,
						fields: {
							userCounter: payload.userCounter,
						},
					},
				});

				return true;
			},

			mute(store, payload)
			{
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				const currentUserId = MessengerParams.getUserId();
				if (existingItem.muteList.includes(currentUserId))
				{
					return false;
				}

				const muteList = [...existingItem.muteList, currentUserId];

				store.commit('update', {
					actionName: 'mute',
					data: {
						dialogId: payload.dialogId,
						fields: validate(store, { muteList }),
					},
				});

				return true;
			},

			unmute(store, payload)
			{
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				const currentUserId = MessengerParams.getUserId();
				const muteList = existingItem.muteList.filter((item) => item !== currentUserId);

				store.commit('update', {
					actionName: 'unmute',
					data: {
						dialogId: payload.dialogId,
						fields: validate(store, { muteList }),
					},
				});

				return true;
			},

			/** @function dialoguesModel/addParticipants */
			addParticipants(store, payload)
			{
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				const newParticipants = payload.participants;
				if (Type.isUndefined(newParticipants))
				{
					return false;
				}

				const validData = validate(
					store,
					{ participants: newParticipants, lastLoadParticipantId: payload.lastLoadParticipantId },
				);
				const uniqId = validData.participants.filter((userId) => !existingItem.participants.includes(userId));
				if (uniqId.length === 0)
				{
					return false;
				}

				const newState = [...existingItem.participants, ...uniqId];
				const userCounter = payload.userCounter || existingItem.userCounter;

				const fields = {
					participants: newState,
					userCounter,
					lastLoadParticipantId: validData.lastLoadParticipantId || existingItem.lastLoadParticipantId,
				};

				store.commit('update', {
					actionName: 'addParticipants',
					data: {
						dialogId: payload.dialogId,
						fields,
					},
				});
			},

			/** @function dialoguesModel/removeParticipants */
			removeParticipants(store, payload)
			{
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				const newParticipants = payload.participants;
				if (Type.isUndefined(newParticipants))
				{
					return false;
				}
				const validUsersId = validate(store, { participants: newParticipants });
				const newState = existingItem.participants.filter(
					(userId) => !validUsersId.participants.includes(userId),
				);
				const userCounter = payload.userCounter || existingItem.userCounter;
				store.commit('update', {
					actionName: 'removeParticipants',
					data: {
						removeData: validUsersId.participants,
						dialogId: payload.dialogId,
						fields: { participants: newState, userCounter },
					},
				});
			},

			/** @function dialoguesModel/clearLastMessageViews */
			clearLastMessageViews: (store, payload) => {
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return;
				}

				const {
					lastMessageViews: defaultLastMessageViews,
				} = dialogState;
				store.commit('update', {
					actionName: 'clearLastMessageViews',
					data: {
						dialogId: payload.dialogId,
						fields: {
							lastMessageViews: defaultLastMessageViews,
						},
					},
				});
			},

			/** @function dialoguesModel/incrementLastMessageViews */
			incrementLastMessageViews: (store, payload) => {
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return;
				}

				const newCounter = existingItem.lastMessageViews.countOfViewers + 1;
				store.commit('update', {
					actionName: 'incrementLastMessageViews',
					data: {
						dialogId: payload.dialogId,
						fields: {
							lastMessageViews: {
								...existingItem.lastMessageViews,
								countOfViewers: newCounter,
							},
						},
					},
				});
			},

			/** @function dialoguesModel/setLastMessageViews */
			setLastMessageViews: (store, payload) => {
				const {
					dialogId,
					fields: {
						userId,
						userName,
						date,
						messageId,
						countOfViewers = 1,
					},
				} = payload;
				const existingItem = store.state.collection[dialogId];
				if (!existingItem)
				{
					return;
				}

				const newLastMessageViews = {
					countOfViewers,
					messageId,
					firstViewer: {
						userId,
						userName,
						date,
					},
				};
				store.commit('update', {
					actionName: 'setLastMessageViews',
					data: {
						dialogId,
						fields: {
							lastMessageViews: newLastMessageViews,
						},
					},
				});
			},

			/** @function dialoguesModel/clearAllCounters */
			clearAllCounters: (store, payload) => {
				Object.values(store.state.collection).forEach((dialogItem) => {
					if (dialogItem.counter > 0)
					{
						store.commit('update', {
							actionName: 'clearAllCounters',
							data: {
								dialogId: dialogItem.dialogId,
								fields: {
									counter: 0,
								},
							},
						});
					}
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			setState: (state, payload) => {
				logger.log('dialoguesModel: setState mutation', payload);

				const {
					collection,
				} = payload.data;

				state.collection = collection;
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			add: (state, payload) => {
				logger.log('dialoguesModel: add mutation', payload);

				const {
					dialogId,
					fields,
				} = payload.data;

				state.collection[dialogId] = fields;
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			update: (state, payload) => {
				logger.log('dialoguesModel: update mutation', payload);

				const {
					dialogId,
					fields,
				} = payload.data;

				state.collection[dialogId] = { ...state.collection[dialogId], ...fields };
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			delete: (state, payload) => {
				logger.log('dialoguesModel: delete mutation', payload);

				const {
					dialogId,
				} = payload.data;

				delete state.collection[dialogId];
			},
		},
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

		if (!Type.isUndefined(fields.participants))
		{
			result.participants = fields.participants.map(
				(userId) => (Type.isString(userId) ? parseInt(userId, 10) : userId),
			);
		}

		if (Type.isNumber(fields.userCounter) || Type.isStringFilled(fields.userCounter))
		{
			result.userCounter = Number.parseInt(fields.userCounter, 10);
		}

		if (!Type.isUndefined(fields.last_id))
		{
			fields.lastId = fields.last_id;
		}

		if (Type.isNumber(fields.lastId) || Type.isStringFilled(fields.lastId))
		{
			result.lastReadId = Number.parseInt(fields.lastId, 10);
		}

		if (Type.isNumber(fields.lastReadId) || Type.isStringFilled(fields.lastReadId))
		{
			result.lastReadId = Number.parseInt(fields.lastReadId, 10);
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

		if (Type.isNumber(fields.lastLoadParticipantId))
		{
			result.lastLoadParticipantId = fields.lastLoadParticipantId;
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
			if (
				Type.isNumber(fields.lastMessageViews.messageId)
				&& Type.isNumber(fields.lastMessageViews.countOfViewers)
			)
			{
				result.lastMessageViews = fields.lastMessageViews;
			}
			else
			{
				result.lastMessageViews = prepareLastMessageViews(fields.lastMessageViews);
			}
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

		if (!Type.isUndefined(fields.writing_list))
		{
			fields.writingList = fields.writing_list;
		}

		if (Type.isArray(fields.writingList))
		{
			result.writingList = prepareWritingList(fields.writingList);
		}

		if (!Type.isUndefined(fields.manager_list))
		{
			fields.managerList = fields.manager_list;
		}

		if (Type.isArray(fields.managerList))
		{
			result.managerList = [];

			fields.managerList.forEach((userId) => {
				userId = Number.parseInt(userId, 10);
				if (userId > 0)
				{
					result.managerList.push(userId);
				}
			});
		}

		if (!Type.isUndefined(fields.mute_list))
		{
			fields.muteList = fields.mute_list;
		}

		if (Type.isArray(fields.muteList) || Type.isPlainObject(fields.muteList))
		{
			result.muteList = prepareMuteList(fields.muteList);
		}

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

		if (Type.isNumber(fields.disk_folder_id))
		{
			result.diskFolderId = fields.disk_folder_id;
		}
		else if (Type.isNumber(fields.diskFolderId))
		{
			result.diskFolderId = fields.diskFolderId;
		}

		fields.role = fields.role?.toString().toLowerCase();
		if (UserRole[fields.role])
		{
			result.role = fields.role;
		}

		if (fields.permissions)
		{
			const preparedPermissions = preparePermissions(fields.permissions);
			if (Object.values(preparedPermissions).length > 0)
			{
				result.permissions = preparedPermissions;
			}
		}

		return result;
	}

	function prepareLastMessageViews(rawLastMessageViews)
	{
		const {
			count_of_viewers: countOfViewers,
			first_viewers: rawFirstViewers,
			message_id: messageId,
		} = rawLastMessageViews;

		let firstViewer;
		for (const rawFirstViewer of rawFirstViewers)
		{
			if (rawFirstViewer.user_id === MessengerParams.getUserId())
			{
				continue;
			}

			firstViewer = {
				userId: rawFirstViewer.user_id,
				userName: rawFirstViewer.user_name,
				date: DateHelper.cast(rawFirstViewer.date),
			};
			break;
		}

		if (countOfViewers > 0 && !firstViewer)
		{
			throw new Error('dialoguesModel: no first viewer for message');
		}

		return {
			countOfViewers,
			firstViewer,
			messageId,
		};
	}

	function prepareMuteList(muteList)
	{
		const result = [];

		if (Type.isArray(muteList))
		{
			muteList.forEach((userId) => {
				userId = Number.parseInt(userId, 10);
				if (userId > 0)
				{
					result.push(userId);
				}
			});
		}
		else if (Type.isPlainObject(muteList))
		{
			Object.entries(muteList).forEach(([key, value]) => {
				if (!value)
				{
					return;
				}
				const userId = Number.parseInt(key, 10);
				if (userId > 0)
				{
					result.push(userId);
				}
			});
		}

		return result;
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

	function prepareWritingList(writingList)
	{
		const result = [];

		writingList.forEach((user) => {
			const item = {};

			if (!user.userId)
			{
				return false;
			}

			item.userId = Number.parseInt(user.userId, 10);
			item.userName = user.userName;
			result.push(item);

			return true;
		});

		return result;
	}

	/**
	 * @private
	 * @param {object} fields
	 * @return {object}
	 */
	function preparePermissions(fields)
	{
		const result = {};
		if (Type.isStringFilled(fields.manage_users_add) || Type.isStringFilled(fields.manageUsersAdd))
		{
			result.manageUsersAdd = fields.manage_users_add || fields.manageUsersAdd;
		}

		if (Type.isStringFilled(fields.manage_users_delete) || Type.isStringFilled(fields.manageUsersDelete))
		{
			result.manageUsersDelete = fields.manage_users_delete || fields.manageUsersDelete;
		}

		if (Type.isStringFilled(fields.manage_ui) || Type.isStringFilled(fields.manageUi))
		{
			result.manageUi = fields.manage_ui || fields.manageUi;
		}

		if (Type.isStringFilled(fields.manage_settings) || Type.isStringFilled(fields.manageSettings))
		{
			result.manageSettings = fields.manage_settings || fields.manageSettings;
		}

		if (Type.isStringFilled(fields.can_post) || Type.isStringFilled(fields.canPost))
		{
			result.canPost = fields.can_post || fields.canPost;
		}

		return result;
	}

	module.exports = { dialoguesModel };
});
