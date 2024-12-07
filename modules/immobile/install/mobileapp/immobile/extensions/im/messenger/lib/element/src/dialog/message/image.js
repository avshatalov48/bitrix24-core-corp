/**
 * @module im/messenger/lib/element/dialog/message/image
 */
jn.define('im/messenger/lib/element/dialog/message/image', (require, exports, module) => {
	const { MessageType } = require('im/messenger/const');
	const { Message } = require('im/messenger/lib/element/dialog/message/base');
	const { Image } = require('im/messenger/lib/element/dialog/message/element/image/image');

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

			this.setShowUsername(modelMessage, false);

			if (modelMessage.text)
			{
				this.setMessage(modelMessage.text, { dialogId: options.dialogId });
			}

			this.setLoadText();

			this.image = Image.createByFileModel(file).toMessageFormat();

			/* region deprecated properties */
			this.imageUrl = this.image.url;
			this.previewParams = this.image.previewParams;
			/* end region */
		}

		getType()
		{
			return MessageType.image;
		}

		setShowTail()
		{
			return this;
		}
	}

	module.exports = {
		ImageMessage,
	};
});
