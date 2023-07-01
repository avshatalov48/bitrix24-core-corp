/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

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
		constructor(modelMessage = {}, options = {}, file = {})
		{
			super(modelMessage, options);

			this.setImageUrl(file.urlPreview);

			if (modelMessage.text)
			{
				this.setMessage(modelMessage.text);
			}
		}

		getType()
		{
			return 'image';
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
