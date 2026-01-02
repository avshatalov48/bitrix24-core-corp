/**
 * @module im/messenger/provider/pull/anchor
 */
jn.define('im/messenger/provider/pull/anchor', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { getLogger } = require('im/messenger/lib/logger');

	/**
	 * @class AnchorPullHandler
	 */
	class AnchorPullHandler extends BasePullHandler
	{
		constructor()
		{
			const logger = getLogger('pull-handler--anchor');
			super({ logger });
		}

		handleAddAnchor(params, extra, command)
		{
			this.logger.info(`${this.getClassName()}.handleAddAnchor: `, params);
			this.store.dispatch('anchorModel/add', params)
				.catch((err) => this.logger.error(`${this.getClassName()}.handleAddAnchor.catch err:`, err))
			;
		}

		handleDeleteAnchor(params, extra, command)
		{
			this.logger.info(`${this.getClassName()}.handleDeleteAnchor: `, params);
			this.store.dispatch('anchorModel/delete', params)
				.catch((err) => this.logger.error(`${this.getClassName()}.handleDeleteAnchor.catch err:`, err))
			;
		}

		handleDeleteChatAnchors(params, extra, command)
		{
			this.logger.info(`${this.getClassName()}.handleDeleteChatAnchors: `, params);
			this.store.dispatch('anchorModel/deleteByChatId', params)
				.catch((err) => this.logger.error(`${this.getClassName()}.handleDeleteChatAnchor.catch err:`, err))
			;
		}
	}

	module.exports = {
		AnchorPullHandler,
	};
});
