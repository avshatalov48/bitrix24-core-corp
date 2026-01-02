/**
 * @module im/messenger/db/update/version/22
 */
jn.define('im/messenger/db/update/version/22', (require, exports, module) => {
	const { ReactionTable } = require('im/messenger/db/table');

	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isReactionTableExist = await updater.isTableExists('b_im_reaction');
		if (isReactionTableExist === true)
		{
			await updater.addColumnIfNotExists(ReactionTable, 'dialogId');
		}
	};
});
