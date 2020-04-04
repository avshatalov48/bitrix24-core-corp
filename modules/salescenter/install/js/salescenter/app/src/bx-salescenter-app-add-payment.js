import {config} from './config';

import {Vue} from 'ui.vue';
import {Vuex} from 'ui.vue.vuex';
import {Manager} from 'salescenter.manager';
import "currency";

import "./bx-salescenter-app-add-payment-product";

Vue.component(config.templateAddPaymentName,
{
	data()
	{
		return {};
	},
	created()
	{
		const defaultCurrency = this.$root.$app.options.currencyCode || '';
		if (this.$root.$app.options.showPaySystemSettingBanner)
		{
			this.$store.commit('orderCreation/showBanner');
		}
		const defaultPrice = BX.Currency.currencyFormat(0, defaultCurrency, true);
		this.$store.dispatch('orderCreation/setCurrency', defaultCurrency);
		this.$store.commit('orderCreation/setTotal', {
			sum: defaultPrice,
			discount: defaultPrice,
			result: defaultPrice,
		});
		this.addBasketItemForm();
	},
	methods:
	{
		refreshBasket(timeout = 300)
		{
			this.$store.dispatch('orderCreation/refreshBasket', {timeout});
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
			<div v-for="(item, index) in order.basket" class="salescenter-app-form-wrapper">
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
			<div class="salescenter-app-add-item-container">
				<a @click="addBasketItemForm" class="salescenter-app-add-item-link">{{localize.SALESCENTER_PRODUCT_ADD_PRODUCT}}</a>
			</div>
			<div class="salescenter-app-result-container">
				<div class="salescenter-app-result-grid-row">
					<div class="salescenter-app-result-grid-item">{{localize.SALESCENTER_PRODUCT_TOTAL_SUM}}:</div>
					<div class="salescenter-app-result-grid-item" :class="total.result !== total.sum ? 'salescenter-app-text-line-through' : ''" v-html="total.sum"></div>
				</div>
				<div class="salescenter-app-result-grid-row salescenter-app-result-grid-benefit">
					<div class="salescenter-app-result-grid-item">{{localize.SALESCENTER_PRODUCT_TOTAL_DISCOUNT}}:</div>
					<div class="salescenter-app-result-grid-item" v-html="total.discount"></div>
				</div>
				<div class="salescenter-app-result-grid-row salescenter-app-result-grid-total">
					<div class="salescenter-app-result-grid-item">{{localize.SALESCENTER_PRODUCT_TOTAL_RESULT}}:</div>
					<div class="salescenter-app-result-grid-item" v-html="total.result"></div>
				</div>
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