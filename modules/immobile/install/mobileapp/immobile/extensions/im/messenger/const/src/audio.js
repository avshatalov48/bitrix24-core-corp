/**
 * @module im/messenger/const/src/audio
 */
jn.define('im/messenger/const/src/audio', (require, exports, module) => {
	const AudioEvents = {
		playingMediaChanged: 'playingMediaChanged',
		speedChanged: 'speedChanged',
		timeUpdate: 'timeupdate',
	};

	module.exports = {
		AudioEvents,
	};
});
