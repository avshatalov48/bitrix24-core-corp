/**
 * @module im/messenger/lib/element/dialog/message/file
 */
jn.define('im/messenger/lib/element/dialog/message/file', (require, exports, module) => {
	const { Type } = require('type');
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

			/* region deprecated properties */
			this.fileName = '';
			this.fileSize = '';
			this.fileIconDownloadSvg = '';
			this.fileIconSvg = '';
			/* end region */

			this.file = {
				id: 0,
				name: '',
				size: '',
				iconDownloadSvg: '',
				iconSvg: '',
				originalName: '',
			};

			this.setMessage(modelMessage.text);
			this.setFileName(file);
			this.setFileSize(file);
			this.setFileIconDownloadSvg();
			this.setFileIconSvg(file);
			this.setFileId(file.id);

			this.setShowTail(true);
		}

		getType()
		{
			return 'file';
		}

		setFileId(fileId)
		{
			if (!Type.isNumber(fileId))
			{
				return;
			}

			this.file.id = fileId.toString();
		}

		setFileName(file)
		{
			const fileName = getShortFileName(file.name, 20);
			this.fileName = fileName;
			this.file.name = fileName;
			this.file.originalName = file.name;
		}

		setFileSize(file)
		{
			if (file.size > 0)
			{
				const fileSize = formatFileSize(file.size);
				this.fileSize = fileSize;
				this.file.size = fileSize;
			}
		}

		setFileIconDownloadSvg()
		{
			const arrowInCircle = getArrowInCircle();
			this.fileIconDownloadSvg = arrowInCircle;
			this.file.iconDownloadSvg = arrowInCircle;
		}

		setFileIconSvg(file)
		{
			// TODO: Refactor. It is worth moving the SVG generation into a separate function.
			const easyIcon = EasyIcon(file.extension, 24);
			const fileIconSvg = easyIcon.children[0].props.svg.content;

			this.fileIconSvg = fileIconSvg;
			this.file.iconSvg = fileIconSvg;
		}
	}

	module.exports = {
		FileMessage,
	};
});
