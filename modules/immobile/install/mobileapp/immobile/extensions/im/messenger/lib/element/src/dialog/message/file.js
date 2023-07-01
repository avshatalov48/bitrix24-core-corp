/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/element/dialog/message/file
 */
jn.define('im/messenger/lib/element/dialog/message/file', (require, exports, module) => {

	const { Message } = require('im/messenger/lib/element/dialog/message/base');
	const { formatFileSize, getShortFileName } = require('im/messenger/lib/helper/file');

	/**
	 * @class FileMessage
	 */
	class FileMessage extends Message
	{
		constructor(modelMessage = {}, options = {}, file = {})
		{
			super(modelMessage, options);

			const clipEmoji = String.fromCodePoint(128206);
			const fileText = `${clipEmoji} ${getShortFileName(file.name, 30)} \n      ${formatFileSize(file.size)}`;
			if (modelMessage.text !== '')
			{
				this.setMessage(fileText + '\n\n' + modelMessage.text);
			}
			else
			{
				this.setMessage(fileText);
			}

			this.setShowTail(true);
		}

		getType()
		{
			return 'file';
		}
	}

	module.exports = {
		FileMessage,
	};
});
