/**
 * @module im/messenger/lib/element/dialog/message/text
 */
jn.define('im/messenger/lib/element/dialog/message/text', (require, exports, module) => {
	const { MessageType } = require('im/messenger/const');
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

			this.setMessage(modelMessage.text, { dialogId: options.dialogId });
			this.setShowTail(true);
		}

		getType()
		{
			return MessageType.text;
		}
	}

	module.exports = {
		TextMessage,
	};
});
