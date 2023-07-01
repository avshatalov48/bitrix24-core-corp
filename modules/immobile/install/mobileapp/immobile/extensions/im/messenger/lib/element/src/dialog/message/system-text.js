/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/element/dialog/message/system-text
 */
jn.define('im/messenger/lib/element/dialog/message/system-text', (require, exports, module) => {

	const { Message } = require('im/messenger/lib/element/dialog/message/base');

	/**
	 * @class SystemTextMessage
	 */
	class SystemTextMessage extends Message
	{
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			this.setMessage(modelMessage.text);
			this.setIsBackgroundOn(true);
			this.setBackgroundColor('#525C69');
			this.setFontColor('#FFFFFF');
		}

		getType()
		{
			return 'text';
		}
	}

	module.exports = {
		SystemTextMessage,
	};
});
