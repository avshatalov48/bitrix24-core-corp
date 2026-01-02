/**
 * @module im/messenger/controller/sidebar-v2/tabs/audio/src/const
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/audio/src/const', (require, exports, module) => {
	const PlayerStage = Object.freeze({
		PLAYING: 'playing',
		PAUSED: 'paused',
		LOADING: 'loading',
		IDLE: 'idle',
	});

	module.exports = {
		PlayerStage,
	};
});
