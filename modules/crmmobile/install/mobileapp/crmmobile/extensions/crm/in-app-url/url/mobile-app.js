/**
 * @module crm/in-app-url/url/mobile-app
 */
jn.define('crm/in-app-url/url/mobile-app', (require, exports, module) => {
	const { CrmUrlBase } = require('crm/in-app-url/url/base');

	/**
	 * @class CrmMobileAppUrl
	 */
	class CrmMobileAppUrl extends CrmUrlBase
	{
		getListPattern()
		{
			return `/mobile${super.getListPattern()}`;
		}

		getDetailPattern()
		{
			return `${this.getListPattern()}?page=view&${this.getMobileDetailParams()}=:id`;
		}

		getUrl()
		{
			if (this.isExistUrl() && this.url.isMobileView)
			{
				return this.url.toString();
			}

			return this.createUrl();
		}
	}

	module.exports = { CrmMobileAppUrl };
});
