import {config} from './config';

import {Vue} from 'ui.vue';
import {Vuex} from 'ui.vue.vuex';
import {Manager} from 'salescenter.manager';
import "currency";

import "./bx-salescenter-app-add-payment-product";
import {MixinTemplatesType} from "./components/templates-type-mixin";
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

		handleIMessagePayment(event)
		{
			const payment = event.target.name;
			this.$root.$app.setIMessage(payment, event.target.checked);
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

		iMessageAvailable()
		{
			return this.$root.$app.isApplePayAvailable && this.$root.$app.connector === 'imessage';
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
				
				<div class="salescenter-app-result-grid-row salescenter-app-result-grid-total-sm">
					<component :is="'basket-item-add-block'" v-if="editable"
						v-on:on-refresh-basket="refreshBasket"
						v-on:on-add-basket-item="addBasketItemForm"
						v-on:on-change-basket-item="changeBasketItem"
					>
						<template v-slot:product-add-title>{{localize.SALESCENTER_PRODUCT_ADD_PRODUCT}}</template>
						<template v-slot:product-add-from-catalog-title>{{localize.SALESCENTER_PRODUCT_ADD_PRODUCT_FROM_CATALOG}}</template>
					</component>
					<div class="salescenter-app-form-col" style="flex: 1; display: flex; justify-content: flex-end; padding: 0;">
						<div class="salescenter-app-result-grid-item">{{localize.SALESCENTER_PRODUCT_TOTAL_SUM}}:</div>
						<div class="salescenter-app-result-grid-item salescenter-app-result-grid-item-currency" :class="total.result !== total.sum ? 'salescenter-app-text-line-through' : ''" v-html="total.sum"></div>
						<div class="salescenter-app-result-grid-item-currency-symbol" v-html="currencySymbol"></div>
					</div>
				</div>
				<div class="salescenter-app-result-grid-row salescenter-app-result-grid-benefit salescenter-app-result-grid-total-sm">
					<div class="salescenter-app-result-grid-item">{{localize.SALESCENTER_PRODUCT_TOTAL_DISCOUNT}}:</div>
					<div class="salescenter-app-result-grid-item salescenter-app-result-grid-item-currency" v-html="total.discount"></div>
					<div class="salescenter-app-result-grid-item-currency-symbol salescenter-app-result-grid-item" v-html="currencySymbol"></div>
				</div>
				<div class="salescenter-app-result-grid-row salescenter-app-result-grid-total salescenter-app-result-grid-total-big">
					<div class="salescenter-app-result-grid-item">{{localize.SALESCENTER_PRODUCT_PRODUCTS_PRICE}}:</div>
					<div class="salescenter-app-result-grid-item salescenter-app-result-grid-item-currency" v-html="total.result"></div>
					<div class="salescenter-app-result-grid-item-currency-symbol" v-html="currencySymbol"></div>
				</div>
			</div>
			<div v-if="iMessageAvailable" class="salescenter-app-payment-container">
				<label class="ui-ctl ui-ctl-checkbox">
					<input type="checkbox" class="ui-ctl-element" @change="handleIMessagePayment($event)">
					<div class="ui-ctl-label-text">{{localize.SALESCENTER_IMESSAGE_PAYMENT}}</div>
				</label>
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