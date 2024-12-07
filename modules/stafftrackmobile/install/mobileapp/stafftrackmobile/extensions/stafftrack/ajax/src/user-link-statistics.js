/**
 * @module stafftrack/ajax/user-link-statistics
 */
jn.define('stafftrack/ajax/user-link-statistics', (require, exports, module) => {
	const { BaseAjax } = require('stafftrack/ajax/base');

	const UserLinkStatisticsActions = {
		GET: 'get',
		SEND: 'send',
	};

	class UserLinkStatisticsAjax extends BaseAjax
	{
		/**
		 * @returns {string}
		 */
		getEndpoint()
		{
			return 'stafftrack.UserLinkStatistics';
		}

		/**
		 * @param userId {number}
		 * @param hash {string}
		 * @returns {Promise<Object, void>}
		 */
		get(userId, hash)
		{
			return this.fetch(UserLinkStatisticsActions.GET, { userId, hash });
		}

		/**
		 *
		 * @param dialogId
		 * @param link
		 * @returns {Promise<Object, void>}
		 */
		send(dialogId, link)
		{
			return this.fetch(UserLinkStatisticsActions.SEND, { dialogId, link });
		}
	}

	module.exports = { UserLinkStatisticsAjax: new UserLinkStatisticsAjax() };
});
