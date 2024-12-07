/**
 * @module im/messenger/lib/element/dialog/message/check-in/factory
 */
jn.define('im/messenger/lib/element/dialog/message/check-in/factory', (require, exports, module) => {
	const { CustomMessageFactory } = require('im/messenger/lib/element/dialog/message/custom/factory');
	const { CheckInMessage } = require('im/messenger/lib/element/dialog/message/check-in/message');
	const { TextMessage } = require('im/messenger/lib/element/dialog/message/text');
	const { Feature } = require('im/messenger/lib/feature');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class CheckInMessage
	 */
	class CheckInMessageFactory extends CustomMessageFactory
	{
		static create(modelMessage, options = {})
		{
			if (!Feature.isCheckInMessageSupported)
			{
				return new TextMessage(modelMessage, options);
			}

			try
			{
				return new CheckInMessage(modelMessage, options);
			}
			catch (error)
			{
				Logger.error('CheckInMessageFactory.create: error', error);

				return new TextMessage(modelMessage, options);
			}
		}

		static checkSuitableForDisplay(modelMessage)
		{
			return modelMessage.params?.componentId === CheckInMessageFactory.getComponentId();
		}

		static getComponentId()
		{
			return CheckInMessage.getComponentId();
		}
	}

	module.exports = {
		CheckInMessageFactory,
	};
});
