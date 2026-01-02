/**
 * @module im/messenger/controller/file-download-menu/src/file-download
 */
jn.define('im/messenger/controller/file-download-menu/src/file-download', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { fileSaver } = require('utils/file');
	const { ContextMenu, Icon } = require('layout/ui/context-menu');
	const { Logger } = require('im/messenger/lib/logger');
	const { DiskService } = require('im/messenger/provider/services/disk');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { EventType } = require('im/messenger/const');
	const { FileDownloadType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class FileDownloadMenu
	 */
	class FileDownloadMenu
	{
		/**
		 * @param {FileDownloadMenuProps} props
		 * @returns {FileDownloadMenu}
		 */
		static create(props)
		{
			return new FileDownloadMenu(props);
		}

		/**
		 * @constructor
		 * @param {FileDownloadMenuProps} props
		 */
		constructor(props)
		{
			this.fileId = props.fileId;
			this.dialogId = props.dialogId;
			this.store = serviceLocator.get('core').getStore();
			this.diskService = new DiskService();

			this.menu = new ContextMenu({
				actions: this.createActions(),
			});

			Logger.log(`${this.constructor.name} created for file: `, this.fileId);
		}

		createActions()
		{
			return this.createActionDownloadOneFile();
		}

		createActionDownloadOneFile()
		{
			return [
				{
					id: 'download-to-device',
					title: Loc.getMessage('IMMOBILE_MESSENGER_FILE_DOWNLOAD_MENU_DOWNLOAD_TO_DEVICE'),
					icon: Icon.DOWNLOAD,
					onClickCallback: () => {
						this.menuClose(this.downloadFileToDevice);
					},
				},
				{
					id: 'download-to-disk',
					title: Loc.getMessage('IMMOBILE_MESSENGER_FILE_DOWNLOAD_MENU_DOWNLOAD_TO_DISK_MSGVER_1'),
					icon: Icon.FOLDER_24,
					onClickCallback: () => {
						this.menuClose(this.downloadFileToDisk);
					},
				},
			];
		}

		async open()
		{
			this.subscribeExternalEvents();

			this.layoutWidget = await this.menu.show();
			this.layoutWidget.on(EventType.view.close, () => {
				this.unsubscribeExternalEvents();
			});
		}

		downloadFileToDevice = () => {
			const file = this.getFile();
			if (!file)
			{
				return;
			}

			Logger.log('FileDownloadMenu.downloadFileToDevice', file);

			fileSaver().save({
				files: [{ url: file.urlDownload }],
			}).then(() => {
				this.#showSuccessSaveToast(FileDownloadType.device, Icon.DOWNLOAD);
			}).catch((error) => {
				Logger.error('FileDownloadMenu.downloadFileToDevice:', error);
			});
		};

		downloadFileToDisk = () => {
			const file = this.getFile();
			if (!file)
			{
				return;
			}

			Logger.log('FileDownloadMenu.downloadFileToDisk', file);

			this.diskService.save(file.id)
				.then(() => {
					this.#showSuccessSaveToast(FileDownloadType.disk, Icon.FOLDER_SUCCESS);
				})
				.catch((error) => {
					Logger.error('FileDownloadMenu.downloadFileToDisk', error);
					this.#showSaveFailureToast(FileDownloadType.device);
				});
		};

		getFile()
		{
			return this.store.getters['filesModel/getById'](this.fileId) || {};
		}

		subscribeExternalEvents()
		{
			BX.addCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		unsubscribeExternalEvents()
		{
			BX.removeCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		deleteDialogHandler = ({ dialogId }) => {
			if (String(this.dialogId) !== String(dialogId))
			{
				return;
			}

			this.menuClose();
		};

		menuClose(callback)
		{
			this.menu.close(callback);
		}

		#showSuccessSaveToast(type, icon)
		{
			const phrases = {
				[FileDownloadType.disk]: 'IMMOBILE_MESSENGER_FILE_DOWNLOAD_TO_DISK_SUCCESS_MSGVER_1',
				[FileDownloadType.device]: 'IMMOBILE_MESSENGER_FILE_DOWNLOAD_TO_DEVICE_SUCCESS',
			};

			Notification.showToastWithParams(
				{
					message: Loc.getMessage(phrases[type]),
					icon,
				},
			);
		}

		#showSaveFailureToast(type)
		{
			const phrases = {
				[FileDownloadType.disk]: 'IMMOBILE_MESSENGER_FILE_DOWNLOAD_TO_DISK_FAILURE',
				[FileDownloadType.device]: 'IMMOBILE_MESSENGER_FILE_DOWNLOAD_TO_DEVICE_FAILURE',
			};

			Notification.showErrorToast(
				{
					message: Loc.getMessage(phrases[type]),
				},
			);
		}
	}

	module.exports = {
		FileDownloadMenu,
	};
});
