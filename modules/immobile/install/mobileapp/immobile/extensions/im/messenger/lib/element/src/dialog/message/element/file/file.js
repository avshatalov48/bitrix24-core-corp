/**
 * @module im/messenger/lib/element/dialog/message/element/file/file
 */
jn.define('im/messenger/lib/element/dialog/message/element/file/file', (require, exports, module) => {
	const { Type } = require('type');
	const { Feature: MobileFeature } = require('feature');
	const {
		Icon,
		resolveFileIcon,
	} = require('assets/icons');
	const { EasyIcon } = require('layout/ui/file/icon');

	const { getArrowInCircle } = require('im/messenger/assets/common');
	const {
		formatFileSize,
		getShortFileName,
		getFileIconTypeByExtension,
	} = require('im/messenger/lib/helper/file');

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
				type: this.#getMessageElementType(),
				name: this.#getName(),
				size: this.#getSize(),
				iconDownloadName: this.#getIconDownloadName(),
				iconDownloadFallbackUrl: this.#getIconDownloadFallbackUrl(),
				iconDownloadSvg: this.#getIconDownloadSvg(),
				iconSvg: this.#getIconSvg(),
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
			if (this.fileModel.size > 0)
			{
				return formatFileSize(this.fileModel.size);
			}

			return '';
		}

		/**
		 * @return {MessageFile['iconDownloadName']}
		 */
		#getIconDownloadName()
		{
			return Icon.DOWNLOAD.getIconName();
		}

		/**
		 * @return {MessageFile['iconDownloadFallbackUrl']}
		 */
		#getIconDownloadFallbackUrl()
		{
			return currentDomain + Icon.DOWNLOAD.getPath();
		}

		/**
		 * @return {MessageFile['iconDownloadSvg']}
		 */
		#getIconDownloadSvg()
		{
			return getArrowInCircle();
		}

		/**
		 * @return {MessageFile['iconSvg']}
		 */
		#getIconSvg()
		{
			if (!MobileFeature.isAirStyleSupported())
			{
				const easyIcon = EasyIcon(this.fileModel.extension, 24);

				return easyIcon?.children[0]?.props?.svg?.content || '';
			}

			const fileIconType = getFileIconTypeByExtension(this.fileModel.extension);

			return resolveFileIcon(this.fileModel.extension, fileIconType).getSvg();
		}
	}

	module.exports = { File };
});
