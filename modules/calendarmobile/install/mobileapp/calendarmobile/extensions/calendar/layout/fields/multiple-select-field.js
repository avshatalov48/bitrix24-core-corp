/**
 * @module calendar/layout/fields/multiple-select-field
 */
jn.define('calendar/layout/fields/multiple-select-field', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Selector } = require('calendar/layout/fields/layout/selector');
	const { BottomSheet } = require('bottom-sheet');
	const { Color } = require('tokens');

	/**
	 * @class MultipleSelectField
	 */
	class MultipleSelectField extends LayoutComponent
	{
		get style()
		{
			return this.props.style ?? {};
		}

		render()
		{
			return View(
				{
					style: {
						borderRadius: 15,
						backgroundColor: AppTheme.colors.accentSoftBlue3,
						paddingVertical: 5,
						paddingHorizontal: 15,
						...(this.style.field || {}),
					},
					clickable: true,
					onClick: this.onFieldClickHandler.bind(this),
				},
				Text(
					{
						style: {
							fontSize: 15,
							color: AppTheme.colors.accentMainLinks,
							...(this.style.text || {}),
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
			const component = (layoutWidget) => new Selector({
				layoutWidget,
				title: this.props.title,
				items: this.props.items,
				selected: this.props.selected,
				onChange: this.props.onChange,
				checkedBackground: this.style.checkedBackground,
				checkColor: this.style.checkColor,
			});

			(new BottomSheet({ component })
				.setBackgroundColor(Color.bgNavigation.toHex())
				.setMediumPositionPercent(60)
				.setParentWidget(this.props.layoutWidget)
				.open()
				.catch(console.error))
			;
		}
	}

	module.exports = { MultipleSelectField };
});
