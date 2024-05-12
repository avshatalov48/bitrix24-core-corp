/**
 * @module im/messenger/const/cache
 */
jn.define('im/messenger/const/cache', (require, exports, module) => {

	const CacheNamespace = 'im/messenger/cache/v2.2/';
	const CacheName = Object.freeze({
		chatRecent: 'chat-recent',
		copilotRecent: 'copilot-recent',
		draft: 'draft',
	});

	module.exports = {
		CacheNamespace,
		CacheName,
	};
});
