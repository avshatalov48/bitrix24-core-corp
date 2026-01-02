/**
 * @module im/messenger/lib/element/dialog/message/vote/factory
 */
jn.define('im/messenger/lib/element/dialog/message/vote/factory', (require, exports, module) => {
	const { CustomMessageFactory } = require('im/messenger/lib/element/dialog/message/custom/factory');
	const { VoteMessage } = require('im/messenger/lib/element/dialog/message/vote/message');
	const { TextMessage } = require('im/messenger/lib/element/dialog/message/text');
	const { Logger } = require('im/messenger/lib/logger');
	const { Feature } = require('im/messenger/lib/feature');

	/**
	 * @class VoteMessage
	 */
	class VoteMessageFactory extends CustomMessageFactory
	{
		static create(modelMessage, options = {})
		{
			try
			{
				return new VoteMessage(modelMessage, options);
			}
			catch (error)
			{
				Logger.error('VoteMessageFactory.create: error', error);

				return new TextMessage(modelMessage, options);
			}
		}

		static checkSuitableForDisplay(modelMessage)
		{
			return Feature.isVoteMessageAvailable;
		}

		static getComponentId()
		{
			return VoteMessage.getComponentId();
		}
	}

	module.exports = { VoteMessageFactory };
});
