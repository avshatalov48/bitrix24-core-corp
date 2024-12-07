/**
 * @module im/messenger/provider/data/result
 */
jn.define('im/messenger/provider/data/result', (require, exports, module) => {

	/**
	 * @template T
	 * @class DataProviderResult
	 */
	class DataProviderResult
	{
		#data;
		#source;

		constructor(data = null, source = null)
		{
			this.#data = data;
			this.#source = source;
		}

		hasData()
		{
			return this.#data !== null;
		}

		/**
		 * @return {T | null}
		 */
		getData()
		{
			return this.#data;
		}

		/**
		 * @return {'model' | 'database' | null}
		 */
		getSource()
		{
			return this.#source;
		}
	}

	module.exports = { DataProviderResult };
});
