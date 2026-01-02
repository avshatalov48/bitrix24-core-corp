/**
 * @module im/messenger/lib/element/dialog/message/element/file/file
 */
jn.define('im/messenger/lib/element/dialog/message/element/file/file', (require, exports, module) => {
	const { Type } = require('type');
	const {
		Icon,
		resolveFileIcon,
	} = require('assets/icons');

	const {
		formatFileSize,
		getShortFileName,
		getFileIconTypeByExtension,
	} = require('im/messenger/lib/helper/file');
	const { withCurrentDomain } = require('utils/url');

	class File
	{
		/**
		 * @param {FilesModelState} fileModel
		 */
		static createByFileModel(fileModel)
		{
			return new this(fileModel);
		}

		/**
		 * @param {FilesModelState} fileModel
		 */
		constructor(fileModel)
		{
			this.fileModel = fileModel;
		}

		/**
		 * @return {MessageFile}
		 */
		toMessageFormat()
		{
			return {
				id: this.#getId(),
				testId: this.#getOriginalName(),
				type: this.#getMessageElementType(),
				name: this.#getName(),
				size: this.#getSize(),
				iconDownloadName: this.#getIconDownloadName(),
				iconDownloadFallbackUrl: this.#getIconDownloadFallbackUrl(),
				iconSvg: this.#getIconSvg(),
				sizeBytes: this.#getSizeBytes(),
				url: this.#getDownloadUrl(),
				originalName: this.#getOriginalName(),
			};
		}

		/**
		 * @return {MessageFile['type']}
		 */
		#getMessageElementType()
		{
			return 'file';
		}

		/**
		 * @return {MessageFile['id']}
		 */
		#getId()
		{
			if (Type.isNumber(this.fileModel.id))
			{
				return this.fileModel.id.toString();
			}

			return 0;
		}

		/**
		 * @return {MessageFile['name']}
		 */
		#getName()
		{
			return getShortFileName(this.fileModel.name, 20) || '';
		}

		/**
		 * @return {MessageFile['originalName']}
		 */
		#getOriginalName()
		{
			return this.fileModel.name || '';
		}

		/**
		 * @return {MessageFile['size']}
		 */
		#getSize()
		{
			const sizeBytes = this.#getSizeBytes();
			if (sizeBytes > 0)
			{
				return formatFileSize(sizeBytes);
			}

			return '';
		}

		#getSizeBytes()
		{
			return this.fileModel.size;
		}

		/**
		 * @return {MessageFile['iconDownloadName']}
		 */
		#getIconDownloadName()
		{
			return this.#getIcon().getIconName();
		}

		/**
		 * @return {MessageFile['iconDownloadFallbackUrl']}
		 */
		#getIconDownloadFallbackUrl()
		{
			return withCurrentDomain(this.#getIcon().getPath());
		}

		/**
		 * @returns {Icon}
		 */
		#getIcon()
		{
			return Icon.DOWNLOAD;
		}

		/**
		 * @return {MessageFile['iconSvg']}
		 */
		#getIconSvg()
		{
			const fileIconType = getFileIconTypeByExtension(this.fileModel.extension);

			return resolveFileIcon(this.fileModel.extension, fileIconType).getSvg();
		}

		#getDownloadUrl()
		{
			return this.fileModel.urlDownload;
		}
	}

	module.exports = { File };
});
