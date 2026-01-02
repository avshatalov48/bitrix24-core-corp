/**
 * @module in-app-url/route
 */
jn.define('in-app-url/route', (require, exports, module) => {
	const ROUTE_PATTERN = '([^&=$/?]+)';
	const ROUTE_PATTERN_REGEXP = /:([A-Z_a-z][\w-]*)/g;

	/**
	 * @class Route
	 */
	class Route
	{
		#name = '';
		#routes = [];
		#handler = null;

		/**
		 * @param {RouteProps} props
		 */
		constructor({ pattern, handler, routes })
		{
			this.pattern = pattern;
			this.#handler = handler;
			this.#name = '';
			this.#routes = this.#createRoutesList(routes, pattern);
		}

		handler(handler)
		{
			this.#handler = handler;

			return this;
		}

		/**
		 * @public
		 * @param {string} val
		 */
		name(val)
		{
			this.#name = val;
		}

		/**
		 * @param {string} name
		 * @return {boolean}
		 */
		hasName(name)
		{
			return this.#name === name;
		}

		/**
		 * @return {string[]}
		 */
		getPatternVariables(pattern)
		{
			return Array.from(pattern.matchAll(ROUTE_PATTERN_REGEXP), (m) => m[1]);
		}

		/**
		 * @param {Url | string} url
		 * @return {boolean}
		 */
		match(url)
		{
			return this.#routes.some(({ pattern } = {}) => this.testPattern(url.toString(), pattern));
		}

		/**
		 * @param {string} url
		 * @return {RouteOptions | undefined}
		 */
		findRoute(url)
		{
			return this.#routes.find(({ pattern } = {}) => this.testPattern(url.toString(), pattern));
		}

		/**
		 * @param {string} url
		 * @param {string} pattern
		 * @returns {boolean}
		 */
		testPattern(url, pattern)
		{
			return this.#patternRegexp(pattern).test(url);
		}

		/**
		 * @deprecated
		 * @param {object} variables
		 * @return {string}
		 */
		makeUrl(variables = {})
		{
			let url = this.pattern;

			this.getPatternVariables(this.pattern).forEach((key) => {
				if (variables[key])
				{
					url = url.replaceAll(new RegExp(`:${key}`, 'g'), variables[key]);
				}
				else
				{
					throw new Error(`Variable ${key} is not defined in makeUrl method`);
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
			const urlString = typeof url === 'string' ? url : url.toString();
			const route = this.findRoute(urlString);

			if (!route || !urlString)
			{
				throw new Error(`Route ${this.#name} does not match url ${urlString}`);
			}

			const pathParams = this.parsePathParams(route, url);

			if (!this.#handler)
			{
				throw new Error(`Route ${this.#name} does not have a handler`);
			}

			return this.#handler(
				pathParams,
				{
					context,
					url: urlString,
					routes: this.#routes,
					queryParams: url.queryParams,
				},
			);
		}

		/**
		 * @param {string} pattern
		 * @param {Object} params
		 */
		addRoute(pattern, params)
		{
			this.#routes.push({ pattern, params });

			return this;
		}

		/**
		 * @private
		 * @param {RouteOptions} route
		 * @param {Url} url
		 * @return {object}
		 */
		parsePathParams(route, url)
		{
			if (!route || !url)
			{
				return {};
			}

			const routePattern = route.pattern;
			const values = [...url.toString().matchAll(this.#patternRegexp(routePattern))].shift().slice(1);
			const paramsDict = {};

			this.getPatternVariables(routePattern).forEach((variable, index) => {
				paramsDict[variable] = values[index];
			});

			return paramsDict;
		}

		/**
		 * Converts friendly route pattern into standard RegExp
		 * @param {string} pattern
		 * @returns {RegExp}
		 */
		#patternRegexp(pattern)
		{
			return new RegExp(pattern?.replaceAll(ROUTE_PATTERN_REGEXP, ROUTE_PATTERN), 'g');
		}

		/**
		 * @param {Array<RouteOptions>} routesList
		 * @param {string} pattern
		 */
		#createRoutesList(routesList, pattern)
		{
			const routes = Array.isArray(routesList) && routesList.length > 0 ? routesList : [];

			if (pattern)
			{
				routes.push({ pattern });
			}

			return routes;
		}

		/**
		 * @public
		 * @returns {RegExp}
		 */
		get regexp()
		{
			return this.#patternRegexp(this.pattern);
		}
	}

	module.exports = { Route };
});
