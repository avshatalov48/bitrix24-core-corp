/**
 * @module layout/ui/fields/theme/air/elements/field-wrapper
 */
jn.define('layout/ui/fields/theme/air/elements/field-wrapper', (require, exports, module) => {
	const { Title } = require('layout/ui/fields/theme/air/elements/title');
	const { Color, Indent } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	/**
	 * @param {object} props
	 * @param {BaseField} props.field - field instance
	 * @param {array|object} children
	 */
	const FieldWrapper = ({ field, titleIndent = Indent.XL }, ...children) => View(
		{
			testId: `${field.testId}_FIELD`,
			ref: field.bindContainerRef,
			onClick: field.getContentClickHandler(),
			style: {
				flexDirection: 'column',
				paddingBottom: field.shouldShowBorder() && Indent.XS,
				borderBottomWidth: field.shouldShowBorder() && 1,
				borderBottomColor: Color.bgSeparatorSecondary.toHex(),
			},
		},
		field.shouldShowTitle() && View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center',
					marginBottom: titleIndent.toNumber(),
				},
			},
			field.isRestricted() && IconView({
				style: {
					marginHorizontal: Indent.XS2.toNumber(),
				},
				icon: Icon.LOCK,
				color: Color.base1,
				size: 20,
			}),
			Title({
				text: field.getTitleText(),
				textMultiple: field.getConfig()?.textMultiple ?? '',
				testId: field.testId,
				count: Array.isArray(field.getValue()) ? field.getValue().length : 0,
			}),
		),
		...Array.isArray(children) ? children : [children],
	);

	module.exports = {
		FieldWrapper,
	};
});
