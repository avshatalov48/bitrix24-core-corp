jn.define('layout/ui/fields/datetime/theme/inline', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { IconView } = require('ui-system/blocks/icon');
	// eslint-disable-next-line no-unused-vars
	const { DateTimeField } = require('layout/ui/fields/datetime');
	const { withPressed } = require('utils/color');

	/**
	 * @function DateTimeRenderFunction
	 * @param  {DateTimeField} field
	 * @return {object}
	 */
	const DateTimeInlineRenderFunction = (field) => {
		const {
			wrapper = {},
			text = {},
			chevronIcon = {},
		} = field.styles;

		const {
			borderColor,
			backgroundColor,
		} = wrapper;

		const {
			color,
		} = text;

		const {
			color: chevronColor,
		} = chevronIcon;

		return View(
			{
				style: {
					flexDirection: 'row',
				},
			},
			View(
				{
					testId: field.testId,
					style: {
						alignItems: 'center',
						flexDirection: 'row',
						borderColor: borderColor || AppTheme.colors.base6,
						backgroundColor: backgroundColor || withPressed(AppTheme.colors.base8),
						borderWidth: 1,
						borderRadius: 21,
						paddingLeft: 8,
						paddingRight: 4,
						paddingVertical: 4,
						flexShrink: 2,
					},
					onLongClick: field.getContentLongClickHandler(),
					onClick: () => field.focus(),
				},
				Text({
					text: field.getForcedTextValue()
						|| (field.isEmpty() ? field.getReadOnlyEmptyValue() : field.getFormattedDate()),
					style: {
						color: color || AppTheme.colors.base3,
						fontSize: 14,
						marginRight: 4,
						flexShrink: 2,
					},
					numberOfLines: 1,
					ellipsize: 'end',
				}),
				!field.isReadOnly() && IconView({
					icon: 'chevronDown',
					iconSize: {
						width: 12,
						height: 12,
					},
					iconColor: chevronColor || AppTheme.colors.base3,
				}),
			),
		);
	};

	module.exports = {
		DateTimeInlineRenderFunction,
	};
});
