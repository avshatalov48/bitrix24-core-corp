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
						let decodedValue = '';
						if (value)
						{
							const replaced = value.replaceAll('+', ' ');
							try
							{
								decodedValue = decodeURIComponent(replaced);
							}
							catch
							{
								decodedValue = value;
							}
						}
						// eslint-disable-next-line no-param-reassign
						params[key] = decodedValue;

						return params;
					},
					{},
				);
			}

			return {};
		}

		/**
		 * @return {boolean}
		 */
		get isEncoded()
		{
			if (!Type.isString(this.href))
			{
				return false;
			}

			if (!/%[\da-f]{2}/i.test(this.href))
			{
				return false;
			}

			try
			{
				decodeURIComponent(this.href);

				return true;
			}
			catch
			{
				return false;
			}
		}
	}

	module.exports = {
		Url,
	};
});
