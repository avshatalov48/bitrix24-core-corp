/**
 * @module im/messenger/controller/dialog/lib/app-rating-client
 */
jn.define('im/messenger/controller/dialog/lib/app-rating-client', (require, exports, module) => {
	const { AppRatingClientBase } = require('app-rating-client');
	const AppRatingUserEvent = {
		COPILOT_REQUESTS: 'copilot_query',
		MESSAGES_SENT: 'messages_sent',
	};

	/**
	 * @class AppRatingClient
	 */
	class AppRatingClient extends AppRatingClientBase
	{
		/**
		 * @public
		 * @returns {object}
		 */
		getLimits()
		{
			return {
				[AppRatingUserEvent.MESSAGES_SENT]: 150,
				[AppRatingUserEvent.COPILOT_REQUESTS]: 5,
			};
		}

		/**
		 * @public
		 * @param {boolean} isChatWithCopilot
		 * @returns {void}
		 */
		tryOpenAppRatingAfterChatClose(isChatWithCopilot)
		{
			const event = isChatWithCopilot
				? AppRatingUserEvent.COPILOT_REQUESTS
				: AppRatingUserEvent.MESSAGES_SENT;
			this.tryOpenAppRating({
				event,
			});
		}

		/**
		 * @public
		 * @param {boolean} isChatWithCopilot
		 * @returns {void}
		 */
		increaseSendMessageCounter(isChatWithCopilot)
		{
			void this.increaseCounter(
				isChatWithCopilot
					? AppRatingUserEvent.COPILOT_REQUESTS
					: AppRatingUserEvent.MESSAGES_SENT,
			);
		}
	}

	module.exports = {
		AppRatingClient: new AppRatingClient(),
	};
});
