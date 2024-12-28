/**
 * @module sign/download-file
 */
jn.define('sign/download-file', (require, exports, module) => {
	const { NotifyManager } = require('notify-manager');
	const { Filesystem, utils } = require('native/filesystem');
	const { Loc } = require('loc');

	const downloadFile = (url) => {
		NotifyManager.showLoadingIndicator();
		Filesystem.downloadFile(url)
			.then((localPath) => {
				NotifyManager.hideLoadingIndicatorWithoutFallback();
				utils.saveFile(localPath).catch(() => dialogs.showSharingDialog({ uri: localPath }));
			})
			.catch(() => {
				NotifyManager.showErrors([{
					message: Loc.getMessage('SIGN_MOBILE_DOWNLOAD_FILE_ERROR_TEXT'),
				}]);
			})
		;
	};

	module.exports = { downloadFile };
});
