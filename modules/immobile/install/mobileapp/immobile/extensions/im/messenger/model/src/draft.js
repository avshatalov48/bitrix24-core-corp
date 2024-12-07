// eslint-disable-next-line no-param-reassign
/**
 * @module im/messenger/model/draft
 */
jn.define('im/messenger/model/draft', (require, exports, module) => {
	const { Type } = require('type');
	const { DraftCache } = require('im/messenger/cache');
	const { DraftType, MessageType } = require('im/messenger/const');

	/** @type {DraftModelState} */
	const draftDefaultElement = Object.freeze({
		dialogId: 0,
		messageId: 0,
		messageType: MessageType.text,
		type: DraftType.text,
		text: '',
		message: [],
		userName: '',
		image: null,
		video: null,
	});

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
				store.commit('setState', {
					actionName: 'setState',
					data: {
						collection: payload.collection,
					},
				});
			},
			/**
			 * @function draftModel/set
			 * @param store
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
						actionName: 'set',
						data: {
							dialogId: validPayload.dialogId,
							fields: validPayload,
						},
					});

					return;
				}

				store.commit('add', {
					actionName: 'set',
					data: {
						dialogId: validPayload.dialogId,
						fields: { ...draftDefaultElement, ...validPayload },
					},
				});
			},

			/**
			 * @function draftModel/delete
			 * @param store
			 * @param {{dialogId: string|number}} payload
			 */
			delete: (store, payload) => {
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				store.commit('delete', {
					actionName: 'delete',
					data: {
						dialogId: payload.dialogId,
					},
				});

				return true;
			},
		},

		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<DraftSetStateData, DraftSetStateActions>} payload
			 */
			setState: (state, payload) => {
				const {
					collection,
				} = payload.data;

				// eslint-disable-next-line no-param-reassign
				state.collection = collection;
			},

			/**
			 * @param state
			 * @param {MutationPayload<DraftAddData, DraftAddActions>} payload
			 */
			add: (state, payload) => {
				const {
					dialogId,
					fields,
				} = payload.data;

				// eslint-disable-next-line no-param-reassign
				state.collection[dialogId] = fields;

				DraftCache.save(state);
			},

			/**
			 * @param state
			 * @param {MutationPayload<DraftUpdateData, DraftUpdateActions>} payload
			 */
			update: (state, payload) => {
				const {
					dialogId,
					fields,
				} = payload.data;

				// eslint-disable-next-line no-param-reassign
				state.collection[dialogId] = { ...state.collection[dialogId], ...fields };

				DraftCache.save(state);
			},

			/**
			 * @param state
			 * @param {MutationPayload<DraftDeleteData, DraftDeleteActions>} payload
			 */
			delete: (state, payload) => {
				const {
					dialogId,
				} = payload.data;

				// eslint-disable-next-line no-param-reassign
				delete state.collection[dialogId];

				DraftCache.save(state);
			},
		},
	};

	/**
	 *
	 * @param {DraftModelState} fields
	 * @return {Partial<DraftModelState>}
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

		if (Type.isPlainObject(fields.video))
		{
			result.video = fields.video;
		}

		if (Type.isPlainObject(fields.image))
		{
			result.image = fields.image;
		}

		return result;
	}

	module.exports = { draftModel, draftDefaultElement };
});
