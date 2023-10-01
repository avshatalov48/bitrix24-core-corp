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

			this.setImageUrl(file.urlPreview);
			this.setShowUsername(modelMessage, false);

			if (modelMessage.text)
			{
				this.setMessage(modelMessage.text);
			}
		}

		getType()
		{
			return 'image';
		}

		setShowTail()
		{
			return this;
		}

		setImageUrl(imageUrl)
		{
			if (!Type.isStringFilled(imageUrl))
			{
				return;
			}

			this.imageUrl = imageUrl;
		}
	}

	module.exports = {
		ImageMessage,
	};
});
