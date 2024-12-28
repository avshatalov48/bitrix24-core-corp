/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/dialogues/collab/collab
 */
jn.define('im/messenger/model/dialogues/collab/collab', (require, exports, module) => {
	const { Type } = require('type');

	const { validateEntities, validateSetCounterPayload, validateSetGuestCountPayload } = require('im/messenger/model/dialogues/collab/validators');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--dialogues-collab');

	const collabDefaultElement = Object.freeze({
		guestCount: 0,
		collabId: 0,
		entities: {},
	});

	const collabModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/** @function dialoguesModel/collabModel/getByDialogId */
			getByDialogId: (state) => (dialogId) => {
				return state.collection[dialogId];
			},

			/** @function dialoguesModel/collabModel/getCollabIdByDialogId */
			getCollabIdByDialogId: (state, getters, rootState, rootGetters) => (dialogId) => {
				return rootGetters['dialoguesModel/collabModel/getByDialogId'](dialogId)?.collabId ?? null;
			},

			/** @function dialoguesModel/collabModel/getGuestCountByDialogId */
			getGuestCountByDialogId: (state, getters, rootState, rootGetters) => (dialogId) => {
				return rootGetters['dialoguesModel/collabModel/getByDialogId'](dialogId)?.guestCount ?? null;
			},

			/** @function dialoguesModel/collabModel/getEntity */
			getEntity: (state) => (dialogId, entity) => {
				return state.collection[dialogId]?.entities[entity] ?? {};
			},
		},
		actions: {
			/** @function dialoguesModel/collabModel/set */
			set: (store, payload) => {
				const {
					dialogId,
					guestCount,
					collabId,
					entities,
				} = payload;

				const isValidCollabId = Type.isNumber(collabId);
				const isValidDialogId = Type.isString(dialogId);

				if (!isValidCollabId || !isValidDialogId)
				{
					return;
				}

				store.commit('set', {
					actionName: 'set',
					data: {
						dialogId,
						collabId,
						entities: validateEntities(entities),
					},
				});

				store.dispatch('setGuestCount', {
					dialogId,
					guestCount,
				});
			},

			/**
			 * @function dialoguesModel/collabModel/setEntityCounter
			 */
			setEntityCounter: (store, payload) => {
				const {
					dialogId,
					entity,
					counter,
				} = payload;

				const hasCollection = store.state.collection[dialogId];
				if (!validateSetCounterPayload(payload) || !hasCollection)
				{
					return;
				}

				store.commit('setEntityCounter', {
					actionName: 'setEntityCounter',
					data: {
						dialogId,
						entity,
						counter,
					},
				});
			},

			/**
			 * @function dialoguesModel/collabModel/setGuestCount
			 */
			setGuestCount: (store, payload) => {
				const {
					dialogId,
					guestCount,
				} = payload;

				const hasCollection = store.state.collection[dialogId];
				if (!validateSetGuestCountPayload(payload) || !hasCollection)
				{
					return;
				}

				store.commit('setGuestCount', {
					actionName: 'setGuestCount',
					data: {
						dialogId,
						guestCount,
					},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {
			 * MutationPayload<CollabSetData, CollabSetActions>
			 * } payload
			 */
			set: (state, payload) => {
				logger.log('CollabModel: set mutation', payload);
				const {
					dialogId,
					collabId,
					entities,
				} = payload.data;

				const currentElement = state.collection[dialogId] || { ...collabDefaultElement };
				state.collection[dialogId] = {
					...currentElement,
					collabId,
					entities: {
						...currentElement.entities,
						...entities,
					},
				};
			},
			/**
			 * @param state
			 * @param {MutationPayload<CollabSetEntityCounterData, CollabSetEntityCounterActions>} payload
			 */
			setEntityCounter: (state, payload) => {
				logger.log('CollabModel: setEntities mutation', payload);

				const { dialogId, entity, counter } = payload.data;

				state.collection[dialogId].entities[entity].counter = counter;
			},
			/**
			 * @param state
			 * @param {MutationPayload<CollabSetGuestCountData, CollabSetGuestCountActions>} payload
			 */
			setGuestCount: (state, payload) => {
				logger.log('CollabModel: setGuestCount mutation', payload);
				const {
					dialogId,
					guestCount,
				} = payload.data;

				state.collection[dialogId].guestCount = guestCount;
			},
		},
	};

	module.exports = { collabModel };
});
