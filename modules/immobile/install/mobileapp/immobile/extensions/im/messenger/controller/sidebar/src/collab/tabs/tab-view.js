/**
 * @module im/messenger/controller/sidebar/collab/tabs/tab-view
 */
jn.define('im/messenger/controller/sidebar/collab/tabs/tab-view', (require, exports, module) => {
	const { SidebarTabView } = require('im/messenger/controller/sidebar/chat/tabs/tab-view');
	const { CollabParticipantsView } = require('im/messenger/controller/sidebar/collab/tabs/participants/participants-view');
	const { SidebarTab } = require('im/messenger/const');

	/**
	 * @class CollabTabView
	 * @typedef {LayoutComponent<SidebarTabViewProps, SidebarTabViewState>} SidebarTabView
	 */
	class CollabTabView extends SidebarTabView
	{
		renderParticipantsList()
		{
			return new CollabParticipantsView({
				dialogId: this.props.dialogId,
				id: SidebarTab.participant,
			});
		}
	}

	module.exports = { CollabTabView };
});
