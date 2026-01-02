/**
 * @module im/messenger/const/cache
 */
jn.define('im/messenger/const/cache', (require, exports, module) => {

	const CacheNamespace = `${currentDomain}/${env.userId}/im/messenger/cache/v2.2/`;
	const CacheName = Object.freeze({
		developer: 'developer',
	});

	module.exports = {
		CacheNamespace,
		CacheName,
	};
});
