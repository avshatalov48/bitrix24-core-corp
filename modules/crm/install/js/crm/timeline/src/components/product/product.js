import {ajax, Loc} from 'main.core';
import 'currency';
import 'ui.design-tokens';
import './styles.css';

export default {
	props: {
		product: {
			required: true,
			type: Object,
		},
		dealId: {
			required: true,
			type: Number,
		},
		isAddToDealVisible: {
			required: true,
			type: Boolean,
		},
		showProductLink: {
			default: true,
			type: Boolean,
		},
	},
	methods: {
		addProductToDeal()
		{
			if (this.product.isInDeal)
			{
				return;
			}

			this.$emit('product-adding-to-deal');
			this.product.isInDeal = true;

			ajax.runAction(
				'crm.timeline.encouragebuyproducts.addproducttodeal',
				{
					data: {
						dealId: this.dealId,
						productId: this.product.offerId,
						options: {
							price: this.product.price
						}
					}
				}
			).then((result) => {
				this.$emit('product-added-to-deal');
				this.product.isInDeal = true;
			}).catch((result) => {
				this.product.isInDeal = false;
			});
		},
		openDetailPage()
		{
			if (BX.type.isNotEmptyString(this.product.adminLink))
			{
				if (this.product?.slider === 'N')
				{
					window.open(this.product.adminLink, '_blank');
				}
				else
				{
					BX.SidePanel.Instance.open(this.product.adminLink);
				}
			}
		}
	},
	computed: {
		isBottomAreaVisible()
		{
			return this.isVariationInfoVisible || this.isPriceVisible;
		},
		isVariationInfoVisible()
		{
			return (
				this.product.hasOwnProperty('variationInfo')
				&& this.product.variationInfo
			);
		},
		isPriceVisible()
		{
			return (
				this.product.hasOwnProperty('price')
				&& this.product.hasOwnProperty('currency')
				&& this.product.price
				&& this.product.currency
			);
		},
		price()
		{
			return BX.Currency.currencyFormat(this.product.price, this.product.currency, true);
		},
		imageStyle()
		{
			if (!this.product.image)
			{
				return {};
			}

			return {
				backgroundImage: 'url(' + this.product.image + ')'
			};
		},
		buttonText()
		{
			return Loc.getMessage(
				this.product.isInDeal
					? 'CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_PRODUCT_IN_DEAL'
					: 'CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_ADD_PRODUCT_TO_DEAL'
			);
		},
	},
	// language=Vue
	template: `
		<li
			:class="{'crm-entity-stream-advice-list-item--active': product.isInDeal}"
			class="crm-entity-stream-advice-list-item"
		>
			<div class="crm-entity-stream-advice-list-content">
				<div
					:style="imageStyle"
					class="crm-entity-stream-advice-list-icon"
				>
				</div>
				<div class="crm-entity-stream-advice-list-inner">
					<a
						v-if="showProductLink"
						@click.prevent="openDetailPage"
						href="#"
						class="crm-entity-stream-advice-list-name"
					>
						{{product.name}}
					</a>
					<span
						v-else
						class="crm-entity-stream-advice-list-name"
					>
						{{product.name}}
					</span>

					<div
						v-if="isBottomAreaVisible"
						class="crm-entity-stream-advice-list-desc-box"
					>
						<span
							v-if="isVariationInfoVisible"
							class="crm-entity-stream-advice-list-desc-name"
						>
							{{product.variationInfo}}
						</span>
						<span
							v-if="isPriceVisible"
							v-html="price"
							class="crm-entity-stream-advice-list-desc-value"
						>

						</span>
					</div>
				</div>
			</div>
			<div v-if="isAddToDealVisible" class="crm-entity-stream-advice-list-btn-box">
				<button
					@click="addProductToDeal"
					class="ui-btn ui-btn-round ui-btn-xs crm-entity-stream-advice-list-btn"
				>
					{{buttonText}}
				</button>
			</div>
		</li>
	`
}
