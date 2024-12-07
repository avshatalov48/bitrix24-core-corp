/* eslint-disable no-param-reassign */
/**
 * @module im/messenger/model/queue
 */
jn.define('im/messenger/model/queue', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { Uuid } = require('utils/uuid');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--queue');

	const queueDefaultElement = Object.freeze({
		id: '',
		requestName: '',
		requestData: {},
		priority: 0,
		messageId: 0,
	});

	const queueModel = {
		namespaced: true,
		state: () => ({
			collection: [],
		}),
		getters: {
			getQueue: (state) => {
				return state.collection;
			},
		},
		actions: {
			/** @function queueModel/add */
			add: (store, payload) => {
				let requests = payload;
				if (!Array.isArray(requests) && Type.isPlainObject(requests))
				{
					requests = [requests];
				}

				const validateRequests = requests.filter((req) => isValid(req));
				let oldSomeRequestIds = [];
				validateRequests.forEach((request) => {
					if (Type.isUndefined(request.id))
					{
						request.id = Uuid.getV4();
					}

					if (!Type.isUndefined(request.messageId))
					{
						oldSomeRequestIds = [...oldSomeRequestIds, ...findDoubleRequestByMessageId(store, request)];
					}
				});

				if (oldSomeRequestIds.length > 0)
				{
					store.commit('deleteById', {
						actionName: 'deleteById',
						data: {
							requestsIds: oldSomeRequestIds,
						},
					});
				}

				store.commit('add', {
					actionName: 'add',
					data: {
						requests: validateRequests,
					},
				});
			},

			/** @function queueModel/delete */
			delete: (store, payload) => {
				let requests = payload;
				if (!Array.isArray(requests) && Type.isPlainObject(requests))
				{
					requests = [requests];
				}

				let oldSomeRequestIds = [];
				requests.forEach((request) => {
					if (!Type.isUndefined(request.messageId))
					{
						oldSomeRequestIds = [...oldSomeRequestIds, ...findDoubleRequestByMessageId(store, request)];
					}
				});

				if (oldSomeRequestIds.length > 0)
				{
					store.commit('deleteById', {
						actionName: 'deleteById',
						data: {
							requestsIds: oldSomeRequestIds,
						},
					});
				}
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<QueueAddData, QueueAddActions>} payload
			 */
			add: (state, payload) => {
				const {
					requests,
				} = payload.data;
				logger.log('queueModel: add mutation', payload);

				state.collection = [...state.collection, ...requests];
			},

			/**
			 * @param state
			 * @param {MutationPayload<QueueDeleteByIdData, QueueDeleteByIdActions>} payload
			 */
			deleteById: (state, payload) => {
				const {
					requestsIds,
				} = payload.data;
				logger.log('queueModel: deleteById mutation', payload);

				state.collection = state.collection.filter((r) => !requestsIds.includes(r.id));
			},
		},
	};

	function isValid(req)
	{
		return !Type.isUndefined(req.requestName) && !Type.isUndefined(req.requestData) && !Type.isUndefined(req.priority);
	}

	function findDoubleRequestByMessageId(store, request)
	{
		const ids = [];
		const collectionClone = clone(store.state.collection);
		collectionClone.forEach((requestClone) => {
			if ((requestClone.requestName === request.requestName) && (requestClone.messageId === request.messageId))
			{
				ids.push(requestClone.id);
			}
		});

		return ids;
	}

	module.exports = { queueModel, queueDefaultElement };
});
