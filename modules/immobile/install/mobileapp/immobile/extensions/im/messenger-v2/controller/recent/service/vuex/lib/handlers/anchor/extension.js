/**
 * @module im/messenger-v2/controller/recent/service/vuex/lib/handlers/anchor
 */
jn.define('im/messenger-v2/controller/recent/service/vuex/lib/handlers/anchor', (require, exports, module) => {
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	class AnchorMutationHandler
	{
		/**
		 * @param {RecentLocator} recentLocator
		 * @param {Logger} logger
		 */
		constructor(recentLocator, logger)
		{
			/**
			 * @private
			 * @type {RecentLocator}
			 */
			this.recentLocator = recentLocator;
			/**
			 * @private
			 * @type {Logger}
			 */
			this.logger = logger;
			/**
			 * @private
			 * @type {MessengerCoreStore}
			 */
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @param {MutationPayload<AnchorAddData>} payload
		 */
		addHandler = ({ payload }) => {
			this.logger.log('anchorAddHandler', payload);
			const { anchor } = payload.data;

			this.#updateRecentItem(anchor);
		};

		/**
		 * @param {MutationPayload<AnchorDeleteData>} payload
		 */
		deleteHandler = ({ payload }) => {
			this.logger.log('anchorDeleteHandler', payload);
			const { anchor } = payload.data;

			this.#updateRecentItem(anchor);
		};

		/**
		 * @param {MutationPayload<AnchorDeleteManyData>} payload
		 */
		deleteManyHandler = ({ payload }) => {
			this.logger.log('anchorDeleteManyHandler', payload);
			const { anchorList } = payload.data;

			if (!Type.isArrayFilled(anchorList))
			{
				return;
			}

			for (const anchor of anchorList)
			{
				this.#updateRecentItem(anchor);
			}
		};

		/**
		 * @param {AnchorModelState} anchor
		 */
		#updateRecentItem(anchor)
		{
			if (!anchor.chatId)
			{
				return;
			}

			const recentItem = this.store.getters['recentModel/getByChatId'](anchor.chatId);

			if (!this.#hasItemInCurrentTab(recentItem?.id))
			{
				return;
			}

			this.recentLocator.get('render').upsertItems([recentItem]);
		}

		/**
		 * @param {?string} dialogId
		 * @return {boolean}
		 */
		#hasItemInCurrentTab(dialogId)
		{
			return this.store.getters['recentModel/hasItemInTab'](dialogId, this.recentLocator.get('id'));
		}
	}

	module.exports = { AnchorMutationHandler };
});
