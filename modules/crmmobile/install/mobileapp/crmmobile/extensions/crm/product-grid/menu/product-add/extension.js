/**
 * @module crm/product-grid/menu/product-add
 */
jn.define('crm/product-grid/menu/product-add', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { FloatingMenuItem } = require('layout/ui/detail-card/floating-button/menu/item');
	const { AnalyticsLabel } = require('analytics-label');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { ContextMenu } = require('layout/ui/context-menu');

	const MenuItemId = {
		SELECTOR: 'db',
		BARCODE_SCANNER: 'barcodescanner',
	};

	/**
	 * @class ProductAddMenu
	 */
	class ProductAddMenu
	{
		static getFloatingMenuItems(params = {})
		{
			const isSearchOnly = Boolean(params.isSearchOnly);
			const selectorText = isSearchOnly
				? Loc.getMessage('PRODUCT_GRID_MENU_PRODUCT_ADD_CHOOSE_FROM_CATALOG_SHORT_MSGVER_1')
				: Loc.getMessage('PRODUCT_GRID_MENU_PRODUCT_ADD_CHOOSE_FROM_CATALOG_MSGVER_2')
			;

			const items = [
				new FloatingMenuItem({
					id: TabType.CRM_PRODUCT,
					title: selectorText,
					shortTitle: Loc.getMessage('PRODUCT_GRID_MENU_PRODUCT_ADD_CHOOSE_FROM_CATALOG_SHORT_MSGVER_1'),
					isSupported: true,
					isAvailable: true,
					checkUnsavedChanges: false,
					position: 100,
					icon: Icon.PRODUCT,
				}),
			];

			if (!isSearchOnly)
			{
				items.push(new FloatingMenuItem({
					id: MenuItemId.BARCODE_SCANNER,
					title: Loc.getMessage('PRODUCT_GRID_MENU_PRODUCT_ADD_OPEN_BARCODE_SCANNER'),
					shortTitle: Loc.getMessage('PRODUCT_GRID_MENU_PRODUCT_ADD_OPEN_BARCODE_SCANNER_SHORT'),
					isSupported: true,
					isAvailable: true,
					isAvailableRecentMenu: false,
					shouldSaveInRecent: false,
					isShowRecentMenu: false,
					checkUnsavedChanges: false,
					position: 200,
					icon: Icon.BARCODE,
				}));
			}

			return items;
		}

		constructor(props)
		{
			this.props = props || {};
			this.callbacks = this.props.callbacks || {};
			this.analytics = this.props.analytics || {};

			this.items = this.buildItems();
			this.menuInstance = new ContextMenu({
				actions: this.items,
				params: {
					showCancelButton: true,
					showActionLoader: false,
				},
			});
		}

		buildItems()
		{
			const isSearchOnly = Boolean(this.props.isCatalogHidden);
			const selectorText = isSearchOnly
				? Loc.getMessage('PRODUCT_GRID_MENU_PRODUCT_ADD_CHOOSE_FROM_CATALOG_SHORT_MSGVER_1')
				: Loc.getMessage('PRODUCT_GRID_MENU_PRODUCT_ADD_CHOOSE_FROM_CATALOG_MSGVER_2')
			;

			const items = [
				{
					id: MenuItemId.SELECTOR,
					title: selectorText,
					icon: Icon.ADD_PRODUCT,
					onClickCallback: this.callback.bind(this, MenuItemId.SELECTOR),
				},
			];

			if (!isSearchOnly)
			{
				items.push({
					id: MenuItemId.BARCODE_SCANNER,
					title: Loc.getMessage('PRODUCT_GRID_MENU_PRODUCT_ADD_OPEN_BARCODE_SCANNER'),
					icon: Icon.BARCODE,
					onClickCallback: this.callback.bind(this, MenuItemId.BARCODE_SCANNER),
				});
			}

			return items;
		}

		callback(eventName)
		{
			return this.menuInstance.close(() => this.handleAction(eventName));
		}

		handleAction(eventName)
		{
			const { callbacks } = this.props;

			if (callbacks[eventName])
			{
				callbacks[eventName]();

				AnalyticsLabel.send({
					event: `crm-entity-product-add-${eventName}`,
					entity: this.analytics.entityTypeName,
				});
			}
		}

		show()
		{
			return this.menuInstance.show();
		}
	}

	module.exports = {
		ProductAddMenu,
		MenuItemId,
	};
});
