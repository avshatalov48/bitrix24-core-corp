/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants', (require, exports, module) => {
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SidebarBaseTab } = require('im/messenger/controller/sidebar-v2/tabs/base');
	const { ParticipantsService } = require('im/messenger/controller/sidebar-v2/services/participants-service');
	const { SidebarParticipantsTabContent } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/content');
	const { resolveSidebarType, SidebarType } = require('im/messenger/controller/sidebar-v2/factory');

	class SidebarParticipantsTab extends SidebarBaseTab
	{
		getId()
		{
			return 'participants';
		}

		getTitle()
		{
			const sidebarType = resolveSidebarType(this.dialogId);

			return sidebarType === SidebarType.channel
				? Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TABS_CHANNEL_PARTICIPANTS_TITLE')
				: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TABS_PARTICIPANTS_TITLE');
		}

		createDataProvider()
		{
			return new ParticipantsService(this.dialogData);
		}

		getView()
		{
			return (props = {}) => new SidebarParticipantsTabContent({
				dialog: this.dialogData,
				dataProvider: this.getDataProvider(),
				dialogLocator: this.dialogLocator,
				...props,
			});
		}
	}

	module.exports = { SidebarParticipantsTab };
});
