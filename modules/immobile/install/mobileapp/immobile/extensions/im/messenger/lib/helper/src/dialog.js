/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/helper/dialog
 */
jn.define('im/messenger/lib/helper/dialog', (require, exports, module) => {

	const { Type } = require('type');

	/**
	 * @class DialogHelper
	 */
	class DialogHelper
	{
		isChatId(chatId)
		{
			return Type.isNumber(Number(chatId));
		}

		isDialogId(dialogId)
		{
			return (
				dialogId.toString().startsWith('chat')
				&& Type.isNumber(Number(dialogId.slice(4)))
			);
		}
	}

	module.exports = {
		DialogHelper,
	};
});
