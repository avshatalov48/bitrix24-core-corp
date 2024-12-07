/**
 * @module im/messenger/db/update/version/7
 */
jn.define('im/messenger/db/update/version/7', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isMessageTableExist = await updater.isTableExists('b_im_message');
		if (isMessageTableExist === true)
		{
			// because when adding a goToMessageContext, backwards-incompatible changes
			// occurred in working with locally stored messages
			await updater.dropTable('b_im_message');
		}

		const isTempMessageTableExist = await updater.isTableExists('b_im_temp_message');
		if (isTempMessageTableExist === true)
		{
			await updater.dropTable('b_im_temp_message');
		}

		const isDialogTableExist = await updater.isTableExists('b_im_dialog');
		if (isDialogTableExist === true)
		{
			await updater.dropTable('b_im_dialog');
		}

		const isFileTableExist = await updater.isTableExists('b_im_file');
		if (isFileTableExist === true)
		{
			await updater.dropTable('b_im_file');
		}

		const isLinkPinTableExist = await updater.isTableExists('b_im_link_pin');
		if (isLinkPinTableExist === true)
		{
			await updater.dropTable('b_im_link_pin');
		}

		const isLinkPinMessageTableExist = await updater.isTableExists('b_im_link_pin_message');
		if (isLinkPinMessageTableExist === true)
		{
			await updater.dropTable('b_im_link_pin_message');
		}

		const isQueueTableExist = await updater.isTableExists('b_im_queue');
		if (isQueueTableExist === true)
		{
			await updater.dropTable('b_im_queue');
		}

		const isReactionTableExist = await updater.isTableExists('b_im_reaction');
		if (isReactionTableExist === true)
		{
			await updater.dropTable('b_im_reaction');
		}

		const isRecentTableExist = await updater.isTableExists('b_im_recent');
		if (isRecentTableExist === true)
		{
			await updater.dropTable('b_im_recent');
		}

		const isSmileTableExist = await updater.isTableExists('b_im_smile');
		if (isSmileTableExist === true)
		{
			await updater.dropTable('b_im_smile');
		}

		const isUserTableExist = await updater.isTableExists('b_im_user');
		if (isUserTableExist === true)
		{
			await updater.dropTable('b_im_user');
		}

		Application.storageById('im/messenger/cache/v2.2/chat-recent').clear();
		Application.storageById('im/messenger/cache/v2.2/copilot-recent').clear();
		Application.storageById('im/messenger/cache/v2.2/draft').clear();
	};
});
