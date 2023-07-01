/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/element/dialog/message/unsupported
 */
jn.define('im/messenger/lib/element/dialog/message/unsupported', (require, exports, module) => {

	const { Message } = require('im/messenger/lib/element/dialog/message/base');

	/**
	 * @class UnsupportedMessage
	 */
	class UnsupportedMessage extends Message
	{
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			this.setMessage('| Unsupported message |');
		}

		getType()
		{
			return 'unknown';
		}
	}

	module.exports = {
		UnsupportedMessage,
	};
});
