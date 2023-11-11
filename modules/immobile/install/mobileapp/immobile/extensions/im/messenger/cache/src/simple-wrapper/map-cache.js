/**
 * @module im/messenger/cache/simple-wrapper/map-cache
 */
jn.define('im/messenger/cache/simple-wrapper/map-cache', (require, exports, module) => {
	/**
	 * @class MapCache
	 * @desc The class returns wrapper by map collection with use time of fresh cache
	 */
	class MapCache
	{
		/**
		 * @constructor
		 * @param {number} freshnessTime - time of fresh cache
		 */
		constructor(freshnessTime)
		{
			this.freshnessTime = freshnessTime;
			this.lastChangesTime = Date.now();
			this.cache = new Map();
		}

		get(key)
		{
			return this.cache.get(key);
		}

		has(key)
		{
			return this.cache.has(key);
		}

		getAll()
		{
			return Object.fromEntries(this.cache.entries());
		}

		set(key, value)
		{
			this.cache.set(key, value);
			this.refresh();
		}

		clear()
		{
			this.cache.clear();
			this.refresh();
		}

		size()
		{
			return this.cache.size;
		}

		refresh()
		{
			this.lastChangesTime = Date.now();
		}

		isFresh()
		{
			const offset = Date.now() - this.lastChangesTime;

			return offset < this.freshnessTime;
		}
	}

	module.exports = { MapCache };
});
