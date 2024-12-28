/**
 * @module ui-system/blocks/chips/chip-button
 */
jn.define('ui-system/blocks/chips/chip-button', (require, exports, module) => {
	const { Indent, Component, Color } = require('tokens');
	const { Ellipsize } = require('utils/enums/style');
	const { mergeImmutable } = require('utils/object');
	const { PureComponent } = require('layout/pure-component');
	const { IconView, Icon, iconTypes } = require('ui-system/blocks/icon');
	const { ChipButtonDesign } = require('ui-system/blocks/chips/chip-button/src/design-enum');
	const { ChipButtonMode } = require('ui-system/blocks/chips/chip-button/src/mode-enum');
	const { ChipButtonSize } = require('ui-system/blocks/chips/chip-button/src/size-enum');

	const Direction = {
		LEFT: 'left',
		RIGHT: 'right',
	};

	/**
	 * @typedef {Object} ChipButtonProps
	 * @property {string} [text]
	 * @property {Icon} [icon]
	 * @property {boolean} [dropdown]
	 * @property {boolean} [compact]
	 * @property {ChipButtonMode} [mode=ChipButtonMode.SOLID]
	 * @property {ChipButtonDesign} [design=ChipButtonDesign.PRIMARY]
	 * @property {Ellipsize} [ellipsize]
	 * @property {BadgeStatus | BadgeCounter} [badge]
	 * @property {Avatar | AvatarStack} [avatar]
	 * @property {Function} [forwardRef]
	 * @property {Color} [backgroundColor]
	 *
	 * @class ChipButton
	 */
	class ChipButton extends PureComponent
	{
		/**
		 * @param {ChipButtonProps} props
		 */
		constructor(props)
		{
			super(props);

			this.initStyle(props);
		}

		componentWillReceiveProps(props)
		{
			this.initStyle(props);
		}

		initStyle(props)
		{
			const { compact } = props;

			this.design = this.getDesign(props);
			this.size = compact ? ChipButtonSize.SMALL : ChipButtonSize.NORMAL;
		}

		#renderText()
		{
			const text = this.getText();

			if (!text)
			{
				return null;
			}

			const Typography = this.getTypography();

			return Typography({
				text,
				testId: this.getTestId('value'),
				color: this.getColor(),
				ellipsize: this.#getEllipsize(),
				numberOfLines: 1,
				style: {
					flexShrink: 1,
				},
			});
		}

		#renderLeftContent()
		{
			const { icon, avatar } = this.props;

			if (!icon && !avatar)
			{
				return null;
			}

			const hasText = Boolean(this.getText());

			const style = {
				marginRight: hasText ? Indent.XS2.toNumber() : 0,
			};

			return avatar
				? this.#renderAvatar({ style, testId: 'avatar' })
				: this.#renderIcon({ icon, style, testId: 'left-icon' });
		}

		#renderAvatar({ style, testId })
		{
			const { avatar } = this.props;

			if (avatar?.props?.size > 20)
			{
				console.warn('ChipButton: The size of the avatar should not exceed 20px according to the design system.');
			}

			return View(
				{
					style,
					testId: this.getTestId(testId),
				},
				avatar,
			);
		}

		#renderDropdown()
		{
			const { dropdown } = this.props;

			if (!dropdown)
			{
				return null;
			}

			return this.#renderIcon({
				icon: Icon.CHEVRON_DOWN_SIZE_S,
				testId: 'dropdown',
			});
		}

		#renderIcon({ style, icon, testId })
		{
			return IconView({
				color: this.getIconColor(),
				icon,
				style,
				testId: this.getTestId(testId),
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
						flexGrow: 1,
						marginLeft: Indent.XS.toNumber(),
					},
				},
				badge,
			);
		}

		render()
		{
			const { testId, style = {}, onLayout, forwardRef } = this.props;
			const renderProps = mergeImmutable(
				{
					ref: forwardRef,
					testId,
					onLayout,
					style: {
						flexDirection: 'row',
						flexShrink: 1,
					},
				},
				{ style },
			);

			return View(
				renderProps,
				this.#renderBody(),
			);
		}

		#renderBody()
		{
			return View(
				{
					onClick: this.#handleOnClick,
					style: this.getBodyStyle(),
				},
				this.#renderLeftContent(),
				this.#renderText(),
				this.#renderBadge(),
				this.#renderDropdown(),
			);
		}

		getTypography()
		{
			return this.size.getTypography();
		}

		getBodyStyle()
		{
			const { backgroundColor = null } = this.props;
			const { color, ...chipStyle } = this.design;

			const style = {
				flexShrink: 1,
				flexDirection: 'row',
				alignItems: 'center',
				height: this.size.getHeight(),
				borderRadius: this.#getBorderRadius(),
				paddingLeft: this.#getInternalPadding(Direction.LEFT),
				paddingRight: this.#getInternalPadding(Direction.RIGHT),
				paddingHorizontal: Indent.L.toNumber(),
				...chipStyle,
			};

			if (backgroundColor)
			{
				style.backgroundColor = backgroundColor?.toHex();
			}

			return style;
		}

		#handleOnClick = () => {
			const { onClick } = this.props;

			if (onClick)
			{
				onClick();
			}
		};

		/**
		 * @param {string} direction
		 * @return {number}
		 */
		#getInternalPadding(direction)
		{
			const { icon, dropdown } = this.props;

			if (dropdown && direction === Direction.RIGHT)
			{
				return this.size.getIndent(direction, 'dropdown');
			}

			if ((icon && direction === Direction.LEFT) || this.isOnlyIcon())
			{
				return this.size.getIndent(direction, 'icon');
			}

			return this.size.getIndent(direction, 'text');
		}

		#getEllipsize()
		{
			const { ellipsize } = this.props;

			return Ellipsize.resolve(ellipsize, Ellipsize.END).toString();
		}

		#getBorderRadius()
		{
			const borderRadius = this.isRounded()
				? Component.elementAccentCorner
				: this.size.getRadius();

			return borderRadius.toNumber();
		}

		getDesign(props)
		{
			const { design, disabled, mode } = props;

			if (design === null)
			{
				return {};
			}

			const finalDesign = disabled
				? design.getDisabled()
				: ChipButtonDesign.resolve(design, ChipButtonDesign.PRIMARY);

			return finalDesign.getStyle(mode);
		}

		getColor()
		{
			return this.design?.color;
		}

		getIconColor()
		{
			return this.getColor();
		}

		getText()
		{
			const { text } = this.props;

			return text;
		}

		getTestId(suffix)
		{
			const { testId } = this.props;

			return [testId, suffix].join('-').trim();
		}

		isOnlyIcon()
		{
			const { icon, dropdown, text } = this.props;

			return (icon || dropdown) && !text;
		}

		isRounded()
		{
			const { rounded = true } = this.props;

			return Boolean(rounded);
		}
	}

	ChipButton.defaultProps = {
		compact: false,
		rounded: true,
	};

	ChipButton.propTypes = {
		testId: PropTypes.string.isRequired,
		text: PropTypes.string,
		compact: PropTypes.bool,
		badge: PropTypes.object,
		avatar: PropTypes.object,
		rounded: PropTypes.bool,
		dropdown: PropTypes.bool,
		forwardRef: PropTypes.func,
		icon: PropTypes.instanceOf(Icon),
		design: PropTypes.instanceOf(ChipButtonDesign),
		mode: PropTypes.instanceOf(ChipButtonMode),
		ellipsize: PropTypes.instanceOf(Ellipsize),
		color: PropTypes.instanceOf(Color),
		backgroundColor: PropTypes.instanceOf(Color),
	};

	module.exports = {
		/**
		 * @param {ChipButtonProps} props
		 */
		ChipButton: (props) => new ChipButton(props),
		ChipButtonClass: ChipButton,
		ChipButtonDesign,
		ChipButtonMode,
		ChipButtonSize,
		Ellipsize,
		iconTypes,
	};
});
