import {Vue} from 'ui.vue';
import {Loc} from 'main.core';
import HistoryItemMixin from '../../mixins/history-item';
import ProductListMixin from '../../mixins/product-list';
import ProductList from '../../components/product/product-list';

export default Vue.extend({
	mixins: [HistoryItemMixin, ProductListMixin],
	components: {
		'product-list': ProductList,
	},
	created() {
		this.products = this.data.SENT_PRODUCTS;
		this.dealId = this.data.DEAL_ID;
		this.productsGrid = null;
		this.subscribeCustomEvents();
		BX.Crm.EntityEditor.getDefault().tapController('PRODUCT_LIST', (controller) => {
			this.setProductsGrid(controller.getProductList());
		});
	},
	computed: {
		showProductLink()
		{
			return this.data.showProductLink === undefined || this.data.showProductLink === true;
		}
	},
	methods: {
		isAddToDealVisible()
		{
			return this.isProductsGridAvailable() && this.data.canAddProductToDeal === true;
		},
	},
	// language=Vue
	template: `
		<div class="crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-history crm-entity-stream-section-advice">
			<div class="crm-entity-stream-section-icon crm-entity-stream-section-icon-advice"></div>
			<div class="crm-entity-stream-advice-content">
				<div class="crm-entity-stream-advice-info">
					${Loc.getMessage('CRM_TIMELINE_PRODUCT_COMPILATION_LIST_COMPILATION_SENT')}
				</div>
				<div class="crm-entity-stream-advice-inner">
					<!--<ul class="crm-entity-stream-advice-list">-->
					<product-list
						:products="products"
						:dealId="dealId"
						:isAddToDealVisible="isAddToDealVisible()"
						:showProductLink="showProductLink"
						@product-added-to-deal="handleProductAddedToDeal"
						@product-adding-to-deal="handleProductAddingToDeal"
					></product-list>
					<!--</ul>-->
				</div>
			</div>
		</div>
	`
});
