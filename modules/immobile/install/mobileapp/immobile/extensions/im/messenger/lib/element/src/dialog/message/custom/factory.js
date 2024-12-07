/**
 * @module im/messenger/lib/element/dialog/message/custom/factory
 */
jn.define('im/messenger/lib/element/dialog/message/custom/factory', (require, exports, module) => {
	/**
	 * @class CustomMessageFactory
	 */
	class CustomMessageFactory
	{
		/**
		 * @abstract
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 * @return {Message}
		 */
		static create(modelMessage, options = {})
		{
			throw new Error('CustomMessage: create() must be override in subclass.');
		}

		/**
		 * @abstract
		 * @return {boolean}
		 */
		static checkSuitableForDisplay(modelMessage)
		{
			throw new Error('CustomMessage: checkSuitableForDisplay() must be override in subclass.');
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		static getComponentId()
		{
			throw new Error('CustomMessage: getComponentId() must be override in subclass.');
		}
	}

	module.exports = {
		CustomMessageFactory,
	};
});
