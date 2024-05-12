/**
 * @module im/messenger/controller/file-download-menu
 */
jn.define('im/messenger/controller/file-download-menu', (require, exports, module) => {
	const { Filesystem, utils } = require('native/filesystem');

	const { Loc } = require('loc');
	const { withCurrentDomain } = require('utils/url');
	const { NotifyManager } = require('notify-manager');
	include('InAppNotifier');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Logger } = require('im/messenger/lib/logger');
	const { FileDownloadMenuSvg } = require('im/messenger/assets/file-download-menu');
	const { DiskService } = require('im/messenger/provider/service');

	/**
	 * @class FileDownloadMenu
	 */
	class FileDownloadMenu
	{
		static createByFileId(fileId, options = {})
		{
			return new this(fileId, options);
		}

		constructor(fileId, options)
		{
			this.fileId = fileId;
			this.options = options;
			this.store = serviceLocator.get('core').getStore();
			this.diskService = new DiskService();

			this.menu = new ContextMenu({
				actions: this.createActions(),
			});

			Logger.log('FileDownloadMenu: created for file: ', this.fileId);
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

		open()
		{
			this.menu.show();
		}

		downloadFileToDevice()
		{
			const file = this.getFile();
			if (!file)
			{
				return;
			}

			Logger.log('FileDownloadMenu.downloadFileToDevice', file);
			const fileDownloadUrl = withCurrentDomain(file.urlDownload);

			NotifyManager.showLoadingIndicator();
			Filesystem.downloadFile(fileDownloadUrl)
				.then((localPath) => {
					NotifyManager.hideLoadingIndicatorWithoutFallback();
					utils.saveFile(localPath);
				})
				.catch((error) => {
					Logger.error('FileDownloadMenu.downloadFileToDevice error:', error);
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

			Logger.log('FileDownloadMenu.downloadFileToDisk', file);

			this.diskService.save(file.id)
				.then(() => {
					InAppNotifier.showNotification({
						title: Loc.getMessage('IMMOBILE_MESSENGER_FILE_DOWNLOAD_TO_DISK_SUCCESS'),
						time: 2,
					});
				})
				.catch((error) => {
					Logger.error('FileDownloadMenu.downloadFileToDisk error:', error);
				})
			;
		}

		getFile()
		{
			return this.store.getters['filesModel/getById'](this.fileId) || {};
		}
	}

	module.exports = {
		FileDownloadMenu,
	};
});
