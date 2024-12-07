/**
 * @module layout/ui/simple-list/items/empty-space
 */
jn.define('layout/ui/simple-list/items/empty-space', (require, exports, module) => {
	const AppTheme = require('apptheme');

	/**
	 * @class EmptySpace
	 */
	class EmptySpace extends LayoutComponent
	{
		get colors()
		{
			return this.props.showAirStyle ? AppTheme.realColors : AppTheme.colors;
		}

		render()
		{
			const { item, customStyles } = this.props;
			const defaultHeight = 20;
			const height = item?.height || defaultHeight;
			const viewStyle = {
				height,
				backgroundColor: item?.color || this.colors.bgPrimary,
				...customStyles,
			};

			return View(
				{
					style: viewStyle,
				},
				// empty View can't be rendered in Android
				// also text must not be empty, so we keep single space to avoid crash on iOS
				Text({
					style: {
						height,
					},
					text: ' ',
				}),
			);
		}
	}

	module.exports = { EmptySpace };
});
