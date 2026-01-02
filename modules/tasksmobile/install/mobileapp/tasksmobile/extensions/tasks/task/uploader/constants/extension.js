/**
 * @module tasks/task/uploader/constants
 */
jn.define('tasks/task/uploader/constants', (require, exports, module) => {
	const TaskUploaderEvents = {
		FILE_SUCCESS_UPLOAD: 'ontaskfilesuccessupload',
		FILE_FAIL_UPLOAD: 'ontaskfilefailupload',
	};

	module.exports = { TaskUploaderEvents };
});
