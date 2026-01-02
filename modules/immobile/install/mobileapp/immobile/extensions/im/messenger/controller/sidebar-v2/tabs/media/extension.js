/**
 * @module im/messenger/controller/sidebar-v2/tabs/media
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/media', (require, exports, module) => {
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SidebarBaseTab } = require('im/messenger/controller/sidebar-v2/tabs/base');
	const { SidebarFileService } = require('im/messenger/controller/sidebar-v2/services/file-service');
	const { SidebarMediaTabContent } = require('im/messenger/controller/sidebar-v2/tabs/media/src/content');
	const { SidebarFileType } = require('im/messenger/controller/sidebar-v2/const');

	class SidebarMediaTab extends SidebarBaseTab
	{
		getId()
		{
			return 'media';
		}

		getTitle()
		{
			return Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TABS_MEDIA_TITLE');
		}

		createDataProvider()
		{
			const { chatId, dialogId } = this.dialogData;

			return new SidebarFileService({
				chatId,
				dialogId,
				subtypes: [
					SidebarFileType.media,
				],
			});
		}

		getView()
		{
			return (props = {}) => new SidebarMediaTabContent({
				dialogData: this.dialogData,
				dialogLocator: this.dialogLocator,
				widgetNavigator: this.widgetNavigator,
				dataProvider: this.getDataProvider(),
				...props,
			});
		}
	}

	module.exports = { SidebarMediaTab };
});
