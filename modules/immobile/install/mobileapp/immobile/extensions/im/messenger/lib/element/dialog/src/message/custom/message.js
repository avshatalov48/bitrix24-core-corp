/**
 * @module im/messenger/lib/element/dialog/message/custom/message
 */
jn.define('im/messenger/lib/element/dialog/message/custom/message', (require, exports, module) => {
	const { Message } = require('im/messenger/lib/element/dialog/message/base');

	/**
	 * @class CustomMessage
	 */
	class CustomMessage extends Message
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 */
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		static getComponentId()
		{
			throw new Error('CustomMessage: getComponentId() must be override in subclass.');
		}

		/**
		 * @abstract
		 * @return {object | undefined}
		 */
		get metaData()
		{
			throw new Error('CustomMessage: metaData() must be override in subclass.');
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		getType()
		{
			throw new Error('CustomMessage: getType() must be override in subclass.');
		}

		getComponentParams()
		{
			return this.getModelMessage()?.params?.COMPONENT_PARAMS ?? {};
		}
	}

	module.exports = {
		CustomMessage,
	};
});
