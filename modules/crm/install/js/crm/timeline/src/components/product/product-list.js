import {Loc} from 'main.core';
import Product from './product';
import './styles.css';

export default {
	props: {
		products: [],
		dealId: null,
		isAddToDealVisible: false,
		showProductLink: true,
	},

	data()
	{
		return {
			isShortList: true,
			shortListProductsCnt: 3,
		};
	},

	components: {
		'product': Product,
	},

	methods: {
		onProductAddedToDeal()
		{
			this.$emit('product-added-to-deal');
		},

		onProductAddingToDeal()
		{
			this.$emit('product-adding-to-deal');
		},

		showMore()
		{
			this.isShortList = false;
			const listWrap = this.$refs.adviceList;
			listWrap.style.maxHeight = 950 + 'px';
		},
	},

	computed: {
		isShowMoreVisible()
		{
			return this.isShortList && this.products.length > this.shortListProductsCnt;
		},

		visibleProducts()
		{
			let result = [];

			const length =
				this.isShortList && this.shortListProductsCnt < this.products.length
					? this.shortListProductsCnt
					: this.products.length
			;
			for (let productIndex = 0; productIndex < length; productIndex++)
			{
				result.push(this.products[productIndex]);
			}

			return result;
		},
	},
	// language=Vue
	template: `
		<div>
			<transition-group ref="adviceList" class="crm-entity-stream-advice-list" name="list" tag="ul">
				<product
					v-for="product in visibleProducts"
					v-bind:key="product.offerId"
					:product="product"
					:dealId="dealId"
					:isAddToDealVisible="isAddToDealVisible"
					:showProductLink="showProductLink"
					@product-added-to-deal="onProductAddedToDeal"
					@product-adding-to-deal="onProductAddingToDeal"
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
	`
}
