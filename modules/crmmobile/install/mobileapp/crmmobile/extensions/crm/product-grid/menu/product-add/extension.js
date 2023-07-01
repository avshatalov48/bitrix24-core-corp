/**
 * @module crm/product-grid/menu/product-add
 */
jn.define('crm/product-grid/menu/product-add', (require, exports, module) => {
	const { Loc } = require('loc');
	const { FloatingMenuItem } = require('layout/ui/detail-card/floating-button/menu/item');
	const { AnalyticsLabel } = require('analytics-label');

	const MenuItemId = {
		SELECTOR: 'db',
		BARCODE_SCANNER: 'barcodescanner',
	};

	/**
	 * @class ProductAddMenu
	 */
	class ProductAddMenu
	{
		static getFloatingMenuItems()
		{
			return [
				new FloatingMenuItem({
					id: MenuItemId.SELECTOR,
					title: Loc.getMessage('PRODUCT_GRID_MENU_PRODUCT_ADD_CHOOSE_FROM_CATALOG_MSGVER_2'),
					shortTitle: Loc.getMessage('PRODUCT_GRID_MENU_PRODUCT_ADD_CHOOSE_FROM_CATALOG_SHORT'),
					isSupported: true,
					isAvailable: true,
					checkUnsavedChanges: false,
					position: 100,
					icon: SvgIcons.product,
				}),
				new FloatingMenuItem({
					id: MenuItemId.BARCODE_SCANNER,
					title: Loc.getMessage('PRODUCT_GRID_MENU_PRODUCT_ADD_OPEN_BARCODE_SCANNER'),
					shortTitle: Loc.getMessage('PRODUCT_GRID_MENU_PRODUCT_ADD_OPEN_BARCODE_SCANNER_SHORT'),
					isSupported: true,
					isAvailable: true,
					checkUnsavedChanges: false,
					position: 200,
					icon: SvgIcons.barcode,
				}),
			];
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
			return [
				{
					id: MenuItemId.SELECTOR,
					title: Loc.getMessage('PRODUCT_GRID_MENU_PRODUCT_ADD_CHOOSE_FROM_CATALOG_MSGVER_2'),
					subTitle: '',
					data: {
						svgIcon: SvgIcons.product,
					},
					onClickCallback: this.callback.bind(this, MenuItemId.SELECTOR),
				},
				{
					id: MenuItemId.BARCODE_SCANNER,
					title: Loc.getMessage('PRODUCT_GRID_MENU_PRODUCT_ADD_OPEN_BARCODE_SCANNER'),
					subTitle: '',
					data: {
						svgIcon: SvgIcons.barcode,
					},
					onClickCallback: this.callback.bind(this, MenuItemId.BARCODE_SCANNER),
				},
			];
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

	const SvgIcons = {
		product: '<svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.3792 6.40512C15.3941 6.39736 15.4085 6.38984 15.4225 6.3856C15.4762 6.37018 15.522 6.38239 15.5803 6.41212L24.1246 9.79412C24.3439 9.89541 24.426 10.0892 24.4212 10.4195V13.8297C23.7518 13.6277 23.0419 13.5192 22.3066 13.5192C18.2696 13.5192 14.997 16.7918 14.997 20.8288C14.997 22.4638 15.5338 23.9734 16.4407 25.191L15.6371 25.5084C15.5243 25.5393 15.3879 25.5459 15.2838 25.4996L6.86545 22.1706C6.69821 22.1057 6.57121 21.8756 6.56885 21.6334V10.349C6.57517 10.0936 6.65247 9.87673 6.86545 9.77655L15.3657 6.41202L15.3792 6.40512ZM15.4728 8.27675L22.0816 10.9003L15.4728 13.5073L8.85809 10.892L15.4728 8.27675Z" fill="#6a737f"/><path d="M21.2212 16.4501H23.3918V19.7433H26.6852V21.9139H23.3918V25.2074H21.2212V21.9139H17.9279V19.7433H21.2212V16.4501Z" fill="#6a737f"/></svg>',
		barcode: '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.78866 6.71875H6.09961V23.2761H7.78866V6.71875ZM23.9001 6.71879H22.2111V23.2762H23.9001V6.71879ZM15.8367 6.71875H17.3265V23.2761H15.8367V6.71875ZM15.2365 6.71879H13.5474V23.2762H15.2365V6.71879ZM8.5338 6.71879H11.5471V23.2762H8.5338V6.71879ZM20.6215 6.71879H18.2371V23.2762H20.6215V6.71879Z" fill="#6a737f"/></svg>',
		db: '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.6456 5.79785C9.88908 5.79785 6.0332 7.0818 6.0332 8.66562V10.2616L6.0333 10.2753V20.1967V20.4891V21.7927C6.0333 23.3765 9.88918 24.6605 14.6456 24.6605C15.261 24.6605 15.8613 24.639 16.4402 24.5982C15.538 23.452 14.9998 22.006 14.9998 20.4342C14.9998 16.7131 18.0164 13.6965 21.7375 13.6965C22.2604 13.6965 22.7694 13.7561 23.258 13.8688V9.05607H23.2579V8.66562C23.2579 7.0818 19.402 5.79785 14.6456 5.79785ZM14.7205 11.3508C17.9995 11.3508 20.6577 10.4499 20.6577 9.33871C20.6577 8.22749 17.9995 7.32667 14.7205 7.32667C11.4415 7.32667 8.78326 8.22749 8.78326 9.33871C8.78326 10.4499 11.4415 11.3508 14.7205 11.3508ZM20.6815 16.1966H22.7784V19.3781H25.96V21.475H22.7784V24.6567H20.6815V21.475H17.4999V19.3781H20.6815V16.1966Z" fill="#6a737f"/></svg>',
	};

	module.exports = {
		ProductAddMenu,
		MenuItemId,
	};
});
