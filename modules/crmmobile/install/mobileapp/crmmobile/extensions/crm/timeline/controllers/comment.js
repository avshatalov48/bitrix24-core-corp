/**
 * @module crm/timeline/controllers/comment
 */
jn.define('crm/timeline/controllers/comment', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');
	const { CommentConfig } = require('crm/timeline/services/file-selector-configs');
	const { FileSelector } = require('layout/ui/file/selector');
	const { confirmDestructiveAction } = require('alert');

	const SupportedActions = {
		ADD_FILE: 'Comment:AddFile',
		DELETE: 'Comment:Delete',
	};

	/**
	 * @class TimelineCommentController
	 */
	class TimelineCommentController extends TimelineBaseController
	{
		static getSupportedActions()
		{
			return Object.values(SupportedActions);
		}

		/**
		 * @public
		 * @param {string} action
		 * @param {object} actionParams
		 */
		onItemAction({ action, actionParams = {} })
		{
			if (action === SupportedActions.ADD_FILE)
			{
				return this.openFileManager(actionParams);
			}

			if (action === SupportedActions.DELETE)
			{
				return this.delete(actionParams);
			}

			return null;
		}

		openFileManager(actionParams)
		{
			if (actionParams.files && actionParams.files !== '')
			{
				this.itemScopeEventBus.emit('Crm.Timeline.Item.OpenFileManagerRequest');

				return;
			}

			FileSelector.open(CommentConfig({
				focused: true,
				entityTypeId: actionParams.ownerTypeId,
				entityId: actionParams.ownerId,
				id: actionParams.entityId,
			}));
		}

		/**
		 * @private
		 * @param {string|number} commentId
		 * @param {number} ownerId
		 * @param {number} ownerTypeId
		 * @param {string|null} confirmationText
		 */
		delete({ commentId, ownerId, ownerTypeId, confirmationText })
		{
			if (!commentId)
			{
				return;
			}

			const data = { id: commentId, ownerTypeId, ownerId };

			confirmDestructiveAction({
				title: '',
				description: confirmationText,
				onDestruct: () => this.executeDeleteAction(data),
			});
		}

		/**
		 * @private
		 * @param {{
		 *   id: string|number,
		 *   ownerTypeId: number,
		 *   ownerId: number,
		 * }} data
		 */
		executeDeleteAction(data = {})
		{
			const action = 'crm.timeline.comment.delete';

			this.item.showLoader();

			BX.ajax.runAction(action, { data })
				.catch((response) => {
					this.item.hideLoader();
					void ErrorNotifier.showError(response.errors[0].message);
				});
		}
	}

	module.exports = { TimelineCommentController };
});
