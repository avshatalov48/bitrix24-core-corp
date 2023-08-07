/**
 * @module catalog/store/product-list/menu/add-product-menu
 */
jn.define('catalog/store/product-list/menu/add-product-menu', (require, exports, module) => {
	const { AnalyticsLabel } = require('analytics-label');

	/**
	 * @class StoreDocumentAddProductMenu
	 */
	class StoreDocumentAddProductMenu
	{
		constructor(props)
		{
			this.props = props || {};
			this.items = this.buildItems();
			this.menuInstance = new ContextMenu({
				actions: this.items,
				params: {
					showCancelButton: false,
					showActionLoader: false,
				},
			});
		}

		buildItems()
		{
			return [
				{
					id: 'db',
					title: BX.message('CSPL_MENU_CHOOSE_FROM_DB_2'),
					subTitle: '',
					data: {
						svgIcon: SvgIcons.db.content,
					},
					onClickCallback: this.callback.bind(this, 'onChooseDb'),
				},
				{
					id: 'barcodescanner',
					title: BX.message('CSPL_MENU_SEARCH_PRODUCT_BY_BARCODE_2'),
					subTitle: '',
					data: {
						svgIcon: SvgIcons.barcode.content,
					},
					onClickCallback: this.callback.bind(this, 'onChooseBarcode'),
				},
			];
		}

		callback(eventName)
		{
			this.menuInstance.close(() => {
				if (this.props[eventName])
				{
					this.props[eventName]();

					AnalyticsLabel.send({
						event: eventName,
						entity: 'store-document',
					});
				}
			});

			return Promise.resolve();
		}

		show()
		{
			this.menuInstance.show();
		}
	}

	const SvgIcons = {
		barcode: {
			content: '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.78866 6.71875H6.09961V23.2761H7.78866V6.71875ZM23.9001 6.71879H22.2111V23.2762H23.9001V6.71879ZM15.8367 6.71875H17.3265V23.2761H15.8367V6.71875ZM15.2365 6.71879H13.5474V23.2762H15.2365V6.71879ZM8.5338 6.71879H11.5471V23.2762H8.5338V6.71879ZM20.6215 6.71879H18.2371V23.2762H20.6215V6.71879Z" fill="#525C69"/></svg>',
		},
		db: {
			content: '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.8757 6.1145C14.8903 6.10691 14.9045 6.09956 14.9181 6.09541C14.9706 6.08034 15.0154 6.09227 15.0724 6.12134L23.4264 9.42799C23.6408 9.52702 23.721 9.71647 23.7164 10.0394V13.3737C23.0618 13.1762 22.3677 13.07 21.6488 13.07C17.7018 13.07 14.502 16.2697 14.502 20.2168C14.502 21.8153 15.0269 23.2913 15.9137 24.4818L15.128 24.7922C15.0177 24.8223 14.8843 24.8288 14.7825 24.7836L6.55171 21.5287C6.3882 21.4653 6.26403 21.2403 6.26172 21.0035V9.97052C6.2679 9.72081 6.34348 9.50876 6.55171 9.41081L14.8626 6.12124L14.8757 6.1145ZM14.9673 7.94442L21.4289 10.5095L14.9673 13.0584L8.49995 10.5014L14.9673 7.94442ZM20.5878 15.9356H22.71V19.1555H25.93V21.2777H22.71V24.4978H20.5878V21.2777H17.3678V19.1555H20.5878V15.9356Z" fill="#8FBC00"/></svg>',
		},
	};

	module.exports = { StoreDocumentAddProductMenu };
});
