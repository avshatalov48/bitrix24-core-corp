/**
 * @module im/messenger/lib/element/dialog/message/call/factory
 */
jn.define('im/messenger/lib/element/dialog/message/call/factory', (require, exports, module) => {
	const { CustomMessageFactory } = require('im/messenger/lib/element/dialog/message/custom/factory');
	const { CallMessage } = require('im/messenger/lib/element/dialog/message/call/message');
	const { TextMessage } = require('im/messenger/lib/element/dialog/message/text');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class CallMessage
	 */
	class CallMessageFactory extends CustomMessageFactory
	{
		static create(modelMessage, options = {})
		{
			try
			{
				return new CallMessage(modelMessage, options);
			}
			catch (error)
			{
				Logger.error('CallMessageFactory.create: error', error);

				return new TextMessage(modelMessage, options);
			}
		}

		static checkSuitableForDisplay(modelMessage)
		{
			return modelMessage.params?.componentId === CallMessageFactory.getComponentId();
		}

		static getComponentId()
		{
			return CallMessage.getComponentId();
		}
	}

	module.exports = {
		CallMessageFactory,
	};
});
