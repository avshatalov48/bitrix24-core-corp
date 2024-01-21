(() => {
	/**
	 * @class Random
	 */
	class Random
	{
		/**
		 * Returns random string of {length}
		 * @param {number} length
		 * @returns {string}
		 */
		static getString(length = 8)
		{
			return [...new Array(length)].map(() => (Math.trunc(Math.random() * 36)).toString(36)).join('');
		}

		/**
		 * Returns random int between {min} and {max} values
		 * @param {number} min
		 * @param {number} max
		 * @returns {number}
		 */
		static getInt(min, max)
		{
			min = Math.ceil(min);
			max = Math.floor(max);

			return Math.floor(Math.random() * (max - min + 1)) + min;
		}

		/**
		 * Returns universally unique identifier
		 * @returns {string}
		 */
		static getUuid()
		{
			throw new Error('Method not implemented yet');
		}
	}

	jnexport(Random);
})();

/**
 * @module utils/random
 */
jn.define('utils/random', (require, exports, module) => {
	module.exports = { Random: this.Random };
});
