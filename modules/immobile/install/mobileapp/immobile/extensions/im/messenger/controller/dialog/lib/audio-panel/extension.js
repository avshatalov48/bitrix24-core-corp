/**
 * @module im/messenger/controller/dialog/lib/audio-panel
 */
jn.define('im/messenger/controller/dialog/lib/audio-panel', (require, exports, module) => {
	const { AudioPanel } = require('im/messenger/controller/dialog/lib/audio-panel/src/audio-panel');
	const { returnToAudioMessage } = require('im/messenger/controller/dialog/lib/audio-panel/src/return-to-audio');

	module.exports = {
		AudioPanel,
		returnToAudioMessage,
	};
});
