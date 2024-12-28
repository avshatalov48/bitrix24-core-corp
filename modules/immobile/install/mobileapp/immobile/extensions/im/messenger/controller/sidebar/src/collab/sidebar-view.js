/**
 * @module im/messenger/controller/sidebar/collab/sidebar-view
 */

jn.define('im/messenger/controller/sidebar/collab/sidebar-view', (require, exports, module) => {
	const { SidebarView } = require('im/messenger/controller/sidebar/chat/sidebar-view');
	const { CollabProfileButtonsController } = require('im/messenger/controller/sidebar/collab/profile-buttons-controller');
	const { ProfileInfo } = require('im/messenger/controller/sidebar/collab/profile-info');
	const { CollabTabView } = require('im/messenger/controller/sidebar/collab/tabs/tab-view');

	/**
	 * @class CollabSidebarView
	 * @typedef {LayoutComponent<CollabSidebarViewProps, CollabSidebarViewState>} ChannelSidebarView
	 */
	class CollabSidebarView extends SidebarView
	{
		renderInfoBlock()
		{
			return View(
				{
					style: {
						marginTop: 12,
						justifyContent: 'center',
						alignItems: 'center',
						flexDirection: 'column',
						paddingHorizontal: 18,
						marginBottom: 18,
						width: '100%',
					},
				},
				new ProfileInfo(this.props),
			);
		}

		renderButtonsBlock()
		{
			return new CollabProfileButtonsController({
				widget: this.props.widget,
				dialogId: this.props.dialogId,
				sidebarService: this.props.sidebarService,
			});
		}

		renderTabs()
		{
			return new CollabTabView({
				dialogId: this.props.dialogId,
				hideParticipants: false,
				isCopilot: false,
			});
		}
	}

	module.exports = { CollabSidebarView };
});
