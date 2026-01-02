/**
 * @module crm/timeline/item/ui/body/blocks/comment-content
 */
jn.define('crm/timeline/item/ui/body/blocks/comment-content', (require, exports, module) => {
	const { inAppUrl } = require('in-app-url');
	const { Loc } = require('loc');
	const { TimelineItemBodyBaseEditableBlock } = require('crm/timeline/item/ui/body/blocks/base-editable-block');
	const { UserProfile } = require('user-profile');

	/**
	 * @class TimelineItemBodyCommentContentBlock
	 */
	class TimelineItemBodyCommentContentBlock extends TimelineItemBodyBaseEditableBlock
	{
		getPreparedActionParams()
		{
			const { actionParams } = this.props.saveAction;

			return {
				id: actionParams.commentId,
				ownerId: actionParams.ownerId,
				ownerTypeId: actionParams.ownerTypeId,
				fields: {
					COMMENT: this.state.text,
				},
			};
		}

		getEditorTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_BLOCK_COMMENT_EDITABLE_TEXT_TITLE');
		}

		getEditorPlaceholder()
		{
			return Loc.getMessage('M_CRM_TIMELINE_BLOCK_COMMENT_EDITABLE_TEXT_PLACEHOLDER');
		}

		openUserProfile(userId)
		{
			void UserProfile.open({
				ownerId: userId,
			});
		}

		getTextParams()
		{
			const params = super.getTextParams();
			params.onLinkClick = ({ url }) => inAppUrl.open(url);
			params.onUserClick = ({ userId }) => this.openUserProfile(userId);

			return params;
		}
	}

	module.exports = { TimelineItemBodyCommentContentBlock };
});
