/**
 * @module ui-system/blocks/chips/chip-button
 */
jn.define('ui-system/blocks/chips/chip-button', (require, exports, module) => {
	const { Indent, Component, Color } = require('tokens');
	const { Ellipsize } = require('utils/enums/style');
	const { Type } = require('type');
	const { mergeImmutable } = require('utils/object');
	const { PureComponent } = require('layout/pure-component');
	const { SpinnerLoader, SpinnerDesign } = require('layout/ui/loaders/spinner');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { ChipButtonDesign } = require('ui-system/blocks/chips/chip-button/src/design-enum');
	const { ChipButtonMode } = require('ui-system/blocks/chips/chip-button/src/mode-enum');
	const { ChipButtonSize } = require('ui-system/blocks/chips/chip-button/src/size-enum');
	const { LoaderPosition } = require('ui-system/blocks/chips/chip-button/src/loader-position-enum');

	const Direction = {
		LEFT: 'left',
		RIGHT: 'right',
	};

	/**
	 * @typedef {Object} ChipButtonProps
	 * @property {string} [text]
	 * @property {Object} [content]
	 * @property {Icon} [icon]
	 * @property {boolean} [loading=false]
	 * @property {boolean} [dropdown=false]
	 * @property {boolean} [compact=false]
	 * @property {boolean} [rounded=true]
	 * @property {boolean} [disabled=false]
	 * @property {ChipButtonMode} [mode=ChipButtonMode.SOLID]
	 * @property {ChipButtonDesign} [design=ChipButtonDesign.PRIMARY]
	 * @property {Ellipsize} [ellipsize]
	 * @property {BadgeStatus | BadgeCounter} [badge]
	 * @property {Avatar | AvatarStack} [avatar]
	 * @property {Function} [forwardRef]
	 * @property {Color} [backgroundColor]
	 * @property {Color} [iconColor]
	 * @property {SpinnerDesign} [loaderDesign]
	 * @property {Function} [onClick]
	 * @property {Function} [onLongClick]
	 * @property {Function} [onLayout]
	 * @property {string} [testId]
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

			if (!Type.isStringFilled(text))
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

		#renderContent()
		{
			return this.getContent();
		}

		#renderLeftContent()
		{
			const hasLeftContent = this.hasText() || this.hasContent();

			if (this.isLoading() && this.getLoaderPosition().isLeft())
			{
				return this.#renderLoader({
					style: {
						marginRight: hasLeftContent ? Indent.XS.toNumber() : 0,
					},
				});
			}

			if (!this.hasLeftIcon() && !this.hasAvatar())
			{
				return null;
			}

			return this.hasAvatar()
				? this.#renderAvatar({
					style: {
						marginRight: hasLeftContent ? Indent.XS.toNumber() : 0,
					},
					testId: 'avatar',
				})
				: this.#renderIcon({
					style: {
						marginRight: hasLeftContent ? Indent.XS2.toNumber() : 0,
					},
					testId: 'left-icon',
					icon: this.getLeftIcon(),
				});
		}

		#renderAvatar({ style, testId })
		{
			const avatar = this.getAvatar();
			const avatarSize = this.size.getAvatar().size;

			if (avatar?.props?.size > 20)
			{
				console.warn(`ChipButton: The size of the avatar should not exceed ${avatarSize}px according to the design system.`);
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
			if (!this.isDropdown())
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
				icon,
				style,
				color: this.getIconColor(),
				size: this.getImageSize(),
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
			const elements = [
				this.#renderLeftContent(),
				this.#renderText(),
				this.#renderContent(),
				this.#renderBadge(),
				this.#renderDropdown(),
			];

			if (this.isLoading() && this.getLoaderPosition().isCenter())
			{
				return this.#renderBodyWrapper([
					this.#renderLoader({
						style: {
							position: 'absolute',
						},
					}),
					View(
						{
							style: {
								flexShrink: 1,
								flexDirection: 'row',
								alignItems: 'center',
								opacity: 0,
							},
						},
						...elements,
					),
				]);
			}

			return this.#renderBodyWrapper(elements);
		}

		#renderBodyWrapper(elements = [])
		{
			const { onClick, onLongClick } = this.props;

			return View(
				{
					onClick,
					onLongClick,
					style: this.getBodyStyle(),
				},
				...elements,
			);
		}

		getTypography()
		{
			return this.size.getTypography();
		}

		getBodyStyle()
		{
			const { color, backgroundColor, ...chipStyle } = this.design;

			return {
				flexShrink: 1,
				position: 'relative',
				flexDirection: 'row',
				alignItems: 'center',
				justifyContent: 'center',
				height: this.size.getHeight(),
				borderRadius: this.#getBorderRadius(),
				paddingLeft: this.#getInternalPadding(Direction.LEFT),
				paddingRight: this.#getInternalPadding(Direction.RIGHT),
				paddingHorizontal: Indent.L.toNumber(),
				backgroundColor: this.getBackgroundColor(),
				...chipStyle,
			};
		}

		/**
		 * @param {string} direction
		 * @return {number}
		 */
		#getInternalPadding(direction)
		{
			if (this.isOnlyIcon())
			{
				return this.size.getIconIndents();
			}

			if (this.isDropdown() && direction === Direction.RIGHT)
			{
				return this.size.getIndent(direction, 'dropdown');
			}

			if (direction === Direction.LEFT)
			{
				if (this.hasAvatar())
				{
					return this.size.getIndent(direction, 'avatar');
				}

				if (this.hasLeftIcon())
				{
					return this.size.getIndent(direction, 'icon');
				}
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

		#renderLoader({ style = {} })
		{
			if (!this.isLoading())
			{
				return null;
			}

			const size = this.getImageSize();

			return View(
				{
					style: {
						...style,
						width: size,
						height: size,
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				SpinnerLoader({
					size: 16,
					testId: this.getTestId('spinner-loader'),
					design: this.#getLoaderDesign(),
				}),
			);
		}

		getDesign(props)
		{
			const { design, mode, disabled } = props;

			if (design === null)
			{
				return {};
			}

			const finalDesign = disabled
				? design.getDisabled()
				: ChipButtonDesign.resolve(design, ChipButtonDesign.PRIMARY);

			return finalDesign.getStyle(mode);
		}

		#getLoaderDesign()
		{
			const { loaderDesign } = this.props;
			const backgroundColor = this.getBackgroundColor();
			const spinnerDesign = SpinnerDesign.resolveByColor(backgroundColor);

			return SpinnerDesign.resolve(loaderDesign, spinnerDesign);
		}

		getBackgroundColor()
		{
			const { backgroundColor: propsBackgroundColor } = this.props;
			const { backgroundColor: designBackgroundColor } = this.design;
			const backgroundColor = propsBackgroundColor || designBackgroundColor;

			if (backgroundColor)
			{
				return backgroundColor.withPressed?.();
			}

			return null;
		}

		getColor()
		{
			return this.design?.color;
		}

		getIconColor()
		{
			const { iconColor } = this.props;

			return iconColor || this.getColor();
		}

		getLeftIcon()
		{
			const { icon } = this.props;

			return icon;
		}

		getText()
		{
			const { text } = this.props;

			return text;
		}

		hasText()
		{
			return Boolean(this.getText());
		}

		getTestId(suffix)
		{
			const { testId } = this.props;

			return [testId, suffix].join('-').trim();
		}

		getImageSize()
		{
			return 20;
		}

		isOnlyIcon()
		{
			return (this.hasLeftIcon() || this.isDropdown() || this.hasAvatar())
				&& !this.hasText()
				&& !this.hasContent();
		}

		isRounded()
		{
			const { rounded = true } = this.props;

			return Boolean(rounded);
		}

		isDisabled()
		{
			const { disabled = false } = this.props;

			return Boolean(disabled);
		}

		isLoading()
		{
			const { loading } = this.props;

			return Boolean(loading);
		}

		isDropdown()
		{
			const { dropdown } = this.props;

			return Boolean(dropdown);
		}

		hasAvatar()
		{
			return Boolean(this.getAvatar());
		}

		getAvatar()
		{
			const { avatar } = this.props;

			return avatar;
		}

		hasLeftIcon()
		{
			return Boolean(this.getLeftIcon());
		}

		getLoaderPosition()
		{
			return this.hasLeftIcon() || this.hasAvatar() ? LoaderPosition.LEFT : LoaderPosition.CENTER;
		}

		getContent()
		{
			const { content } = this.props;

			return content;
		}

		hasContent()
		{
			return Boolean(this.getContent());
		}
	}

	ChipButton.defaultProps = {
		compact: false,
		rounded: true,
		disabled: false,
		dropdown: false,
		loading: false,
		content: null,
	};

	ChipButton.propTypes = {
		testId: PropTypes.string.isRequired,
		text: PropTypes.string,
		content: PropTypes.object,
		loading: PropTypes.bool,
		compact: PropTypes.bool,
		disabled: PropTypes.bool,
		badge: PropTypes.object,
		avatar: PropTypes.object,
		rounded: PropTypes.bool,
		dropdown: PropTypes.bool,
		forwardRef: PropTypes.func,
		onClick: PropTypes.func,
		onLongClick: PropTypes.func,
		onLayout: PropTypes.func,
		icon: PropTypes.instanceOf(Icon),
		design: PropTypes.instanceOf(ChipButtonDesign),
		mode: PropTypes.instanceOf(ChipButtonMode),
		ellipsize: PropTypes.instanceOf(Ellipsize),
		iconColor: PropTypes.instanceOf(Color),
		backgroundColor: PropTypes.instanceOf(Color),
		loaderDesign: PropTypes.instanceOf(SpinnerDesign),
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
		Icon,
	};
});
