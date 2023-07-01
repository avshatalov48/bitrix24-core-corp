/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/element/dialog/message/deleted
 */
jn.define('im/messenger/lib/element/dialog/message/deleted', (require, exports, module) => {

	const { Loc } = require('loc');
	const { Message } = require('im/messenger/lib/element/dialog/message/base');

	/**
	 * @class DeletedMessage
	 */
	class DeletedMessage extends Message
	{
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			const basketEmoji = String.fromCodePoint(128465);
			const message = basketEmoji + ' ' + Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_DELETED');

			this.setMessage(message);
			this.setFontColor('#959CA4');
			this.setShowTail(true);
		}

		getType()
		{
			return 'text';
		}
	}

	module.exports = {
		DeletedMessage,
	};
});
