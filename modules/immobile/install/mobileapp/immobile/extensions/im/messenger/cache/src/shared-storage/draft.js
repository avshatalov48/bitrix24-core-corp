/**
 * @module im/messenger/cache/draft
 */
jn.define('im/messenger/cache/draft', (require, exports, module) => {
	const { Cache } = require('im/messenger/cache/base');

	/**
	 * @class DraftCache
	 */
	class DraftCache extends Cache
	{
		constructor()
		{
			super({
				name: 'draft',
			});
		}
	}

	module.exports = {
		DraftCache: new DraftCache(),
	};
});
