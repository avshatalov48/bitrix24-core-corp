/**
 * @module im/messenger/db/table/comment
 */
jn.define('im/messenger/db/table/comment', (require, exports, module) => {
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');

	class CommentTable extends Table
	{
		getName()
		{
			return 'b_im_comment';
		}

		getPrimaryKey()
		{
			return 'commentChatId';
		}

		getFields()
		{
			return [
				{ name: 'commentChatId', type: FieldType.integer, unique: true, index: true },
				{ name: 'parentMessageId', type: FieldType.integer, index: true },
				{ name: 'parentChatId', type: FieldType.integer },
			];
		}
	}

	module.exports = {
		CommentTable,
	};
});
