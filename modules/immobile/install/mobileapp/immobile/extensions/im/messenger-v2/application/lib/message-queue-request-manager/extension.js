/**
 * @module im/messenger-v2/application/lib/message-queue-request-manager
 */
jn.define('im/messenger-v2/application/lib/message-queue-request-manager', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { RestManager } = require('im/messenger/lib/rest-manager');

	/**
	 * @class MessageQueueRequestManager
	 */
	class MessageQueueRequestManager
	{
		static #instance;

		/**
		 * @return {MessageQueueRequestManager}
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
			this.restManager = new RestManager();
		}

		/**
		 * @return {MessengerCoreStore}
		 */
		get #store()
		{
			return serviceLocator.get('core').getStore();
		}

		/**
		 * @return {QueueService}
		 */
		get #queueService()
		{
			return serviceLocator.get('queue-service');
		}

		/**
		 * @return {Promise}
		 */
		callBatch()
		{
			this.#buildQueueRequests();

			return this.restManager.callBatch()
				.then((response) => this.#clearRequestQueue(response, true))
				.catch((error) => this.#clearRequestQueue(error, true));
		}

		#buildQueueRequests()
		{
			const requests = this.#store.getters['queueModel/getQueue'];
			if (requests && requests.length > 0)
			{
				const sortedRequests = requests.sort((a, b) => a.priority - b.priority);

				sortedRequests.forEach((req) => {
					this.restManager.once(req.requestName, req.requestData);
				});
			}
		}

		/**
		 * @param {Object} response
		 * @param {boolean} withTemporaryMessage
		 * @return {Promise}
		 */
		#clearRequestQueue(response, withTemporaryMessage = false)
		{
			return this.#queueService.clearRequestByBatchResult(response, withTemporaryMessage);
		}
	}

	module.exports = { MessageQueueRequestManager };
});
