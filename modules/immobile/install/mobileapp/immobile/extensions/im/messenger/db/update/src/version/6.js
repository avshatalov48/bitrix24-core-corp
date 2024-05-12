/**
 * @module im/messenger/db/update/version/6
 */
jn.define('im/messenger/db/update/version/6', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isDialogTableExist = await updater.isTableExists('b_im_dialog');
		if (isDialogTableExist === true)
		{
			let isChatIdIndexExist = await updater.isIndexExists('b_im_dialog', 'chatId');
			if (isChatIdIndexExist === true)
			{
				// remove unique index because all invited users has chatId = 0 by default
				await updater.dropIndex('b_im_dialog', 'chatId');
			}
			//
			// isChatIdIndexExist = await updater.isIndexExists('b_im_dialog', 'chatId');
			// if (isChatIdIndexExist === false)
			// {
			// 	await updater.createIndex('b_im_dialog', 'chatId', false);
			// }

			Application.storageById('im/messenger/cache/v2.2/chat-recent').clear();
		}
	};
});
