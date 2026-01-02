/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/dialogues/copilot/model
 */
jn.define('im/messenger/model/dialogues/copilot/model', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');

	const { ModelUtils } = require('im/messenger/lib/utils');

	const { validate, prepareMergeProperty } = require('im/messenger/model/dialogues/copilot/validator');
	const { copilotDefaultElement } = require('im/messenger/model/dialogues/copilot/default-element');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--dialogues-copilot');

	/** @type {CopilotModel} */
	const copilotModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/**
			 * @function dialoguesModel/copilotModel/getByDialogId
			 * @return {?CopilotModelState}
			 */
			getByDialogId: (state) => (dialogId) => {
				if (!Type.isString(dialogId))
				{
					return null;
				}

				return Object.values(state.collection).find((item) => {
					return item.dialogId === dialogId;
				});
			},

			/**
			 * @function dialoguesModel/copilotModel/getMainRoleByDialogId
			 * @return {?object}
			 */
			getMainRoleByDialogId: (state) => (dialogId) => {
				if (!Type.isString(dialogId))
				{
					return null;
				}

				const copilotDataState = Object.values(state.collection).find((item) => {
					return item.dialogId === dialogId;
				});

				if (!copilotDataState || !copilotDataState.roles)
				{
					return null;
				}

				return copilotDataState.roles[copilotDataState.chats[0]?.role];
			},

			/**
			 * @function dialoguesModel/copilotModel/getMainRoleByDialogId
			 * @return {?object}
			 */
			getDefaultRoleByDialogId: (state) => (dialogId) => {
				if (!Type.isString(dialogId))
				{
					return null;
				}

				const copilotDataState = Object.values(state.collection).find((item) => {
					return item.dialogId === dialogId;
				});

				if (!copilotDataState || !copilotDataState.roles)
				{
					return null;
				}

				const roles = clone(copilotDataState.roles);

				return Object.values(roles).find((roleData) => {
					return roleData.default;
				});
			},

			/**
			 * @function dialoguesModel/copilotModel/getRoleByMessageId
			 * @return {?object}
			 */
			getRoleByMessageId: (state) => (dialogId, messageId) => {
				if (!Type.isString(dialogId) || Type.isUndefined(messageId))
				{
					return null;
				}

				const copilotDataState = Object.values(state.collection).find((item) => {
					return item.dialogId === dialogId;
				});

				if (Type.isUndefined(copilotDataState) || Type.isUndefined(copilotDataState.roles))
				{
					return null;
				}

				const messages = clone(copilotDataState.messages);
				const messageData = messages.find((message) => {
					return message?.id === messageId;
				});

				let role = {};
				if (messageData)
				{
					role = copilotDataState.roles[messageData.role];
				}
				else
				{
					role = copilotDataState.roles[copilotDataState.chats[0]?.role];
				}

				if (!role)
				{
					const roles = clone(copilotDataState.roles);
					role = Object.values(roles).find((roleData) => {
						return roleData.default;
					});
				}

				return role;
			},
		},
		actions: {
			/** @function dialoguesModel/copilotModel/setFromSync */
			setFromSync: (store, copilotList) => {
				return store.dispatch('setCollection', { itemList: copilotList, actionName: 'setFromSync' });
			},

			/** @function dialoguesModel/copilotModel/setCollection */
			setCollection: (store, payload) => {
				const { itemList, actionName = 'setCollection' } = ModelUtils.normalizeItemListPayload(payload);

				const updateItems = [];
				const addItems = [];
				itemList.forEach((element) => {
					/** @type {CopilotModelState} */
					const existingItem = store.state.collection[element.dialogId];
					const validElement = validate(element);
					if (existingItem)
					{
						const validMergeElement = prepareMergeProperty(validElement, existingItem);
						updateItems.push(
							{
								dialogId: validElement.dialogId,
								fields: validMergeElement,
							},
						);
					}
					else
					{
						addItems.push(
							{
								dialogId: validElement.dialogId,
								fields: { ...copilotDefaultElement, ...validElement },
							},
						);
					}
				});

				if (updateItems.length > 0)
				{
					store.commit('updateCollection', {
						actionName,
						data: { updateItems },
					});
				}

				if (addItems.length > 0)
				{
					store.commit('addCollection', {
						actionName,
						data: { addItems },
					});
				}
			},

			/** @function dialoguesModel/copilotModel/update */
			update: (store, payload) => {
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				const data = {
					dialogId: payload.dialogId,
					fields: payload.fields,
				};

				store.commit('update', {
					actionName: 'update',
					data,
				});

				return true;
			},

			/** @function dialoguesModel/copilotModel/updateRole */
			updateRole: (store, payload) => {
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				const newRoleData = { ...existingItem.roles, ...payload.fields.roles };
				const data = {
					dialogId: payload.dialogId,
					fields: { chats: payload.fields.chats, roles: newRoleData },
				};

				store.commit('update', {
					actionName: 'updateRole',
					data,
				});

				return true;
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<CopilotUpdateData, CopilotUpdateActions>} payload
			 */
			update: (state, payload) => {
				logger.log('copilotModel: update mutation', payload);
				const {
					dialogId,
					fields,
				} = payload.data;

				state.collection[dialogId] = { ...state.collection[dialogId], ...fields };
			},

			/**
			 * @param state
			 * @param {MutationPayload<CopilotUpdateCollectionData, CopilotUpdateActions>} payload
			 */
			updateCollection: (state, payload) => {
				logger.log('copilotModel: updateCollection mutation', payload);

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
			 * @param {MutationPayload<CopilotAddData, CopilotAddActions>} payload
			 */
			add: (state, payload) => {
				logger.log('copilotModel: add mutation', payload);

				const {
					dialogId,
					fields,
				} = payload.data;

				state.collection[dialogId] = fields;
			},

			/**
			 * @param state
			 * @param {MutationPayload<CopilotAddCollectionData, CopilotAddActions>} payload
			 */
			addCollection: (state, payload) => {
				logger.log('copilotModel: addCollection mutation', payload);

				payload.data.addItems.forEach((item) => {
					const {
						dialogId,
						fields,
					} = item;

					state.collection[dialogId] = fields;
				});
			},
		},
	};

	module.exports = { copilotModel };
});
