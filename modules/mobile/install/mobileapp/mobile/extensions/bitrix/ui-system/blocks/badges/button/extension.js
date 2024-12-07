/**
 * @module ui-system/blocks/badges/button
 */
jn.define('ui-system/blocks/badges/button', (require, exports, module) => {
	const { Component, Color } = require('tokens');
	const { mergeImmutable } = require('utils/object');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { PropTypes } = require('utils/validation');
	const { BadgeButtonDesign } = require('ui-system/blocks/badges/button/src/design-enum');
	const { BadgeButtonSize } = require('ui-system/blocks/badges/button/src/size-enum');

	/**
	 * @function BadgeButton
	 * @param {Object} props
	 * @param {string} props.testId
	 * @param {string} props.icon
	 * @param {boolean} [props.stroke]
	 * @param {BadgeButtonDesign} [props.design]
	 * @param {BadgeButtonSize} [props.size]
	 */
	const BadgeButton = (props = {}) => {
		PropTypes.validate(BadgeButton.propTypes, props, 'BadgeButton');

		const {
			testId,
			icon = null,
			design = BadgeButtonDesign.GREY,
			size = BadgeButtonSize.M,
			stroke = true,
			...restProps
		} = props;

		if (!BadgeButtonDesign.has(design))
		{
			console.warn('BadgeButton: button design not selected');

			return null;
		}

		const buttonDesign = BadgeButtonDesign.resolve(design, BadgeButtonDesign.GREY);
		const buttonSize = BadgeButtonSize.resolve(size, BadgeButtonSize.NORMAL);
		const backgroundSize = buttonSize.getBackgroundSize();
		const iconSize = buttonSize.getIconSize();
		const outlineIcon = Icon.resolve(icon, Icon.CROSS);

		const style = {
			width: backgroundSize,
			height: backgroundSize,
			alignItems: 'center',
			justifyContent: 'center',
			borderRadius: Component.elementAccentCorner.toNumber(),
			backgroundColor: buttonDesign.getBackgroundColor().toHex(),
		};

		if (stroke)
		{
			style.borderWidth = 1;
			style.borderColor = buttonDesign.getBorderColor().toHex();
		}

		const viewProps = mergeImmutable({
			testId: testId && `${testId}_${design.getName()}`,
			style,
		}, restProps);

		return View(
			viewProps,
			outlineIcon && IconView({
				color: Color.resolve(buttonDesign.getColor(), Color.base4),
				icon: outlineIcon,
				size: iconSize,
			}),
		);
	};

	BadgeButton.defaultProps = {
		icon: null,
		stroke: true,
	};

	BadgeButton.propTypes = {
		testId: PropTypes.string.isRequired,
		design: PropTypes.object.isRequired,
		icon: PropTypes.object,
		size: PropTypes.object,
		stroke: PropTypes.bool,
	};

	module.exports = {
		BadgeButton,
		BadgeButtonDesign,
		BadgeButtonSize,
		Icon,
	};
});
