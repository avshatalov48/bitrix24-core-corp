/**
 * @module im/messenger/db/repository/comment
 */
jn.define('im/messenger/db/repository/comment', (require, exports, module) => {

	const { CommentTable } = require('im/messenger/db/table');

	/**
	 * @class CommentRepository
	 */
	class CommentRepository
	{
		constructor()
		{
			/**
			 * @type {CommentTable}
			 */
			this.commentTable = new CommentTable();
		}

		/**
		 * @param {Array<RelationCommentInfo>} relationCommentInfoList
		 * @return {Promise<{lastInsertId: number, columns: *[], changes: number, rows: *[], errors: Error[]}|void>}
		 */
		async saveFromModel(relationCommentInfoList)
		{
			const commentListToAdd = [];
			relationCommentInfoList.forEach((relationCommentInfo) => {
				const commentToAdd = this.commentTable.validate(relationCommentInfo);

				commentListToAdd.push(commentToAdd);
			});

			return this.commentTable.add(commentListToAdd, true);
		}

		/**
		 * @param {number} commentChatId
		 * @return {Promise<RelationCommentInfo | null>}
		 */
		async getByCommentChatId(commentChatId)
		{
			return this.commentTable.getById(commentChatId);
		}

		/**
		 * @param {number} parentChatId
		 * @return {Promise<void>}
		 */
		async deleteByParentChatId(parentChatId)
		{
			return this.commentTable.delete({
				parentChatId,
			});
		}
	}

	module.exports = {
		CommentRepository,
	};
});
