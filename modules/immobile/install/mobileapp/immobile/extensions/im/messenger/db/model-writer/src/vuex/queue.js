/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/queue
 */
jn.define('im/messenger/db/model-writer/vuex/queue', (require, exports, module) => {
	const { Type } = require('type');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');
	const { Logger } = require('im/messenger/lib/logger');

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
		 * @param {MutationPayload<QueueAddData, QueueAddActions>} payload
		 */
		addRouter({ payload })
		{
			if (this.checkIsValidMutation(payload) === false)
			{
				return;
			}

			const requests = payload?.data?.requests ?? [];
			if (!Type.isArrayFilled(requests))
			{
				return;
			}

			this.repository.queue.saveFromModel(requests)
				.catch((error) => Logger.error(`${this.constructor.name}.saveFromModel.catch:`, error));
		}

		/**
		 * @param {MutationPayload<QueueDeleteByIdData, QueueDeleteByIdActions>} payload
		 * @return {Promise}
		 */
		deleteRouter({ payload })
		{
			if (this.checkIsValidMutation(payload) === false)
			{
				return;
			}
			const requestsIds = payload.data.requestsIds ?? [];
			if (requestsIds.length > 0)
			{
				this.repository.queue.deleteByIdList(requestsIds)
					.catch((error) => Logger.error(`${this.constructor.name}.saveFromModel.catch:`, error));
			}
		}

		/**
		 * @protected
		 * @param payload
		 * @return {boolean}
		 */
		checkIsValidMutation(payload)
		{
			const actionName = payload?.actionName;
			if (actionName)
			{
				return true;
			}

			Logger.error('Writer: invalid mutation skipped: ', payload);

			return false;
		}
	}

	module.exports = {
		QueueWriter,
	};
});
