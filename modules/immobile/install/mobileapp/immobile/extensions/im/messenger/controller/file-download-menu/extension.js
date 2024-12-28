/**
 * @module im/messenger/controller/file-download-menu
 */
jn.define('im/messenger/controller/file-download-menu', (require, exports, module) => {
	const { Filesystem, utils } = require('native/filesystem');

	const { ContextMenu } = require('layout/ui/context-menu');
	const { Loc } = require('loc');
	const { withCurrentDomain } = require('utils/url');
	const { NotifyManager } = require('notify-manager');
	include('InAppNotifier');

	const { EventType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Logger } = require('im/messenger/lib/logger');
	const { FileDownloadMenuSvg } = require('im/messenger/assets/file-download-menu');
	const { DiskService } = require('im/messenger/provider/service');
	const { Notification } = require('im/messenger/lib/ui/notification');

	/**
	 * @class FileDownloadMenu
	 */
	class FileDownloadMenu
	{
		static createByFileId(props)
		{
			return new this(props);
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
			const downloadToDeviceHandler = () => {
				this.menu.close(() => this.downloadFileToDevice());
			};

			const downloadToDiskHandler = () => {
				this.menu.close(() => this.downloadFileToDisk());
			};

			return [
				{
					id: 'download-to-device',
					title: Loc.getMessage('IMMOBILE_MESSENGER_FILE_DOWNLOAD_MENU_DOWNLOAD_TO_DEVICE'),
					data: {
						svgIcon: FileDownloadMenuSvg.getDownloadToDevice(),
					},
					onClickCallback: downloadToDeviceHandler,
				},
				{
					id: 'download-to-disk',
					title: Loc.getMessage('IMMOBILE_MESSENGER_FILE_DOWNLOAD_MENU_DOWNLOAD_TO_DISK'),
					data: {
						svgIcon: FileDownloadMenuSvg.getDownloadToDisk(),
					},
					onClickCallback: downloadToDiskHandler,
				},
			];
		}

		async open()
		{
			this.bindMethods();
			this.subscribeExternalEvents();

			this.layoutWidget = await this.menu.show();
			this.layoutWidget.on(EventType.view.close, () => {
				this.unsubscribeExternalEvents();
			});
		}

		downloadFileToDevice()
		{
			const file = this.getFile();
			if (!file)
			{
				return;
			}

			Logger.log(`${this.constructor.name}.downloadFileToDevice`, file);
			const fileDownloadUrl = withCurrentDomain(file.urlDownload);

			NotifyManager.showLoadingIndicator();
			Filesystem.downloadFile(fileDownloadUrl)
				.then((localPath) => {
					NotifyManager.hideLoadingIndicatorWithoutFallback();
					utils.saveFile(localPath);
				})
				.catch((error) => {
					Logger.error(`${this.constructor.name}.downloadFileToDevice:`, error);
				})
			;
		}

		downloadFileToDisk()
		{
			const file = this.getFile();
			if (!file)
			{
				return;
			}

			Logger.log(`${this.constructor.name}.downloadFileToDisk`, file);

			this.diskService.save(file.id)
				.then(() => {
					Notification.showToastWithParams(
						{
							message: Loc.getMessage('IMMOBILE_MESSENGER_FILE_DOWNLOAD_TO_DISK_SUCCESS'),
							svgType: 'catalogueSuccess',
						},
					);
				})
				.catch((error) => {
					Logger.error(`${this.constructor.name}.downloadFileToDisk`, error);
				})
			;
		}

		getFile()
		{
			return this.store.getters['filesModel/getById'](this.fileId) || {};
		}

		bindMethods()
		{
			this.deleteDialogHandler = this.deleteDialogHandler.bind(this);
		}

		subscribeExternalEvents()
		{
			BX.addCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		unsubscribeExternalEvents()
		{
			BX.removeCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		deleteDialogHandler({ dialogId })
		{
			if (String(this.dialogId) !== String(dialogId))
			{
				return;
			}

			this.menu.close(() => {});
		}
	}

	module.exports = {
		FileDownloadMenu,
	};
});
