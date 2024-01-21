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
		/**
		 * @param chatId
		 * @return {boolean}
		 */
		isChatId(chatId)
		{
			return Type.isNumber(Number(chatId));
		}

		/**
		 * @param dialogId
		 * @return {boolean}
		 */
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
