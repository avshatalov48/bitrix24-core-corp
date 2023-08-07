/**
 * @module im/messenger/lib/element/dialog/message/file
 */
jn.define('im/messenger/lib/element/dialog/message/file', (require, exports, module) => {
	const { EasyIcon } = require('layout/ui/file/icon');
	const { getArrowInCircle } = require('im/messenger/assets/common');
	const { Message } = require('im/messenger/lib/element/dialog/message/base');
	const { formatFileSize, getShortFileName } = require('im/messenger/lib/helper/file');

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

			this.fileName = '';
			this.fileSize = '';
			this.fileIconDownloadSvg = '';
			this.fileIconSvg = '';

			this.setMessage(modelMessage.text);
			this.setFileName(file);
			this.setFileSize(file);
			this.setFileIconDownloadSvg();
			this.setFileIconSvg(file);

			this.setShowTail(true);
		}

		getType()
		{
			return 'file';
		}

		setFileName(file)
		{
			this.fileName = getShortFileName(file.name, 20);
		}

		setFileSize(file)
		{
			if (file.size > 0)
			{
				this.fileSize = formatFileSize(file.size);
			}
		}

		setFileIconDownloadSvg()
		{
			this.fileIconDownloadSvg = getArrowInCircle();
		}

		setFileIconSvg(file)
		{
			// TODO: Refactor. It is worth moving the SVG generation into a separate function.
			const easyIcon = EasyIcon(file.extension, 24);

			this.fileIconSvg = easyIcon.children[0].props.svg.content;
		}
	}

	module.exports = {
		FileMessage,
	};
});
