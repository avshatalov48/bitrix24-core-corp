/**
 * @module in-app-url/url
 */
jn.define('in-app-url/url', (require, exports, module) => {

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

			return startingPoints.some(item => this.value.startsWith(item));
		}

		/**
		 * @return {boolean}
		 */
		get isExternal()
		{
			return !this.isLocal;
		}

		/**
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
		get isBitrix24()
		{
			return this.value.startsWith('bitrix24://');
		}

		/**
		 * @return {object}
		 */
		get queryParams()
		{
			const cutHash = url => url.split('#')[0];
			const queryString = cutHash(this.value).split('?')[1];

			if (queryString)
			{
				return queryString.split('&').reduce(
					(params, param) => {
						const [key, value] = param.split('=');
						params[key] = value ? decodeURIComponent(value.replace(/\+/g, ' ')) : '';
						return params;
					},
					{}
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