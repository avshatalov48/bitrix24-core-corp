/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/queue
 */
jn.define('im/messenger/db/model-writer/vuex/queue', (require, exports, module) => {
	const { Type } = require('type');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class QueueWriter extends Writer
	{
		subscribeEvents()
		{
			this.storeManager
				.on('queueModel/add', this.addRouter)
				.on('queueModel/deleteById', this.deleteRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('queueModel/add', this.addRouter)
				.off('queueModel/deleteById', this.deleteRouter)
			;
		}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		addRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const data = mutation?.payload?.data || {};
			if (!Type.isArrayFilled(data.requests))
			{
				return;
			}

			const requests = [];
			data.requests.forEach((request) => {
				if (request.requestName)
				{
					requests.push(request);
				}
			});

			if (!Type.isArrayFilled(requests))
			{
				return;
			}

			this.repository.queue.saveFromModel(requests);
		}

		/**
		 * @param {MutationPayload} mutation.payload
		 * @return {Promise}
		 */
		deleteRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return Promise.resolve(false);
			}
			const data = mutation?.payload.data || {};
			let ids;
			if (mutation?.payload?.actionName === 'deleteById')
			{
				ids = data.requestsIds;
			}

			if (ids.length > 0)
			{
				return this.repository.queue.deleteByIdList(ids);
			}

			return Promise.resolve(false);
		}
	}

	module.exports = {
		QueueWriter,
	};
});
