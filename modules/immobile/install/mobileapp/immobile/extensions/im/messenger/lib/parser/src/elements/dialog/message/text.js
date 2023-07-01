/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/elements/dialog/message/text
 */
jn.define('im/messenger/lib/parser/elements/dialog/message/text', (require, exports, module) => {

	/**
	 * @class MessageText
	 */
	class MessageText
	{
		constructor(text = '')
		{
			this.type = MessageText.getType();
			this.text = text;
		}

		static getType()
		{
			return 'text';
		}
	}

	module.exports = {
		MessageText,
	};
});
