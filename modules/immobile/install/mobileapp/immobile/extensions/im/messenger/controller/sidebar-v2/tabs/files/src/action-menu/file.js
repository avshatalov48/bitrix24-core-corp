/**
 * @module im/messenger/controller/sidebar-v2/tabs/files/src/action-menu/file
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/files/src/action-menu/file', (require, exports, module) => {
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { Icon } = require('assets/icons');
	const { UIMenu } = require('layout/ui/menu');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogHelper } = require('im/messenger/lib/helper/dialog');
	const {
		downloadFileToDevice,
		downloadFileToDisk,
	} = require('im/messenger/controller/sidebar-v2/utils/file');
	const {
		SidebarActionType,
	} = require('im/messenger/const');

	const Sections = {
		COMMON: 'common',
	};
	/**
	 * @class ActionMenu
	 */
	class FileActionMenu
	{
		/**
		 * @param props
		 * @param {string} props.dialogId
		 * @param {number} props.messageId
		 * @param {number} props.fileId
		 * @param {WidgetNavigator} props.widgetNavigator
		 */
		constructor(props)
		{
			/**
			 * @protected
			 * @type {MessengerCoreStore}
			 */
			this.store = serviceLocator.get('core').getStore();

			this.dialogId = props.dialogId;
			this.messageId = props.messageId;
			this.fileId = props.fileId;
			this.file = this.store.getters['filesModel/getById'](this.fileId);
			this.dialogHelper = DialogHelper.createByDialogId(this.dialogId);
			this.widgetNavigator = props.widgetNavigator;
		}

		getTestId(suffix)
		{
			const prefix = 'sidebar-tab-file-item-action-menu';

			return suffix ? `${prefix}-${suffix}` : prefix;
		}

		get actions()
		{
			const openInDialogTitle = this.dialogHelper.isChannel
				? Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_OPEN_IN_CHANNEL')
				: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_OPEN_IN_CHAT');

			return [
				{
					id: SidebarActionType.downloadFileToDevice,
					testId: this.getTestId(SidebarActionType.downloadFileToDevice),
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_FILE_ACTION_MENU_DOWNLOAD_TO_DEVICE'),
					iconName: Icon.DOWNLOAD,
					onItemSelected: () => downloadFileToDevice(this.file.urlDownload),
					section: Sections.COMMON,
					sort: 100,
				},
				{
					id: SidebarActionType.downloadFileToDisk,
					testId: this.getTestId(SidebarActionType.downloadFileToDisk),
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_FILE_ACTION_MENU_DOWNLOAD_TO_DISK'),
					iconName: Icon.FOLDER_24,
					onItemSelected: () => downloadFileToDisk(this.file.id),
					section: Sections.COMMON,
					sort: 200,
				},
				{
					id: SidebarActionType.openMessageInChat,
					testId: this.getTestId(SidebarActionType.openMessageInChat),
					title: openInDialogTitle,
					iconName: Icon.ARROW_TO_THE_RIGHT,
					onItemSelected: this.gotoMessage,
					section: Sections.COMMON,
					sort: 200,
				},
			];
		}

		gotoMessage = async () => {
			this.widgetNavigator.backToChatMessage(Number(this.messageId));
		};

		async show(target)
		{
			this.menu = new UIMenu(this.actions);
			this.menu.show({ target });
		}
	}
	module.exports = { FileActionMenu };
});
