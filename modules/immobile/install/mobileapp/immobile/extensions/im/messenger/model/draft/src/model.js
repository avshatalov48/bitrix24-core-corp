// eslint-disable-next-line no-param-reassign

/**
 * @module im/messenger/model/draft/src/model
 */
jn.define('im/messenger/model/draft/src/model', (require, exports, module) => {
	const { Type } = require('type');
	const { DraftType } = require('im/messenger/const');
	const { draftDefaultElement } = require('im/messenger/model/draft/src/default-element');
	const { validate } = require('im/messenger/model/draft/src/validator');

	/** @type {DraftModel} */
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

			setFromLocalDatabase: (store, payload) => {
				payload.forEach((item) => {
					store.dispatch('set', item);
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

				const validPayload = {
					...validate(payload),
					lastActivityDate: new Date(),
				};
				const dialogId = validPayload.dialogId;
				const existingItem = store.state.collection[dialogId];
				const mutationName = existingItem ? 'update' : 'add';
				const mutationFields = existingItem ? validPayload : { ...draftDefaultElement, ...validPayload };

				if (DraftType.reply !== mutationFields.type && !mutationFields.text.trim())
				{
					store.commit('delete', {
						actionName: 'set',
						data: {
							dialogId,
						},
					});

					return;
				}

				store.commit(mutationName, {
					actionName: 'set',
					data: {
						dialogId,
						fields: mutationFields,
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
				state.collection[dialogId] = {
					...state.collection[dialogId],
					...fields,
				};
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
			},
		},
	};

	module.exports = { draftModel, draftDefaultElement };
});
