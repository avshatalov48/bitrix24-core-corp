/**
 * @module im/messenger/lib/element/dialog/message/image
 */
jn.define('im/messenger/lib/element/dialog/message/image', (require, exports, module) => {
	const { Type } = require('type');

	const { Message } = require('im/messenger/lib/element/dialog/message/base');

	/**
	 * @class ImageMessage
	 */
	class ImageMessage extends Message
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
			this.imageUrl = null;
			this.previewParams = {
				height: 0,
				width: 0,
			};
			/* end region */

			this.image = {
				id: 0,
				url: null,
				previewParams: {
					height: 0,
					width: 0,
				},
			};

			this.setImageId(file.id);
			this.setImageUrl(file.urlShow);
			this.setShowUsername(modelMessage, false);

			if (modelMessage.text)
			{
				this.setMessage(modelMessage.text);
			}

			this.setPreviewParams(file.image);
			this.setLoadText();
		}

		getType()
		{
			return 'image';
		}

		setShowTail()
		{
			return this;
		}

		setImageId(imageId)
		{
			if (!Type.isNumber(imageId))
			{
				return;
			}

			this.image.id = imageId.toString();
		}

		setImageUrl(imageUrl)
		{
			if (!Type.isStringFilled(imageUrl))
			{
				return;
			}

			this.imageUrl = imageUrl;
			this.image.url = imageUrl;
		}

		setPreviewParams(param)
		{
			if (Type.isObject(param))
			{
				this.previewParams = {
					height: param.height || 0,
					width: param.width || 0,
				};

				this.image.previewParams = {
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

				this.image.previewParams = {
					height: 0,
					width: 0,
				};
			}
		}
	}

	module.exports = {
		ImageMessage,
	};
});
