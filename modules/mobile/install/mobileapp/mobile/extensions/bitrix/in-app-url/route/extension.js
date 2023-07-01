/**
 * @module in-app-url/route
 */
jn.define('in-app-url/route', (require, exports, module) => {

	class Route
	{
		/**
		 * @param {string} pattern
		 * @param {function} handler
		 */
		constructor({ pattern, handler })
		{
			this.pattern = pattern;
			this.handler = handler;
			this.$name = '';
		}

		/**
		 * @public
		 * @param {string} val
		 */
		name(val)
		{
			this.$name = val;
		}

		/**
		 * @param {string} name
		 * @return {boolean}
		 */
		hasName(name)
		{
			return this.$name === name;
		}

		/**
		 * Converts friendly route pattern into standard RegExp
		 * @return {RegExp}
		 */
		get regexp()
		{
			const pattern = this.pattern.replace(/(:\w+)/g, '(\\w+)');

			return new RegExp(pattern, 'g');
		}

		/**
		 * @return {string[]}
		 */
		get patternVariables()
		{
			return Array.from(this.pattern.matchAll(/:(\w+)/g), m => m[1]);
		}

		/**
		 * @public
		 * @param {Url} url
		 * @return {boolean}
		 */
		match(url)
		{
			return this.regexp.test(url.toString());
		}

		/**
		 * @public
		 * @param {object} variables
		 * @return {string}
		 */
		makeUrl(variables = {})
		{
			let url = this.pattern;

			this.patternVariables.forEach(key => {
				if (variables.hasOwnProperty(key))
				{
					url = url.replaceAll(`:${key}`, variables[key]);
				}
			});

			return url;
		}

		/**
		 * @public
		 * @param {Url} url
		 * @param {object} context
		 * @return {any}
		 */
		dispatch(url, context)
		{
			if (!this.match(url))
			{
				throw new Error(`Route ${this.pattern} does not match url ${url}`);
			}

			const pathParams = this.parsePathParams(url);

			return this.handler(pathParams, {
				context,
				url: url.toString(),
				queryParams: url.queryParams,
			});
		}

		/**
		 * @private
		 * @param {Url} url
		 * @return {object}
		 */
		parsePathParams(url)
		{
			const values = [...url.toString().matchAll(this.regexp)].shift().slice(1);

			const paramsDict = {};

			this.patternVariables.forEach((variable, index) => {
				paramsDict[variable] = values[index];
			});

			return paramsDict;
		}
	}

	module.exports = { Route };

});