import {Vuex} from 'ui.vue.vuex';
import {Popup} from 'main.popup';
import {Loc} from 'main.core';

const BasketItemAddBlock = {
	props: [],
	methods:
	{
		refreshBasket()
		{
			this.$emit('on-refresh-basket');
		},
		changeBasketItem(item)
		{
			this.$emit('on-change-basket-item', item);
		},
		addBasketItemForm()
		{
			this.$emit('on-add-basket-item');
		},
		getInternalIndexByProductId(productId)
		{
			let basket = this.$store.getters['orderCreation/getBasket']();
			return Object
				.keys(basket)
				.findIndex((inx) =>{
					return parseInt(basket[inx].productId) === parseInt(productId)
				});
		},
		onAddBasketItem(params)
		{
			this.$store.commit('orderCreation/addBasketItem');
			let basketItemIndex = this.countItems-1;
			this.$store.commit('orderCreation/updateBasketItem', {
				index : basketItemIndex,
				fields : params
			});

			let basketItem = this.order.basket[basketItemIndex];

			if (basketItem.id === undefined || parseInt(basketItem.id) <= 0)
				return true;

			let fields = {
				name: basketItem.name,
				productId: basketItem.id,
				sort: basketItemIndex,
				module: 'catalog',
				quantity: basketItem.quantity > 0 ? basketItem.quantity : 1,
			};

			BX.ajax.runAction(
				"salescenter.api.order.getFileControl",
				{ data: { productId: basketItem.id } }
			).then((result) => {
				let data = BX.prop.getObject(result, "data", {});
				if (data.fileControl)
				{
					let fileControl = BX.processHTML(data.fileControl);
					fields.fileControlHtml = fileControl['HTML'];
				}

				this.changeBasketItem({
					index:  basketItemIndex,
					fields: fields
				});
				this.refreshBasket();
			});
		},
		onUpdateBasketItem(inx, fields)
		{
			this.$store.dispatch('orderCreation/changeBasketItem', {
				index : inx,
				fields : fields
			});
		},
		/*
		* By default, basket collection contains a fake|empty item,
		*  that is deleted when you select items from the catalog.
		* Also, products can be added to the form and become an empty string,
		*  while stay a item of basket collection
		* */
		removeEmptyItems()
		{
			let basket = this.$store.getters['orderCreation/getBasket']();
			basket.forEach((item, i)=>{
				if(
					basket[i].name === ''
					&& basket[i].price < 1e-10
				)
				{
					this.$store.dispatch('orderCreation/deleteBasketItem', {
						index: i
					});
				}
			});
		},
		modifyBasketItem(params)
		{
			let productId = parseInt(params.id);
			if(productId > 0)
			{
				let inx = this.getInternalIndexByProductId(productId);
				if(inx >= 0)
				{
					this.showDialogProductExists(params);
				}
				else
				{
					this.removeEmptyItems();
					this.onAddBasketItem(params);
				}
			}
		},
		showDialogProductExists(params)
		{
			this.popup = new Popup(null, null, {
				events: {
					onPopupClose: () => {this.popup.destroy()}
				},
				zIndex: 4000,
				autoHide: true,
				closeByEsc: true,
				closeIcon: true,
				titleBar: Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_PROD_EXIST_DLG_TITLE'),
				draggable: true,
				resizable: false,
				lightShadow: true,
				cacheable: false,
				overlay: true,
				content: Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_PROD_EXIST_DLG_TEXT').replace('#NAME#', params.name),
				buttons: this.getButtons(params),
			});

			this.popup.show();
		},
		getButtons(product)
		{
			let buttons = [];
			let params = product;
			buttons.push(
				new BX.UI.SaveButton(
					{
						text : Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_PROD_EXIST_DLG_OK'),
						onclick: () => {
							let productId = parseInt(params.id);
							let inx = this.getInternalIndexByProductId(productId);
							if(inx >= 0)
							{
								let item = this.$store.getters['orderCreation/getBasket']()[inx];
								let fields = {
									quantity: parseInt(item.quantity) + 1
								};
								this.onUpdateBasketItem(inx, fields);
							}
							this.popup.destroy();
						}
					}
				)
			);

			buttons.push(
				new BX.UI.CancelButton(
					{
						text : Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_PROD_EXIST_DLG_NO'),
						onclick: () => {this.popup.destroy()}
					}
				)
			);
			return buttons;
		},
		showDialogProductSearch()
		{
			let funcName = 'addBasketItemFromDialogProductSearch';
			window[funcName] = params => this.modifyBasketItem(params);

			let popup = new BX.CDialog({
				content_url: '/bitrix/tools/sale/product_search_dialog.php?'+
					//todo: 'lang='+this._settings.languageId+
					//todo: '&LID='+this._settings.siteId+
					'&caller=order_edit'+
					'&func_name='+funcName+
					'&STORE_FROM_ID=0'+
					'&public_mode=Y',
				height: Math.max(500, window.innerHeight-400),
				width: Math.max(800, window.innerWidth-400),
				draggable: true,
				resizable: true,
				min_height: 500,
				min_width: 800,
				zIndex: 3100
			});

			popup.Show();
		},
	},
	computed:
	{
		countItems()
		{
			return this.order.basket.length;
		},
		...Vuex.mapState({
			order: state => state.orderCreation,
		})
	},
	template: `
		<div class="salescenter-app-form-col" style="flex: 1; white-space: nowrap;">
			<a class="salescenter-app-add-item-link" @click="addBasketItemForm">
				<slot name="product-add-title"></slot>
			</a>
			<a class="salescenter-app-add-item-link salescenter-app-add-item-link-catalog" @click="showDialogProductSearch">
				<slot name="product-add-from-catalog-title"></slot>
			</a>
		</div>
	`
};

export {
	BasketItemAddBlock,
}