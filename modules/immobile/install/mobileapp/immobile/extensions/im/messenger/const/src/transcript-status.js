/**
 * @module im/messenger/const/transcript-status
 */

jn.define('im/messenger/const/transcript-status', (require, exports, module) => {
	const TranscriptStatus = Object.freeze({
		ready: 'ready', 		// file is ready to be transcribed
		progress: 'progress', 	// file is in the process of transcription
		expanded: 'expanded', 	// transcription text is expanded
		error: 'error', 		// transcription error has occurred
	});

	const TranscriptResponseStatus = {
		success: 'Success',
		error: 'Error',
		pending: 'Pending,',
	};

	module.exports = {
		TranscriptStatus,
		TranscriptResponseStatus,
	};
});
