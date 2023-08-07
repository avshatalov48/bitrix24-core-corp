/**
 * @module im/messenger/const/file-status
 */
jn.define('im/messenger/const/file-status', (require, exports, module) => {
	const FileStatus = Object.freeze({
		upload: 'upload',
		wait: 'wait',
		progress: 'progress',
		done: 'done',
		error: 'error',
	});

	module.exports = {
		FileStatus,
	};
});
