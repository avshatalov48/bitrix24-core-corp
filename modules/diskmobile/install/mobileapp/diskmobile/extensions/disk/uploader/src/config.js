/**
 * @module disk/uploader/src/config
 */
jn.define('disk/uploader/src/config', (require, exports, module) => {
	const Defaults = {
		// todo App crashes on 20-30 parallel uploads. Will solve this in next uploader versions.
		MAX_ATTACHED_FILES_COUNT: 10,
		PREVIEW_MAX_WIDTH: 120,
		PREVIEW_MAX_HEIGHT: 120,

		// @link https://docs.aws.amazon.com/AmazonS3/latest/dev/qfacts.html
		CHUNK_SIZE: 5_242_880,
	};

	const UploadStatus = {
		PROGRESS: 'progress',
		DONE: 'done',
		ERROR: 'error',
		CANCELED: 'canceled',
	};

	module.exports = { Defaults, UploadStatus };
});
