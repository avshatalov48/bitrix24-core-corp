/**
 * @module crm/in-app-url/url
 */
jn.define('crm/in-app-url/url', (require, exports, module) => {

	const { Url } = require('in-app-url/url');
	const { CrmMobileUrl } = require('crm/in-app-url/url/mobile');
	const { CrmMobileAppUrl } = require('crm/in-app-url/url/mobile-app');
	const { get } = require('utils/object');

	/**
	 * @class CrmUrl
	 */
	class CrmUrl
	{

		constructor(props)
		{
			const { url } = props;

			this.url = new Url(url);
			this.controller = this.getUrlController({ ...props, url: this.url });
		}

		get isUniversalActivityScenario()
		{
			return get(
				jnExtensionData.get('crm:in-app-url/url'),
				'isUniversalActivityScenarioEnabled',
				false,
			);
		};

		static createUrl(props)
		{
			const crmUrl = new this(props);

			return crmUrl.toString();
		}

		getUrlController(props)
		{
			const controller = this.isUniversalActivityScenario ? CrmMobileUrl : CrmMobileAppUrl;

			return new controller(props);
		}

		toString()
		{
			return this.controller.getUrl();
		}

	}

	module.exports = { CrmUrl };
});