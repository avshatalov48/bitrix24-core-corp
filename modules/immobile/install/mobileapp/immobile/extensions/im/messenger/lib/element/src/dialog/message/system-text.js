/**
 * @module im/messenger/lib/element/dialog/message/system-text
 */
jn.define('im/messenger/lib/element/dialog/message/system-text', (require, exports, module) => {
	const { Message } = require('im/messenger/lib/element/dialog/message/base');
	const { MessageType } = require('im/messenger/const');

	/**
	 * @class SystemTextMessage
	 */
	class SystemTextMessage extends Message
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 */
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			this.setMessage(modelMessage.text);
			this.setIsBackgroundOn(true);
			this.setBackgroundColor('#525C6966');
			this.setFontColor('#FFFFFF');
			this.setShowAvatarForce(false);
			this.setAvatarUri(null);
		}

		getType()
		{
			return MessageType.systemText;
		}

		setShowTail()
		{
			return this;
		}
	}

	module.exports = {
		SystemTextMessage,
	};
});
