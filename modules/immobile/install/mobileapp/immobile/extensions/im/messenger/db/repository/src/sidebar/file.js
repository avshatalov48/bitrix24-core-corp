/**
 * @module im/messenger/db/repository/sidebar/file
 */

jn.define('im/messenger/db/repository/sidebar/file', (require, exports, module) => {
	const { SidebarFileTable } = require('im/messenger/db/table/sidebar/file');

	/**
	 * @class SidebarFileRepository
	 */
	class SidebarFileRepository
	{
		constructor()
		{
			this.sidebarFileTable = new SidebarFileTable();
		}

		/**
		 * @param {Map<number, SidebarFile>} files
		 */
		async saveFromModel(files)
		{
			const filesToAdd = [];

			files.forEach((file) => {
				const fileToAdd = this.sidebarFileTable.validate(file);

				filesToAdd.push(fileToAdd);
			});

			return this.sidebarFileTable.add(filesToAdd, true);
		}

		/**
		 * @param {number} chatId
		 */
		async deleteByChatId(chatId)
		{
			return this.sidebarFileTable.delete({
				chatId,
			});
		}

		async deleteById(id)
		{
			return this.sidebarFileTable.deleteByIdList([id]);
		}
	}

	module.exports = {
		SidebarFileRepository,
	};
});
