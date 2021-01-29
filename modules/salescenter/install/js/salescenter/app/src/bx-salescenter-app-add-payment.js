import {config} from './config';

import {Vue} from 'ui.vue';
import {Vuex} from 'ui.vue.vuex';
import {Manager} from 'salescenter.manager';
import "currency";

import "./bx-salescenter-app-add-payment-product";
import {MixinTemplatesType} from "./components/deal-receiving-payment/templates-type-mixin";
import {BasketItemAddBlock} from "./components/basket-item-add";

Vue.component(config.templateAddPaymentName,
{
	mixins:[MixinTemplatesType],
	components: {
		'basket-item-add-block': BasketItemAddBlock,
	},
	data()
	{
		return {};
	},
	mounted()
	{
		if(parseInt(this.$root.$app.options.associatedEntityId)>0)
		{
			this.$root.$emit("on-change-editable", false);
		}
	},
	created()
	{
		this.currencySymbol = this.$root.$app.options.currencySymbol;

		const defaultCurrency = this.$root.$app.options.currencyCode || '';

		this.$store.dispatch('orderCreation/setCurrency', defaultCurrency);

		if (BX.type.isArray(this.$root.$app.options.basket) && this.$root.$app.options.basket.length>0)
		{
			this.$root.$app.options.basket.forEach((fields) => {
				this.$store.dispatch('orderCreation/changeBasketItem', {
					index : fields.sort,
					fields : fields
				});
			});

			if (typeof (this.$root.$app.options.totals) !== "undefined")
			{
				this.$store.commit('orderCreation/setTotal', {
					sum: this.$root.$app.options.totals.sum,
					discount: this.$root.$app.options.totals.discount,
					result: this.$root.$app.options.totals.result,
					resultNumeric: parseFloat(this.$root.$app.options.totals.result),
				});
			}
		}
		else
		{
			this.addBasketItemForm();

			this.$store.commit('orderCreation/setTotal', {
				sum: 0,
				discount: 0,
				result: 0,
			});
		}

		if (this.$root.$app.options.showPaySystemSettingBanner)
		{
			this.$store.commit('orderCreation/showBanner');
		}
	},
	methods:
	{
		refreshBasket()
		{
			this.$store.dispatch('orderCreation/refreshBasket');
		},
		changeBasketItem(item)
		{
			this.$store.dispatch('orderCreation/changeBasketItem', {
				index: item.index,
				fields: item.fields
			});
		},
		removeItem(item)
		{
			this.$store.dispatch('orderCreation/removeItem', {
				index: item.index
			});
			this.refreshBasket();
		},
		addBasketItemForm()
		{
			this.$store.commit('orderCreation/addBasketItem');
		},
		hideBanner()
		{
			this.$store.commit('orderCreation/hideBanner');
			const userOptionName = this.$root.$app.options.orderCreationOption || false;
			const userOptionKeyName = this.$root.$app.options.paySystemBannerOptionName || false;
			if (userOptionName && userOptionKeyName)
			{
				BX.userOptions.save('salescenter', userOptionName, userOptionKeyName, 'Y');
			}
		},
		openControlPanel()
		{
			Manager.openControlPanel();
		},
	},
	computed:
	{
		localize()
		{
			return Vue.getFilteredPhrases('SALESCENTER_');
		},

		total()
		{
			return this.order.total;
		},

		countItems()
		{
			return this.order.basket.length;
		},

		isShowedBanner()
		{
			return this.order.showPaySystemSettingBanner;
		},

		...Vuex.mapState({
			order: state => state.orderCreation,
		})
	},
	template: `
	<div class="salescenter-app-payment-side">
		<div class="salescenter-app-page-content">
			<div v-for="(item, index) in order.basket" class="salescenter-app-form-wrapper" :key="index">
				<${config.templateAddPaymentProductName} 
					:basketItem="item" 
					:basketItemIndex="index"  
					:countItems="countItems"
					:selectedProductIds="order.selectedProducts"
					@changeBasketItem="changeBasketItem" 
					@removeItem="removeItem" 
					@refreshBasket="refreshBasket" 
				/>
			</div>
			<div class="salescenter-app-result-container"  style="padding-right: 15px">
				<table class="salescenter-app-payment-side-table">
					<tr>
						<td colspan="2">
							<component :is="'basket-item-add-block'" v-if="editable"
								v-on:on-refresh-basket="refreshBasket"
								v-on:on-add-basket-item="addBasketItemForm"
								v-on:on-change-basket-item="changeBasketItem"
							>
								<template v-slot:product-add-title>{{localize.SALESCENTER_PRODUCT_ADD_PRODUCT}}</template>
								<template v-slot:product-add-from-catalog-title>{{localize.SALESCENTER_PRODUCT_ADD_PRODUCT_FROM_CATALOG}}</template>
							</component>
						</td>
					</tr>
					<tr style="text-align: right;">
						<td>{{localize.SALESCENTER_PRODUCT_TOTAL_SUM}}:</td>
						<td>
							<span :class="total.result !== total.sum ? 'salescenter-app-text-line-through' : ''" v-html="total.sum"></span>
							<span class="salescenter-app-result-grid-item-currency-symbol" v-html="currencySymbol"></span>
						</td>
					</tr>
					<tr style="text-align: right;">
						<td class="salescenter-app-result-grid-benefit salescenter-app-payment-side-table-td-border">{{localize.SALESCENTER_PRODUCT_TOTAL_DISCOUNT}}:</td>
						<td class="salescenter-app-payment-side-table-td-border">
							<span v-html="total.discount"></span>
							<span class="salescenter-app-result-grid-item-currency-symbol" v-html="currencySymbol"></span>
						</td>
					</tr>
					<tr style="text-align: right;">
						<td class="salescenter-app-result-grid-total-big">{{localize.SALESCENTER_PRODUCT_PRODUCTS_PRICE}}:</td>
						<td class="salescenter-app-result-grid-total-big">
							<span v-html="total.result"></span>
							<span class="salescenter-app-result-grid-item-currency-symbol" v-html="currencySymbol"></span>
						</td>
					</tr>
				</table>
			</div>
			<div class="salescenter-app-banner"  v-if="isShowedBanner">
				<div class="salescenter-app-banner-inner">
					<div class="salescenter-app-banner-title">{{localize.SALESCENTER_BANNER_TITLE}}</div>
					<div class="salescenter-app-banner-content">
						<div class="salescenter-app-banner-text">{{localize.SALESCENTER_BANNER_TEXT}}</div>
						<div class="salescenter-app-banner-btn-block">
							<button class="ui-btn ui-btn-sm ui-btn-primary salescenter-app-banner-btn-connect" @click="openControlPanel">{{localize.SALESCENTER_BANNER_BTN_CONFIGURE}}</button>
							<button class="ui-btn ui-btn-sm ui-btn-link salescenter-app-banner-btn-hide" @click="hideBanner">{{localize.SALESCENTER_BANNER_BTN_HIDE}}</button>
						</div>
					</div>
					<div class="salescenter-app-banner-close" @click="hideBanner"></div>
				</div>
			</div>
		</div>
	</div>
`,
});