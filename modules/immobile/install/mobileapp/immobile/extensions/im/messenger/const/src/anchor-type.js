/**
 * @module im/messenger/const/anchor-type
 */
jn.define('im/messenger/const/anchor-type', (require, exports, module) => {
	const AnchorType = Object.freeze({
		mention: 'MENTION',
		reaction: 'REACTION',
	});

	module.exports = {
		AnchorType,
	};
});
