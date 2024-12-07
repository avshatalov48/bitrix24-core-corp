/**
 * @module layout/ui/detail-card/floating-button/menu
 */
jn.define('layout/ui/detail-card/floating-button/menu', (require, exports, module) => {
	const { RecentGridView } = require('layout/ui/detail-card/floating-button/menu/recent/grid-view');
	const { MenuRecentStorage } = require('layout/ui/detail-card/floating-button/menu/recent/storage');
	const { ImageAfterTypes } = require('layout/ui/context-menu/item');
	const { ContextMenu } = require('layout/ui/context-menu');

	const UNSUPPORTED_SECTION = 'unsupported';

	/**
	 * @class FloatingButtonMenu
	 */
	class FloatingButtonMenu
	{
		constructor({ detailCard, items, useRecent = false })
		{
			/** @type {DetailCardComponent} */
			this.detailCard = detailCard;
			this.items = items;

			/** @type {MenuRecentStorage|null} */
			this.recentStorage = null;

			if (useRecent)
			{
				this.recentStorage = new MenuRecentStorage({
					entityTypeId: this.detailCard.getEntityTypeId(),
					categoryId: this.detailCard.getComponentParams().categoryId,
				});
			}
		}

		/**
		 * @public
		 * @param {string} actionId
		 * @param {?string} tabId
		 */
		onAddToRecent(actionId, tabId = null)
		{
			if (this.recentStorage)
			{
				this.recentStorage.addEvent(actionId, tabId);
			}
		}

		/**
		 * @public
		 * @return {FloatingMenuItem|null}
		 */
		getActiveTabMenuItem()
		{
			const finder = (item) => item.getTabId() === this.detailCard.activeTab && item.isActive();

			return this.buildMenuItems().find(finder) || null;
		}

		/**
		 * @public
		 * @param {string} actionId
		 * @param {?string} tabId
		 * @return {FloatingMenuItem|null}
		 */
		getMenuItem(actionId, tabId = null)
		{
			const finder = (item) => {
				if (tabId && item.getTabId() !== tabId)
				{
					return false;
				}

				return item.getId() === actionId;
			};

			return this.getNestedItemsRecursive().find(finder) || null;
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		showContextMenu()
		{
			this.contextMenu = new ContextMenu({
				testId: 'FLOATING_BUTTON',
				customSection: this.getGridViewRecentItems(),
				actions: this.prepareMenuActions(),
				params: {
					showActionLoader: false,
					showCancelButton: false,
					showPartiallyHidden: true,
				},
				analyticsLabel: {
					...this.detailCard.getEntityAnalyticsData(),
					source: 'detail-card-context-menu',
					entityTypeId: this.detailCard.getEntityTypeId(),
				},
			});

			return this.contextMenu.show();
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		closeContextMenu()
		{
			if (this.contextMenu)
			{
				return new Promise((resolve) => this.contextMenu.close(resolve));
			}

			return Promise.resolve();
		}

		getGridViewRecentItems()
		{
			const items = this.getRecentItems();

			if (items.length < 3)
			{
				return null;
			}

			return {
				layout: new RecentGridView({
					items,
					detailCard: this.detailCard,
				}),
				height: RecentGridView.getHeight(),
			};
		}

		/**
		 * @private
		 * @return {FloatingMenuItem[]}
		 */
		buildMenuItems()
		{
			/** @var {FloatingMenuItem[]} items */
			let items = [...this.items];

			items = items.filter((item) => (item.isAvailable() && !item.isDisabled()));
			items.sort((a, b) => a.getPosition() - b.getPosition());

			return items;
		}

		/**
		 * @private
		 * @return {FloatingMenuItem[]}
		 */
		getNestedItemsRecursive(skipRootItems = false)
		{
			const nestedItems = [];

			this.items.forEach((item) => {
				if (!skipRootItems)
				{
					nestedItems.push(item);
				}

				nestedItems.push(...item.getNestedItemsRecursive(true));
			});

			return nestedItems;
		}

		/**
		 * @private
		 * @return {FloatingMenuItem[]}
		 */
		getRecentItems()
		{
			if (!this.recentStorage)
			{
				this.getNestedItemsRecursive(false).forEach((item) => item.setIsRecent(false));

				return [];
			}

			const recentItems = [];

			const rankedItems = this.recentStorage.getRankedItems();
			const nestedItems = this.getNestedItemsRecursive(true);

			rankedItems.forEach((item) => {
				const nestedItem = nestedItems.find((nestedItem) => {
					return nestedItem.getId() === item.actionId && nestedItem.getTabId() === item.tabId;
				});

				if (nestedItem && nestedItem.isActive())
				{
					nestedItem.setIsRecent(true);
					recentItems.push(nestedItem);
				}
			});

			nestedItems.forEach((item) => {
				const foundInRecent = recentItems.find((recentItem) => {
					return recentItem.getId() === item.getId() && recentItem.getTabId() === item.getTabId();
				});
				if (foundInRecent)
				{
					return;
				}

				if (item && item.isActive() && item.isAvailableRecentMenu())
				{
					item.setIsRecent(true);
					recentItems.push(item);
				}
			});

			return recentItems;
		}

		getRecentPosition(actionId, tabId)
		{
			const findHash = `${tabId || 'root'}/${actionId}`;

			return this.getRecentItems().findIndex((item) => {
				const itemHash = `${item.getTabId() || 'root'}/${item.getId()}`;

				return itemHash === findHash;
			});
		}

		prepareMenuActions()
		{
			let items = this.buildMenuItems();

			const activeItems = items.filter((item) => item.isActive());
			if (activeItems.length === 1 && activeItems[0].hasNestedItems())
			{
				items = activeItems[0].getNestedItems();
			}

			const actions = items.map((menuItem) => this.prepareMenuAction(menuItem));
			const supportedItems = actions.filter((action) => action.sectionCode !== UNSUPPORTED_SECTION);
			const unsupportedItems = actions.filter((action) => action.sectionCode === UNSUPPORTED_SECTION);

			return [
				...supportedItems,
				...unsupportedItems,
			];
		}

		getIconAfter(itemIconAfter)
		{
			if (itemIconAfter && itemIconAfter.type)
			{
				return itemIconAfter.type;
			}

			return null;
		}

		/**
		 * @param {FloatingMenuItem} menuItem
		 * @return {*}
		 */
		prepareMenuAction(menuItem)
		{
			return {
				id: menuItem.getId(),
				title: menuItem.getTitle(),
				subtitle: menuItem.getSubtitle(),
				subtitleType: menuItem.getSubtitleType(),
				sectionCode: menuItem.isSupported() ? menuItem.getSectionCode() : UNSUPPORTED_SECTION,
				dimmed: !menuItem.isSupported(),
				showActionLoader: menuItem.shouldShowLoader(),
				showArrow: menuItem.shouldShowArrow(),
				badges: menuItem.getBadges(),
				icon: menuItem.getIcon(),
				data: {
					svgIcon: menuItem.getIcon(),
					svgIconAfter: {
						type: menuItem.isSupported()
							? this.getIconAfter(menuItem.getIconAfter())
							: ImageAfterTypes.WEB,
					},
				},
				onClickCallback: menuItem.getOnClickCallback(),
			};
		}
	}

	module.exports = { FloatingButtonMenu };
});
