/**
 * @module im/messenger/model/files/transcript/default-element
 */

jn.define('im/messenger/model/files/transcript/default-element', (require, exports, module) => {
	const { TranscriptStatus } = require('im/messenger/const');

	/**
	 * @type {Readonly<TranscriptModel}>}
	 */
	const transcriptDefaultElement = Object.freeze({
		fileId: 0,
		messageId: 0,
		chatId: 0,
		text: '',
		status: TranscriptStatus.ready,
	});

	module.exports = {
		transcriptDefaultElement,
	};
});
