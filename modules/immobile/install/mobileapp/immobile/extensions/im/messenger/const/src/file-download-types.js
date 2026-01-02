/**
 * @module im/messenger/const/src/file-download-types
 */
jn.define('im/messenger/const/src/file-download-types', (require, exports, module) => {

	const FileDownloadType = Object.freeze({
		disk: 'DISK',
		device: 'DEVICE',
	});

	module.exports = {
		FileDownloadType,
	};
});
