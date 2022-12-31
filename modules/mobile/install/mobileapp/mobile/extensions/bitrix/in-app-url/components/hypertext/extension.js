/**
 * @module in-app-url/components/hypertext
 */
jn.define('in-app-url/components/hypertext', (require, exports, module) => {

	const { inAppUrl } = require('in-app-url');

	/**
	 * @param {{
	 *     text: string,
	 *     context: object,
	 * }} props
	 * @return {LayoutComponent}
	 */
	function Hypertext(props)
	{
		const {
			text,
			context,
		} = props;

		return BBCodeText({
			...props,
			value: text,
			onLinkClick: ({ url }) => inAppUrl.open(url, context),
		});
	}

	module.exports = { Hypertext };

});