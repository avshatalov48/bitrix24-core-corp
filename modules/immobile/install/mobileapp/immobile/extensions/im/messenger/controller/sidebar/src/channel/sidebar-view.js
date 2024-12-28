/**
 * @module im/messenger/controller/sidebar/channel/sidebar-view
 */
jn.define('im/messenger/controller/sidebar/channel/sidebar-view', (require, exports, module) => {
	const { SidebarView } = require('im/messenger/controller/sidebar/chat/sidebar-view');
	const { ChannelProfileBtn } = require('im/messenger/controller/sidebar/channel/profile-btn-view');
	const { ChannelTabView } = require('im/messenger/controller/sidebar/channel/tabs/tab-view');
	const { ChannelProfileInfo } = require('im/messenger/controller/sidebar/channel/profile-info');

	/**
	 * @class ChannelSidebarView
	 * @typedef {LayoutComponent<ChannelSidebarViewProps, ChannelSidebarViewState>} ChannelSidebarView
	 */
	class ChannelSidebarView extends SidebarView
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
				new ChannelProfileInfo(this.props),
			);
		}

		renderButtonsBlock()
		{
			return new ChannelProfileBtn({ buttonElements: this.props.buttonElements });
		}

		renderTabs()
		{
			return new ChannelTabView({
				dialogId: this.props.dialogId,
			});
		}
	}

	module.exports = { ChannelSidebarView };
});