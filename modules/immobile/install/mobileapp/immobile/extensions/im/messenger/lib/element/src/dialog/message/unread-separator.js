/**
 * @module im/messenger/lib/element/dialog/message/unread-separator
 */
jn.define('im/messenger/lib/element/dialog/message/unread-separator', (require, exports, module) => {
	const { Loc } = require('loc');

	const {
		MessageAlign,
		MessageTextAlign,
	} = require('im/messenger/lib/element/dialog/message/base');
	const { Message } = require('im/messenger/lib/element/dialog/message/base');

	/**
	 * @class UnreadSeparatorMessage
	 */
	class UnreadSeparatorMessage extends Message
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 */
		constructor(modelMessage = {}, options = {})
		{
			if (!modelMessage.id)
			{
				// eslint-disable-next-line no-param-reassign
				modelMessage.id = UnreadSeparatorMessage.getDefaultId();
			}

			super(modelMessage, options);

			this.setMessage(Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_NEW'));
			this.setShowReaction(false);
			this.setCanBeQuoted(false);
			this.setIsBackgroundOn(true);
			this.setIsBackgroundWide(true);
			this.setMessageAlign(MessageAlign.center);
			this.setTextAlign(MessageTextAlign.center);
			this.setFontColor('#FFFFFF');
			this.setBackgroundColor('#525C6966');
			this.setRoundedCorners(false);
			this.setMarginTop(12);
			this.setMarginBottom(4);
		}

		getType()
		{
			return 'system-text';
		}

		setShowTail()
		{
			return this;
		}

		static getDefaultId()
		{
			return 'template-separator-unread';
		}
	}

	module.exports = {
		UnreadSeparatorMessage,
	};
});
