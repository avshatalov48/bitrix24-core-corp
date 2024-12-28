/**
 * @module ui-system/form/buttons/button
 */
jn.define('ui-system/form/buttons/button', (require, exports, module) => {
	const { Type } = require('type');
	const { Component, Color } = require('tokens');
	const { isLightColor } = require('utils/color');
	const { capitalize } = require('utils/string');
	const { Ellipsize } = require('utils/enums/style');
	const { mergeImmutable } = require('utils/object');
	const { Text } = require('ui-system/typography/text');
	const { SpinnerLoader, SpinnerDesign } = require('layout/ui/loaders/spinner');
	const { PropTypes } = require('utils/validation');
	const { IconView, iconTypes, Icon } = require('ui-system/blocks/icon');
	const { ButtonSize } = require('ui-system/form/buttons/button/src/size-enum');
	const { ButtonDesign } = require('ui-system/form/buttons/button/src/design-enum');

	const Direction = {
		LEFT: 'left',
		RIGHT: 'right',
	};

	/**
	 * @typedef {Object} ButtonProps
	 * @property {Function} [forwardRef]
	 * @property {string} testId
	 * @property {string} [text]
	 * @property {Icon} [leftIcon]
	 * @property {Color} [leftIconColor]
	 * @property {Icon} [rightIcon]
	 * @property {Color} [rightIconColor]
	 * @property {ButtonSize} [size]
	 * @property {Ellipsize} [ellipsize]
	 * @property {ButtonDesign} [design=ButtonDesign.FILLED]
	 * @property {SpinnerDesign} [loaderDesign]
	 * @property {object} [badge]
	 * @property {boolean} [stretched]
	 * @property {boolean} [rounded]
	 * @property {boolean} [border]
	 * @property {boolean} [loading]
	 * @property {boolean} [disabled]
	 * @property {Color} [color]
	 * @property {Color} [borderColor]
	 * @property {number} [borderRadius]
	 * @property {Color} [backgroundColor]
	 * @property {ElementStyle} [style]
	 * @property {function} [onClick]
	 * @property {Function} [onLayout]
	 * @property {Function} [onDisabledClick]
	 * @property {Function} [onLongClick]
	 *
	 * @class Button
	 * @param {ButtonProps} props
	 * @returns {Button}
	 */
	class Button extends LayoutComponent
	{
		/**
		 * @return {ButtonSize}
		 */
		get size()
		{
			const { size } = this.props;

			return ButtonSize.resolve(size, ButtonSize.XL);
		}

		/**
		 * @return {ButtonDesign}
		 */
		get design()
		{
			const { design } = this.props;

			return ButtonDesign.resolve(design, ButtonDesign.FILLED);
		}

		get designStyle()
		{
			return this.design.getStyle();
		}

		render()
		{
			const { testId, forwardRef, style = {}, onLayout } = this.props;

			if (!this.shouldRenderButton())
			{
				return null;
			}

			const mainProps = mergeImmutable(
				{
					onLayout,
					style: this.getMainStyle(),
				},
				{ style },
			);

			return View(
				mainProps,
				View(
					{
						testId,
						ref: forwardRef,
						style: this.getButtonStyle(),
						onClick: this.#handleOnClick,
						onLongClick: this.#handleOnLongClick,
					},
					this.#renderBody(),
				),
			);
		}

		shouldRenderButton()
		{
			const { text, leftIcon, rightIcon } = this.props;

			return text || leftIcon || rightIcon;
		}

		isSquared()
		{
			const { leftIcon, rightIcon, text } = this.props;

			return (leftIcon || rightIcon) && !text;
		}

		#renderBody()
		{
			const { leftIcon, rightIcon, badge } = this.props;

			return View(
				{
					style: {
						flexShrink: 1,
						position: 'relative',
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				this.#renderLoader(),
				View(
					{
						style: {
							flexShrink: 1,
							flexDirection: 'row',
							alignItems: 'center',
							opacity: this.isLoading() ? 0 : 1,
						},
					},
					this.#renderIcon({
						icon: leftIcon,
						direction: Direction.LEFT,
					}),
					this.#renderText(),
					this.#renderIcon({
						icon: rightIcon,
						direction: Direction.RIGHT,
						badge,
					}),
					this.#renderBadge(),
				),
			);
		}

		#renderText()
		{
			const { text, leftIcon, rightIcon, badge } = this.props;

			if (!text)
			{
				return null;
			}

			const { accent, size } = this.size.getTypography();

			return Text({
				accent,
				size,
				text,
				color: this.getColor(),
				numberOfLines: 1,
				ellipsize: this.#getEllipsize(),
				style: {
					flexShrink: 1,
					marginLeft: this.size.getTextIndents({ icon: Boolean(leftIcon) }),
					marginRight: this.size.getTextIndents({ icon: Boolean(rightIcon), badge }),
				},
			});
		}

		#renderIcon({ icon, direction, badge })
		{
			if (!icon)
			{
				return null;
			}

			let style = {
				flexGrow: 1,
			};

			if (direction)
			{
				style[`margin${capitalize(direction)}`] = this.size.getIconIndents({ badge });
			}

			if (this.isSquared())
			{
				const horizontal = this.size.getIconIndents({ squared: true });

				style = {
					marginRight: horizontal,
					marginLeft: horizontal,
				};
			}

			return IconView({
				icon,
				size: this.size.getIconSize(),
				color: this.getIconColor(direction),
				style,
			});
		}

		#renderBadge()
		{
			const { badge } = this.props;

			if (!badge)
			{
				return null;
			}

			return View(
				{
					style: {
						marginRight: this.size.getBadgeIndent(),
					},
				},
				badge,
			);
		}

		#renderLoader()
		{
			if (!this.isLoading())
			{
				return null;
			}

			return SpinnerLoader({
				size: this.size.getIconSize(),
				design: this.#getLoaderDesign(),
				style: {
					position: 'absolute',
				},
			});
		}

		#getLoaderDesign()
		{
			const { loaderDesign } = this.props;
			const backgroundColor = this.getBackgroundColor();
			const hex = typeof backgroundColor === 'object' ? backgroundColor?.default : backgroundColor;
			const design = !hex || isLightColor(hex)
				? SpinnerDesign.BLUE
				: SpinnerDesign.WHITE;

			return SpinnerDesign.resolve(loaderDesign, design);
		}

		#handleOnClick = () => {
			const { onClick, onDisabledClick } = this.props;

			if (onClick && !this.#isDisabled())
			{
				onClick();
			}

			if (onDisabledClick && this.#isDisabled())
			{
				onDisabledClick();
			}
		};

		#handleOnLongClick = () => {
			const { onLongClick } = this.props;

			if (onLongClick && !this.#isDisabled())
			{
				onLongClick();
			}
		};

		getMainStyle()
		{
			return {
				flexShrink: 1,
				flexDirection: 'row',
				alignItems: 'flex-start',
				...this.getStretchedStyle(),
			};
		}

		getButtonStyle()
		{
			return {
				flexDirection: 'row',
				flexShrink: 1,
				backgroundColor: this.getBackgroundColor(),
				justifyContent: 'center',
				height: this.size.getHeight(),
				...this.getBorderStyle(),
				...this.getStretchedStyle(),
			};
		}

		getBorderStyle()
		{
			const { borderColor: designBorderColor } = this.designStyle;
			const { border, borderColor, borderRadius, design, rounded } = this.props;
			const { radius } = this.size.getBorder();

			const style = {
				borderRadius: radius.toNumber(),
			};

			if (Type.isNumber(borderRadius))
			{
				style.borderRadius = borderRadius;
			}

			if (rounded)
			{
				style.borderRadius = Component.elementAccentCorner.toNumber();
			}

			if ((design && designBorderColor) || (border && borderColor))
			{
				const { width: borderWidth } = this.size.getBorder();
				let buttonBorderColor = designBorderColor;

				if (borderColor)
				{
					buttonBorderColor = borderColor;
				}

				if (this.#isDisabled())
				{
					buttonBorderColor = this.getDisabledStyle().borderColor;
				}

				const opacity = designBorderColor
					? this.design.getOpacity('borderColor')
					: null;

				style.borderWidth = borderWidth;
				style.borderColor = buttonBorderColor.toHex(opacity);
			}

			return style;
		}

		/**
		 * @return {ColorEnum}
		 */
		getColor()
		{
			let { color: designColor } = this.designStyle;
			const { color: propsColor } = this.props;

			if (this.#isDisabled())
			{
				designColor = this.getDisabledStyle().color;
			}

			return Color.resolve(propsColor, designColor);
		}

		/**
		 * @param {Direction} direction
		 * @return {ColorEnum}
		 */
		getIconColor(direction)
		{
			const iconColor = this.props[`${direction}IconColor`];

			return Color.resolve(iconColor, this.getColor());
		}

		getBackgroundColor()
		{
			const { backgroundColor: designBackgroundColor } = this.designStyle;
			const { backgroundColor } = this.props;
			const background = backgroundColor || designBackgroundColor;

			if (background)
			{
				const disabledColor = this.getDisabledStyle().backgroundColor.toHex();

				return this.#isDisabled()
					? {
						default: disabledColor,
						pressed: disabledColor,
					}
					: background?.withPressed();
			}

			return designBackgroundColor?.withPressed();
		}

		getDisabledStyle()
		{
			return this.design.getDisabled().getStyle();
		}

		getStretchedStyle()
		{
			if (!this.isStretched())
			{
				return {};
			}

			return {
				flexShrink: 1,
				width: '100%',
			};
		}

		#getEllipsize()
		{
			const { ellipsize } = this.props;

			return Ellipsize.resolve(ellipsize, Ellipsize.END).toString();
		}

		isStretched()
		{
			const { stretched = false } = this.props;

			return stretched;
		}

		isLoading()
		{
			const { loading } = this.props;

			return Boolean(loading);
		}

		#isDisabled()
		{
			const { disabled } = this.props;

			return Boolean(disabled);
		}
	}

	Button.defaultProps = {
		stretched: false,
		rounded: false,
		border: false,
		loading: false,
	};

	Button.propTypes = {
		forwardRef: PropTypes.func,
		testId: PropTypes.string.isRequired,
		text: PropTypes.string,
		leftIcon: PropTypes.instanceOf(Icon),
		leftIconColor: PropTypes.instanceOf(Color),
		rightIcon: PropTypes.instanceOf(Icon),
		rightIconColor: PropTypes.instanceOf(Color),
		size: PropTypes.instanceOf(ButtonSize),
		ellipsize: PropTypes.instanceOf(Ellipsize),
		design: PropTypes.instanceOf(ButtonDesign),
		loaderDesign: PropTypes.instanceOf(SpinnerDesign),
		badge: PropTypes.object,
		stretched: PropTypes.bool,
		rounded: PropTypes.bool,
		border: PropTypes.bool,
		loading: PropTypes.bool,
		disabled: PropTypes.bool,
		color: PropTypes.instanceOf(Color),
		borderColor: PropTypes.instanceOf(Color),
		backgroundColor: PropTypes.instanceOf(Color),
		borderRadius: PropTypes.number,
		style: PropTypes.object,
		onClick: PropTypes.func,
		onLayout: PropTypes.func,
		onDisabledClick: PropTypes.func,
		onLongClick: PropTypes.func,
	};

	module.exports = {
		/**
		 * @param {ButtonProps} props
		 * @returns {Button}
		 */
		Button: (props) => new Button(props),
		ButtonClass: Button,
		ButtonDesign,
		ButtonSize,
		LoaderDesign: SpinnerDesign,
		Icon,
		IconTypes: iconTypes,
		Ellipsize,
	};
});
