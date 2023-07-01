/**
 * @module crm/timeline/controllers/helpdesk
 */
jn.define('crm/timeline/controllers/helpdesk', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');

	const SupportedActions = {
		OPEN: 'Helpdesk:Open',
	};

	/**
	 * @class TimelineHelpdeskController
	 */
	class TimelineHelpdeskController extends TimelineBaseController
	{
		static getSupportedActions()
		{
			return Object.values(SupportedActions);
		}

		/**
		 * @public
		 * @param {string} action
		 * @param {object} actionParams
		 */
		onItemAction({ action, actionParams = {} })
		{
			if (action === SupportedActions.OPEN)
			{
				const articleCode = actionParams.mobileArticleCode || actionParams.articleCode;
				if (articleCode)
				{
					helpdesk.openHelpArticle(String(articleCode));
				}
			}
		}
	}

	module.exports = { TimelineHelpdeskController };
});
