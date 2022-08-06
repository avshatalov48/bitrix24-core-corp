/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/converter/dialog/message/unsupported
 */
jn.define('im/messenger/lib/converter/dialog/message/unsupported', (require, exports, module) => {

	const { TextMessage } = jn.require('im/messenger/lib/converter/dialog/message/text');

	/**
	 * @class UnsupportedMessage
	 */
	class UnsupportedMessage extends TextMessage
	{
		constructor(modelMessage = {}, options = {})
		{
			modelMessage.text = '| Unsupported message |';

			super(modelMessage, options);
		}

		getType()
		{
			return '';
		}
	}

	module.exports = {
		UnsupportedMessage,
	};
});
