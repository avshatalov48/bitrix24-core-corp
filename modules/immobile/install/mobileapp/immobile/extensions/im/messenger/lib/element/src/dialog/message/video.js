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
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 * @param {FilesModelState} file
		 */
		constructor(modelMessage = {}, options = {}, file = {})
		{
			super(modelMessage, options);

			const tvEmoji = String.fromCodePoint(128_250);
			const videoText = `${tvEmoji} ${getShortFileName(file.name, 25)}\n      ${formatFileSize(file.size)}`;
			if (modelMessage.text === '')
			{
				this.setMessage(videoText);
			}
			else
			{
				this.setMessage(`${videoText}\n\n${modelMessage.text}`);
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
