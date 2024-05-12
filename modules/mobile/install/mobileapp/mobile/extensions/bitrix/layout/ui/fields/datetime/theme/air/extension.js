/**
 * @module layout/ui/fields/datetime/theme/air
 */
jn.define('layout/ui/fields/datetime/theme/air', (require, exports, module) => {
	const { DateTimeFieldClass } = require('layout/ui/fields/datetime');
	const { withTheme } = require('layout/ui/fields/theme');
	const { FieldWrapper } = require('layout/ui/fields/theme/air/elements/field-wrapper');
	const { Color, Indent } = require('tokens');
	const { IconView } = require('ui-system/blocks/icon');

	const IMAGE_SIZE = 32;

	/**
	 * @param  {DateTimeField} field - instance of the DateTimeFieldClass.
	 * @return {function} - functional theme component
	 */
	const AirTheme = ({ field }) => FieldWrapper(
		{ field },
		View(
			{
				style: {
					flexDirection: 'row',
					paddingVertical: Indent.L,
				},
				onLongClick: field.getContentLongClickHandler(),
				onClick: () => field.focus(),
			},
			field.getLeftIcon().icon && IconView({
				icon: field.getLeftIcon().icon,
				size: {
					width: IMAGE_SIZE,
					height: IMAGE_SIZE,
				},
				iconColor: Color.accentMainPrimaryalt,
			}),
			Text({
				text: field.isEmpty() ? field.getEmptyText() : field.getDisplayedValue(),
				style: {
					color: Color.base2,
					fontSize: 14,
					marginLeft: Indent.M,
					flexShrink: 2,
				},
				numberOfLines: 1,
				ellipsize: 'end',
			}),
		),
	);

	/** @type {function(object): object} */
	const DateTimeField = withTheme(DateTimeFieldClass, AirTheme);

	module.exports = {
		AirTheme,
		DateTimeField,
	};
});
