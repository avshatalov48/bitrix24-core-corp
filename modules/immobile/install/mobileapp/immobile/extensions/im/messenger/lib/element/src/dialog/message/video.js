/**
 * @module im/messenger/lib/element/dialog/message/video
 */
jn.define('im/messenger/lib/element/dialog/message/video', (require, exports, module) => {
	const { Message } = require('im/messenger/lib/element/dialog/message/base');
	const { Type } = require('type');

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
			this.videoUrl = null;
			this.localVideoUrl = null;
			this.previewImage = null;

			if (modelMessage.text !== '')
			{
				this.setMessage(modelMessage.text);
			}

			if (Type.isStringFilled(file.urlShow))
			{
				this.setVideoUrl(file.urlShow);
			}

			if (Type.isStringFilled(file.localUrl))
			{
				this.setLocalVideoUrl(file.localUrl);
			}

			if (Type.isStringFilled(file.urlPreview))
			{
				this.setPreviewImage(file.urlPreview);
			}

			this.setPreviewParams(file.image);

			this.setShowTail(true);
		}

		/**
		 * @param {string} value
		 * @private
		 */
		setVideoUrl(value)
		{
			this.videoUrl = value;
		}

		/**
		 * @param {string} value
		 * @private
		 */
		setLocalVideoUrl(value)
		{
			this.localVideoUrl = value;
		}

		/**
		 * @param {string} value
		 * @private
		 */
		setPreviewImage(value)
		{
			this.previewImage = value;
		}

		/**
		 * @param {object|boolean} param
		 * @param {number} param.height
		 * @param {number} param.width
		 * @private
		 */
		setPreviewParams(param)
		{
			if (Type.isObject(param))
			{
				this.previewParams = {
					height: param.height || 0,
					width: param.width || 0,
				};
			}
			else
			{
				this.previewParams = {
					height: 0,
					width: 0,
				};
			}
		}

		getType()
		{
			return 'video';
		}

		setShowTail()
		{
			return this;
		}
	}

	module.exports = {
		VideoMessage,
	};
})
;
