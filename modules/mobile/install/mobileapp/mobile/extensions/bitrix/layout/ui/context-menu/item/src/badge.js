/**
 * @module layout/ui/context-menu/item/badge
 */
jn.define('layout/ui/context-menu/item/badge', (require, exports, module) => {

	const { Type } = require('type');

	/**
	 * @class Badge
	 */
	class Badge extends LayoutComponent
	{
		render()
		{
			const { color, backgroundColor } = this.props;

			return View(
				{
					style: {
						backgroundColor: Type.isStringFilled(backgroundColor) ? backgroundColor : '#ffffff',
						paddingHorizontal: 6,
						paddingVertical: 2,
						borderRadius: 12,
						marginLeft: 6,
					},
				},
				Text({
					style: {
						color: Type.isStringFilled(color) ? color : '#000000',
						textAlign: 'center',
						fontSize: 8,
						fontWeight: '700',
					},
					text: this.props.title.toLocaleUpperCase(env.languageId),
				}),
			);
		}
	}

	module.exports = { Badge };
});
