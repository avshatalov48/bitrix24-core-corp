/**
 * @module im/messenger/const/file-status
 */
jn.define('im/messenger/const/file-status', (require, exports, module) => {
	const FileStatus = Object.freeze({
		upload: 'upload', 		// file added to upload register and model
		progress: 'progress', 	// file upload has started, progress is changing
		wait: 'wait', 			// file upload complete, awaiting commit
		done: 'done', 			// file was successfully committed to disk
		error: 'error',
	});

	module.exports = {
		FileStatus,
	};
});
