/**
 * @module im/messenger/lib/element/dialog/message/unsupported
 */
jn.define('im/messenger/lib/element/dialog/message/unsupported', (require, exports, module) => {
	const { Message } = require('im/messenger/lib/element/dialog/message/base');

	/**
	 * @class UnsupportedMessage
	 */
	class UnsupportedMessage extends Message
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 */
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			this.setMessage('| Unsupported message |');
		}

		getType()
		{
			return 'unknown';
		}
	}

	module.exports = {
		UnsupportedMessage,
	};
});
