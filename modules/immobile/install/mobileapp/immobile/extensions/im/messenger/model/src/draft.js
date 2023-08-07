// eslint-disable-next-line no-param-reassign
/**
 * @module im/messenger/model/draft
 */
jn.define('im/messenger/model/draft', (require, exports, module) => {
	const { Type } = require('type');
	const { DraftCache } = require('im/messenger/cache');
	const { DraftType, MessageType } = require('im/messenger/const');

	/** @type {DraftModelState} */
	const draftState = {
		dialogId: 0,
		messageId: 0,
		messageType: MessageType.text,
		type: DraftType.text,
		text: '',
		message: [],
		userName: '',
	};

	const draftModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),

		getters: {
			/**
			 * @function draftModel/getById
			 * @return {DraftModelState}
			 */
			getById: (state) => (id) => {
				return state.collection[id];
			},
		},

		actions: {
			/** @function draftModel/setState */
			setState: (store, payload) => {
				store.commit('setState', payload);
			},
			/**
			 * @function draftModel/set
			 * @param {DraftModelState} payload
			 */
			set: (store, payload) => {
				if (!Type.isPlainObject(payload))
				{
					return;
				}

				const validPayload = validate(payload);

				const existingItem = store.state.collection[validPayload.dialogId];

				if (existingItem)
				{
					store.commit('update', {
						dialogId: validPayload.dialogId,
						fields: validPayload,
					});

					return;
				}

				store.commit('add', {
					dialogId: validPayload.dialogId,
					fields: { ...draftState, ...validPayload },
				});
			},

			/**
			 * @function draftModel/delete
			 * @param {{dialogId: string|number}} payload
			 */
			delete: (store, payload) => {
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				store.commit('delete', payload.dialogId);

				return true;
			},
		},

		mutations: {
			setState: (state, payload) => {
				// eslint-disable-next-line no-param-reassign
				state.collection = payload.collection;
			},
			add: (state, payload) => {
				// eslint-disable-next-line no-param-reassign
				state.collection[payload.dialogId] = payload.fields;

				DraftCache.save(state);
			},
			update: (state, payload) => {
				// eslint-disable-next-line no-param-reassign
				state.collection[payload.dialogId] = { ...state.collection[payload.dialogId], ...payload.fields };

				DraftCache.save(state);
			},
			delete: (state, payload) => {
				// eslint-disable-next-line no-param-reassign
				delete state.collection[payload.dialogId];

				DraftCache.save(state);
			},
		},
	};

	/**
	 *
	 * @param {DraftModelState} fields
	 */
	function validate(fields)
	{
		/** @type {DraftModelState} */
		const result = {};

		if (Type.isStringFilled(fields.dialogId) || Type.isNumber(fields.dialogId))
		{
			result.dialogId = fields.dialogId;
		}

		if (Type.isStringFilled(fields.messageId) || Type.isNumber(fields.messageId))
		{
			result.messageId = fields.messageId;
		}

		if (Type.isStringFilled(fields.type))
		{
			result.type = fields.type;
		}

		if (Type.isStringFilled(fields.messageType))
		{
			result.messageType = fields.messageType;
		}

		if (Type.isString(fields.text))
		{
			result.text = fields.text;
		}

		if (Type.isArray(fields.message))
		{
			result.message = fields.message;
		}

		if (Type.isStringFilled(fields.userName))
		{
			result.userName = fields.userName;
		}

		return result;
	}

	module.exports = { draftModel };
});
