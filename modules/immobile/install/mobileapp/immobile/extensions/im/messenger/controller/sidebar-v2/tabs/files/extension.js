/**
 * @module im/messenger/controller/sidebar-v2/tabs/files
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/files', (require, exports, module) => {
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SidebarBaseTab } = require('im/messenger/controller/sidebar-v2/tabs/base');
	const { SidebarFileService } = require('im/messenger/controller/sidebar-v2/services/file-service');
	const { SidebarFilesTabContent } = require('im/messenger/controller/sidebar-v2/tabs/files/src/content');
	const { SidebarFileType } = require('im/messenger/controller/sidebar-v2/const');

	class SidebarFilesTab extends SidebarBaseTab
	{
		constructor(props)
		{
			super(props);

			this.widgetNavigator = props.widgetNavigator;
		}

		getId()
		{
			return 'files';
		}

		getTitle()
		{
			return Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TABS_FILES_TITLE');
		}

		createDataProvider()
		{
			const { chatId, dialogId } = this.dialogData;

			return new SidebarFileService({
				chatId,
				dialogId,
				subtypes: [
					SidebarFileType.document,
					SidebarFileType.other,
				],
			});
		}

		getView()
		{
			return (props = {}) => new SidebarFilesTabContent({
				dialogData: this.dialogData,
				widgetNavigator: this.widgetNavigator,
				dataProvider: this.getDataProvider(),
				...props,
			});
		}
	}

	module.exports = { SidebarFilesTab, SidebarFilesTabContent };
});
