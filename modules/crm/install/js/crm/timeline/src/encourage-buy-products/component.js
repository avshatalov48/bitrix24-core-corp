import {Vue} from 'ui.vue';
import {BaseEvent, EventEmitter, Dom} from 'main.core.events';
import HistoryItemMixin from '../mixins/history-item';
import Product from './product';
import {Loc} from 'main.core';
import {UI} from 'ui.notification';
import './styles.css';

export default Vue.extend({
	mixins: [HistoryItemMixin],
	components: {
		'product': Product,
	},
	data()
	{
		return {
			isShortList: true,
			shortListProductsCnt: 3,
			isNotificationShown: false,
			activeRequestsCnt: 0,
			dealId: null,
			products: [],
			isProductsGridAvailable: false,
		};
	},
	created() {
		this.products = this.data.VIEWED_PRODUCTS;
		this.dealId = this.data.DEAL_ID;
		this._productsGrid = null;
		this.subscribeCustomEvents();
		BX.Crm.EntityEditor.getDefault().tapController('PRODUCT_LIST', (controller) => {
			this.setProductsGrid(controller.getProductList());
		});
	},
	methods: {
		setProductsGrid(productsGrid)
		{
			this._productsGrid = productsGrid;
			if (this._productsGrid)
			{
				this.onProductsGridChanged();
				this.isProductsGridAvailable = true;
			}
		},
		showMore()
		{
			this.isShortList = false;
			const listWrap = document.querySelector('.crm-entity-stream-advice-list');
			listWrap.style.maxHeight = 950 + 'px';
		},
		// region event handlers
		handleProductAddingToDeal()
		{
			this.activeRequestsCnt++;
		},
		handleProductAddedToDeal()
		{
			if (this.activeRequestsCnt > 0)
			{
				this.activeRequestsCnt--;
			}

			if (!(this.activeRequestsCnt === 0 && this._productsGrid))
			{
				return;
			}

			BX.Crm.EntityEditor.getDefault().reload();
			this._productsGrid.reloadGrid(false);

			if (!this.isNotificationShown)
			{
				UI.Notification.Center.notify({
					content: Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_PRODUCTS_ADDED_TO_DEAL'),
					events: {
						onClose: (event) => {
							this.isNotificationShown = false;
						}
					},
					actions: [
						{
							title: Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_EDIT_PRODUCTS'),
							events: {
								click: (event, balloon, action) => {
									BX.onCustomEvent(window, 'OpenEntityDetailTab', ['tab_products']);
									balloon.close();
								}
							}
						}
					]
				});

				this.isNotificationShown = true;
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
			if (!this._productsGrid)
			{
				return;
			}

			let dealOfferIds = this._productsGrid.products.map((product, index) => {
				if (!(product.hasOwnProperty('fields') && product.fields.hasOwnProperty('OFFER_ID')))
				{
					return null;
				}

				return product.fields.OFFER_ID;
			});

			for (const [i, product] of this.products.entries())
			{
				let isInDeal = dealOfferIds.some(id => id == product.offerId);

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
	computed: {
		visibleProducts()
		{
			let result = [];

			let i = 1;
			for (const product of this.products)
			{
				if (this.isShortList && i > this.shortListProductsCnt)
				{
					break;
				}

				result.push(product);

				i++;
			}

			return result;
		},
		isShowMoreVisible()
		{
			return this.isShortList && this.products.length > this.shortListProductsCnt;
		},
	},
	template: `
		<div class="crm-entity-stream-section crm-entity-stream-section-advice">
			<div class="crm-entity-stream-section-icon crm-entity-stream-section-icon-advice"></div>
			<div class="crm-entity-stream-advice-content">
				<div class="crm-entity-stream-advice-info">
					${Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_LOOK_AT_CLIENT_PRODUCTS')}
					${Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_ENCOURAGE_CLIENT_BUY_PRODUCTS')}
				</div>
				<div class="crm-entity-stream-advice-inner">
					<h3 class="crm-entity-stream-advice-subtitle">
						${Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_VIEWED_PRODUCTS')}
					</h3>
					<!--<ul class="crm-entity-stream-advice-list">-->
					<transition-group class="crm-entity-stream-advice-list" name="list" tag="ul">						
						<product
							v-for="product in visibleProducts"
							v-bind:key="product"
							:product="product"
							:dealId="dealId"
							:isAddToDealVisible="isProductsGridAvailable"
							@product-added-to-deal="handleProductAddedToDeal"
							@product-adding-to-deal="handleProductAddingToDeal"
						></product>
					</transition-group>
					<!--</ul>-->
					<a
						v-if="isShowMoreVisible"
						@click.prevent="showMore"
						class="crm-entity-stream-advice-link"
						href="#"
					>
						${Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_SHOW_MORE')}
					</a>
				</div>
			</div>
		</div>
	`
});
