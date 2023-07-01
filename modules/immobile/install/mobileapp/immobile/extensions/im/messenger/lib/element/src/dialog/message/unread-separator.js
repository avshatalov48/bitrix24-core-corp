/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

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
		constructor(modelMessage = {}, options = {})
		{
			if (!modelMessage.id)
			{
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
			this.setBackgroundColor('#525C69');
			this.setRoundedCorners(false);
		}

		getType()
		{
			return 'text';
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
