/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/converter/dialog/message/audio
 */
jn.define('im/messenger/lib/converter/dialog/message/audio', (require, exports, module) => {

	const { Message } = jn.require('im/messenger/lib/converter/dialog/message/base');

	/**
	 * @class AudioMessage
	 */
	class AudioMessage extends Message
	{
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);
		}

		getType()
		{
			return 'audio';
		}
	}

	module.exports = {
		AudioMessage,
	};
});
