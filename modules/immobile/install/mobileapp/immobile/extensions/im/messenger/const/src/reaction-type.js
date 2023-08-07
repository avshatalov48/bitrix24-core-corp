/**
 * @module im/messenger/const/reaction-type
 */
jn.define('im/messenger/const/reaction-type', (require, exports, module) => {
	const ReactionType = Object.freeze({
		like: 'like',
		kiss: 'kiss',
		laugh: 'laugh',
		wonder: 'wonder',
		cry: 'cry',
		angry: 'angry',
		facepalm: 'facepalm',
	});

	module.exports = { ReactionType };
});
