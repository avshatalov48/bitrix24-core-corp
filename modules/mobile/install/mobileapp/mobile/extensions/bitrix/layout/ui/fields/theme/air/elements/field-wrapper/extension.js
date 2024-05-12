/**
 * @module layout/ui/fields/theme/air/elements/field-wrapper
 */
jn.define('layout/ui/fields/theme/air/elements/field-wrapper', (require, exports, module) => {
	const { Title } = require('layout/ui/fields/theme/air/elements/title');
	const { Color, Indent } = require('tokens');

	/**
	 * @param {object} props
	 * @param {BaseField} props.field - field instance
	 * @param {array|object} children
	 */
	const FieldWrapper = ({ field }, ...children) => View(
		{
			testId: `${field.testId}_FIELD`,
			ref: field.bindContainerRefHandler,
			onClick: field.getContentClickHandler(),
			style: {
				flexDirection: 'column',
				marginHorizontal: Indent.XL3,
				paddingBottom: field.shouldShowBorder() && Indent.XS,
				marginBottom: field.shouldShowBorder() && Indent.XL,
				borderBottomWidth: field.shouldShowBorder() && 1,
				borderBottomColor: Color.bgSeparatorSecondary,
			},
		},
		field.shouldShowTitle() && Title({ text: field.getTitleText(), testId: field.testId }),
		...Array.isArray(children) ? children : [children],
	);

	module.exports = {
		FieldWrapper,
	};
});
