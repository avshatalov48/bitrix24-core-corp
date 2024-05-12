/**
 * @module ui-system/form/buttons/button
 */
jn.define('ui-system/form/buttons/button', (require, exports, module) => {
	const { PropTypes } = require('utils/validation');
	const { PlainView } = require('ui-system/blocks/plain-view');
	const { Color, Indent, IndentTypes, Corner, CornerTypes } = require('tokens');
	const { mergeImmutable } = require('utils/object');

	const buttonTypes = Object.freeze({
		XL: 'XL',
		L: 'L',
		M: 'M',
		S: 'S',
		XS: 'XS',
	});

	const paddingHorizontalMap = {
		[buttonTypes.XL]: Indent[IndentTypes.XL4],
		[buttonTypes.L]: Indent[IndentTypes.XL3],
		[buttonTypes.M]: Indent[IndentTypes.XL2],
		[buttonTypes.S]: Indent[IndentTypes.L],
		[buttonTypes.XS]: Indent[IndentTypes.M],
	};

	const textPaddingMap = {
		[buttonTypes.XL]: IndentTypes.XS,
		[buttonTypes.L]: IndentTypes.XS,
		[buttonTypes.M]: IndentTypes.XS2,
		[buttonTypes.S]: IndentTypes.XS2,
		[buttonTypes.XS]: IndentTypes.XS2,
	};

	const cornerMap = {
		[buttonTypes.XL]: Corner.M,
		[buttonTypes.L]: Corner.M,
		[buttonTypes.M]: Corner.M,
		[buttonTypes.S]: Corner.S,
		[buttonTypes.XS]: Corner.XS,
	};

	const FONT_SIZES = {
		[buttonTypes.XL]: 18,
		[buttonTypes.L]: 16,
		[buttonTypes.M]: 14,
		[buttonTypes.S]: 14,
		[buttonTypes.XS]: 12,
	};

	const BUTTON_SIZES = {
		[buttonTypes.XL]: 48,
		[buttonTypes.L]: 42,
		[buttonTypes.M]: 36,
		[buttonTypes.S]: 28,
		[buttonTypes.XS]: 22,
	};

	/**
	 * @param {buttonTypes} size
	 * @return number
	 */
	const getButtonSize = (size = buttonTypes.M) => buttonTypes[size] || buttonTypes.M;

	/**
	 * @param {buttonTypes} size
	 * @return number
	 */
	const getInternalIndents = (size = buttonTypes.M) => paddingHorizontalMap[getButtonSize(size)];

	/**
	 *
	 * @param {borderColor | backgroundColor | color | icon} type
	 * @param {boolean} isFill
	 * return string;
	 */
	const getDisabledColor = (type, isFill) => {
		if (type === 'borderColor')
		{
			return Color.base6;
		}

		if (type === 'color' || type === 'icon')
		{
			return isFill ? Color.base5 : Color.base6;
		}

		if (type === 'backgroundColor')
		{
			return Color.base7;
		}

		return null;
	};

	/**
	 * @function Button
	 * @param {object} props
	 * @param {string} props.text
	 * @param {string} props.size
	 * @param {string} props.color
	 * @param {boolean} props.stretched
	 * @param {boolean} props.rounded
	 * @param {boolean} props.disabled
	 * @param {View} props.after
	 * @param {View} props.before
	 * @param {boolean} props.border
	 * @param {string} props.borderColor
	 * @param {string} props.backgroundColor
	 * @param {object} props.style
	 * @return {Button}
	 */
	const Button = (props) => {
		const {
			text,
			size,
			color = null,
			disabled = false,
			stretched = false,
			rounded = false,
			after = null,
			before = null,
			border = false,
			borderColor = Color.base5,
			backgroundColor = null,
			...restProps
		} = props;

		if (!text && !after && !before)
		{
			return null;
		}

		let buttonTextColor = color;
		const buttonSize = getButtonSize(size);
		const paddingHorizontal = getInternalIndents(buttonSize);

		const style = {
			backgroundColor,
			justifyContent: 'center',
			borderRadius: cornerMap[buttonSize],
			height: BUTTON_SIZES[buttonSize],
			paddingLeft: paddingHorizontal,
			paddingRight: paddingHorizontal,
		};

		if (stretched)
		{
			style.width = stretched ? '100%' : null;
			style.alignItems = stretched ? 'center' : 'auto';
		}

		if (border)
		{
			style.borderWidth = 1.2;
			style.borderColor = borderColor;
		}

		if (rounded)
		{
			style.borderRadius = Corner[CornerTypes.circle];
		}

		if (disabled)
		{
			const isFill = Boolean(backgroundColor);
			buttonTextColor = getDisabledColor('color', isFill);

			if (backgroundColor)
			{
				style.backgroundColor = getDisabledColor('backgroundColor', isFill);
			}

			if (border)
			{
				style.borderColor = getDisabledColor('borderColor', isFill);
			}
		}

		const fontSize = FONT_SIZES[buttonSize];
		const mainProps = mergeImmutable({ style }, restProps);
		const textIndent = textPaddingMap[buttonSize];

		return View(
			mainProps,
			PlainView({
				text,
				after,
				before,
				fontSize,
				indent: textIndent,
				color: buttonTextColor,
			}),
		);
	};

	Button.defaultProps = {
		stretched: false,
		rounded: false,
		border: false,
		text: null,
		after: null,
		before: null,
		size: BUTTON_SIZES.XL,
		color: Color.baseWhiteFixed,
		backgroundColor: Color.accentMainPrimary,
	};

	Button.propTypes = {
		stretched: PropTypes.bool,
		rounded: PropTypes.bool,
		border: PropTypes.bool,
		text: PropTypes.string,
		after: PropTypes.object,
		before: PropTypes.object,
		size: PropTypes.string,
		color: PropTypes.string,
		borderColor: PropTypes.string,
		backgroundColor: PropTypes.string,
	};

	module.exports = {
		Button,
		buttonTypes,
		getButtonSize,
		getDisabledColor,
		getInternalIndents,
	};
});
