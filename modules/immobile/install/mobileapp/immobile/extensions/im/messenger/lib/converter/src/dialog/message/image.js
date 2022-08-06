/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/converter/dialog/message/image
 */
jn.define('im/messenger/lib/converter/dialog/message/image', (require, exports, module) => {

	const { Message } = jn.require('im/messenger/lib/converter/dialog/message/base');

	/**
	 * @class ImageMessage
	 */
	class ImageMessage extends Message
	{
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);
		}

		getType()
		{
			return 'image';
		}
	}

	module.exports = {
		ImageMessage,
	};
});
