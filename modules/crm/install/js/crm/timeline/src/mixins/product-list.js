import {UI} from 'ui.notification';
import {Loc} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Vue} from 'ui.vue';

export default {
	data()
	{
		return {
			notificationBalloon: false,
			activeRequestsCnt: 0,
			dealId: null,
			products: [],
		};
	},

	methods: {
		isProductsGridAvailable()
		{
			return !!this.productsGrid;
		},
		setProductsGrid(productsGrid)
		{
			this.productsGrid = productsGrid;
			if (this.productsGrid)
			{
				this.onProductsGridChanged();
			}
		},
		// region event handlers
		handleProductAddingToDeal()
		{
			this.activeRequestsCnt += 1;
		},
		handleProductAddedToDeal()
		{
			if (this.activeRequestsCnt > 0)
			{
				this.activeRequestsCnt -= 1;
			}

			if (!(this.activeRequestsCnt === 0 && this.productsGrid))
			{
				return;
			}

			BX.Crm.EntityEditor.getDefault().reload();
			this.productsGrid.reloadGrid(false);

			if (!this.notificationBalloon || this.notificationBalloon.getState() !== BX.UI.Notification.State.OPEN)
			{
				this.notificationBalloon = UI.Notification.Center.notify({
					content: Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_PRODUCTS_ADDED_TO_DEAL'),
					actions: [
						{
							title: Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_EDIT_PRODUCTS'),
							events: {
								click: (event, balloon, action) => {
									BX.onCustomEvent(window, 'OpenEntityDetailTab', ['tab_products']);
									balloon.close();
								},
							},
						},
					],
				});
			}
		},
		// endregion
		// region custom events
		subscribeCustomEvents()
		{
			EventEmitter.subscribe('EntityProductListController', this.onProductsGridCreated);
			EventEmitter.subscribe('BX.Crm.EntityEditor:onSave', this.onProductsGridChanged);
		},
		unsubscribeCustomEvents()
		{
			EventEmitter.unsubscribe('EntityProductListController', this.onProductsGridCreated);
			EventEmitter.unsubscribe('BX.Crm.EntityEditor:onSave', this.onProductsGridChanged);
		},
		onProductsGridCreated(event: BaseEvent)
		{
			this.setProductsGrid(event.getData()[0]);
		},
		onProductsGridChanged(event: BaseEvent)
		{
			if (!this.productsGrid)
			{
				return;
			}

			let dealOfferIds = this.productsGrid.products.map((product, index) => {
				if (!(product.hasOwnProperty('fields') && product.fields.hasOwnProperty('OFFER_ID')))
				{
					return null;
				}

				return product.fields.OFFER_ID;
			});

			for (const [i, product] of this.products.entries())
			{
				let isInDeal = dealOfferIds.some(id => parseInt(id) === parseInt(product.offerId));

				if (product.isInDeal === isInDeal)
				{
					continue;
				}

				Vue.set(this.products, i, Object.assign({}, product, {isInDeal}));
			}
		},
		// endregion
		beforeDestroy()
		{
			this.unsubscribeCustomEvents();
		}
	},
}