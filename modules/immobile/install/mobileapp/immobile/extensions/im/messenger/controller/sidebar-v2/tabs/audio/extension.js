/**
 * @module im/messenger/controller/sidebar-v2/tabs/audio
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/audio', (require, exports, module) => {
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SidebarBaseTab } = require('im/messenger/controller/sidebar-v2/tabs/base');
	const { SidebarFileService } = require('im/messenger/controller/sidebar-v2/services/file-service');
	const { SidebarFileType } = require('im/messenger/controller/sidebar-v2/const');
	const { SidebarAudioTabContent } = require('im/messenger/controller/sidebar-v2/tabs/audio/src/content');

	class SidebarAudioTab extends SidebarBaseTab
	{
		getId()
		{
			return 'audio';
		}

		getTitle()
		{
			return Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TABS_AUDIO_TITLE');
		}

		createDataProvider()
		{
			const { chatId, dialogId } = this.dialogData;

			return new SidebarFileService({
				chatId,
				dialogId,
				subtypes: [
					SidebarFileType.audio,
				],
			});
		}

		getView()
		{
			return (props = {}) => new SidebarAudioTabContent({
				dialogData: this.dialogData,
				widgetNavigator: this.widgetNavigator,
				dataProvider: this.getDataProvider(),
				...props,
			});
		}
	}

	module.exports = { SidebarAudioTab };
});
