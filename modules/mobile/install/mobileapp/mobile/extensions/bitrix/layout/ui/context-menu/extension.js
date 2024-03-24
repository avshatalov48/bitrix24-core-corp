(() => {
	/**
	 * @typedef {Object} ContextMenuProperties
	 * @property {ContextMenuActionProperties[]} actions
	 * @property {Object} titlesBySectionCode
	 * @property {?string} parentId
	 * @property {?Object} parent
	 * @property {function} onClose
	 * @property {function} onCancel
	 *
	 * @property {?Object} params
	 * @property {string} params.title
	 * @property {boolean} params.showCancelButton
	 * @property {boolean} params.showPartiallyHidden
	 * @property {boolean} params.shouldResizeContent
	 * @property {boolean} params.showActionLoader
	 * @property {boolean} params.isCustomIconColor
	 *
	 * @property {?Object} banner
	 * @property {string[]} banner.featureItems
	 * @property {string} banner.imagePath
	 * @property {string} banner.imageSvg
	 * @property {?string} banner.positioning
	 * @property {?string} banner.title
	 * @property {?boolean} banner.showSubtitle
	 * @property {?string} banner.buttonText
	 * @property {?string} banner.buttonType
	 * @property {?object} banner.onButtonClick
	 * @property {?object} banner.onCloseBanner
	 * @property {?object} banner.qrAuth
	 * @property {string} banner.qrAuth.redirectUrl
	 *
	 * @property {?Object} customSection
	 * @property {number} customSection.height
	 * @property {View} customSection.layout
	 *
	 * @property {?Object} analyticsLabel
	 */

	/**
	 * @typedef {Object} ContextMenuActionProperties
	 * @property {string} id
	 * @property {string} title
	 * @property {?string} type
	 * @property {?string} sectionCode
	 * @property {function} onActiveCallback
	 * @property {boolean} showActionLoader
	 * @property {boolean} isCustomIconColor
	 */

	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');
	const { ContextMenuBanner, BannerPositioning } = require('layout/ui/context-menu/banner');
	const { Type } = require('type');
	const { ContextMenuItem } = require('layout/ui/context-menu/item');

	const ACTION_DELETE = 'delete';
	const ACTION_CANCEL = 'cancel';

	const DEFAULT_BANNER_HEIGHT = device.screen.width > 375 ? 258 : 300;
	const DEFAULT_BANNER_TITLE_HEIGHT = 36;
	const DEFAULT_BANNER_LOGO_HEIGHT_VERTICAL = 200;
	const DEFAULT_BANNER_ITEM_HEIGHT = 30;
	const DEFAULT_BANNER_ITEM_HEIGHT_VERTICAL = 48;
	const DEFAULT_BANNER_BUTTON_HEIGHT = 72;
	const DEFAULT_BANNER_SUBTEXT_HEIGHT = 72;

	const IS_IOS = Application.getPlatform() === 'ios';
	const INDENT_AFTER_ACTION_BUTTON = 28 + (IS_IOS ? 0 : 26);

	const ANDROID_BOTTOM_SAFE_AREA = 34;

	const INDENT_BETWEEN_SECTIONS = 10;
	const ITEM_HEIGHT = 58;
	const SECTION_TITLE_HEIGHT = 38;
	const TITLE_HEIGHT = 44;

	/**
	 * @class ContextMenu
	 */
	class ContextMenu extends LayoutComponent
	{
		/**
		 * @param {ContextMenuProperties} props
		 */
		constructor(props)
		{
			super(props);

			this.state = {
				enabled: true,
			};

			this.closedByApi = false;
			this.closeHandler = this.close.bind(this);

			this.renderAction = this.renderAction.bind(this);
			this.getActionParentWidget = this.getActionParentWidget.bind(this);

			this.initialize(props);
		}

		initialize(props)
		{
			this.params = BX.prop.getObject(props, 'params', {});
			this.testId = `${props.testId}_CONTEXT_MENU`;
			this.parentId = props.parentId;
			this.parent = BX.prop.getObject(props, 'parent', {});
			this.titlesBySectionCode = BX.prop.getObject(props, 'titlesBySectionCode', {});
			this.titleActionsBySectionCode = BX.prop.getObject(props, 'titleActionsBySectionCode', {});
			this.analyticsLabel = BX.prop.getObject(props, 'analyticsLabel', null);

			this.banner = BX.prop.getObject(props, 'banner', null);
			this.customSection = BX.prop.getObject(props, 'customSection', null);

			this.actions = this.prepareActions(BX.prop.getArray(props, 'actions', []));
			this.actionsBySections = this.getActionsBySections();

			this.styles = {
				view: {
					backgroundColor: this.backgroundDisabled ? AppTheme.colors.bgContentPrimary : AppTheme.colors.bgSecondary,
					safeArea: {
						bottom: true,
					},
				},
			};
		}

		componentDidUpdate(prevProps, prevState)
		{
			if (this.layoutWidget && !this.showPartiallyHidden)
			{
				const calculatedHeight = this.calcMediumHeight();

				if (this.height !== calculatedHeight)
				{
					this.height = calculatedHeight;
					this.layoutWidget.setBottomSheetHeight(calculatedHeight);
				}
			}
		}

		get title()
		{
			return BX.prop.getString(this.params, 'title', '');
		}

		get showCancelButton()
		{
			return false;
		}

		get showActionLoader()
		{
			return BX.prop.getBoolean(this.params, 'showActionLoader', false);
		}

		get isCustomIconColor()
		{
			return BX.prop.getBoolean(this.params, 'isCustomIconColor', false);
		}

		get isRawIcon()
		{
			return BX.prop.getBoolean(this.params, 'isRawIcon', false);
		}

		get shouldResizeContent()
		{
			return BX.prop.getBoolean(this.params, 'shouldResizeContent', false);
		}

		get showPartiallyHidden()
		{
			return BX.prop.getBoolean(this.params, 'showPartiallyHidden', false);
		}

		get mediumPositionPercent()
		{
			return BX.prop.getNumber(this.params, 'mediumPositionPercent', 50);
		}

		get helpUrl()
		{
			return BX.prop.getString(this.params, 'helpUrl', null);
		}

		prepareActions(actions)
		{
			if (!Array.isArray(actions))
			{
				return [];
			}

			return actions.map((action) => {
				if (action.type)
				{
					action = {
						...this.getActionConfigByType(action.type),
						...action,
					};
				}

				action.sectionCode = (action.sectionCode || ContextMenuSection.getDefaultSectionName());
				action.parentId = this.parentId;
				action.parent = this.parent;
				action.updateItemHandler = this.props.updateItemHandler;
				action.closeHandler = this.closeHandler;
				action.analyticsLabel = this.analyticsLabel;

				if (!action.hasOwnProperty('showActionLoader'))
				{
					action.showActionLoader = this.showActionLoader;
				}

				if (!action.hasOwnProperty('isCustomIconColor'))
				{
					action.isCustomIconColor = this.isCustomIconColor;
				}

				if (!action.hasOwnProperty('isRawIcon'))
				{
					action.isRawIcon = this.isRawIcon;
				}

				return action;
			});
		}

		getActionConfigByType(type)
		{
			if (type === ACTION_DELETE)
			{
				return {
					data: {
						svgIcon: '<svg width="16" height="20" viewBox="0 0 16 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.365 0.358398H6.40958V1.84482H1.87921C1.1169 1.84482 0.498932 2.46279 0.498932 3.2251V4.81772H15.276V3.2251C15.276 2.46279 14.658 1.84482 13.8957 1.84482H9.365V0.358398Z" fill="#6a737f"/><path d="M1.97671 6.30421H13.7984L12.6903 18.8431C12.6484 19.318 12.2505 19.6823 11.7737 19.6823H4.00133C3.52452 19.6823 3.12669 19.318 3.08472 18.8431L1.97671 6.30421Z" fill="#6a737f"/></svg>',
					},
				};
			}

			return {};
		}

		getActionsBySections()
		{
			const actionsBySections = {};

			this.actions.forEach((action) => {
				const sectionCode = action.sectionCode;

				actionsBySections[sectionCode] = actionsBySections[sectionCode] || [];
				actionsBySections[sectionCode].push({
					...action,
					isActive:
						action.hasOwnProperty('onActiveCallback')
							? action.onActiveCallback(action.id, action.parentId, action.parent)
							: true
					,
				});
			});

			if (this.showCancelButton)
			{
				const serviceSectionName = ContextMenuSection.getServiceSectionName();

				actionsBySections[serviceSectionName] = actionsBySections[serviceSectionName] || [];
				actionsBySections[serviceSectionName].push(this.getCancelButtonConfig());
			}

			return actionsBySections;
		}

		render()
		{
			return ScrollView(
				{
					style: this.styles.view,
					testId: this.testId,
				},
				View(
					{},
					this.renderBanner(),
					...this.renderSections(),
				),
			);
		}

		renderBanner()
		{
			if (this.banner)
			{
				return new ContextMenuBanner({
					banner: this.banner,
					menu: this,
					parentWidget: this.parentWidget,
				});
			}

			return null;
		}

		renderSections()
		{
			const results = new Map();
			let i = 0;

			if (this.customSection)
			{
				results.set('custom', this.customSection.layout);
				i++;
			}

			for (const sectionCode in this.actionsBySections)
			{
				const section = ContextMenuSection.create({
					id: sectionCode,
					title: this.titlesBySectionCode[sectionCode],
					titleAction: this.titleActionsBySectionCode[sectionCode],
					actions: this.actionsBySections[sectionCode],
					renderAction: this.renderAction,
					enabled: this.state.enabled,
					style: {
						marginTop: i > 0 ? INDENT_BETWEEN_SECTIONS : 0,
					},
					showTitleBorder: this.showTitleBorder(this.actionsBySections[sectionCode]),
					closeHandler: this.closeHandler,
				});

				results.set(sectionCode, section);
				i++;
			}

			return results.values();
		}

		showTitleBorder(actionsBySections)
		{
			return actionsBySections.find((action) => action.isActive);
		}

		renderAction(action, params = {})
		{
			return ContextMenuItem.create({
				...action,
				...params,
				testId: this.testId,
				getParentWidget: this.getActionParentWidget,
			});
		}

		getActionParentWidget()
		{
			return this.layoutWidget;
		}

		getCancelButtonConfig()
		{
			return {
				id: ACTION_CANCEL,
				isActive: true,
				parentId: this.parentId,
				title: BX.message('CONTEXT_MENU_CANCEL'),
				sectionCode: ContextMenuSection.getServiceSectionName(),
				largeIcon: false,
				type: ContextMenuItem.getTypeCancelName(),
				showActionLoader: false,
				data: {
					svgIcon: '<svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.7562 8.54581L16.4267 14.2163L14.2165 16.4266L8.54596 10.7561L2.87545 16.4266L0.665192 14.2163L6.3357 8.54581L0.665192 2.87529L2.87545 0.665039L8.54596 6.33555L14.2165 0.665039L16.4267 2.87529L10.7562 8.54581Z" fill="#A8ADB4"/></svg>',
				},
				onClickCallback: () => Promise.resolve(true),
				closeHandler: this.closeHandler,
			};
		}

		/**
		 * @public
		 * @param {ContextMenuProperties} props
		 * @return void
		 */
		rerender(props)
		{
			this.initialize(props);
			this.forceUpdate();
		}

		/**
		 * @private
		 */
		forceUpdate()
		{
			this.setState({});
		}

		/**
		 * @public
		 * @param {Function} callback
		 */
		close(callback = () => {})
		{
			this.closedByApi = true;

			if (this.layoutWidget)
			{
				this.layoutWidget.close(callback);
			}
		}

		/**
		 * @public
		 * @param parentWidget
		 * @return {Promise}
		 */
		show(parentWidget = PageManager)
		{
			return new Promise((resolve, reject) => {
				const widgetParams = this.prepareWidgetParams();

				this.parentWidget = parentWidget || PageManager;

				this.parentWidget
					.openWidget('layout', widgetParams)
					.then((layoutWidget) => {
						this.layoutWidget = layoutWidget;

						if (Type.isStringFilled(this.title))
						{
							layoutWidget.setTitle({ text: this.title });
							layoutWidget.enableNavigationBarBorder(false);
						}

						layoutWidget.setListener((eventName) => {
							if (eventName === 'onViewHidden' || eventName === 'onViewRemoved')
							{
								if (this.props.onClose)
								{
									this.props.onClose();
								}

								if (!this.closedByApi && this.props.onCancel)
								{
									this.props.onCancel();
								}
							}
						});

						layoutWidget.showComponent(this);

						resolve();
					})
					.catch(reject)
				;
			});
		}

		/**
		 * @private
		 * @returns {Object}
		 */
		prepareWidgetParams()
		{
			let onlyMediumPosition = true;
			let mediumPositionHeight = this.calcMediumHeight();
			let topPosition = 0;

			if (this.showPartiallyHidden)
			{
				const threshold = this.getDeviceScreenHeight() * this.mediumPositionPercent / 100;

				if (mediumPositionHeight > threshold)
				{
					mediumPositionHeight = this.calcMediumHeightForThreshold(threshold);

					if (threshold > mediumPositionHeight)
					{
						topPosition = this.calcTopPositionOffset();
						onlyMediumPosition = false;
					}
				}
			}

			this.height = mediumPositionHeight;

			const widgetParams = {
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					shouldResizeContent: this.shouldResizeContent,
					swipeAllowed: true,
					forceDismissOnSwipeDown: true,
					onlyMediumPosition,
					topPosition,
					mediumPositionHeight,
					horizontalSwipeAllowed: false,
					hideNavigationBar: !Type.isStringFilled(this.title),
					navigationBarColor: AppTheme.colors.bgSecondary,
					helpUrl: this.helpUrl,
				},
			};

			Object.keys(widgetParams.backdrop).forEach((key) => {
				if (Type.isNil(widgetParams.backdrop[key]))
				{
					delete widgetParams.backdrop[key];
				}
			});

			return widgetParams;
		}

		/**
		 * @private
		 * @returns {number}
		 */
		calcMediumHeight()
		{
			return (
				this.getTitleHeight()
				+ this.getBannerHeight()
				+ this.getCustomSectionHeight()
				+ this.getSectionsHeight()
				+ this.getBottomSafeArea()
			);
		}

		getBottomSafeArea()
		{
			// fix android height calculated with native bottom buttons
			return IS_IOS ? 0 : ANDROID_BOTTOM_SAFE_AREA;
		}

		/**
		 * @private
		 * @returns {number}
		 */
		calcMediumHeightForThreshold(threshold)
		{
			let height = this.getTitleHeight() + this.getBannerHeight() + this.getCustomSectionHeight();

			for (const [sectionCode, sectionActions] of Object.entries(this.actionsBySections))
			{
				if (
					Type.isStringFilled(this.titlesBySectionCode[sectionCode])
					|| Type.isObject(this.titlesBySectionCode[sectionCode]) // BBCode case
				)
				{
					height += SECTION_TITLE_HEIGHT;
				}

				for (const action of sectionActions)
				{
					if (action.isActive)
					{
						height += ITEM_HEIGHT;
					}

					// why check cancel - it looks like the end, and we can show a fully expanded menu
					// otherwise, we can show only a part of the menu
					if (height >= threshold && action.id !== ACTION_CANCEL)
					{
						return height - ITEM_HEIGHT - 1;
					}
				}

				height += INDENT_BETWEEN_SECTIONS;
			}

			return height;
		}

		/**
		 * @private
		 * @returns {number}
		 */
		getTitleHeight()
		{
			return Type.isStringFilled(this.title) ? TITLE_HEIGHT : 0;
		}

		getSectionsHeight()
		{
			let height = 0;

			for (const sectionCode in this.actionsBySections)
			{
				if (
					Type.isStringFilled(this.titlesBySectionCode[sectionCode])
					|| Type.isObject(this.titlesBySectionCode[sectionCode]) // BBCode case
				)
				{
					height += SECTION_TITLE_HEIGHT;
				}

				height += this.actionsBySections[sectionCode].reduce((sum, action) => {
					if (action.isActive)
					{
						return sum + ITEM_HEIGHT;
					}

					return sum;
				}, INDENT_BETWEEN_SECTIONS);
			}

			return height;
		}

		get isActionBanner()
		{
			return (this.banner.qrauth || this.banner.onButtonClick || this.banner.onCloseBanner);
		}

		get backgroundDisabled()
		{
			return (this.banner && !this.showCancelButton && this.isActionBanner);
		}

		get spaceRequiredForButton()
		{
			return (this.banner && this.showCancelButton && this.isActionBanner);
		}

		getBannerHeight()
		{
			if (this.banner)
			{
				let itemsHeight = 0;

				if (Type.isStringFilled(this.banner.title))
				{
					itemsHeight += DEFAULT_BANNER_TITLE_HEIGHT;
				}

				if (this.banner.positioning === BannerPositioning.Vertical)
				{
					itemsHeight += DEFAULT_BANNER_LOGO_HEIGHT_VERTICAL;

					itemsHeight += Array.isArray(this.banner.featureItems)
						? this.banner.featureItems.length * DEFAULT_BANNER_ITEM_HEIGHT_VERTICAL
						: 0;
				}
				else
				{
					itemsHeight += Array.isArray(this.banner.featureItems)
						? this.banner.featureItems.length * DEFAULT_BANNER_ITEM_HEIGHT
						: 0;
				}

				if (this.isActionBanner)
				{
					if (this.spaceRequiredForButton)
					{
						itemsHeight += DEFAULT_BANNER_BUTTON_HEIGHT;
					}
					else
					{
						itemsHeight += INDENT_AFTER_ACTION_BUTTON;
					}
				}

				if (this.banner.subtext)
				{
					itemsHeight += DEFAULT_BANNER_SUBTEXT_HEIGHT;
				}

				return Math.max(DEFAULT_BANNER_HEIGHT, itemsHeight);
			}

			return 0;
		}

		getCustomSectionHeight()
		{
			if (this.customSection)
			{
				return this.customSection.height;
			}

			return 0;
		}

		/**
		 * @private
		 * @returns {number}
		 */
		calcTopPositionOffset()
		{
			// eslint-disable-next-line max-len
			const topOffsetForExpandedMenu = this.getDeviceScreenHeight() - this.getTopSafeAreaHeight() - this.calcMediumHeight();

			return Math.max(topOffsetForExpandedMenu, 70);
		}

		getDeviceScreenHeight()
		{
			const {
				screen: {
					height: screenHeight,
				} = {},
			} = device || {};

			return screenHeight;
		}

		getTopSafeAreaHeight()
		{
			const {
				screen: {
					safeArea: {
						top: topSafeArea,
					},
				} = {},
			} = device || {};

			return topSafeArea;
		}

		setSelectedActions(ids, showSelectedImage = true)
		{
			this.actions.map((action) => {
				if (ids.includes(action.id))
				{
					action.isSelected = true;
					action.showSelectedImage = showSelectedImage;
				}
				else
				{
					action.isSelected = false;
					action.showSelectedImage = false;
				}
			});

			this.actionsBySections = this.getActionsBySections();
		}
	}

	this.ContextMenu = ContextMenu;
})();

/**
 * @module layout/ui/context-menu
 */
jn.define('layout/ui/context-menu', (require, exports, module) => {
	module.exports = { ContextMenu: this.ContextMenu };
});
