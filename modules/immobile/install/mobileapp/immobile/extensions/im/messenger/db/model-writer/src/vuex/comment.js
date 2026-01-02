/**
 * @module im/messenger/db/model-writer/src/vuex/comment
 */
jn.define('im/messenger/db/model-writer/src/vuex/comment', (require, exports, module) => {
	const { Type } = require('type');

	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class CommentWriter extends Writer
	{
		initRouters()
		{
			super.initRouters();

			this.setRouter = this.setRouter.bind(this);
		}

		subscribeEvents()
		{
			this.storeManager
				.on('commentModel/setComments', this.setRouter)
				.on('commentModel/setCommentsWithCounters', this.setRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('commentModel/setComments', this.setRouter)
				.off('commentModel/setCommentsWithCounters', this.setRouter)
			;
		}

		/**
		 * @param {MutationPayload<CommentsSetCommentsData, CommentsSetCommentsActions>} mutation.payload
		 */
		setRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const saveActions = [
				'setComments',
				'setCommentWithCounter',
			];
			if (!saveActions.includes(actionName))
			{
				return;
			}

			const commentList = data.commentList;
			if (!Type.isArrayFilled(commentList))
			{
				return;
			}

			const commentChatIdList = data.commentList.map((commentInfo) => commentInfo.chatId);
			const relationCommentInfoList = this.store.getters['commentModel/getRelationCommentInfoByCommentChatIdList'](commentChatIdList);
			if (!Type.isArrayFilled(relationCommentInfoList))
			{
				return;
			}

			void this.repository.comment.saveFromModel(relationCommentInfoList);
		}
	}

	module.exports = {
		CommentWriter,
	};
});
