/**
 * @module in-app-url/url
 */
jn.define('in-app-url/url', (require, exports, module) => {
	const { schemes } = require('in-app-url/const');

	/**
	 * @class Url
	 */
	class Url
	{
		/**
		 * @param {string} value
		 */
		constructor(value)
		{
			/** @type string */
			this.value = value;
		}

		withHttp(url)
		{
			return `http://${url.split('//')[1]}`;
		}

		withHttps(url)
		{
			return `https://${url.split('//')[1]}`;
		}

		/**
		 * @return {boolean}
		 */
		get isLocal()
		{
			const localSchemes = Object.values(schemes);
			const startingPoints = [
				'/',
				...localSchemes,
				currentDomain,
				this.withHttp(currentDomain),
				this.withHttps(currentDomain),
			];

			return startingPoints.some((item) => this.value.startsWith(item));
		}

		/**
		 * @return {boolean}
		 */
		get isExternal()
		{
			return !this.isLocal;
		}

		/**
		 * @deprecated use b24 or bitrix24 deeplink instead
		 * @return {boolean}
		 */
		get isMobileView()
		{
			return /^((\w+:)?\/\/[^\/]+)?\/mobile\//i.test(this.value);
		}

		/**
		 * @return {boolean}
		 */
		get isEmail()
		{
			return this.value.startsWith('mailto:');
		}

		/**
		 * @return {boolean}
		 */
		get isPhoneNumber()
		{
			return this.value.startsWith('tel:');
		}

		/**
		 * @return {boolean}
		 */
		get isBitrix24()
		{
			return this.value.startsWith(schemes.bitrix24) || this.value.startsWith(schemes.b24);
		}

		/**
		 * @return {object}
		 */
		get queryParams()
		{
			const cutHash = (url) => url.split('#')[0];
			const queryString = cutHash(this.value).split('?')[1];

			if (queryString)
			{
				return queryString.split('&').reduce(
					(params, param) => {
						const [key, value] = param.split('=');
						params[key] = value ? decodeURIComponent(value.replace(/\+/g, ' ')) : '';

						return params;
					},
					{},
				);
			}

			return {};
		}

		/**
		 * @return {string}
		 */
		toString()
		{
			return this.value;
		}
	}

	module.exports = { Url };
});
