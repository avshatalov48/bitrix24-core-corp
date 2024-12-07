/**
 * @module im/messenger/controller/sidebar/channel/tabs/tab-view
 */
jn.define('im/messenger/controller/sidebar/channel/tabs/tab-view', (require, exports, module) => {
	const { SidebarTabView } = require('im/messenger/controller/sidebar/chat/tabs/tab-view');
	const { ChannelParticipantsView } = require('im/messenger/controller/sidebar/channel/tabs/participants/participants-view');
	const { Loc } = require('loc');
	const { SidebarTab } = require('im/messenger/const');

	/**
	 * @class ChannelTabView
	 * @typedef {LayoutComponent<ChannelSidebarTabViewProps, ChannelSidebarTabViewState>} ChannelTabView
	 */
	class ChannelTabView extends SidebarTabView
	{
		/**
		 * @constructor
		 * @param {SidebarTabViewProps} props
		 */
		constructor(props)
		{
			super(props);
			this.state = {
				tabItems: this.buildTabsData(),
				selectedTab: { id: SidebarTab.participant },
			};
		}

		/**
		 * @desc Build tabs data by object
		 * @return {object[]}
		 */

		getTitleTabs()
		{
			return {
				...super.getTitleTabs(),
				[SidebarTab.participant]: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_PARTICIPANTS'),
			};
		}

		renderParticipantsList()
		{
			return new ChannelParticipantsView({
				dialogId: this.props.dialogId,
				id: SidebarTab.participant,
			});
		}
	}

	module.exports = { ChannelTabView };
});
