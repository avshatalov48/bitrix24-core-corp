/**
 * @module ui-system/blocks/chips/chip-button
 */
jn.define('ui-system/blocks/chips/chip-button', (require, exports, module) => {
	const { Indent, Component, Color } = require('tokens');
	const { Ellipsize } = require('utils/enums/style');
	const { mergeImmutable } = require('utils/object');
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
	 * @property {boolean} [text]
	 * @property {Icon} [icon]
	 * @property {boolean} [compact]
	 * @property {ChipButtonMode} [mode=ChipButtonMode.SOLID]
	 * @property {ChipButtonDesign} [design=ChipButtonDesign.PRIMARY]
	 * @property {Ellipsize} [ellipsize]
	 * @property {BadgeStatus | BadgeCounter} [badge]
	 * @property {Function} [forwardRef]
	 * @property {Color} [backgroundColor]
	 *
	 * @class ChipButton
	 * @return ChipButton
	 */
	class ChipButton extends LayoutComponent
	{
		/**
		 * @param {ChipButtonProps} props
		 */
		constructor(props)
		{
			super(props);

			this.style = {};
			this.size = {};

			this.#initStyle(props);
		}

		componentWillReceiveProps(props)
		{
			this.#initStyle(props);
		}

		#initStyle(props)
		{
			const { design, mode, compact, disabled } = props;

			const finalDesign = disabled
				? design.getDisabled()
				: ChipButtonDesign.resolve(design, ChipButtonDesign.PRIMARY);

			this.style = finalDesign.getStyle(mode);
			this.size = compact ? ChipButtonSize.SMALL : ChipButtonSize.NORMAL;
		}

		#renderText()
		{
			const { text } = this.props;

			if (!text)
			{
				return null;
			}

			const { color } = this.style;
			const Typography = this.size.getTypography();

			return Typography({
				text,
				color,
				ellipsize: this.#getEllipsize(),
				numberOfLines: 1,
				style: {
					flexShrink: 1,
				},
			});
		}

		#renderIcon()
		{
			const { icon, text } = this.props;

			if (!icon)
			{
				return null;
			}

			const { color } = this.style;

			const iconStyle = {
				marginRight: text ? Indent.XS2.toNumber() : 0,
			};

			return IconView({
				color,
				icon,
				style: iconStyle,
			});
		}

		#renderDropdown()
		{
			const { dropdown } = this.props;
			if (!dropdown)
			{
				return null;
			}

			const { color } = this.style;

			return IconView({
				color,
				icon: Icon.CHEVRON_DOWN,
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
				this.#renderIcon(),
				this.#renderText(),
				this.#renderBadge(),
				this.#renderDropdown(),
			);
		}

		getBodyStyle()
		{
			const { backgroundColor = null } = this.props;
			const { color, ...chipStyle } = this.style;

			const style = {
				flexShrink: 1,
				flexDirection: 'row',
				alignItems: 'center',
				height: this.size.getHeight(),
				borderRadius: Component.elementAccentCorner.toNumber(),
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

		isOnlyIcon()
		{
			const { icon, dropdown, text } = this.props;

			return (icon || dropdown) && !text;
		}
	}

	ChipButton.defaultProps = {
		compact: false,
	};

	ChipButton.propTypes = {
		testId: PropTypes.string.isRequired,
		text: PropTypes.string,
		compact: PropTypes.bool,
		badge: PropTypes.object,
		dropdown: PropTypes.bool,
		forwardRef: PropTypes.func,
		icon: PropTypes.instanceOf(Icon),
		design: PropTypes.instanceOf(ChipButtonDesign),
		mode: PropTypes.instanceOf(ChipButtonMode),
		ellipsize: PropTypes.instanceOf(Ellipsize),
		backgroundColor: PropTypes.instanceOf(Color),

	};

	module.exports = {
		/**
		 * @param {ChipButtonProps} props
		 */
		ChipButton: (props) => new ChipButton(props),
		ChipButtonDesign,
		ChipButtonMode,
		Ellipsize,
		iconTypes,
	};
});
