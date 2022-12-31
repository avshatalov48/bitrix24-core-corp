/**
 * @module crm/in-app-url/router/mobile-url
 */
jn.define('crm/in-app-url/router/mobile-url', (require, exports, module) => {

	const { inAppUrl } = require('in-app-url');
	const { routeMap } = require('crm/in-app-url/router/route-map');
	const { has, get } = require('utils/object');

	class CrmMobileUrl
	{
		constructor(props)
		{
			const { url, eventName } = props;
			this.url = inAppUrl.url(url);
			this.entityType = null;
			this.eventName = eventName;
			this.mobileRoute = this.getMobileRoute();
		}

		get routeParams()
		{
			const key = this.route.params;
			const value = this.mobileRoute.params;

			return {
				[key]: this.url.queryParams[value],
			};
		}

		get routeName()
		{
			return this.route.name;
		}

		get route()
		{
			const { name } = this.mobileRoute;

			return routeMap[this.entityType][name];
		}

		getMobileRoute()
		{
			const foundPath = (type) => [type, 'mobileEvents', this.eventName];

			this.entityType = Object.keys(routeMap).find((type) =>
				has(routeMap, foundPath(type), false),
			);

			if (!this.entityType)
			{
				return false;
			}

			return get(routeMap, foundPath(this.entityType));
		}

		generatePath(name, params)
		{
			if (!name)
			{
				return '';
			}

			return inAppUrl.route(name, params);
		}

		toString()
		{
			return this.generatePath(this.routeName, this.routeParams);
		}
	}

	module.exports = { CrmMobileUrl };

});