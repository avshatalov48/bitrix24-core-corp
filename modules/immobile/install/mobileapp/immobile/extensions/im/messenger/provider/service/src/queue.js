/**
 * @module im/messenger/provider/service/queue
 */
jn.define('im/messenger/provider/service/queue', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Type } = require('type');
	const { ErrorCode } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class QueueService
	 */
	class QueueService
	{
		/*
		* @return {SendingService}
		*/
		static getInstance()
		{
			if (!this.instance)
			{
				this.instance = new this();
			}

			return this.instance;
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
		 * @param {string|number} messageId
		 * @return {Promise}
		 */
		putRequest(requestName, requestData, priority, messageId)
		{
			return this.store.dispatch(
				'queueModel/add',
				{ requestName, requestData, priority, messageId },
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
			const requests = this.store.getters['queueModel/getQueue'];
			if (requests.length === 0)
			{
				return Promise.resolve(true);
			}

			const removeRequest = [];
			const removeTemporaryMessageIds = [];
			const keysBatch = Object.keys(batchResponse);
			requests.forEach((req) => {
				if (req.messageId !== 0 && !Type.isUndefined(req.messageId))
				{
					const requestResponseKey = keysBatch.find(
						(key) => key.includes(req.requestName) && key.includes(req.messageId),
					);
					const requestResponse = batchResponse[requestResponseKey];
					if (requestResponse && (requestResponse.status !== ErrorCode.NO_INTERNET_CONNECTION))
					{
						removeRequest.push(req);
					}

					if (withTemporaryMessage)
					{
						removeTemporaryMessageIds.push(req.messageId);
					}
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
