/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/elements/dialog/message/quote-active
 */
jn.define('im/messenger/lib/parser/elements/dialog/message/quote-active', (require, exports, module) => {

	const { Type } = require('type');

	/**
	 * @class QuoteActive
	 */
	class QuoteActive
	{
		constructor(title, text, dialogId, messageId)
		{
			this.type = QuoteActive.getType();

			if (Type.isStringFilled(title))
			{
				this.title = title;
			}

			if (Type.isStringFilled(text))
			{
				this.text = text;
			}

			if (Type.isStringFilled(dialogId))
			{
				this.dialogId = dialogId;
			}

			if (Type.isStringFilled(messageId))
			{
				this.messageId = messageId;
			}
		}

		static getType()
		{
			return 'quote-active';
		}
	}

	module.exports = {
		QuoteActive,
	};
});
