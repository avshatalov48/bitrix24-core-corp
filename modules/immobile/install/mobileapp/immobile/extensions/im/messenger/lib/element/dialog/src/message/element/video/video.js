/**
 * @module im/messenger/lib/element/dialog/message/element/video/video
 */
jn.define('im/messenger/lib/element/dialog/message/element/video/video', (require, exports, module) => {
	const { Type } = require('type');
	const { FileStatus } = require('im/messenger/const');

	class Video
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
		 * @return {MessageVideo}
		 */
		toMessageFormat()
		{
			return {
				id: this.#getId(),
				type: this.#getMessageElementType(),
				localUrl: this.#getLocalUrl(),
				url: this.#getUrl(),
				previewImage: this.#getPreviewImage(),
				previewParams: this.#getPreviewParams(),
				size: this.#getSize(),
				status: this.#getUploadStatus(),
			};
		}

		/**
		 * @return {MessageVideo['type']}
		 */
		#getMessageElementType()
		{
			return 'video';
		}

		/**
		 * @return {MessageVideo['id']}
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
		 * @return {MessageVideo['localUrl']}
		 */
		#getLocalUrl()
		{
			if (Type.isStringFilled(this.fileModel.localUrl))
			{
				return this.fileModel.localUrl;
			}

			return null;
		}

		/**
		 * @return {MessageVideo['url']}
		 */
		#getUrl()
		{
			if (Type.isStringFilled(this.fileModel.urlShow))
			{
				return this.fileModel.urlShow;
			}

			return null;
		}

		/**
		 * @return {MessageVideo['previewImage']}
		 */
		#getPreviewImage()
		{
			if (Type.isStringFilled(this.fileModel.urlLocalPreview))
			{
				return this.fileModel.urlLocalPreview;
			}

			if (Type.isStringFilled(this.fileModel.urlPreview))
			{
				return this.fileModel.urlPreview;
			}

			return null;
		}

		/**
		 * @return {MessageVideo['previewParams']}
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
		 * @return {MessageVideo['size']}
		 */
		#getSize()
		{
			if (Type.isNumber(this.fileModel.size))
			{
				return this.fileModel.size;
			}

			return 0;
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

	module.exports = { Video };
});
