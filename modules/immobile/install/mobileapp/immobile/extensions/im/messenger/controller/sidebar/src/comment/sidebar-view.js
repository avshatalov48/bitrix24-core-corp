/**
 * @module im/messenger/controller/sidebar/comment/sidebar-view
 */
jn.define('im/messenger/controller/sidebar/comment/sidebar-view', (require, exports, module) => {
	const { ChannelSidebarView } = require('im/messenger/controller/sidebar/channel/sidebar-view');
	const { ChannelProfileBtn } = require('im/messenger/controller/sidebar/channel/profile-btn-view');
	const { CommentProfileInfo } = require('im/messenger/controller/sidebar/comment/profile-info');
	const { SidebarTabView } = require('im/messenger/controller/sidebar/chat/tabs/tab-view');

	/**
	 * @class CommentSidebarView
	 * @typedef {LayoutComponent<CommentSidebarViewProps, CommentSidebarViewState>} CommentSidebarView
	 */
	class CommentSidebarView extends ChannelSidebarView
	{
		renderInfoBlock()
		{
			return View(
				{
					style: {
						justifyContent: 'center',
						alignItems: 'center',
						flexDirection: 'column',
						width: '100%',
						paddingHorizontal: 18,
						marginTop: 12,
					},
				},
				new CommentProfileInfo(this.props),
			);
		}

		renderButtonsBlock()
		{
			return new ChannelProfileBtn({ buttonElements: this.props.buttonElements });
		}

		renderTabs()
		{
			return new SidebarTabView({
				dialogId: this.props.dialogId,
				hideParticipants: true,
			});
		}
	}

	module.exports = { CommentSidebarView };
});