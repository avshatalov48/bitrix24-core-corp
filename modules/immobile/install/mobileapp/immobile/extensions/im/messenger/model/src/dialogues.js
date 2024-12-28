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
	const { clone, mergeImmutable, isEqual } = require('utils/object');
	const { copilotModel } = require('im/messenger/model/dialogues/copilot');
	const { collabModel } = require('im/messenger/model/dialogues/collab/collab');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const logger = LoggerManager.getInstance().getLogger('model--dialogues');

	const dialogDefaultElement = Object.freeze({
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
			manageMessages: UserRole.none,
			manageUsersAdd: UserRole.none,
			manageUsersDelete: UserRole.none,
			manageUi: UserRole.none,
			manageSettings: UserRole.none,
			canPost: UserRole.none,
		},
		tariffRestrictions: {
			isHistoryLimitExceeded: false,
		},
		aiProvider: '',
		parentChatId: 0, // unsafe in local database
		parentMessageId: 0, // unsafe in local database
		messageCount: 0, // unsafe in local database
	});

	/** @type {DialoguesMessengerModel} */
	const dialoguesModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		modules: {
			copilotModel,
			collabModel,
		},
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

			/**
			 * @function dialoguesModel/getByParentMessageId
			 * @return {DialoguesModelState | undefined}
			 */
			getByParentMessageId: (state) => (parentMessageId) => {
				return Object.values(state.collection).find((item) => {
					return item.parentMessageId === parentMessageId;
				});
			},

			/**
			 * @function dialoguesModel/getByParentChatId
			 * @return {DialoguesModelState | undefined}
			 */
			getByParentChatId: (state) => (parentChatId) => {
				return Object.values(state.collection).find((item) => {
					return item.parentChatId === parentChatId;
				});
			},
		},
		actions: {
			/** @function dialoguesModel/setState */
			setState: (store, payload) => {
				Object.entries(payload.collection).forEach(([key, value]) => {
					payload.collection[key] = { ...dialogDefaultElement, ...payload.collection[key] };
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
								fields: { ...dialogDefaultElement, ...element },
							},
						});
					}
				});
			},

			/** @function dialoguesModel/setFromLocalDatabase */
			setFromLocalDatabase: (store, payload) => {
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
							actionName: 'setFromLocalDatabase',
							data: {
								dialogId: element.dialogId,
								fields: element,
							},
						});
					}
					else
					{
						store.commit('add', {
							actionName: 'setFromLocalDatabase',
							data: {
								dialogId: element.dialogId,
								fields: { ...dialogDefaultElement, ...element },
							},
						});
					}
				});
			},

			/** @function dialoguesModel/setCollectionFromLocalDatabase */
			setCollectionFromLocalDatabase: (store, payload) => {
				if (!Array.isArray(payload) && Type.isPlainObject(payload))
				{
					payload = [payload];
				}

				const updateItems = [];
				const addItems = [];
				payload.map((element) => {
					return validate(store, element);
				}).forEach((element) => {
					/** @type {DialoguesModelState} */
					const existingItem = store.state.collection[element.dialogId];
					if (existingItem)
					{
						updateItems.push({
							dialogId: element.dialogId,
							fields: element,
						});
					}
					else
					{
						addItems.push({
							dialogId: element.dialogId,
							fields: { ...dialogDefaultElement, ...element },
						});
					}
				});

				if (updateItems.length > 0)
				{
					store.commit('updateCollection', {
						actionName: 'setCollectionFromLocalDatabase',
						data: { updateItems },
					});
				}

				if (addItems.length > 0)
				{
					store.commit('addCollection', {
						actionName: 'setCollectionFromLocalDatabase',
						data: { addItems },
					});
				}
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
								fields: { ...dialogDefaultElement, ...element },
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

			/** @function dialoguesModel/updatePermissions */
			updatePermissions: (store, payload) => {
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				const newPermissions = preparePermissions(payload.fields);

				const permissions = {
					...ChatPermission.getActionGroupsByChatType(existingItem.type),
					...existingItem.permissions,
					...newPermissions,
				};

				store.commit('update', {
					actionName: 'updatePermissions',
					data: {
						dialogId: payload.dialogId,
						fields: { permissions },
					},
				});

				return true;
			},

			/** @function dialoguesModel/updateType */
			updateType: (store, payload) => {
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				const newType = payload.type;
				if (newType === existingItem.type)
				{
					return false;
				}

				store.commit('update', {
					actionName: 'updateType',
					data: {
						dialogId: payload.dialogId,
						fields: { type: payload.type },
					},
				});

				return true;
			},

			/**
			 * @function dialoguesModel/updateTariffRestrictions
			 * @param store
			 * @param {DialogUpdateTariffRestrictionsPayload} payload
			 */
			updateTariffRestrictions: (store, payload) => {
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				if (payload.isForceUpdate === false
					&& isEqual(existingItem.tariffRestrictions, payload.tariffRestrictions, true))
				{
					return false;
				}

				store.commit('update', {
					actionName: 'updateTariffRestrictions',
					data: {
						dialogId: payload.dialogId,
						fields: {
							tariffRestrictions: payload.tariffRestrictions,
						},
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

			/** @function dialoguesModel/deleteFromModel */
			deleteFromModel: (store, payload) => {
				store.commit('delete', {
					actionName: 'deleteFromModel',
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

			/** @function dialoguesModel/updateManagerList */
			updateManagerList(store, payload)
			{
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				if (existingItem.managerList === payload.managerList)
				{
					return false;
				}

				store.commit('update', {
					actionName: 'updateManagerList',
					data: {
						dialogId: payload.dialogId,
						fields: {
							managerList: payload.managerList,
						},
					},
				});

				return true;
			},

			/** @function dialoguesModel/mute */
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

			/** @function dialoguesModel/unmute */
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
				const newStateManager = existingItem.managerList.filter(
					(userId) => !validUsersId.participants.includes(userId),
				);
				const userCounter = payload.userCounter || existingItem.userCounter;

				store.commit('update', {
					actionName: 'removeParticipants',
					data: {
						removeData: validUsersId.participants,
						dialogId: payload.dialogId,
						fields: { participants: newState, userCounter, managerList: newStateManager },
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
				} = dialogDefaultElement;
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
			 * @param {MutationPayload<DialoguesSetStateData, DialoguesSetStateActions>} payload
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
			 * @param {MutationPayload<DialoguesAddData, DialoguesAddActions>} payload
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
			 * @param {MutationPayload<DialoguesAddCollectionData, DialoguesAddActions>} payload
			 */
			addCollection: (state, payload) => {
				logger.log('dialoguesModel: addCollection mutation', payload);

				payload.data.addItems.forEach((item) => {
					const {
						dialogId,
						fields,
					} = item;

					state.collection[dialogId] = fields;
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<DialoguesUpdateData, DialoguesUpdateActions>} payload
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
			 * @param {MutationPayload<DialoguesUpdateCollectionData, DialoguesUpdateActions>} payload
			 */
			updateCollection: (state, payload) => {
				logger.log('dialoguesModel: updateCollection mutation', payload);

				payload.data.updateItems.forEach((item) => {
					const {
						dialogId,
						fields,
					} = item;

					state.collection[dialogId] = { ...state.collection[dialogId], ...fields };
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<DialoguesDeleteData, DialoguesDeleteActions>} payload
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

	/**
	 *
	 * @param {MessengerStore<DialoguesMessengerModel>} store
	 * @param fields
	 * @return {{}}
	 */
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

		if (!Type.isUndefined(fields.permissions))
		{
			result.permissions = {};
			if (Type.isObject(fields.permissions))
			{
				result.permissions = preparePermissions(fields.permissions);
			}

			result.permissions = mergeImmutable(ChatPermission.getActionGroupsByChatType(result.type), result.permissions);
		}

		if (Type.isStringFilled(fields.aiProvider))
		{
			result.aiProvider = fields.aiProvider;
		}

		if (Type.isStringFilled(fields.ai_provider))
		{
			result.aiProvider = fields.ai_provider;
		}

		if (Type.isNumber(fields.parentChatId))
		{
			result.parentChatId = fields.parentChatId;
		}

		if (Type.isNumber(fields.parentMessageId))
		{
			result.parentMessageId = fields.parentMessageId;
		}

		if (fields.tariffRestrictions)
		{
			result.tariffRestrictions = fields.tariffRestrictions;
		}

		return result;
	}

	function prepareLastMessageViews(rawLastMessageViews)
	{
		if (Type.isObject(rawLastMessageViews.firstViewer) || rawLastMessageViews.firstViewer === null)
		{
			return rawLastMessageViews;
		}

		let countOfViewers = rawLastMessageViews.countOfViewers;
		let rawFirstViewers = rawLastMessageViews.firstViewers;
		let messageId = rawLastMessageViews.messageId;
		let firstViewer = null;

		try
		{
			if (
				Type.isUndefined(countOfViewers)
				&& !Type.isUndefined(rawLastMessageViews.count_of_viewers)
			) // old rest response
			{
				countOfViewers = rawLastMessageViews.count_of_viewers;
				rawFirstViewers = rawLastMessageViews.first_viewers;
				messageId = rawLastMessageViews.message_id;

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
			}
			else
			{
				if (Type.isNil(rawFirstViewers))
				{
					// case for get data from local db (lastMessageViews is empty object)
					return {
						countOfViewers: 0,
						firstViewer: null,
						messageId: 0,
					};
				}

				for (const rawFirstViewer of rawFirstViewers)
				{
					if (rawFirstViewer.userId === MessengerParams.getUserId())
					{
						continue;
					}

					firstViewer = {
						userId: rawFirstViewer.userId,
						userName: rawFirstViewer.userName,
						date: DateHelper.cast(rawFirstViewer.date),
					};
					break;
				}
			}
		}
		catch (error)
		{
			logger.error('dialoguesModel.prepareLastMessageViews.catch:', error);
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
			result = currentDomain + avatar;
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
			fields.manageMessages = fields.can_post || fields.canPost;
		}

		if (Type.isStringFilled(fields.manage_messages) || Type.isStringFilled(fields.manageMessages))
		{
			result.manageMessages = fields.manage_messages || fields.manageMessages;
		}

		return result;
	}

	module.exports = { dialoguesModel, dialogDefaultElement };
});
