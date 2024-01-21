/**
 * @module im/messenger/lib/element/dialog/message/text
 */
jn.define('im/messenger/lib/element/dialog/message/text', (require, exports, module) => {
	const { Message } = require('im/messenger/lib/element/dialog/message/base');

	/**
	 * @class TextMessage
	 */
	class TextMessage extends Message
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 */
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			// this.setMessage(modelMessage.text + `\n\n ID: [b]${modelMessage.id}[/b]`);
			this.setMessage(modelMessage.text);
			this.setShowTail(true);
		}

		getType()
		{
			return 'text';
		}
	}

	module.exports = {
		TextMessage,
	};
});
