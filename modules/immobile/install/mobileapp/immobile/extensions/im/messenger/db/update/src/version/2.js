/**
 * @module im/messenger/db/update/version/2
 */
jn.define('im/messenger/db/update/version/2', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isMessageTableExist = await updater.isTableExists('b_im_message');
		if (isMessageTableExist === true)
		{
			const isAttachFieldExist = await updater
				.isColumnExists('b_im_message', 'attach')
			;
			if (isAttachFieldExist === false)
			{
				updater.executeSql({
					query: 'ALTER TABLE b_im_message ADD COLUMN attach TEXT',
				});
			}
			const isRichLinkIdFieldExist = await updater
				.isColumnExists('b_im_message', 'richLinkId')
			;
			if (isRichLinkIdFieldExist === false)
			{
				updater.executeSql({
					query: 'ALTER TABLE b_im_message ADD COLUMN richLinkId INTEGER',
				});
			}
		}
	};
});