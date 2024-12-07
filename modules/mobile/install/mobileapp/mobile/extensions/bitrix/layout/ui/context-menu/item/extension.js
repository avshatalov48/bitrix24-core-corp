/**
 * @module layout/ui/context-menu/item
 */
jn.define('layout/ui/context-menu/item', (require, exports, module) => {
	const { Type } = require('type');
	const { Color, Corner, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { PropTypes } = require('utils/validation');
	const { AnalyticsLabel } = require('analytics-label');
	const { ContextMenuSection } = require('layout/ui/context-menu/section');
	const { SpinnerLoader, SpinnerDesign } = require('layout/ui/loaders/spinner');
	const { mergeImmutable } = require('utils/object');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Text2, Text5, Text6 } = require('ui-system/typography');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { IconAfterType } = require('layout/ui/context-menu/item/src/icon-after-type-enum');
	const { ItemType } = require('layout/ui/context-menu/item/src/item-type-enum');

	/**
	 * @typedef {Object} ContextMenuItemProps
	 * @property {string} testId
	 * @property {boolean} [active]
	 * @property {boolean} [selected]
	 * @property {boolean} [showSelectedImage]
	 * @property {boolean} [disabled]
	 * @property {boolean} [destructive]
	 * @property {boolean} [divide]
	 * @property {boolean} [dimmed]
	 * @property {boolean} [showArrow]
	 * @property {boolean} [badgeNew=false]
	 * @property {number} [counter]
	 * @property {BadgeCounterDesign} [counterDesign]
	 * @property {boolean} [showActionLoader]
	 * @property {string} [title]
	 * @property {string} [subtitle]
	 * @property {Icon} [icon]
	 * @property {Object} [analyticsLabel]
	 * @property {IconAfterType} [iconAfter]
	 * @property {Function} [onClickCallback]
	 * @property {Function} [onDisableClick]
	 * @property {Function} [onActiveCallback]
	 *
	 * @class ContextMenuItem
	 * @param {...ContextMenuItemProps} props
	 */
	class ContextMenuItem extends LayoutComponent
	{
		static create(props)
		{
			return new ContextMenuItem(props);
		}

		constructor(props)
		{
			super(props);

			this.state = {
				isProcessing: false,
			};

			this.handleSelectItem = this.handleSelectItem.bind(this);
		}

		getId()
		{
			return this.props.id;
		}

		getParentId()
		{
			return this.props.parentId;
		}

		getParent()
		{
			const { parent } = this.props;

			return parent || null;
		}

		/**
		 * @returns {ItemType}
		 */
		get type()
		{
			const { type } = this.props;
			let enumType = type;

			if (typeof type === 'string')
			{
				enumType = ItemType.getEnum(type.toUpperCase());
			}

			return ItemType.resolve(enumType, ItemType.ITEM);
		}

		/**
		 * @returns {boolean}
		 */
		showArrow()
		{
			const { showArrow = false } = this.props;

			return Boolean(showArrow);
		}

		/**
		 * @returns {boolean}
		 */
		showActionLoader()
		{
			const { showActionLoader = false } = this.props;

			return Boolean(showActionLoader) && this.isProcessing();
		}

		/**
		 * @returns {boolean}
		 */
		isSelected()
		{
			const { selected, isSelected } = this.props;

			return (Boolean(selected) || Boolean(isSelected)) && !this.isDisabled();
		}

		/**
		 * @returns {boolean}
		 */
		isDimmed()
		{
			if (this.getSectionCode() === ContextMenuSection.getServiceSectionName())
			{
				return true;
			}

			const { dimmed } = this.props;

			return Boolean(dimmed);
		}

		/**
		 * @returns {boolean}
		 */
		isDestructive()
		{
			const { destructive = false, isDestructive = false } = this.props;

			return Boolean(destructive) || Boolean(isDestructive);
		}

		getSectionCode()
		{
			const { sectionCode } = this.props;

			return sectionCode || ContextMenuSection.getDefaultSectionName();
		}

		get data()
		{
			return this.props.data || {};
		}

		get updateItemHandler()
		{
			return this.props.updateItemHandler;
		}

		get closeMenuHandler()
		{
			return this.props.closeHandler;
		}

		get onClickCallback()
		{
			return this.props.onClickCallback;
		}

		get onDisableClick()
		{
			return this.props.onDisableClick;
		}

		getParentWidget()
		{
			const { getParentWidget } = this.props;

			if (typeof getParentWidget !== 'function')
			{
				return null;
			}

			return getParentWidget();
		}

		/**
		 * @returns {boolean}
		 */
		isActive()
		{
			const { isActive = false, active = false } = this.props;

			return Boolean(isActive) || Boolean(active);
		}

		getAnalyticsLabel()
		{
			return BX.prop.getObject(this.props, 'analyticsLabel', null);
		}

		isRawIcon()
		{
			return BX.prop.getBoolean(this.props, 'isRawIcon', false);
		}

		get testId()
		{
			const { id, testId } = this.props;

			return `${testId || 'ContextMenu'}_${id}`;
		}

		render()
		{
			if (!this.isActive())
			{
				return null;
			}

			const { container: containerStyle = {} } = this.getCustomStyles();

			return View(
				{
					testId: this.testId,
					style: mergeImmutable({
						height: 58,
						width: '100%',
						position: 'relative',
						backgroundColor: this.isDimmed() ? this.getDimmedColor() : null,
					}, containerStyle),
					onClick: this.handleSelectItem,
				},
				View(
					{
						style: this.getContainerStyle(),
					},
					this.renderIconContainer(this.renderIcon()),
					this.renderContentContainer(),
				),
				this.renderDivider(),
			);
		}

		getContainerStyle()
		{
			return {
				flex: 1,
				flexDirection: 'row',
				paddingHorizontal: Indent.L.toNumber(),
				marginHorizontal: Indent.M.toNumber(),
				marginVertical: Indent.S.toNumber(),
				borderRadius: Corner.M.toNumber(),
				backgroundColor: this.isSelected() ? this.getSelectedColor() : null,
			};
		}

		onClickSelected(callback)
		{
			if (!callback)
			{
				return null;
			}

			const parentWidget = this.getParentWidget();
			const ensureMenuClosed = (handler) => {
				if (parentWidget)
				{
					parentWidget.close(handler);
				}
				else
				{
					handler();
				}
			};

			return callback(
				this.getId(),
				this.getParentId(),
				{
					parentWidget,
					ensureMenuClosed,
					parent: this.getParent(),
				},
			);
		}

		sendAnalytics()
		{
			const analyticsLabel = this.getAnalyticsLabel();

			if (Type.isPlainObject(analyticsLabel))
			{
				AnalyticsLabel.send({
					event: 'context-menu-click',
					id: this.getId(),
					...analyticsLabel,
				});
			}
		}

		handleSelectItem()
		{
			if (this.isProcessing() || this.type.isLayout())
			{
				return;
			}

			if (this.isDisabled())
			{
				if (this.onDisableClick)
				{
					this.onClickSelected(this.onDisableClick);
				}

				return;
			}

			const { needProcessing = true } = this.props;

			if (!needProcessing)
			{
				this.onClickSelected(this.onClickCallback);
				this.sendAnalytics();

				return;
			}

			this.setState({ isProcessing: true }, () => {
				let promise = this.onClickSelected(this.onClickCallback);
				this.sendAnalytics();

				if (!(promise instanceof Promise))
				{
					promise = Promise.resolve();
				}

				promise.then(
					({ action, id, params, closeMenu = true, closeCallback } = {}) => {
						if (closeMenu)
						{
							this.closeMenuHandler(closeCallback);
						}

						this.setState({ isProcessing: false }, () => {
							if (action && this.updateItemHandler)
							{
								this.updateItemHandler(action, id, params);
							}
						});
					},
					({ errors } = {}) => {
						this.setState({ isProcessing: false }, () => {
							if (errors && errors.length > 0)
							{
								this.showErrors(errors);
							}
						});
					},
				).catch(console.error);
			});
		}

		showError(errorText)
		{
			if (errorText.length > 0)
			{
				navigator.notification.alert(errorText, null, '');
			}
		}

		showErrors(errors, callback = null)
		{
			navigator.notification.alert(
				errors.map((error) => error.message).join('\n'),
				callback,
				'',
			);
		}

		renderContentContainer()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'space-between',
					},
				},
				View(
					{
						style: {
							flexDirection: 'column',
							justifyContent: 'center',
						},
					},
					this.renderTitle(),
					this.renderSubTitle(),
				),
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
						},
					},
					this.renderBadgeCounter(),
					this.renderSelected(),
					this.renderArrow(),
				),
			);
		}

		renderIconContainer(view)
		{
			if (!view)
			{
				return null;
			}

			return View(
				{
					style: {
						justifyContent: 'center',
						alignContent: 'center',
						marginRight: Indent.L.toNumber(),
					},
				},
				view,
			);
		}

		renderIcon()
		{
			if (this.showActionLoader())
			{
				return this.renderLoader();
			}

			if (!this.showIcon())
			{
				return null;
			}

			const icon = this.getIcon();

			if (icon)
			{
				return IconView({
					icon,
					color: this.getIconColor(),
					size: this.getIconSize(),
				});
			}

			if (this.isCustomIcon())
			{
				return this.renderImg();
			}

			return null;
		}

		renderImg()
		{
			const { imgUri, svgUri, svgIcon } = this.data;
			const iconSize = this.getIconSize();
			const imgParams = {
				style: {
					width: iconSize,
					height: iconSize,
				},
				resizeMode: this.getImageResizeMode(imgUri),
			};

			if (imgUri)
			{
				imgParams.uri = imgUri;
			}

			if (svgUri || svgIcon)
			{
				imgParams.svg = {};
				imgParams.tintColor = this.getIconColor(Color.base3)?.toHex();
			}

			if (svgUri)
			{
				imgParams.svg.uri = svgUri;
			}

			if (svgIcon)
			{
				imgParams.svg.content = svgIcon;
			}

			return Image(imgParams);
		}

		renderLoader()
		{
			const iconSize = this.getIconSize();

			return SpinnerLoader({
				size: iconSize,
				design: SpinnerDesign.BLUE,
			});
		}

		renderTitle()
		{
			const { title } = this.props;

			if (!title)
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				View(
					{
						style: {
							marginRight: Indent.XS.toNumber(),
						},
					},
					this.type.isLayout()
						? title
						: Text2({
							testId: `${this.testId}_title`,
							text: title,
							color: this.getTextColor(Color.base1),
							numberOfLines: 1,
							ellipsize: 'end',
						}),
				),
				this.renderAfterContainer(),
			);
		}

		renderSubTitle()
		{
			const { subtitle, subtitleType } = this.props;

			if (!subtitle)
			{
				return null;
			}

			const customStyles = this.getCustomStyles();
			const isWarning = subtitleType === 'warning';
			const color = isWarning ? Color.accentMainWarning : Color.base3;

			return Text5({
				testId: `${this.testId}_subtitle`,
				style: customStyles?.item?.subtitle || {},
				color: this.getTextColor(color),
				text: subtitle,
				numberOfLines: 1,
				ellipsize: 'end',
			});
		}

		renderBadgeCounter()
		{
			const { label, counter, counterDesign = BadgeCounterDesign.ALERT } = this.props;

			if (!label && !counter)
			{
				return null;
			}

			const design = this.isDisabled() ? BadgeCounterDesign.GREY : counterDesign;
			const value = counter || label;

			return BadgeCounter({ testId: this.testId, value, design });
		}

		renderSelected()
		{
			const { showSelectedImage = true } = this.props;

			if (!this.isSelected() || !showSelectedImage)
			{
				return null;
			}

			return IconView({
				testId: `${this.testId}_selected`,
				size: 28,
				icon: Icon.CHECK_SIZE_S,
				color: Color.accentMainPrimary,
				style: {
					marginLeft: Indent.XL.toNumber(),
				},
			});
		}

		renderArrow()
		{
			if (!this.showArrow())
			{
				return null;
			}

			const color = this.isDisabled() ? this.getDisabledColor() : Color.base4;

			return IconView({
				color,
				size: 22,
				icon: Icon.CHEVRON_TO_THE_RIGHT_SIZE_M,
				style: {
					marginLeft: Indent.XL.toNumber(),
				},
			});
		}

		renderAfterContainer()
		{
			const { iconAfter } = this.props;
			const size = 22;
			const svgIconAfter = this.data?.svgIconAfter;
			const iconType = iconAfter || svgIconAfter?.type;

			if (IconAfterType.has(iconType))
			{
				return IconView({
					icon: iconType.getIcon(),
					color: this.getIconColor(Color.base5),
					size,
				});
			}

			if (svgIconAfter)
			{
				return Image({
					tintColor: this.isDisabled()
						? this.getDisabledColor().toHex()
						: null,
					style: {
						width: size,
						height: size,
					},
					resizeMode: 'center',
					svg: svgIconAfter,
				});
			}

			return this.renderAfterBadge();
		}

		renderAfterBadge()
		{
			const { badges = [], badgeNew = false } = this.props;

			const badgeText = ({ text, color = Color.base3, style = {} }) => Text6({
				text,
				color,
				style: {
					alignSelf: 'flex-start',
					...style,
				},
			});

			if (badgeNew)
			{
				return badgeText({
					color: Color.accentMainSuccess,
					text: Loc.getMessage('CONTEXT_MENU_ITEM_BADGE_NEW'),
				});
			}

			if (Array.isArray(badges) && badges.length > 0)
			{
				return View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					...badges
						.filter(({ title }) => Boolean(title.trim()))
						.map(({
							color,
							backgroundColor,
							title,
						}, index) => View(
							{
								style: {
									marginRight: index === 0 ? Indent.XL.toNumber() : 0,
								},
							},
							badgeText({
								text: title.toLocaleLowerCase(env.languageId),
								style: {
									color: color || backgroundColor,
								},
							}),
						)),
				);
			}

			return null;
		}

		renderDivider()
		{
			const { divider = true } = this.props;
			if (!divider)
			{
				return null;
			}

			const { paddingHorizontal, marginHorizontal } = this.getContainerStyle();
			const selectedHorizontalMargin = paddingHorizontal + marginHorizontal;
			const iconContainerSize = this.getIconSize() + Indent.L.toNumber();
			const left = selectedHorizontalMargin + (this.showIcon() || this.showActionLoader() ? iconContainerSize : 0);

			return View({
				style: {
					left,
					width: '100%',
					bottom: 0,
					borderBottomWidth: 1,
					borderBottomColor: Color.bgSeparatorPrimary.toHex(),
				},
			});
		}

		getImageResizeMode(imgUri)
		{
			if (this.isRawIcon())
			{
				return 'cover';
			}

			if (imgUri)
			{
				return 'contain';
			}

			return 'center';
		}

		/**
		 * @returns {Icon|null}
		 */
		getIcon()
		{
			const { icon } = this.props;
			const itemIcon = icon || this.type.getIcon();

			if (itemIcon instanceof Icon)
			{
				return itemIcon;
			}

			return null;
		}

		/**
		 * @returns {number}
		 */
		getIconSize()
		{
			const { largeIcon = true } = this.props;

			return largeIcon ? 30 : 16;
		}

		/**
		 * @param {Color} [iconColor]
		 * @returns {Color}
		 */
		getIconColor(iconColor)
		{
			const { isCustomIconColor = false } = this.props;

			if (this.isDisabled())
			{
				return this.getDisabledColor();
			}

			if (isCustomIconColor)
			{
				return null;
			}

			if (this.isDestructive())
			{
				return Color.accentMainAlert;
			}

			if (iconColor)
			{
				return iconColor;
			}

			return Color.base1;
		}

		getSelectedColor()
		{
			return Color.accentSoftBlue2.toHex();
		}

		getDimmedColor()
		{
			return Color.bgContentTertiary.toHex();
		}

		/**
		 * @returns {Color}
		 */
		getDisabledColor()
		{
			return Color.base5;
		}

		/**
		 * @returns {Color}
		 */
		getTextColor(textColor)
		{
			if (this.isDestructive())
			{
				return Color.accentMainAlert;
			}

			if (this.isDisabled())
			{
				return this.getDisabledColor();
			}

			return textColor;
		}

		getCustomStyles()
		{
			return this.data?.style || {};
		}

		isProcessing()
		{
			return this.state.isProcessing;
		}

		isCustomIcon()
		{
			const { imgUri, svgUri, svgIcon } = this.data;

			return Boolean(imgUri || svgUri || svgIcon);
		}

		showIcon()
		{
			const { showIcon = true } = this.props;

			return showIcon && (Boolean(this.getIcon()) || this.isCustomIcon());
		}

		/**
		 * @returns {boolean}
		 */
		isDisabled()
		{
			const { disabled = false, isDisabled = false } = this.props;

			return Boolean(disabled) || Boolean(isDisabled);
		}

		static getHeight()
		{
			return 58;
		}
	}

	ContextMenuItem.defaultProps = {
		active: false,
		selected: false,
		divider: true,
		disabled: false,
		destructive: false,
		showArrow: false,
		badgeNew: false,
		showActionLoader: false,
		showSelectedImage: true,
	};

	ContextMenuItem.propTypes = {
		testId: PropTypes.string.isRequired,
		id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
		analyticsLabel: PropTypes.object,
		active: PropTypes.bool,
		selected: PropTypes.bool,
		showSelectedImage: PropTypes.bool,
		disabled: PropTypes.bool,
		destructive: PropTypes.bool,
		divider: PropTypes.bool,
		showArrow: PropTypes.bool,
		badgeNew: PropTypes.bool,
		counter: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		counterDesign: PropTypes.instanceOf(BadgeCounterDesign),
		showActionLoader: PropTypes.bool,
		title: PropTypes.string,
		subtitle: PropTypes.string,
		icon: PropTypes.instanceOf(Icon),
		iconAfter: PropTypes.instanceOf(IconAfterType),
		onClickCallback: PropTypes.func,
		onDisableClick: PropTypes.func,
		onActiveCallback: PropTypes.func,
	};

	module.exports = {
		ContextMenuItem,
		BadgeCounterDesign,
		ContextMenuItemType: ItemType,
		ImageAfterTypes: IconAfterType,
	};
});
