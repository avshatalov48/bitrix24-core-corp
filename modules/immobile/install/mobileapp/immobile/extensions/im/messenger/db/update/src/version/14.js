/**
 * @module im/messenger/db/update/version/14
 */
jn.define('im/messenger/db/update/version/14', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isQueueTableExist = await updater.isTableExists('b_im_queue');
		if (isQueueTableExist === true)
		{
			const isColumnMessageIdExist = await updater
				.isColumnExists('b_im_queue', 'messageId')
			;
			// if messageId column is had then dropping all table and updater restore it
			if (isColumnMessageIdExist === true)
			{
				await updater.dropTable('b_im_queue');
			}
		}
	};
});
