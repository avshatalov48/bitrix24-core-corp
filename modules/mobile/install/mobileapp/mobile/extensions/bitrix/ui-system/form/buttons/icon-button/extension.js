/**
 * @module ui-system/form/buttons/icon-button
 */
jn.define('ui-system/form/buttons/icon-button', (require, exports, module) => {
	const { PropTypes } = require('utils/validation');
	const { Color, Indent, IndentTypes } = require('tokens');
	const { IconView, iconTypes } = require('ui-system/blocks/icon');
	const { Button, getButtonSize, buttonTypes } = require('ui-system/form/buttons/button');

	const iconSizeMap = {
		[buttonTypes.XL]: 28,
		[buttonTypes.L]: 28,
		[buttonTypes.M]: 24,
		[buttonTypes.S]: 24,
		[buttonTypes.XS]: 20,
	};

	const paddingHorizontalMap = {
		XL: Indent[IndentTypes.XL],
		L: Indent[IndentTypes.L],
		M: Indent[IndentTypes.L],
		S: Indent[IndentTypes.S],
		XS: Indent[IndentTypes.XS],
	};

	const squaredPaddingHorizontalMap = {
		XL: Indent[IndentTypes.L],
		L: Indent[IndentTypes.M],
		M: Indent[IndentTypes.S],
		S: Indent[IndentTypes.XS2],
		XS: Indent[IndentTypes.XS2],
	};

	/**
	 * @param {buttonTypes} size
	 * @param {boolean} squared
	 * @return number
	 */
	const getInternalIndents = (
		size = buttonTypes.M,
		squared = false,
	) => (squared ? squaredPaddingHorizontalMap[size] : paddingHorizontalMap[size]);

	/**
	 * @function IconButton
	 * @param {object} props
	 * @param {string} props.text
	 * @param {string} props.size
	 * @param {string} props.color
	 * @param {boolean} props.stretched
	 * @param {boolean} props.rounded
	 * @param {boolean} props.disabled
	 * @param {string} props.leftIcon
	 * @param {string} props.leftIconColor
	 * @param {string} props.rightIcon
	 * @param {string} props.rightIconColor
	 * @param {string} props.backgroundColor
	 * @param {object} props.style
	 * @return {IconButton}
	 */
	const IconButton = (props) => {
		const {
			text,
			size,
			color,
			stretched,
			disabled,
			leftIcon,
			leftIconColor,
			rightIcon,
			rightIconColor,
			backgroundColor,
			style = {},
			...restProps
		} = props;

		const buttonSize = getButtonSize(size);
		const iconSize = iconSizeMap[buttonSize];

		let after = null;
		let before = null;
		let buttonStretched = stretched;

		const getIconColor = (iconColor) => iconColor || color;

		if (leftIcon)
		{
			after = IconView({
				icon: leftIcon,
				color: getIconColor(leftIconColor),
				size: iconSize,
				disabled,
			});

			style.paddingLeft = getInternalIndents(buttonSize);
		}

		if (rightIcon)
		{
			before = IconView({
				icon: rightIcon,
				color: getIconColor(rightIconColor),
				size: iconSize,
				disabled,
			});

			style.paddingRight = getInternalIndents(buttonSize);
		}

		const isSquared = (leftIcon || rightIcon) && !text;

		if (isSquared)
		{
			const paddingHorizontal = getInternalIndents(buttonSize, isSquared);
			style.paddingLeft = paddingHorizontal;
			style.paddingRight = paddingHorizontal;
			buttonStretched = false;
		}

		return Button({
			after,
			before,
			text,
			style,
			color,
			disabled,
			backgroundColor,
			size: buttonSize,
			stretched: buttonStretched,
			...restProps,
		});
	};

	IconButton.defaultProps = {
		size: buttonTypes.M,
		leftIcon: null,
		leftIconColor: Color.base1,
		rightIcon: null,
		rightIconColor: Color.base1,
	};

	IconButton.propTypes = {
		size: PropTypes.string,
		leftIcon: PropTypes.string,
		leftIconColor: PropTypes.string,
		rightIcon: PropTypes.string,
		rightIconColor: PropTypes.string,
	};

	module.exports = { IconButton, iconTypes, buttonTypes };
});
