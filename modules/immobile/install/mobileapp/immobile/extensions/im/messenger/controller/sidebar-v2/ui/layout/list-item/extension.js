/**
 * @module im/messenger/controller/sidebar-v2/ui/layout/list-item
 */
jn.define('im/messenger/controller/sidebar-v2/ui/layout/list-item', (require, exports, module) => {
	const { Animated } = require('animation/animated');
	const { isObjectLike, isFunction } = require('utils/object');
	const { Indent, Component, Color } = require('tokens');
	const { Text6, BBCodeText } = require('ui-system/typography');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { PropTypes } = require('utils/validation');
	const { SidebarAvatar } = require('im/messenger/controller/sidebar-v2/ui/sidebar-avatar');

	const MIN_ITEM_HEIGHT = 69;

	/**
	 * @typedef ListItemProps
	 * @property {string} testId
	 * @property {Object | Function} [title]
	 * @property {Object | Function} [subtitle]
	 * @property {Function | ChatAvatarAdapterProps} [leftIcon]
	 * @property {Function} [onClick]
	 * @property {Function} [onLongClick]
	 * @property {Function} [onShowMenu]
	 * @property {boolean} [divider=true]
	 * @property {Function} [ref]
	 *
	 * @class ListItem
	 */
	class ListItem extends LayoutComponent
	{
		/**
		 * @param {ListItemProps} props
		 */
		constructor(props)
		{
			super(props);

			this.testId = props.testId;

			const contentMargins = Animated.newCalculatedValue(Indent.XL.toNumber() + Component.paddingLr.toNumber());
			this.contentSize = Animated.newCalculatedValue2D(0, 0);
			this.width = contentMargins.addCV(this.contentSize.getValue2());

			/**
			 * @public
			 * @readonly
			 */
			this.containerRef = null;

			/**
			 * @public
			 * @readonly
			 */
			this.menuIconRef = null;
		}

		render()
		{
			const { onClick } = this.props;

			return View(
				{
					style: {
						minHeight: MIN_ITEM_HEIGHT,
						justifyContent: 'center',
						backgroundColor: Color.bgContentPrimary.withPressed(),
					},
				},
				View(
					{
						onClick,
						onLongClick: this.handleOnLongClick,
						ref: this.onRef,
						testId: `${this.testId}_item`,
						style: {
							alignItems: 'center',
							flexDirection: 'row',
							paddingLeft: Component.paddingLr.toNumber(),
							paddingTop: Indent.XL.toNumber(),
							paddingBottom: Indent.L.toNumber(),
						},
					},
					this.renderLeftIcon(),
					View(
						{
							style: {
								flex: 1,
							},
						},
						this.renderTitle(),
						View(
							{
								style: {
									marginTop: Indent.XS2.toNumber(),
								},
							},
							this.renderSubTitle(),
						),
					),
					this.renderMenuActions(),
				),
				this.renderDivider(),
			);
		}

		renderTitle()
		{
			const { title } = this.props;

			if (isObjectLike(title))
			{
				return BBCodeText({
					size: 2,
					testId: `${this.testId}-title-${title?.text}`,
					color: Color.base1,
					ellipsize: 'end',
					numberOfLines: 1,
					value: title.text,
					...title,
				});
			}

			if (isFunction(title))
			{
				return title();
			}

			return title || null;
		}

		renderSubTitle()
		{
			const { subtitle } = this.props;

			if (isObjectLike(subtitle))
			{
				return Text6({
					testId: `${this.testId}-subtitle-${subtitle?.text}`,
					color: Color.base3,
					ellipsize: 'end',
					numberOfLines: 1,
					...subtitle,
				});
			}

			if (isFunction(subtitle))
			{
				return subtitle();
			}

			return subtitle || null;
		}

		renderLeftIcon()
		{
			const { leftIcon } = this.props;

			if (!leftIcon)
			{
				return null;
			}

			const imageRender = typeof leftIcon === 'function'
				? leftIcon()
				: SidebarAvatar({
					...leftIcon,
					testId: `${this.testId}_chatAvatar`,
					size: 40,
				});

			return View(
				{
					style: {
						marginRight: Indent.XL2.toNumber(),
					},
					onLayoutCalculated: {
						contentSize: this.contentSize,
					},
				},
				imageRender,
			);
		}

		renderMenuActions()
		{
			const { onShowMenu } = this.props;

			if (!onShowMenu)
			{
				return null;
			}

			return View(
				{
					style: {
						height: '100%',
						justifyContent: 'center',
						paddingLeft: Indent.XL2.toNumber(),
						paddingRight: Component.paddingLr.toNumber(),
					},
					onClick: this.handleOnMenuClick,
				},
				IconView({
					forwardRef: (ref) => {
						this.menuIconRef = ref;
					},
					testId: `${this.testId}_menu_icon`,
					icon: Icon.MORE,
					size: 24,
					color: Color.base4,
				}),
			);
		}

		onRef = (ref) => {
			this.containerRef = ref;
			if (this.props.ref)
			{
				this.props.ref(ref);
			}
		};

		onShowMenu = (params) => {
			const { onShowMenu } = this.props;

			onShowMenu?.(params);
		};

		handleOnLongClick = () => {
			this.onShowMenu({ ref: this.containerRef });
		};

		handleOnMenuClick = () => {
			this.onShowMenu({ ref: this.menuIconRef });
		};

		renderDivider()
		{
			const { divider = true } = this.props;

			if (!divider)
			{
				return null;
			}

			return View({
				style: {
					bottom: 0,
					left: this.width,
					position: 'absolute',
					width: '100%',
					height: 1,
					backgroundColor: Color.bgSeparatorSecondary.toHex(),
				},
			});
		}
	}

	ListItem.propTypes = {
		testId: PropTypes.string.isRequired,
		title: PropTypes.oneOfType([PropTypes.object, PropTypes.func]),
		subtitle: PropTypes.oneOfType([PropTypes.object, PropTypes.func]),
		leftIcon: PropTypes.oneOfType([PropTypes.object, PropTypes.func]),
		onClick: PropTypes.func,
		onShowMenu: PropTypes.func,
		divider: PropTypes.bool,
	};

	module.exports = {
		ListItem,
	};
});
