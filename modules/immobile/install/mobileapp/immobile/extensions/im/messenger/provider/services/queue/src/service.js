/**
 * @module im/messenger/provider/services/queue/service
 */
jn.define('im/messenger/provider/services/queue/service', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Type } = require('type');
	const { ErrorCode } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class QueueService
	 */
	class QueueService
	{
		static #instance = null;
		/**
		 * @return {QueueService}
		 */
		static getInstance()
		{
			if (!this.#instance)
			{
				this.#instance = new this();
			}

			return this.#instance;
		}

		constructor()
		{
			/** @private */
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @desc save request in store vuex as array element
		 * @param {string} requestName
		 * @param {object} requestData
		 * @param {number} priority
		 * @param {Array<MessageId>} messageIdList
		 * @return {Promise}
		 */
		putRequest(requestName, requestData, priority, messageIdList)
		{
			return this.store.dispatch(
				'queueModel/add',
				{ requestName, requestData, priority, messageIdList },
			);
		}

		/**
		 * @desc clear request from store by result call batch
		 * @param {object} batchResponse
		 * @param {boolean} [withTemporaryMessage=false]
		 * @return {Promise}
		 */
		clearRequestByBatchResult(batchResponse, withTemporaryMessage = false)
		{
			const queue = this.store.getters['queueModel/getQueue'];
			if (!Type.isArrayFilled(queue))
			{
				return Promise.resolve(true);
			}

			const removeRequest = [];
			const removeTemporaryMessageIds = [];
			const keysBatch = Object.keys(batchResponse);

			queue.forEach((req) => {
				if (!Type.isArrayFilled(req.messageIdList))
				{
					return;
				}

				const requestResponseKey = keysBatch.find(
					(key) => key.includes(req.requestName) && key.includes(req.messageIdList[0]),
				);

				const requestResponse = batchResponse[requestResponseKey];
				if (requestResponse && (requestResponse.status !== ErrorCode.NO_INTERNET_CONNECTION))
				{
					removeRequest.push(req);
				}

				if (withTemporaryMessage)
				{
					removeTemporaryMessageIds.push(...req.messageIdList);
				}
			});

			if (removeTemporaryMessageIds.length > 0)
			{
				this.store.dispatch('messagesModel/deleteTemporaryMessages', { ids: removeTemporaryMessageIds })
					.catch((errors) => {
						Logger.error('QueueService.clearRequestByBatchResult deleteTemporaryMessages error: ', errors);
					});
			}

			return this.store.dispatch('queueModel/delete', removeRequest);
		}
	}

	module.exports = {
		QueueService,
	};
});
