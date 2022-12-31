/**
 * @module crm/in-app-url/router
 */
jn.define('crm/in-app-url/router', (require, exports, module) => {

	const { inAppUrl } = require('in-app-url');
	const { CrmMobileUrl } = require('crm/in-app-url/router/mobile-url');
	const { routeMap } = require('crm/in-app-url/router/route-map');
	const { get } = require('utils/object');

	/**
	 * @class CrmRouter
	 */
	class CrmRouter
	{
		constructor(props)
		{
			this.url = this.getUrl(props);
		}

		getUrl(props)
		{
			const { url } = props;
			const appUrl = inAppUrl.url(url);

			return appUrl.isMobileView ? new CrmMobileUrl(props) : url;
		}

		static getDetailRouteByType(type)
		{
			const routeName = get(routeMap, [type, 'detail'], false);
			if (!routeName)
			{
				console.error('not found route');
			}

			return routeName;
		}

		open()
		{
			const path = this.url.toString();

			inAppUrl.open(path, { canOpenInDefault: true });
		}
	}

	module.exports = { CrmRouter, routeMap };

});