/**
 * @module layout/ui/context-menu
 */
jn.define('layout/ui/context-menu', (require, exports, module) => {
	const { Type } = require('type');
	const { Color } = require('tokens');
	const { Icon } = require('assets/icons');
	const { BottomSheet } = require('bottom-sheet');
	const { AreaList } = require('ui-system/layout/area-list');
	const { PropTypes } = require('utils/validation');
	const { ContextMenuSection } = require('layout/ui/context-menu/section');
	const { ContextMenuItem, ImageAfterTypes, BadgeCounterDesign } = require(
		'layout/ui/context-menu/item',
	);
	const { ContextMenuBanner, BannerPositioning } = require('layout/ui/context-menu/banner');

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
	const TITLE_HEIGHT = 44;

	/**
	 * @typedef {Object} ContextMenuProps
	 * @property {ContextMenuItemProps[]} actions
	 * @property {ContextMenuBannerProps} banner
	 * @property {Object} analyticsLabel
	 * @property {Object} parent
	 * @property {Object} titlesBySectionCode
	 * @property {Object} titleActionsBySectionCode
	 * @property {Function} onCancel
	 * @property {Function} onClose
	 * @property {Object} params
	 * @property {string} params.title
	 * @property {boolean} params.showActionLoader
	 * @property {string} params.helpUrl
	 * @class ContextMenu
	 */
	class ContextMenu extends LayoutComponent
	{
		/**
		 * @param {...ContextMenuProps} props
		 */
		constructor(props)
		{
			super(props);

			this.state = {
				enabled: true,
			};

			this.layoutWidget = null;
			this.parentWidget = null;
			this.closedByApi = false;

			this.renderAction = this.renderAction.bind(this);
			this.getActionParentWidget = this.getActionParentWidget.bind(this);

			this.initialize(props);
		}

		initialize(props)
		{
			this.parentId = props.parentId;
			this.params = BX.prop.getObject(props, 'params', {});
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
					backgroundColor: this.backgroundDisabled
						? Color.bgContentPrimary.toHex()
						: Color.bgSecondary.toHex(),
					safeArea: {
						bottom: true,
					},
				},
			};
		}

		#getTestId()
		{
			const { testId } = this.props;

			return `${testId}_CONTEXT_MENU`;
		}

		prepareActions(actions)
		{
			if (!Array.isArray(actions))
			{
				return [];
			}

			const {
				updateItemHandler,
				isRawIcon = false,
				showActionLoader = false,
				isCustomIconColor = false,
			} = this.props;

			const actionItems = [];

			actions.forEach((action) => {
				actionItems.push({
					showActionLoader,
					isCustomIconColor,
					isRawIcon,
					updateItemHandler,
					sectionCode: (action.sectionCode || ContextMenuSection.getDefaultSectionName()),
					parentId: this.parentId,
					parent: this.parent,
					closeHandler: this.close,
					analyticsLabel: this.analyticsLabel,
					...action,
				});
			});

			return actionItems;
		}

		getActionsBySections()
		{
			const actionsBySections = {};

			this.actions.forEach((action) => {
				const sectionCode = action.sectionCode;
				const onActiveCallback = Type.isFunction(action.onActiveCallback)
					? action.onActiveCallback(action.id, action.parentId, action.parent)
					: true;
				const active = Boolean(action.active ?? action.isActive ?? onActiveCallback);

				actionsBySections[sectionCode] = actionsBySections[sectionCode] || [];
				actionsBySections[sectionCode].push({ ...action, active });
			});

			return actionsBySections;
		}

		render()
		{
			return AreaList(
				{
					testId: this.#getTestId(),
				},
				this.renderBanner(),
				...this.renderSections(),
			);
		}

		renderBanner()
		{
			if (this.banner)
			{
				return new ContextMenuBanner({
					banner: this.banner,
					menu: this,
					parentWidget: this.getParentWidget(),
				});
			}

			return null;
		}

		renderSections()
		{
			const results = [];

			if (this.customSection)
			{
				results.push(this.customSection.layout);
			}

			Object.entries(this.actionsBySections).forEach(([sectionCode]) => {
				const title = this.titlesBySectionCode[sectionCode];
				const actions = this.actionsBySections[sectionCode];
				const isActive = actions.some(({ active }) => active);

				if (isActive)
				{
					results.push(ContextMenuSection.create({
						testId: this.#getTestId(),
						id: sectionCode,
						title,
						actions,
						titleAction: this.titleActionsBySectionCode[sectionCode],
						renderAction: this.renderAction,
						enabled: this.state.enabled,
						showTitleBorder: this.showTitleBorder(actions),
						closeHandler: this.close,
					}));
				}
			});

			return results;
		}

		showTitleBorder(actions)
		{
			return actions.find((action) => action.active);
		}

		renderAction(action, params = {})
		{
			return ContextMenuItem.create({
				...action,
				...params,
				testId: this.#getTestId(),
				getParentWidget: this.getActionParentWidget,
			});
		}

		getActionParentWidget()
		{
			return this.layoutWidget;
		}

		/**
		 * @public
		 * @param {...ContextMenuProps} props
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
		close = (callback) => {
			this.closedByApi = true;

			if (this.layoutWidget)
			{
				this.layoutWidget.close(callback);
			}
		};

		/**
		 * @param layoutWidget
		 */
		setLayoutWidget(layoutWidget)
		{
			this.layoutWidget = layoutWidget;
		}

		setParentWidget(parentWidget)
		{
			this.parentWidget = parentWidget;
		}

		getParentWidget()
		{
			return this.parentWidget || PageManager;
		}

		/**
		 * @public
		 * @param parentWidget
		 * @return {Promise}
		 */
		async show(parentWidget)
		{
			this.setParentWidget(parentWidget);

			const { onCancel, onClose, params = {} } = this.props;
			const {
				helpUrl = '',
				shouldResizeContent = false,
				showPartiallyHidden = false,
				mediumPositionPercent = 50,
			} = params;

			const entityBottomSheet = new BottomSheet({ component: this });
			const contextMenuBottomSheet = entityBottomSheet
				.setParentWidget(this.getParentWidget())
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgContentPrimary.toHex())
				.enableForceDismissOnSwipeDown()
				.disableHorizontalSwipe()
				.enableSwipe()
				.enableOnlyMediumPosition()
				.setTitleParams({
					text: this.getTitle(),
					type: 'dialog',
				});

			if (helpUrl)
			{
				contextMenuBottomSheet.setHelpUrl(helpUrl);
			}

			if (shouldResizeContent)
			{
				contextMenuBottomSheet.enableResizeContent();
			}

			if (!showPartiallyHidden)
			{
				contextMenuBottomSheet.setMediumPositionHeight(this.calcMediumHeight());
			}

			let mediumPositionHeight = this.calcMediumHeight();
			const threshold = this.#getDeviceScreenHeight() * mediumPositionPercent / 100;
			if (mediumPositionHeight > threshold)
			{
				mediumPositionHeight = this.calcMediumHeightForThreshold(threshold);

				let topPosition = 0;
				if (threshold > mediumPositionHeight)
				{
					topPosition = this.#calcTopPositionOffset();
					contextMenuBottomSheet.disableOnlyMediumPosition();
				}
				contextMenuBottomSheet.setTopPosition(topPosition);
				contextMenuBottomSheet.setMediumPositionPercent(mediumPositionPercent);
			}
			else
			{
				contextMenuBottomSheet.setMediumPositionHeight(this.calcMediumHeight());
			}

			const layoutWidget = await contextMenuBottomSheet.open();
			layoutWidget.setListener((eventName) => {
				if (eventName === 'onViewHidden' || eventName === 'onViewRemoved')
				{
					if (onClose)
					{
						onClose();
					}

					if (!this.closedByApi && onCancel)
					{
						onCancel();
					}
				}
			});

			this.setLayoutWidget(layoutWidget);

			return layoutWidget;
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

		/**
		 * @private
		 * @returns {number}
		 */
		calcMediumHeightForThreshold(threshold)
		{
			let height = this.getTitleHeight() + this.getBannerHeight() + this.getCustomSectionHeight();

			for (const [sectionCode, sectionActions] of Object.entries(this.actionsBySections))
			{
				if (this.titlesBySectionCode[sectionCode])
				{
					height += ContextMenuItem.getHeight();
				}

				for (const { active } of sectionActions)
				{
					height += active ? ContextMenuItem.getHeight() : 0;

					// why check cancel - it looks like the end, and we can show a fully expanded menu
					// otherwise, we can show only a part of the menu
					if (height >= threshold)
					{
						return height - ContextMenuItem.getHeight() - 1;
					}
				}
			}

			return height;
		}

		getSectionsHeight()
		{
			let height = 0;
			Object.entries(this.actionsBySections).forEach(([sectionCode, sectionActions]) => {
				let activeSection = false;
				sectionActions.forEach(({ active }) => {
					activeSection = active;
					height += active ? ContextMenuItem.getHeight() : 0;
				});

				if (this.titlesBySectionCode[sectionCode] && activeSection)
				{
					height += ContextMenuItem.getHeight();
				}
			});

			return height + ContextMenuSection.getIndentBetweenSections();
		}

		/**
		 * @private
		 * @returns {number}
		 */
		getTitleHeight()
		{
			return this.hasTitle() ? TITLE_HEIGHT : 0;
		}

		get isActionBanner()
		{
			return (this.banner.qrauth || this.banner.onButtonClick || this.banner.onCloseBanner);
		}

		get isActionBannerWithRejectButton()
		{
			return this.banner.showRejectButton;
		}

		get backgroundDisabled()
		{
			return (this.banner && this.isActionBanner);
		}

		get spaceRequiredForButton()
		{
			return (this.banner && this.isActionBanner);
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

				if (this.isActionBannerWithRejectButton)
				{
					itemsHeight += DEFAULT_BANNER_BUTTON_HEIGHT;
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
			return this.customSection?.height || 0;
		}

		getTitle()
		{
			return BX.prop.getString(this.params, 'title', '');
		}

		hasTitle()
		{
			return Type.isStringFilled(this.getTitle());
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
		#calcTopPositionOffset()
		{
			const topOffsetForExpandedMenu = this.#getDeviceScreenHeight()
				- this.#getTopSafeAreaHeight()
				- this.calcMediumHeight();

			return Math.max(topOffsetForExpandedMenu, 70);
		}

		#getDeviceScreenHeight()
		{
			return device?.screen?.height || 0;
		}

		#getTopSafeAreaHeight()
		{
			return device?.screen?.safeArea?.top || 0;
		}

		setSelectedActions(ids, showSelectedImage = true)
		{
			this.actions = this.actions.map((action) => {
				const selected = ids.includes(action.id);
				const updateOptions = {
					selected,
					isSelected: selected,
					showSelectedImage: selected && showSelectedImage,
				};

				return { ...action, ...updateOptions };
			});

			this.actionsBySections = this.getActionsBySections();
		}
	}

	ContextMenuItem.propTypes = {
		actions: PropTypes.objectOf(ContextMenuItem.propTypes),
		titlesBySectionCode: PropTypes.arrayOf(
			PropTypes.objectOf(PropTypes.string),
		),
		params: PropTypes.shape({
			title: PropTypes.string,
			shouldResizeContent: PropTypes.bool,
			showActionLoader: PropTypes.bool,
			showPartiallyHidden: PropTypes.bool,
			mediumPositionPercent: PropTypes.number,
			helpUrl: PropTypes.string,
		}),
		analyticsLabel: PropTypes.object,
		onCancel: PropTypes.func,
		onClose: PropTypes.func,
	};

	module.exports = {
		Icon,
		ContextMenu,
		ImageAfterTypes,
		ContextMenuItem,
		BadgeCounterDesign,
	};
});

(() => {
	const { ContextMenu } = jn.require('layout/ui/context-menu');

	this.ContextMenu = ContextMenu;
})();
