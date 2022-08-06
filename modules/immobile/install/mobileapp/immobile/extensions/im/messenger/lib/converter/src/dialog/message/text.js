/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/converter/dialog/message/text
 */
jn.define('im/messenger/lib/converter/dialog/message/text', (require, exports, module) => {

	const { Message } = jn.require('im/messenger/lib/converter/dialog/message/base');

	/**
	 * @class TextMessage
	 */
	class TextMessage extends Message
	{
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			this.message = '';

			this.setMessage(modelMessage.text);
		}

		getType()
		{
			return 'text';
		}

		setMessage(message)
		{
			this.message = message;
		}
	}

	module.exports = {
		TextMessage,
	};
});
