/**
 * @module crm/in-app-url/url/mobile
 */
jn.define('crm/in-app-url/url/mobile', (require, exports, module) => {
	const { CrmUrlBase } = require('crm/in-app-url/url/base');

	/**
	 * @class CrmMobileUrl
	 */
	class CrmMobileUrl extends CrmUrlBase
	{
		getUrl()
		{
			if (this.isExistUrl() && !this.url.isMobileView)
			{
				return this.url.toString();
			}

			return this.createUrl();
		}
	}

	module.exports = { CrmMobileUrl };
});
