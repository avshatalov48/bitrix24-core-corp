/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/dialogues/model
 */
jn.define('im/messenger/model/dialogues/model', (require, exports, module) => {
	const { Type } = require('type');
	const { unique } = require('utils/array');
	const { clone, isEqual, mergeImmutable } = require('utils/object');
	const { validate, preparePermissions } = require('im/messenger/model/dialogues/validator');
	const { dialogDefaultElement } = require('im/messenger/model/dialogues/default-element');
	const { copilotModel } = require('im/messenger/model/dialogues/copilot/model');
	const { collabModel } = require('im/messenger/model/dialogues/collab/model');
	const { aiAssistantModel } = require('im/messenger/model/dialogues/ai-assistant/model');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const { ModelUtils } = require('im/messenger/lib/utils');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--dialogues');

	/** @type {DialoguesMessengerModel} */
	const dialoguesModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		modules: {
			copilotModel,
			collabModel,
			aiAssistantModel,
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
			 * @function dialoguesModel/getByChatIdList
			 * @return {Array<DialoguesModelState>}
			 */
			getByChatIdList: (state) => (chatIdList) => {
				if (!Type.isArrayFilled(chatIdList))
				{
					return [];
				}

				const dialogModelList = [];
				Object.values(state.collection)
					.forEach((item) => {
						if (chatIdList.includes(item.chatId))
						{
							dialogModelList.push(item);
						}
					});

				return dialogModelList;
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

			/**
			 * @function dialoguesModel/getBackgroundId
			 * @return {DialogBackgroundId|null}
			 */
			getBackgroundId: (state, getters) => (dialogId) => {
				const dialogModel = getters.getById(dialogId);
				if (!dialogModel)
				{
					return null;
				}

				return dialogModel.backgroundId ?? null;
			},
		},
		actions: {
			/** @function dialoguesModel/setState */
			setState: (store, payload) => {
				Object.entries(payload.collection).forEach(([key, value]) => {
					payload.collection[key] = mergeImmutable(dialogDefaultElement, payload.collection[key]);
				});

				store.commit('setState', {
					actionName: 'setState',
					data: {
						collection: payload.collection,
					},
				});
			},

			/**
			 * @function dialoguesModel/setFromSync
			 */
			setFromSync: (store, dialogList) => {
				return store.dispatch('set', { itemList: dialogList, actionName: 'setFromSync' });
			},

			/** @function dialoguesModel/set */
			set: (store, payload) => {
				const { itemList, actionName = 'set' } = ModelUtils.normalizeItemListPayload(payload);

				itemList.map((element) => {
					return validate(element);
				}).forEach((element) => {
					/** @type {DialoguesModelState} */
					const existingItem = store.state.collection[element.dialogId];
					if (existingItem)
					{
						store.commit('update', {
							actionName,
							data: {
								dialogId: element.dialogId,
								fields: element,
							},
						});
					}
					else
					{
						store.commit('add', {
							actionName,
							data: {
								dialogId: element.dialogId,
								fields: mergeImmutable(dialogDefaultElement, element),
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
					return validate(element);
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
								fields: mergeImmutable(dialogDefaultElement, element),
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
					return validate(element);
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
							fields: mergeImmutable(dialogDefaultElement, element),
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

			/** @function dialoguesModel/setFromPush */
			setFromPush: (store, payload) => {
				if (!Type.isArrayFilled(payload))
				{
					return;
				}

				const dialogList = payload.map((dialog) => validate(dialog));

				store.commit('setFromPush', {
					actionName: 'setFromPush',
					data: {
						dialogList,
					},
				});
			},

			/** @function dialoguesModel/add */
			add: (store, payload) => {
				if (!Array.isArray(payload) && Type.isPlainObject(payload))
				{
					payload = [payload];
				}

				payload.map((element) => {
					return validate(element);
				}).forEach((element) => {
					const existingItem = store.state.collection[element.dialogId];
					if (!existingItem)
					{
						store.commit('add', {
							actionName: 'add',
							data: {
								dialogId: element.dialogId,
								fields: mergeImmutable(dialogDefaultElement, element),
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
						fields: validate(payload.fields),
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

				const permissions = mergeImmutable(
					ChatPermission.getActionGroupsByChatType(existingItem.type),
					existingItem.permissions,
					newPermissions,
				);

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
					&& isEqual(existingItem.tariffRestrictions, payload.tariffRestrictions))
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

			/** @function dialoguesModel/setInputAction */
			setInputAction: (store, payload) => {
				const { dialogId, userId, userFirstName, type, statusMessageCode } = payload;
				const existingItem = store.state.collection[String(dialogId)];

				if (!existingItem)
				{
					return false;
				}

				const inputActions = clone(existingItem.inputActions);
				const indexInputActionsByUser = inputActions.findIndex((item) => item.userId === userId);

				const newInputActions = {
					dialogId,
					userId,
					userFirstName,
					action: { type, statusMessageCode },
				};

				let validatedInputActions = [];
				if (indexInputActionsByUser === -1)
				{
					validatedInputActions = validate({
						inputActions: [...inputActions, newInputActions],
					});
				}
				else
				{
					inputActions.splice(indexInputActionsByUser, 1, newInputActions);
					validatedInputActions = validate({
						inputActions,
					});
				}

				return store.commit('update', {
					actionName: 'updateInputAction',
					data: {
						dialogId,
						fields: validatedInputActions,
					},
				});
			},

			/** @function dialoguesModel/removeInputAction */
			removeInputAction: (store, payload) => {
				const { dialogId, userId } = payload;
				const existingItem = store.state.collection[String(dialogId)];

				if (!existingItem)
				{
					return false;
				}
				const inputActions = clone(existingItem.inputActions);
				const indexInputActionsByUser = inputActions.findIndex((item) => item.userId === userId);

				if (indexInputActionsByUser === -1)
				{
					return false;
				}

				inputActions.splice(indexInputActionsByUser, 1);

				return store.commit('update', {
					actionName: 'updateInputAction',
					data: {
						dialogId,
						fields: validate({ inputActions }),
					},
				});
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
						fields: validate({ muteList }),
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
						fields: validate({ muteList }),
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
					{
						participants: newParticipants,
						lastLoadParticipantId: payload.lastLoadParticipantId,
						hasNextPage: payload.hasNextPage,
					},
				);
				const uniqId = unique(validData.participants
					.filter((userId) => !existingItem.participants.includes(userId)));

				if (uniqId.length === 0)
				{
					return false;
				}

				const newState = [...existingItem.participants, ...uniqId];
				const userCounter = payload.userCounter || existingItem.userCounter;

				const fields = {
					participants: newState,
					userCounter,
					hasNextPage: validData.hasNextPage,
					lastLoadParticipantId: validData.lastLoadParticipantId || existingItem.lastLoadParticipantId,
				};

				return store.commit('update', {
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
				const validUsersId = validate({ participants: newParticipants });
				const newState = existingItem.participants.filter(
					(userId) => !validUsersId.participants.includes(userId),
				);
				const newStateManager = existingItem.managerList.filter(
					(userId) => !validUsersId.participants.includes(userId),
				);
				const userCounter = payload.userCounter || existingItem.userCounter;

				return store.commit('update', {
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
				const affectedDialogs = [];
				Object.keys(store.state.collection).forEach((dialogId) => {
					if (store.state.collection[dialogId].counter !== 0)
					{
						affectedDialogs.push(dialogId);
					}
				});

				store.commit('clearAllCounters', {
					actionName: 'clearAllCounters',
					data: {
						affectedDialogs,
					},
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
			 * @param {MutationPayload<DialoguesSetFromPushData, DialoguesSetFromPushActions>} payload
			 */
			setFromPush: (state, payload) => {
				logger.log('dialoguesModel: setFromPush mutation', payload);

				const { dialogList } = payload.data;

				for (const dialog of dialogList)
				{
					state.collection[dialog.dialogId] = mergeImmutable(
						dialogDefaultElement,
						state.collection[dialog.dialogId],
						dialog,
					);
				}
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

				state.collection[dialogId] = mergeImmutable(state.collection[dialogId], fields);
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

					state.collection[dialogId] = mergeImmutable(state.collection[dialogId], fields);
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

			/**
			 * @param state
			 * @param {MutationPayload<DialoguesClearAllCountersData, DialoguesClearAllCountersActions>} payload
			 */
			clearAllCounters: (state, payload) => {
				logger.log('dialoguesModel: cleat all counters', payload);

				Object.keys(state.collection).forEach((dialogId) => {
					state.collection[dialogId] = {
						...state.collection[dialogId],
						counter: 0,
					};
				});
			},
		},
	};

	module.exports = { dialoguesModel };
});
