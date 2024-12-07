/**
 * @module im/messenger/lib/element/dialog/message/file
 */
jn.define('im/messenger/lib/element/dialog/message/file', (require, exports, module) => {
	const { MessageType } = require('im/messenger/const');
	const { Message } = require('im/messenger/lib/element/dialog/message/base');
	const { File } = require('im/messenger/lib/element/dialog/message/element/file/file');

	/**
	 * @class FileMessage
	 */
	class FileMessage extends Message
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 * @param {FilesModelState} file
		 */
		constructor(modelMessage = {}, options = {}, file = {})
		{
			super(modelMessage, options);

			this.setMessage(modelMessage.text, { dialogId: options.dialogId });
			this.setShowTail(true);

			this.file = File.createByFileModel(file).toMessageFormat();

			/* region deprecated properties */
			this.fileName = this.file.name;
			this.fileSize = this.file.size;
			this.fileIconDownloadSvg = this.file.iconDownloadSvg;
			this.fileIconSvg = this.file.iconSvg;
			/* end region */
		}

		getType()
		{
			return MessageType.file;
		}
	}

	module.exports = {
		FileMessage,
	};
});
