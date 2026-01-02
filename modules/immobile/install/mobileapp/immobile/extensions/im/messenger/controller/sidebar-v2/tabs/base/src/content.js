/**
 * @module im/messenger/controller/sidebar-v2/tabs/base/src/content
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/base/src/content', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { assertDefined } = require('utils/validation');
	const { SpinnerLoader, SpinnerDesign } = require('layout/ui/loaders/spinner');
	const { EmptyScreen } = require('im/messenger/controller/sidebar-v2/ui/empty-screen');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SidebarPermissionManager } = require('im/messenger/controller/sidebar-v2/controller/base');
	const { resolveSidebarType, SidebarType } = require('im/messenger/controller/sidebar-v2/factory');

	/**
	 * @abstract class SidebarBaseTabContent
	 */
	class SidebarBaseTabContent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const { dialogId, widgetNavigator, dialogLocator } = props;

			assertDefined(dialogId, 'dialogId property is required');

			this.dialogId = dialogId;
			this.dialogHelper = DialogHelper.createByDialogId(this.dialogId);

			/** @type {SidebarWidgetNavigator} */
			this.widgetNavigator = widgetNavigator;
			assertDefined(this.widgetNavigator, 'widgetNavigator property is required');

			this.dialogLocator = dialogLocator;
			this.store = serviceLocator.get('core').getStore();
			this.storeManager = serviceLocator.get('core').getStoreManager();
			this.currentUserId = serviceLocator.get('core').getUserId();
			this.logger = LoggerManager.getInstance().getLogger(`SidebarV2.TabContent.${this.constructor.name}`);
			this.permissionManager = new SidebarPermissionManager({
				dialogId: this.dialogId,
				dialogHelper: this.dialogHelper,
			});
		}

		/**
		 * @protected
		 * @return {boolean}
		 */
		isPending()
		{
			return this.state.pending === true;
		}

		/**
		 * @protected
		 * @return {boolean}
		 */
		isReady()
		{
			return !this.isPending();
		}

		/**
		 * @protected
		 * @return {boolean}
		 */
		hasNextPage()
		{
			return Boolean(this.state.hasNextPage);
		}

		/**
		 * @protected
		 * @return {boolean}
		 */
		isLastPage()
		{
			return !this.hasNextPage();
		}

		render()
		{
			return View(
				{
					testId: `${this.props.testId}-container`,
					style: {
						flex: 1,
					},
				},
				this.isPending() && this.renderLoader(),
				this.isReady() && this.renderContent(),
			);
		}

		renderLoader()
		{
			return View(
				{
					style: {
						flex: 1,
						paddingTop: 80,
						alignItems: 'center',
					},
				},
				SpinnerLoader({
					testId: 'loader',
					design: SpinnerDesign.BLUE,
				}),
			);
		}

		/**
		 * @protected
		 */
		renderContent()
		{
			return this.hasItems()
				? this.renderItemsContainer()
				: this.renderEmptyScreen();
		}

		/**
		 * @protected
		 * @abstract
		 */
		renderItemsContainer() {}

		/**
		 * @protected
		 */
		renderEmptyScreen()
		{
			const isNotes = resolveSidebarType(this.dialogId) === SidebarType.notes;
			const description = isNotes
				? Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_NOTES_TAB_EMPTY_SUBTITLE')
				: undefined;

			return new EmptyScreen({
				description,
				onScroll: this.props.onScroll,
				onOverscrollTop: this.props.onOverscrollTop,
				onOverscrollBottom: this.props.onOverscrollBottom,
				topOffset: this.props.topOffset,
				workingAreaHeight: this.props.workingAreaHeight,
				...this.getEmptyScreenProps(),
			});
		}

		/**
		 * @public
		 * @abstract
		 * @param {boolean} animated
		 * @return {Promise}
		 */
		scrollToBegin(animated = true)
		{
			return Promise.reject(new Error('Method not implemented'));
		}

		/**
		 * @protected
		 * @abstract
		 */
		bindMethods()
		{}

		/**
		 * @protected
		 * @abstract
		 */
		subscribeStoreEvents()
		{}

		/**
		 * @protected
		 * @abstract
		 */
		unsubscribeStoreEvents()
		{}

		/**
		 * @protected
		 * @return {boolean}
		 */
		hasItems()
		{
			return this.getItems().length > 0;
		}

		/**
		 * @protected
		 * @return {Array}
		 */
		getItems() {}

		/**
		 * @protected
		 * @return {boolean}
		 */
		isEmpty()
		{
			return !this.hasItems();
		}
	}

	module.exports = { SidebarBaseTabContent };
});
