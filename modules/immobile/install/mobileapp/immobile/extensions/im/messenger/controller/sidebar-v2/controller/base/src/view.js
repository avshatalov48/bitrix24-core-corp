/**
 * @module im/messenger/controller/sidebar-v2/controller/base/src/view
 */
jn.define('im/messenger/controller/sidebar-v2/controller/base/src/view', (require, exports, module) => {
	const { Type } = require('type');
	const { isEmpty } = require('utils/object');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { PrimaryButton } = require('im/messenger/controller/sidebar-v2/ui/primary-button');
	const { ChatDescription } = require('im/messenger/controller/sidebar-v2/ui/chat-description');
	const { SidebarAvatar } = require('im/messenger/controller/sidebar-v2/ui/sidebar-avatar');
	const { SidebarTopContainer } = require('im/messenger/controller/sidebar-v2/ui/top-container');
	const { SidebarPlanLimitBanner } = require('im/messenger/controller/sidebar-v2/ui/plan-limit-banner');
	const { Area } = require('ui-system/layout/area');
	const { Indent, Color } = require('tokens');
	const { CardList } = require('ui-system/layout/card-list');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { H4, BBCodeText, Text4 } = require('ui-system/typography');
	const { Avatar, AvatarShape } = require('ui-system/blocks/avatar');

	const isIos = Application.getPlatform() === 'ios';
	const isAndroid = !isIos;

	class SidebarBaseView extends LayoutComponent
	{
		/**
		 * @param {SidebarViewDefaultProps} props
		 */
		constructor(props)
		{
			super(props);

			this.dialogId = props.dialogId;
			this.dialogHelper = DialogHelper.createByDialogId(this.dialogId);
			this.logger = LoggerManager.getInstance().getLogger(`SidebarV2.View.${this.constructor.name}`);

			this.state = {
				selectedTab: null,
				tabs: Array.isArray(props.tabs) ? props.tabs : [],
				primaryActionButtons: Array.isArray(props.primaryActionButtons) ? props.primaryActionButtons : [],
				chatTitle: props.chatTitle,
				isMessagesAutoDeleteDelayEnabled: props.isMessagesAutoDeleteDelayEnabled,
			};

			this.layoutHeightRecalculated = false;
			this.verticalSliderRef = null;
			/** @type {SidebarTopContainer|null} */
			this.topContainerRef = null;
			this.tabSwitcherRef = null;
			this.tabSliderRef = null;
			this.topContainerCollapsed = false;
			this.topContainerToggleInProgress = false;
			this.topContainerCalculatedHeight = null;
			this.workingAreaCalculatedHeight = null;

			/** @type {Object.<string, SidebarBaseTabContent>} */
			this.listViewMap = {};

			this.store = serviceLocator.get('core').getStore();

			/** @type {LayoutWidget} */
			this.widget = props.widget;

			/** @type {SidebarWidgetNavigator} */
			this.widgetNavigator = props.widgetNavigator;

			this.callUserProfile = Type.isFunction(props.callbacks?.callUserProfile)
				? props.callbacks.callUserProfile
				: () => {}
			;

			this.customAvatarProps = props.customAvatarProps;
		}

		get chatTitle()
		{
			return this.state.chatTitle;
		}

		collapseTopContainer()
		{
			if (isAndroid)
			{
				return;
			}

			if (this.topContainerToggleInProgress || this.topContainerCollapsed || this.topContainerCalculatedHeight === null)
			{
				return;
			}

			this.topContainerToggleInProgress = true;

			const scrolledTitle = this.chatTitle.getTitle({ useNotes: true });
			if (scrolledTitle?.length > 0)
			{
				this.widget.setTitle({ text: scrolledTitle }, true);
			}

			this.verticalSliderRef?.animate({ top: -this.topContainerCalculatedHeight, duration: 300 }, () => {
				this.topContainerCollapsed = true;
				this.topContainerToggleInProgress = false;
			});
		}

		expandTopContainer()
		{
			if (isAndroid)
			{
				return;
			}

			if (this.topContainerToggleInProgress || this.topContainerCollapsed === false)
			{
				return;
			}

			this.topContainerToggleInProgress = true;

			this.widget.setTitle({ text: this.props.widgetTitle }, true);

			this.verticalSliderRef?.animate({ top: 0, duration: 300 }, () => {
				this.topContainerCollapsed = false;
				this.topContainerToggleInProgress = false;
			});
		}

		rememberTopContainerHeight = ({ height }) => {
			if (this.topContainerCalculatedHeight === null || this.topContainerCalculatedHeight !== height)
			{
				this.topContainerCalculatedHeight = height;
				this.recalculateLayoutHeight();
			}
		};

		rememberWorkingAreaHeight = ({ height }) => {
			if (this.workingAreaCalculatedHeight === null)
			{
				this.workingAreaCalculatedHeight = height;
				this.recalculateLayoutHeight();
			}
		};

		recalculateLayoutHeight()
		{
			if (this.topContainerCalculatedHeight === null || this.workingAreaCalculatedHeight === null)
			{
				return;
			}

			this.layoutHeightRecalculated = true;

			this.setState({});
		}

		/**
		 * @public
		 * @return {void}
		 */
		scrollToBegin()
		{
			this.scrollSelectedTabToBegin(false)
				.then(() => this.expandTopContainer())
				.catch((err) => this.logger.warn('Selected tab is unscrollable', err));
		}

		/**
		 * @public
		 * @param {boolean} animated
		 * @return {Promise}
		 */
		scrollSelectedTabToBegin(animated = true)
		{
			const selectedTab = this.getSelectedTab();
			if (!selectedTab)
			{
				return Promise.reject(new Error('No tabs found'));
			}

			const selectedTabId = selectedTab.getId();
			const selectedTabViewRef = this.listViewMap[selectedTabId];

			if (selectedTabViewRef?.scrollToBegin)
			{
				return selectedTabViewRef?.scrollToBegin(animated);
			}

			return Promise.reject(new Error('Scrollable ref not found'));
		}

		/**
		 * @protected
		 * @return {number}
		 */
		getSelectedTabIndex()
		{
			return this.state.selectedTab?.id
				? this.state.tabs.findIndex((t) => t.getId() === this.state.selectedTab.id)
				: 0;
		}

		/**
		 * @protected
		 * @return SidebarBaseTab|undefined
		 */
		getSelectedTab()
		{
			return this.state.selectedTab?.id
				? this.state.tabs.find((t) => t.getId() === this.state.selectedTab.id)
				: this.state.tabs[0];
		}

		/**
		 * Works similar as componentWillReceiveProps. Triggered by sidebar controller.
		 * Update state here if needed.
		 * @public
		 * @param {SidebarViewDefaultProps} nextProps
		 */
		refresh(nextProps = {})
		{
			if (!this.topContainerRef)
			{
				return;
			}

			this.dialogHelper = DialogHelper.createByDialogId(this.dialogId);

			const { primaryActionButtons, chatTitle, isMessagesAutoDeleteDelayEnabled } = nextProps;

			this.state = {
				...this.state,
				chatTitle,
				primaryActionButtons,
				isMessagesAutoDeleteDelayEnabled,
			};

			this.topContainerRef.refresh();
		}

		/**
		 * @protected
		 * @return {SidebarViewTheme}
		 */
		getTheme()
		{
			return {};
		}

		render()
		{
			return View(
				{
					onLayout: this.rememberWorkingAreaHeight,
					style: {
						flex: 1,
					},
				},
				View(
					{
						ref: (ref) => {
							this.verticalSliderRef = ref;
						},
						style: this.getVerticalSliderStyle(),
					},
					new SidebarTopContainer({
						ref: (ref) => {
							this.topContainerRef = ref;
						},
						onLayout: this.rememberTopContainerHeight,
						renderHeader: () => this.renderHeader(),
						renderDescription: () => this.renderDescription(),
						renderPrimaryActionButtons: () => this.renderPrimaryActionButtons(),
						renderPlanLimitBanner: () => this.renderPlanLimitBanner(),
					}),
					this.renderTabSwitcher(),
					this.renderTabSlider(),
				),
			);
		}

		getVerticalSliderStyle()
		{
			if (isAndroid)
			{
				return {
					flex: 1,
					width: '100%',
				};
			}

			return {
				height: this.layoutHeightRecalculated
					? (this.workingAreaCalculatedHeight + this.topContainerCalculatedHeight)
					: device.screen.height,
				width: '100%',
			};
		}

		renderHeader()
		{
			return Area(
				{
					isFirst: true,
					excludePaddingSide: {
						bottom: true,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'flex-start',
							alignItems: 'center',
							paddingRight: Indent.M.toNumber(),
							paddingLeft: Indent.XL.toNumber(),
						},
					},
					this.renderAvatar(),
					View(
						{
							style: {
								flex: 1,
								flexDirection: 'column',
								justifyContent: 'center',
							},
							onClick: () => this.callUserProfile(),
						},
						this.renderTitle(),
						this.renderChatInfo(),
					),
				),
			);
		}

		renderAvatar()
		{
			const isCustomAvatar = !isEmpty(this.customAvatarProps);
			const testId = 'avatar';
			const marginRight = Indent.XL3.toNumber();
			let avatar = null;

			if (isCustomAvatar)
			{
				avatar = Avatar({
					testId,
					...this.customAvatarProps,
					shape: AvatarShape[this.customAvatarProps.shape] ?? AvatarShape.CIRCLE,
					style: {
						marginRight,
						...this.customAvatarProps.style,
					},
				});
			}
			else
			{
				avatar = SidebarAvatar({
					testId,
					dialogId: this.dialogId,
					size: 72,
					style: {
						marginRight,
					},
				});
			}

			return View(
				{
					onClick: () => this.callUserProfile(),
				},
				avatar,
			);
		}

		renderTitle()
		{
			const { isMessagesAutoDeleteDelayEnabled } = this.state;

			return View(
				{
					testId: 'chat-title-container',
					style: {
						flexDirection: 'row',
						marginBottom: this.getTheme().titleGap ?? Indent.M.toNumber(),
					},
				},
				H4({
					testId: 'chat-title-value',
					text: this.chatTitle.getTitle({ useNotes: true }),
					numberOfLines: 2,
					ellipsize: 'end',
					style: {
						color: this.chatTitle.getTitleColor({ useNotes: true }),
						flexShrink: 1,
					},
				}),
				isMessagesAutoDeleteDelayEnabled && IconView({
					icon: Icon.SMALL_TIMER_DOT,
					size: 20,
					color: Color.base3,
					style: {
						marginLeft: Indent.XS2.toNumber(),
						top: 1,
					},
				}),
			);
		}

		renderChatInfo()
		{
			const titleParams = this.chatTitle.getTitleParams();
			const descriptionText = this.chatTitle.getDescription({ useNotes: true });
			const detailText = titleParams.detailText;

			return View(
				{
					testId: 'chat-info-container',
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
						},
					},
					Text4({
						text: descriptionText,
						testId: 'chat-info-description-value',
						color: Color.base1,
					}),
				),
				(detailText !== descriptionText) && BBCodeText({
					style: {
						marginTop: Indent.XS.toNumber(),
					},
					value: detailText,
					testId: 'chat-info-detail-text-value',
					size: 5,
					color: Color.base4,
				}),
			);
		}

		renderDescription()
		{
			const dialogModel = this.dialogHelper.dialogModel;
			if (!dialogModel.description?.length || dialogModel.description?.length === 0)
			{
				return null;
			}

			return ChatDescription({
				testId: 'chat-description',
				text: dialogModel.description,
				parentWidget: this.widget,
			});
		}

		renderPrimaryActionButtons()
		{
			const { primaryActionButtons } = this.state;

			if (primaryActionButtons.length === 0)
			{
				return null;
			}

			return Area(
				{
					excludePaddingSide: {
						horizontal: true,
					},
				},
				CardList(
					{
						testId: 'primary-action-buttons',
						horizontal: true,
						showsVerticalScrollIndicator: false,
						showsHorizontalScrollIndicator: false,
						style: {
							height: 78,
						},
					},
					...primaryActionButtons.map((btn, index) => PrimaryButton({
						testId: btn.id,
						testIdSuffix: btn.testIdSuffix,
						title: btn.title,
						icon: btn.icon,
						disabled: btn.disabled,
						counter: btn.counter,
						selected: btn.selected,
						onClick: btn.onClick,
						style: {
							marginLeft: index === 0 ? Indent.XL3.toNumber() : 0,
							marginRight: primaryActionButtons.length - 1 === index ? Indent.XL3.toNumber() : 0,
						},
					})),
				),
			);
		}

		renderPlanLimitBanner()
		{
			return new SidebarPlanLimitBanner({
				testId: 'plan-limit-banner',
				dialogId: this.dialogId,
			});
		}

		renderTabSwitcher()
		{
			if (this.state.tabs.length === 0)
			{
				return null;
			}

			return TabView({
				testId: 'tab-switcher',
				ref: (ref) => {
					this.tabSwitcherRef = ref;
				},
				style: {
					height: 51,
				},
				params: {
					items: this.state.tabs.map((tab) => ({
						id: tab.getId(),
						title: tab.getTitle(),
					})),
				},
				onTabSelected: (tab, changed, options) => {
					if (options?.action === 'click')
					{
						if (changed)
						{
							// clicked on title of another tab
							this.state.selectedTab = tab;
							void this.tabSliderRef?.scrollToPage(this.getSelectedTabIndex());
							this.collapseTopContainer();
						}
						else
						{
							// clicked on title of current tab
							this.scrollSelectedTabToBegin()
								.catch((err) => this.logger.warn('Selected tab is unscrollable', err));
						}
					}

					if (options?.action === 'code' && changed)
					{
						// swiped to another tab
						this.state.selectedTab = tab;
					}
				},
			});
		}

		renderTabSlider()
		{
			const { tabs } = this.state;

			if (tabs.length === 0)
			{
				return null;
			}

			// in ios vertical scroll offset can be less than zero,
			// that's why we need separate onScroll handlers for ios and android.
			const listViewScrollProps = isIos
				? this.getIosScrollHandlers()
				: this.getAndroidScrollHandlers();

			return Slider(
				{
					testId: 'tab-slider',
					style: {
						flex: 1,
					},
					ref: (ref) => {
						this.tabSliderRef = ref;
					},
					onPageChange: (nextPage) => {
						const tabId = tabs[nextPage]?.getId();
						if (tabId && this.tabSwitcherRef)
						{
							this.tabSwitcherRef.setActiveItem(tabId);
						}
					},
				},
				...(tabs.map((tab) => {
					const tabId = tab.getId();
					const TabContent = tab.getView();

					return View(
						{
							testId: 'tab-slider-slide',
							style: {
								flex: 1,
							},
						},
						TabContent({
							...listViewScrollProps,
							testId: `tab-content-${tabId}`,
							dialogId: this.dialogId,
							widget: this.widget,
							widgetNavigator: this.widgetNavigator,
							topOffset: this.topContainerCalculatedHeight,
							workingAreaHeight: this.workingAreaCalculatedHeight,
							ref: (ref) => {
								this.listViewMap[tabId] = ref;
							},
						}),
					);
				})),
			);
		}

		getIosScrollHandlers()
		{
			return {
				onScroll: ({ contentOffset }) => {
					const { y } = contentOffset;
					if (y > 0)
					{
						this.collapseTopContainer();
					}
					else if (y < 0)
					{
						this.expandTopContainer();
					}
				},
			};
		}

		getAndroidScrollHandlers()
		{
			return {};
		}
	}

	module.exports = { SidebarBaseView };
});
