/**
 * @module im/messenger/controller/sidebar-v2/tabs/common-chats
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/common-chats', (require, exports, module) => {
	const { Loc: SidebarLoc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SidebarBaseTab } = require('im/messenger/controller/sidebar-v2/tabs/base');
	const { SidebarCommonChatsTabContent } = require(
		'im/messenger/controller/sidebar-v2/tabs/common-chats/src/content',
	);
	const { SidebarCommonChatsService } = require('im/messenger/controller/sidebar-v2/services/common-chats-service');

	class SidebarCommonChatsTab extends SidebarBaseTab
	{
		getId()
		{
			return 'chats';
		}

		getTitle()
		{
			return SidebarLoc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TABS_COMMON_CHATS_TITLE');
		}

		createDataProvider()
		{
			const { chatId, dialogId } = this.dialogData;

			return new SidebarCommonChatsService({ chatId, dialogId });
		}

		getView()
		{
			return (props = {}) => new SidebarCommonChatsTabContent({
				chatId: this.dialogData?.chatId,
				dataProvider: this.getDataProvider(),
				...props,
			});
		}
	}

	module.exports = { SidebarCommonChatsTab };
});
