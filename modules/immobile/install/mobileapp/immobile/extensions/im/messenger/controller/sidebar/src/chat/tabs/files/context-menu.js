/**
 * @module im/messenger/controller/sidebar/chat/tabs/files/context-menu
 */

jn.define('im/messenger/controller/sidebar/chat/tabs/files/context-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Filesystem, utils } = require('native/filesystem');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--files-context-menu');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType, DialogType, SidebarActionType } = require('im/messenger/const');
	const { NotifyManager } = require('notify-manager');
	const { withCurrentDomain } = require('utils/url');
	const { Icon } = require('assets/icons');
	const { UIMenu } = require('layout/ui/menu');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DiskService } = require('im/messenger/provider/service');

	/**
	 * @class FileContextMenu
	 */
	class FileContextMenu
	{
		static createByFileId(props)
		{
			return new this(props);
		}

		/**
		 * @constructor
		 * @param {FileContextMenuProps} props
		 */
		constructor(props)
		{
			this.dialogId = props.dialogId;
			this.messageId = props.messageId;
			this.ref = props.ref;
			this.store = serviceLocator.get('core').getStore();
			this.diskService = new DiskService();
			this.menu = new UIMenu(this.getActions());
			this.file = this.store.getters['filesModel/getById'](props.fileId);

			logger.log(`${this.constructor.name} created for file: `, props.fileId);
		}

		/**
		 * @desc context menu actions
		 * @return {{
		 * id: string,
		 * title: string,
		 * iconName: typeof Icon,
		 * onItemSelected: () => void,
		 * }[]}
		 */
		getActions()
		{
			const dialog = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const channelTypes = [DialogType.openChannel, DialogType.channel, DialogType.generalChannel];
			const isChannel = channelTypes.includes(dialog?.type);

			const openInDialogTitle = isChannel
				? Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_CONTEXT_MENU_OPEN_IN_CHANNEL')
				: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_CONTEXT_MENU_OPEN_IN_CHAT');

			return [
				{
					id: SidebarActionType.downloadFileToDevice,
					testId: this.getTestId(SidebarActionType.downloadFileToDevice),
					title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_CONTEXT_MENU_FILE_DOWNLOAD_TO_DEVICE'),
					iconName: Icon.DOWNLOAD,
					onItemSelected: this.downloadFileToDevice.bind(this),
				},
				{
					id: SidebarActionType.downloadFileToDisk,
					testId: this.getTestId(SidebarActionType.downloadFileToDisk),
					title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_CONTEXT_MENU_FILE_DOWNLOAD_TO_DISK'),
					iconName: Icon.STORAGE,
					onItemSelected: this.downloadFileToDisk.bind(this),
				},
				{
					id: SidebarActionType.openMessageInChat,
					testId: this.getTestId(SidebarActionType.openMessageInChat),
					title: openInDialogTitle,
					iconName: Icon.ARROW_TO_THE_RIGHT,
					onItemSelected: this.openMessageInChat.bind(this),
				},
			];
		}

		/**
		 * @constructor
		 * @param {string} id
		 * @return string
		 */
		getTestId(id)
		{
			return `SIDEBAR_TAB_FILES_${id.toUpperCase()}`;
		}

		open()
		{
			this.menu.show({ target: this.ref });
		}

		downloadFileToDevice()
		{
			if (!this.file)
			{
				return;
			}

			logger.log(`${this.constructor.name}.downloadFileToDevice`, this.file);
			const fileDownloadUrl = withCurrentDomain(this.file.urlDownload);

			NotifyManager.showLoadingIndicator();
			Filesystem.downloadFile(fileDownloadUrl)
				.then((localPath) => {
					NotifyManager.hideLoadingIndicatorWithoutFallback();
					utils.saveFile(localPath);
				})
				.catch((error) => {
					logger.error(`${this.constructor.name}.downloadFileToDevice:`, error);
				})
			;
		}

		downloadFileToDisk()
		{
			if (!this.file)
			{
				return;
			}

			logger.log(`${this.constructor.name}.downloadFileToDisk`, this.file);

			this.diskService.save(this.file.id)
				.then(() => {
					InAppNotifier.showNotification({
						title: Loc.getMessage('IMMOBILE_MESSENGER_FILE_DOWNLOAD_TO_DISK_SUCCESS'),
						time: 2,
					});
				})
				.catch((error) => {
					logger.error(`${this.constructor.name}.downloadFileToDisk`, error);
				})
			;
		}

		openMessageInChat()
		{
			logger.log(`${this.constructor.name}.openMessageInChat`, this.messageId, this.dialogId);

			MessengerEmitter.emit(EventType.sidebar.destroy);
			MessengerEmitter.emit(EventType.dialog.external.goToMessageContext, {
				dialogId: this.dialogId,
				messageId: this.messageId,
			});
		}
	}

	module.exports = {
		FileContextMenu,
	};
});
