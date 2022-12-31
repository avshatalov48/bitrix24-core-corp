/**
 * @module in-app-url/components/link
 */
jn.define('in-app-url/components/link', (require, exports, module) => {

	const { inAppUrl } = require('in-app-url');

	/**
	 * @param {{
	 *     text: string,
	 *     url: string,
	 *     context: object,
	 *     style: object,
	 *     containerStyle: object,
	 *     renderContent: function|null,
	 * }} props
	 * @return {LayoutComponent}
	 */
	function InAppLink(props)
	{
		const {
			text,
			url,
			context,
			style,
			containerStyle,
			renderContent,
		} = props;

		return View(
			{
				style: containerStyle || {},
				onClick: () => {
					// @todo We probably need some hooks here
					return inAppUrl.open(url, context);
				},
			},
			typeof renderContent === 'function'
				? renderContent(props)
				: Text({
					text,
					style,
				})
		);
	}

	module.exports = { InAppLink };

});