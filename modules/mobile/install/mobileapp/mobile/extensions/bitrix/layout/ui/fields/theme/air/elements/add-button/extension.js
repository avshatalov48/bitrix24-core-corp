/**
 * @module layout/ui/fields/theme/air/elements/add-button
 */
jn.define('layout/ui/fields/theme/air/elements/add-button', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { IconView } = require('ui-system/blocks/icon');
	const { Icon } = require('assets/icons');
	const { Text4 } = require('ui-system/typography/text');
	const { PropTypes } = require('utils/validation');

	/**
	 * @param {object} props
	 * @param {function} props.onClick
	 * @param {string} props.text
	 * @param {string} props.testId
	 * @param {{}} props.style
	 */
	const AddButton = (props) => {
		PropTypes.validate(AddButton.propTypes, props, 'AddButton');

		const { onClick, text, testId, style = {}, bindAddButtonRef = null } = props;

		return View(
			{
				testId: `${testId}_ADD_BUTTON`,
				ref: bindAddButtonRef,
				style: {
					flexDirection: 'row',
					alignItems: 'center',
					paddingVertical: Indent.XS2.getValue(),
					...style,
				},
				onClick,
			},
			View(
				{
					style: {
						width: 24,
						height: 24,
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				IconView({
					icon: Icon.PLUS,
					size: 24,
					color: Color.base4,
				}),
			),
			Text4({
				text,
				style: {
					color: Color.base4.toHex(),
					marginLeft: Indent.M.getValue(),
				},
				numberOfLines: 1,
				ellipsize: 'end',
			}),
		);
	};

	AddButton.propTypes = {
		onClick: PropTypes.func.isRequired,
		text: PropTypes.string.isRequired,
		testId: PropTypes.string.isRequired,
		style: PropTypes.object,
		bindAddButtonRef: PropTypes.func,
	};

	module.exports = {
		AddButton,
	};
});
