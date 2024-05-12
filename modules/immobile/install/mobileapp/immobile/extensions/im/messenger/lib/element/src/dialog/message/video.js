/**
 * @module im/messenger/lib/element/dialog/message/video
 */
jn.define('im/messenger/lib/element/dialog/message/video', (require, exports, module) => {
	const { Type } = require('type');

	const { Message } = require('im/messenger/lib/element/dialog/message/base');

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

			/* region deprecated properties */
			this.videoUrl = null;
			this.localVideoUrl = null;
			this.previewImage = null;
			this.previewParams = {
				height: 0,
				width: 0,
			};
			/* end region */

			this.video = {
				id: 0,
				localUrl: null,
				url: null,
				previewParams: {
					height: 0,
					width: 0,
				},
				size: 0,
			};

			if (modelMessage.text !== '')
			{
				this.setMessage(modelMessage.text);
			}

			if (Type.isNumber(file.id))
			{
				this.setVideoId(file.id);
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

			if (Type.isNumber(file.size))
			{
				this.setSize(file.size);
			}

			this.setPreviewParams(file.image);

			this.setShowTail(true);
			this.setLoadText();
		}

		setVideoId(videoId)
		{
			if (!Type.isNumber(videoId))
			{
				return;
			}

			this.video.id = videoId.toString();
		}

		/**
		 * @param {string} value
		 * @private
		 */
		setVideoUrl(value)
		{
			this.videoUrl = value;
			this.video.url = value;
		}

		/**
		 * @param {string} value
		 * @private
		 */
		setLocalVideoUrl(value)
		{
			this.localVideoUrl = value;
			this.video.localUrl = value;
		}

		/**
		 * @param {string} value
		 * @private
		 */
		setPreviewImage(value)
		{
			this.previewImage = value;
			this.video.previewImage = value;
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

				this.video.previewParams = {
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

				this.video.previewParams = {
					height: 0,
					width: 0,
				};
			}
		}

		setSize(size)
		{
			this.video.size = size;
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
});
