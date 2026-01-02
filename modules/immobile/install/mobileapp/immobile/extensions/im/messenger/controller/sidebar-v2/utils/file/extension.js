/**
 * @module im/messenger/controller/sidebar-v2/utils/file
 */
jn.define('im/messenger/controller/sidebar-v2/utils/file', (require, exports, module) => {
	const { Filesystem, utils } = require('native/filesystem');

	const { Loc } = require('im/messenger/loc');
	const { NotifyManager } = require('notify-manager');
	const { showToast, showErrorToast } = require('toast');
	const { withCurrentDomain } = require('utils/url');
	const { Icon } = require('assets/icons');

	const { RestMethod } = require('im/messenger/const');

	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('SidebarV2--utils-file');

	function downloadFileToDevice(fileUrl)
	{
		const fileDownloadUrl = withCurrentDomain(fileUrl);

		NotifyManager.showLoadingIndicator();
		Filesystem.downloadFile(fileDownloadUrl)
			.then((localPath) => {
				NotifyManager.hideLoadingIndicatorWithoutFallback();
				utils.saveFile(localPath);
			})
			.catch((error) => {
				NotifyManager.hideLoadingIndicator(false, 'error');
				logger.error('downloadFileToDevice:', error);
				showErrorToast({
					icon: Icon.CROSS,
					message: Loc.getMessage('IMMOBILE_SIDEBAR_V2_FILE_ACTION_MENU_DOWNLOAD_TO_DEVICE_ERROR'),
				});
			})
		;
	}

	function downloadFileToDisk(fileId)
	{
		const queryParams = {
			file_id: fileId,
		};

		BX.rest.callMethod(
			RestMethod.imDiskFileSave,
			queryParams,
		).then(() => {
			showToast({
				icon: Icon.CHECK,
				message: Loc.getMessage('IMMOBILE_SIDEBAR_V2_FILE_ACTION_MENU_DOWNLOAD_TO_DISK_SUCCESS'),
			});
		}).catch((error) => {
			logger.error('disk save error: ', error);
			showErrorToast({
				icon: Icon.CROSS,
				message: Loc.getMessage('IMMOBILE_SIDEBAR_V2_FILE_ACTION_MENU_DOWNLOAD_TO_DISK_ERROR'),
			});
		})
		;
	}

	module.exports = {
		downloadFileToDevice,
		downloadFileToDisk,
	};
});
