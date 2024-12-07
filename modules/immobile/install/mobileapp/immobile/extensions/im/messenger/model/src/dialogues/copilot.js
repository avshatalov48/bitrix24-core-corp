/**
 * @module im/messenger/model/dialogues/copilot
 */
jn.define('im/messenger/model/dialogues/copilot', (require, exports, module) => {
	const { Type } = require('type');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { clone } = require('utils/object');
	const logger = LoggerManager.getInstance().getLogger('model--dialogues-copilot');

	/** @type {CopilotModelState} */
	const copilotDefaultElement = Object.freeze({
		dialogId: 'chat0',
		roles: {},
		aiProvider: '',
		chats: [],
		messages: [],
	});

	/**
	 * @type {CopilotModel}
	 */
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
			/** @function dialoguesModel/copilotModel/setCollection */
			setCollection: (store, payload) => {
				if (!Array.isArray(payload) && Type.isPlainObject(payload))
				{
					// eslint-disable-next-line no-param-reassign
					payload = [payload];
				}

				const updateItems = [];
				const addItems = [];
				payload.forEach((element) => {
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
						actionName: 'setCollection',
						data: { updateItems },
					});
				}

				if (addItems.length > 0)
				{
					store.commit('addCollection', {
						actionName: 'setCollection',
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

	function validate(fields)
	{
		const result = {};

		if (!Type.isUndefined(fields.chats) && !Type.isNull(fields.chats))
		{
			result.chats = fields.chats;
		}

		if (!Type.isUndefined(fields.roles) && !Type.isNull(fields.roles))
		{
			result.roles = fields.roles;
		}

		if (!Type.isUndefined(fields.messages) && !Type.isNull(fields.messages) && fields.messages.length > 0)
		{
			result.messages = fields.messages;
		}

		result.dialogId = fields.dialogId || '';
		result.aiProvider = fields.aiProvider || '';

		return result;
	}

	function prepareMergeProperty(newElem, existingElem)
	{
		const result = {
			dialogId: newElem.dialogId || existingElem.dialogId,
			aiProvider: newElem.aiProvider || existingElem.aiProvider,
		};

		if (!Type.isUndefined(newElem.chats) && !Type.isNull(newElem.chats))
		{
			result.chats = newElem.chats;
		}
		else
		{
			result.chats = existingElem.chats;
		}

		if (!Type.isUndefined(newElem.roles) && !Type.isNull(newElem.roles))
		{
			result.roles = { ...existingElem.roles, ...newElem.roles };
		}

		if (!Type.isUndefined(newElem.messages) && !Type.isNull(newElem.messages) && newElem.messages.length > 0)
		{
			result.messages = [...new Set([...existingElem.messages, ...newElem.messages])];
		}

		return result;
	}

	module.exports = { copilotModel, copilotDefaultElement };
});
