/**
 * @module im/messenger/db/update/version/10
 */
jn.define('im/messenger/db/update/version/10', (require, exports, module) => {

	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isRecentTableExist = await updater.isTableExists('b_im_recent');
		if (isRecentTableExist === false)
		{
			return;
		}

		const isLastActivityDateFieldExist = await updater
			.isColumnExists('b_im_recent', 'lastActivityDate')
		;
		const isBirthdayFieldExist = await updater
			.isColumnExists('b_im_user', 'birthday')
		;
		const isAbsentFieldExist = await updater
			.isColumnExists('b_im_user', 'absent')
		;

		if (isLastActivityDateFieldExist === false)
		{
			updater.executeSql({
				query: "ALTER TABLE b_im_recent ADD COLUMN lastActivityDate TEXT",
			});
		}

		if (isBirthdayFieldExist === false)
		{
			updater.executeSql({
				query: "ALTER TABLE b_im_user ADD COLUMN birthday TEXT",
			});
		}

		if (isAbsentFieldExist === false)
		{
			updater.executeSql({
				query: "ALTER TABLE b_im_user ADD COLUMN absent TEXT",
			});
		}
	};
});
