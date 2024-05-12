/**
 * @module im/messenger/cache/draft
 */
jn.define('im/messenger/cache/draft', (require, exports, module) => {
	const {
		CacheName,
	} = require('im/messenger/const');
	const { Cache } = require('im/messenger/cache/base');

	/**
	 * @class DraftCache
	 */
	class DraftCache extends Cache
	{
		constructor()
		{
			super({
				name: CacheName.draft,
			});
		}
	}

	module.exports = {
		DraftCache: new DraftCache(),
	};
});
