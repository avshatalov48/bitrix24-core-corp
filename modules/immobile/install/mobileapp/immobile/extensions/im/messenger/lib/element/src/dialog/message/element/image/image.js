/**
 * @module im/messenger/lib/element/dialog/message/element/image/image
 */
jn.define('im/messenger/lib/element/dialog/message/element/image/image', (require, exports, module) => {
	const { Type } = require('type');
	const { FileStatus } = require('im/messenger/const');

	class Image
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
		 * @return {MessageImage}
		 */
		toMessageFormat()
		{
			return {
				id: this.#getId(),
				type: this.#getMessageElementType(),
				url: this.#getUrl(),
				previewParams: this.#getPreviewParams(),
				status: this.#getUploadStatus(),
			};
		}

		/**
		 * @return {MessageImage['type']}
		 */
		#getMessageElementType()
		{
			return 'image';
		}

		/**
		 * @return {MessageImage['id']}
		 */
		#getId()
		{
			if (Type.isNumber(this.fileModel.id))
			{
				return this.fileModel.id.toString();
			}

			return this.fileModel.id;
		}

		/**
		 * @return {MessageImage['url']}
		 */
		#getUrl()
		{
			const imageUrl = this.fileModel.urlLocalPreview || this.fileModel.urlShow;
			if (Type.isStringFilled(imageUrl))
			{
				return imageUrl;
			}

			return '';
		}

		/**
		 * @return {MessageImage['previewParams']}
		 */
		#getPreviewParams()
		{
			if (Type.isObject(this.fileModel.image))
			{
				return {
					height: this.fileModel.image.height || 0,
					width: this.fileModel.image.width || 0,
				};
			}

			return {
				height: 0,
				width: 0,
			};
		}

		/**
		 * @return {FileStatus}
		 */
		#getUploadStatus()
		{
			const status = this.fileModel.status;
			if ([FileStatus.done, FileStatus.wait].includes(status))
			{
				return FileStatus.done;
			}

			return FileStatus.upload;
		}
	}

	module.exports = { Image };
});
