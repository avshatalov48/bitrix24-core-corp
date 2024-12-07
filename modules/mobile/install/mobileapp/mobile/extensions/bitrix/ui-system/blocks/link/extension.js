/**
 * @module ui-system/blocks/link
 */
jn.define('ui-system/blocks/link', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Text, Text1, Text2, Text3, Text4, Text5, Text6, Capital } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { LinkMode } = require('ui-system/blocks/link/src/mode-enum');
	const { LinkDesign } = require('ui-system/blocks/link/src/design-enum');
	const { PropTypes } = require('utils/validation');
	const { isValidLink } = require('utils/url');
	const { inAppUrl } = require('in-app-url');
	const { Ellipsize } = require('utils/enums/style');

	const ICON_SIZE = 20;

	/**
	 * @typedef LinkProps
	 * @property {string} testId
	 * @property {string} text
	 * @property {number} [size]
	 * @property {string} [href]
	 * @property {boolean} [useInAppLink=true]
	 * @property {Color} [color=Color.accentMainLink]
	 * @property {Ellipsize} [ellipsize=Ellipsize.END]
	 * @property {Icon} [leftIcon]
	 * @property {Icon} [rightIcon]
	 * @property {function} [forwardRef]
	 * @property {function} [onClick]
	 * @property {boolean} [accent=false]
	 * @property {LinkMode} [mode=LinkMode.PLAIN]
	 * @property {LinkDesign} [design=LinkDesign.PRIMARY]
	 * @property {Object} [style]
	 *
	 * @class Link
	 */
	class Link extends LayoutComponent
	{
		get mode()
		{
			const { mode } = this.props;

			return LinkMode.resolve(mode, LinkMode.PLAIN);
		}

		get design()
		{
			const { design } = this.props;

			return LinkDesign.resolve(design, LinkDesign.PRIMARY);
		}

		render()
		{
			const { testId, leftIcon, rightIcon, forwardRef, style = {} } = this.props;

			return View(
				{
					ref: forwardRef,
					testId,
					style: {
						flexDirection: 'row',
						flexShrink: 1,
						...style,
					},
				},
				View(
					{
						style: {
							alignItems: 'center',
							flexDirection: 'row',
							flexShrink: 1,
						},
						onClick: this.#handleOnClick,
					},
					this.#renderIcon(leftIcon, {
						marginRight: Indent.XS2.toNumber(),
					}),
					this.#renderText(),
					this.#renderIcon(rightIcon),
				),
			);
		}

		#renderIcon(icon, style = {})
		{
			if (!icon)
			{
				return null;
			}

			return IconView({
				color: this.getColor(),
				icon,
				size: ICON_SIZE,
				style,
			});
		}

		#renderText()
		{
			const { text, size = 4, accent = false, typography } = this.props;

			const TypographyText = typography || Text;

			return View(
				{
					style: {
						flexShrink: 1,
						...this.getBorderStyle(),
					},
				},
				TypographyText({
					text,
					size,
					accent,
					color: this.getColor(),
					ellipsize: this.#getEllipsize(),
					numberOfLines: 1,
					style: {
						flexShrink: 1,
					},
				}),
			);
		}

		#handleOnClick = () => {
			const { onClick, href, useInAppLink = true } = this.props;

			if (useInAppLink && href && isValidLink(href))
			{
				inAppUrl.open(href);
			}

			if (onClick)
			{
				onClick(href);
			}
		};

		getBorderStyle()
		{
			return {
				borderBottomColor: this.getColor().toHex(0.3),
				paddingBottom: 1,
				...this.mode.getStyle(),
			};
		}

		getColor()
		{
			const { color } = this.props;
			const { color: designColor } = this.design.getStyle();

			return Color.resolve(color, designColor);
		}

		#getEllipsize()
		{
			const { ellipsize } = this.props;

			if (typeof ellipsize === 'string')
			{
				return ellipsize;
			}

			return Ellipsize.resolve(ellipsize, Ellipsize.END).toString();
		}
	}

	Link.defaultProps = {
		useInAppLink: true,
	};

	Link.propTypes = {
		testId: PropTypes.string.isRequired,
		text: PropTypes.string.isRequired,
		size: PropTypes.number,
		href: PropTypes.string,
		useInAppLink: PropTypes.bool,
		color: PropTypes.instanceOf(Color),
		ellipsize: PropTypes.instanceOf(Ellipsize),
		leftIcon: PropTypes.instanceOf(Icon),
		rightIcon: PropTypes.instanceOf(Icon),
		forwardRef: PropTypes.func,
		onClick: PropTypes.func,
		accent: PropTypes.bool,
		mode: PropTypes.instanceOf(LinkMode),
		design: PropTypes.instanceOf(LinkDesign),
		style: PropTypes.object,
	};

	module.exports = {
		/**
		 * @param {LinkProps} props
		 */
		Link: (props) => new Link(props),
		/**
		 * @param {LinkProps} props
		 */
		Link1: (props) => new Link({ ...props, typography: Text1 }),
		/**
		 * @param {LinkProps} props
		 */
		Link2: (props) => new Link({ ...props, typography: Text2 }),
		/**
		 * @param {LinkProps} props
		 */
		Link3: (props) => new Link({ ...props, typography: Text3 }),
		/**
		 * @param {LinkProps} props
		 */
		Link4: (props) => new Link({ ...props, typography: Text4 }),
		/**
		 * @param {LinkProps} props
		 */
		Link5: (props) => new Link({ ...props, typography: Text5 }),
		/**
		 * @param {LinkProps} props
		 */
		Link6: (props) => new Link({ ...props, typography: Text6 }),
		/**
		 * @param {LinkProps} props
		 */
		LinkCapital: (props) => new Link({ ...props, typography: Capital }),
		LinkMode,
		LinkDesign,
		Icon,
		Ellipsize,
	};
});
