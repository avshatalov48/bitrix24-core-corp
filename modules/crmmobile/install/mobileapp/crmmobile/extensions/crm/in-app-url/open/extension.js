/**
 * @module crm/in-app-url/open
 */
jn.define('crm/in-app-url/open', (require, exports, module) => {
	const { CrmUrl } = require('crm/in-app-url/url');
	const { inAppUrl } = require('in-app-url');
	require('crm/in-app-url/routes')(inAppUrl);

	/**
	 * @param {String} props.url
	 * @param {Number} props.entityId
	 * @param {Number} props.entityTypeName
	 * @param {String} props.context
	 */
	const openCrmEntityInAppUrl = (props) => {
		const { context, ...restProps } = props;
		const crmUrl = new CrmUrl(restProps);
		const url = crmUrl.toString();

		inAppUrl.open(url, context, () => {
			console.error(`it is impossible to open url: ${url} `);
		});
	};

	module.exports = { openCrmEntityInAppUrl };
});
