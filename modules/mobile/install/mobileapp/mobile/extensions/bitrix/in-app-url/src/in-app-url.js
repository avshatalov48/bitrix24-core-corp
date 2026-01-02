/**
 * @module in-app-url/src/in-app-url
 */
jn.define('in-app-url/src/in-app-url', (require, exports, module) => {
	const { Url } = require('in-app-url/url');
	const { Route } = require('in-app-url/route');
	const { getHttpPath } = require('utils/url');
	const { stringify } = require('utils/string');

	/**
	 * @class InAppUrl
	 */
	class InAppUrl
	{
		constructor()
		{
			/** @type Route[] */
			this.routes = [];
		}

		/**
		 * @public
		 * @param {string} pattern
		 * @param {Function} handler
		 */
		register(pattern, handler)
		{
			const route = this.#createRoute({ pattern, handler });
			this.routes.push(route);

			return route;
		}

		/**
		 * @param {string} pattern
		 * @param {object} [params]
		 * @returns {Route}
		 */
		addRoute(pattern, params)
		{
			const route = this.#createRoute({
				pattern,
				params,
			});
			this.routes.push(route);

			return route;
		}

		url(path)
		{
			return new Url(path);
		}

		/**
		 * @public
		 * @param {string} path
		 * @param {object} context
		 * @param {function(Url)|null} fallbackFn
		 * @return {false|any}
		 */
		open(path, context = {}, fallbackFn = null)
		{
			path = stringify(path);
			if (path === '')
			{
				console.error('in-app-url: unable to open empty path');

				return false;
			}

			const url = new Url(path);

			if (url.isEmail || url.isPhoneNumber)
			{
				Application.openUrl(url.value);

				return false;
			}

			if (url.isExternal)
			{
				Application.openUrl(InAppUrl.getHttpPath(path));

				return false;
			}

			if (url.isMobileView)
			{
				PageManager.openPage({
					url: url.toString(),
				});

				return false;
			}

			try
			{
				const route = this.findRoute(url);

				if (!route)
				{
					console.warn(`in-app-url: no route found for path ${url}`);
					if (fallbackFn)
					{
						return fallbackFn(url);
					}

					PageManager.openPage({
						bx24ModernStyle: true,
						...context,
						url: url.toString(),
					});

					return false;
				}

				return route.dispatch(url, context);
			}
			catch (e)
			{
				console.error(`in-app-url: error while opening path ${path}`, e);
			}
		}

		/**
		 * @public
		 * @param {string} name
		 * @param {object} variables
		 * @return {Array<Route>}
		 */
		route(name, variables = {})
		{
			return this.findRoutesByName(name).forEach((route) => {
				route.makeUrl(variables);
			});
		}

		/**
		 * @param {Url | string} url
		 * @returns {Route}
		 */
		findRoute(url)
		{
			return this.routes.find((route) => route.match(url));
		}

		findRoutesByName(name)
		{
			return this.routes.filter((route) => route.hasName(name));
		}

		/**
		 * @param options
		 * @returns {Route}
		 */
		#createRoute(options)
		{
			return new Route(options);
		}

		/**
		 * @public
		 * @param {string} path
		 * @return {string}
		 */
		static getHttpPath(path)
		{
			return getHttpPath(path);
		}
	}

	module.exports = { InAppUrl };
});
