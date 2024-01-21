/**
 * @module bizproc/workflow/timeline/components/stubs/content-stub
 * */

jn.define('bizproc/workflow/timeline/components/stubs/content-stub', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Type } = require('type');

	/**
	 * @param {String | undefined} title
	 * @param {Array<View>} children
	 * @returns {View}
	 */
	function ContentStub({ title } = {}, ...children)
	{
		return View(
			{
				style: {
					flexDirection: 'column',
					justifyContent: 'flex-start',
					flex: 1,
					flexGrow: 1,
					flexShrink: '0%',
					overflow: 'hidden',
					marginTop: 12,
					marginRight: 12,
					marginBottom: 12,
				}
			},
			Type.isString(title) && Text({
				text: title,
				style: {
					fontSize: 15,
					fontWeight: '400',
					color: AppTheme.colors.base5,
				}
			}),
			...(Type.isArray(children) ? children : []),
		);
	}

	module.exports = {
		ContentStub,
	}
})