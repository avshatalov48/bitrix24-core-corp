/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/element/dialog/message/video
 */
jn.define('im/messenger/lib/element/dialog/message/video', (require, exports, module) => {

	const { Message } = require('im/messenger/lib/element/dialog/message/base');
	const { formatFileSize, getShortFileName } = require('im/messenger/lib/helper/file');

	/**
	 * @class VideoMessage
	 */
	class VideoMessage extends Message
	{
		constructor(modelMessage = {}, options = {}, file = {})
		{
			super(modelMessage, options);

			const tvEmoji = String.fromCodePoint(128250);
			const playEmoji = String.fromCodePoint(9199);
			const videoText = `${tvEmoji} ${getShortFileName(file.name, 25)} ${playEmoji}\n      ${formatFileSize(file.size)}`;
			if (modelMessage.text !== '')
			{
				this.setMessage(videoText + '\n\n' + modelMessage.text);
			}
			else
			{
				this.setMessage(videoText);
			}

			this.setShowTail(true);
		}

		getType()
		{
			return 'video';
		}
	}

	module.exports = {
		VideoMessage,
	};
});
