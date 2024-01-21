/**
 * @module im/messenger/lib/element/dialog/message/emoji-only
 */
jn.define('im/messenger/lib/element/dialog/message/emoji-only', (require, exports, module) => {
	const { Message } = require('im/messenger/lib/element/dialog/message/base');

	/**
	 * @class EmojiOnlyMessage
	 */
	class EmojiOnlyMessage extends Message
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 */
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			this.setMessage(modelMessage.text, { enableBigSmile: true });
		}

		getType()
		{
			return 'emoji-only';
		}
	}

	module.exports = {
		EmojiOnlyMessage,
	};
});
