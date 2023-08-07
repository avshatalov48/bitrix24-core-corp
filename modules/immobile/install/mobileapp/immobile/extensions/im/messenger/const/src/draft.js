/**
 * @module im/messenger/const/draft
 */
jn.define('im/messenger/const/draft', (require, exports, module) => {
	const DraftType = Object.freeze({
		text: 'text',
		reply: 'reply',
		forward: 'forward',
		edit: 'edit',
		test: 'test',
	});

	module.exports = { DraftType };
});
