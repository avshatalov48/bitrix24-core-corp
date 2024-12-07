/**
 * @module layout/ui/fields/datetime/theme/air
 */
jn.define('layout/ui/fields/datetime/theme/air', (require, exports, module) => {
	const { DateTimeFieldClass } = require('layout/ui/fields/datetime');
	const { withTheme } = require('layout/ui/fields/theme');
	const { FieldWrapper } = require('layout/ui/fields/theme/air/elements/field-wrapper');
	const { Color, Indent } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
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
				testId: `${field.testId}_CONTENT`,
				style: {
					flexDirection: 'row',
					paddingVertical: Indent.M.toNumber(),
					justifyContent: 'space-between',
				},
				onLongClick: field.getContentLongClickHandler(),
				onClick: field.getContentClickHandler(),
			},
			View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				field.getDefaultLeftIcon() && IconView({
					testId: `${field.testId}_ICON`,
					icon: field.getDefaultLeftIcon(),
					size: IMAGE_SIZE,
					color: field.getConfig().color || Color.accentMainPrimaryalt,
				}),
				Text4({
					testId: `${field.testId}_VALUE`,
					text: field.isEmpty() ? field.getEmptyText() : field.getDisplayedValue(),
					style: {
						color: (field.getConfig().color || Color.base2).toHex(),
						marginLeft: Indent.M.toNumber(),
						flexShrink: 2,
					},
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			),
			field.getConfig()?.renderAfter(),
		),
	);

	/** @type {function(object): object} */
	const DateTimeField = withTheme(DateTimeFieldClass, AirTheme);

	module.exports = {
		AirTheme,
		DateTimeField,
	};
});
