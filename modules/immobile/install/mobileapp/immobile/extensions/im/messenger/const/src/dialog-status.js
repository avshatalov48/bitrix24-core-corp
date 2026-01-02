/**
 * @module im/messenger/const/dialog-status
 */

jn.define('im/messenger/const/dialog-status', (require, exports, module) => {

	const UserInputAction = Object.freeze({
		writing: 'writing',
		recordingVoice: 'recordingVoice',
		sendingPhoto: 'sendingPhoto',
		sendingFile: 'sendingFile',
	});

	module.exports = {
		UserInputAction,
	};
});
