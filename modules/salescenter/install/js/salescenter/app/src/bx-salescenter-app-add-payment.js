import {config} from './config';

import {Vue} from 'ui.vue';
import {Vuex} from 'ui.vue.vuex';
import {Manager} from 'salescenter.manager';
import {ProductForm} from 'catalog.product-form';
import "currency";

import "./bx-salescenter-app-add-payment-product";
import {MixinTemplatesType} from "./components/deal-receiving-payment/templates-type-mixin";
import {BasketItemAddBlock} from "./components/basket-item-add";
import {Runtime, Type, Text} from "main.core";
import {EventEmitter} from "main.core.events";
import type {BaseEvent} from "main.core.events";

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
			if (this.productForm)
			{
				this.productForm.setEditable(false);
			}
		}
		if (this.productForm)
		{
			const formWrapper = this.$root.$el.querySelector('.salescenter-app-form-wrapper');
			formWrapper.appendChild(this.productForm.layout());
		}
	},
	created()
	{
		this.refreshId = null;
		this.currencySymbol = this.$root.$app.options.currencySymbol;

		const defaultCurrency = this.$root.$app.options.currencyCode || '';

		this.$store.dispatch('orderCreation/setCurrency', defaultCurrency);
		if (Type.isArray(this.$root.$app.options.basket))
		{
			const fields = [];
			this.$root.$app.options.basket.forEach((item) => {
				fields.push(item.fields);
			});
			this.$store.commit('orderCreation/setBasket', fields);
			this.$store.commit('orderCreation/setTotal', this.$root.$app.options.totals);

			if (this.isNeedDisableSubmit())
			{
				this.$store.commit('orderCreation/disableSubmit');
			}
			else
			{
				this.$store.commit('orderCreation/enableSubmit')
			}
		}

		this.productForm = new ProductForm({
			currencySymbol: this.currencySymbol,
			currency: defaultCurrency,
			iblockId: this.$root.$app.options.catalogIblockId,
			basePriceId: this.$root.$app.options.basePriceId,
			basket: Type.isArray(this.$root.$app.options.basket) ? this.$root.$app.options.basket : [],
			totals: this.$root.$app.options.totals,
			taxList: this.$root.$app.options.vatList,
			measures: this.$root.$app.options.measures,
			showDiscountBlock: this.$root.$app.options.showProductDiscounts,
			showTaxBlock: this.$root.$app.options.showProductTaxes,
			urlBuilderContext: this.$root.$app.options.urlProductBuilderContext,
		});

		this.currencySymbol = this.$root.$app.options.currencySymbol;

		const onChangeWithDebounce = Runtime.debounce(this.onBasketChange, 500, this);
		EventEmitter.subscribe(this.productForm, 'ProductForm:onBasketChange', onChangeWithDebounce);

		if (this.$root.$app.options.showPaySystemSettingBanner)
		{
			this.$store.commit('orderCreation/showBanner');
		}
	},
	methods:
	{
		onBasketChange(event: BaseEvent)
		{
			const data = event.getData();
			if (!Type.isArray(data.basket))
			{
				return;
			}
			const fields = [];
			data.basket.forEach((item) => {
				fields.push(item.fields);
			});
			this.$store.commit('orderCreation/setBasket', fields);

			if (this.isNeedDisableSubmit())
			{
				this.$store.commit('orderCreation/disableSubmit');
				return;
			}

			this.$store.commit('orderCreation/enableSubmit');

			const requestId = Text.getRandom(20);
			this.refreshId = requestId;
			BX.ajax.runAction(
				"salescenter.api.order.refreshBasket",
				{
					data: {basketItems: fields}
				}
			)
			.then((result) => {
				if (this.refreshId !== requestId)
				{
					return;
				}
				
				const data = BX.prop.getObject(result,"data", {});
				this.processRefreshRequest({
					total: BX.prop.getObject(
						data,
						"total",
						{
							sum: 0,
							discount: 0,
							result: 0,
						}
					),
					basket: BX.prop.get(data,"items",[])
				});
			})
			.catch((result) => {
				const data = BX.prop.getObject(result,"data", {});
				this.processRefreshRequest({
					errors: BX.prop.get(result,"errors", []),
					basket: BX.prop.get(data,"items",[])
				});
			});
		},
		processRefreshRequest(data)
		{
			if (this.productForm)
			{
				const preparedBasket = [];
				data.basket.forEach((item) => {
					if (!Type.isStringFilled(item.innerId))
					{
						return;
					}
					preparedBasket.push({
						selectorId: item.innerId,
						fields: item,
					});
				});

				this.productForm.setData({...data, ...{basket: preparedBasket}});
				if (Type.isArray(data.basket))
				{
					this.$store.commit('orderCreation/setBasket', data.basket);
				}
				if (Type.isObject(data.total))
				{
					this.$store.commit('orderCreation/setTotal', data.total);
				}
			}
		},
		refreshBasket(timeout = 300)
		{
			this.$root.$app.startProgress();
			this.$store.dispatch('orderCreation/refreshBasket', {
				timeout,
				onsuccess: () => {
					this.$root.$app.stopProgress();
				},
			});
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
			if (this.productForm)
			{
				this.productForm.addProduct();
			}
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
		isNeedDisableSubmit()
		{
			if (this.countItems <= 0)
			{
				return true;
			}

			const simpleProducts = this.order.basket.filter((item) => {
				return (!Type.isStringFilled(item.module) || item.productId <= 0) && Type.isStringFilled(item.name);
			});

			return simpleProducts.length > 0;
		}
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
			<div class="salescenter-app-form-wrapper"></div>
			<div class="salescenter-app-banner" v-if="isShowedBanner">
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