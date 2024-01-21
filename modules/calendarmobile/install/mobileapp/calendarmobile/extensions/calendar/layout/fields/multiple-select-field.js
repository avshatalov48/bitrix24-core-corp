/**
 * @module calendar/layout/fields/multiple-select-field
 */
jn.define('calendar/layout/fields/multiple-select-field', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Selector } = require('calendar/layout/fields/layout/selector');
	const { BottomSheet } = require('bottom-sheet');

	/**
	 * @class MultipleSelectField
	 */
	class MultipleSelectField extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						borderRadius: 15,
						backgroundColor: AppTheme.colors.accentSoftBlue3,
						paddingVertical: 5,
						paddingHorizontal: 15,
					},
					clickable: true,
					onClick: this.onFieldClickHandler.bind(this),
				},
				Text(
					{
						style: {
							fontSize: 15,
							color: AppTheme.colors.accentMainLinks,
						},
						numberOfLines: 1,
						ellipsize: 'end',
						text: this.props.formatValue(),
					},
				),
			);
		}

		onFieldClickHandler()
		{
			const selector = new Selector({
				title: this.props.title,
				items: this.props.items,
				selected: this.props.selected,
				onChange: this.props.onChange,
			});

			(new BottomSheet({ component: selector })
				.setBackgroundColor(AppTheme.colors.bgNavigation)
				.setMediumPositionPercent(60)
				.setParentWidget(this.props.layoutWidget)
				.open()
				.then((widget) => selector.setLayoutWidget(widget))
				.catch(console.error));
		}
	}

	module.exports = { MultipleSelectField };
});
