/**
 * @module im/messenger/lib/element/dialog/message/file-gallery
 */
jn.define('im/messenger/lib/element/dialog/message/file-gallery', (require, exports, module) => {
	const { MessageType } = require('im/messenger/const');
	const { Message } = require('im/messenger/lib/element/dialog/message/base');
	const { File } = require('im/messenger/lib/element/dialog/message/element/file/file');

	/**
	 * @class FileGalleryMessage
	 */
	class FileGalleryMessage extends Message
	{
		fileList;

		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 * @param {Array<FilesModelState>} fileList
		 */
		constructor(modelMessage = {}, options = {}, fileList = [])
		{
			super(modelMessage, options);

			this.setMessage(modelMessage.text);
			this.setShowTail(true);

			this.fileList = this.createFileList(fileList);
		}

		/**
		 * @protected
		 * @return {string}
		 */
		getType()
		{
			return MessageType.fileGallery;
		}

		/**
		 * @protected
		 * @param {Array<FilesModelState>} fileList
		 */
		createFileList(fileList)
		{
			return fileList.map((file) => {
				return File.createByFileModel(file).toMessageFormat();
			});
		}
	}

	module.exports = {
		FileGalleryMessage,
	};
});
