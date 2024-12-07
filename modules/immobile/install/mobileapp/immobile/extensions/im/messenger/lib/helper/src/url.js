/**
 * @module im/messenger/lib/helper/url
 */
jn.define('im/messenger/lib/helper/url', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @class Url
	 */
	class Url
	{
		#value;

		/**
		 * @param {string} path
		 * @return {Url}
		 */
		static createFromPath(path)
		{
			return new this(`${currentDomain}${path}`);
		}

		/**
		 * @param {string} value
		 */
		constructor(value)
		{
			/** @type string */
			this.#value = Type.isString(value) ? value : '';
		}

		/**
		 * @return {string}
		 */
		get href()
		{
			return this.#value;
		}

		/**
		 * @return {boolean}
		 */
		get isLocal()
		{
			const startingPoints = [
				'bitrix24://',
				'/',
				currentDomain,
			];

			return startingPoints.some((item) => this.#value.startsWith(item));
		}

		/**
		 * @return {object}
		 */
		get queryParams()
		{
			const cutHash = (url) => url.split('#')[0];
			const queryString = cutHash(this.#value).split('?')[1];

			if (queryString)
			{
				return queryString.split('&').reduce(
					(params, param) => {
						const [key, value] = param.split('=');
						// eslint-disable-next-line no-param-reassign
						params[key] = value ? decodeURIComponent(value.replace(/\+/g, ' ')) : '';

						return params;
					},
					{},
				);
			}

			return {};
		}
	}

	module.exports = {
		Url,
	};
});
