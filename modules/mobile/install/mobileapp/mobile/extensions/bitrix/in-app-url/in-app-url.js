/**
 * @module in-app-url/in-app-url
 */
jn.define('in-app-url/in-app-url', (require, exports, module) => {

	const { Route } = require('in-app-url/route');
	const { Url } = require('in-app-url/url');
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
		 * @param {function} handler
		 * @return {Route}
		 */
		register(pattern, handler)
		{
			const route = new Route({ pattern, handler });
			this.routes.push(route);
			
			return route;
		}

		url(path)
		{
			return new Url(path);
		}

		findRoute(url)
		{
			return this.routes.find((route) => route.match(url));
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

			if (url.isEmail)
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
				})

				return false;
			}

			const route = this.findRoute(url);

			if (!route)
			{
				if (url.isBitrix24)
				{
					console.warn(`in-app-url: no route found for path ${url}`);
					if (fallbackFn)
					{
						return fallbackFn(url);
					}
					return false;
				}

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

		/**
		 * @public
		 * @param {string} name
		 * @param {object} variables
		 * @return {string|null}
		 */
		route(name, variables = {})
		{
			const route = this.routes.find(r => r.hasName(name));
			if (!route)
			{
				return null;
			}

			return route.makeUrl(variables);
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