import {ProductForm} from 'catalog.product-form';
import 'currency';
import {MixinTemplatesType} from './components/templates-type-mixin';
import {Runtime, Type, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';
import type {BaseEvent} from 'main.core.events';
import {Loc} from 'main.core';

export default {
	mixins:[MixinTemplatesType],
	mounted()
	{
		if (this.$root.$app.options.templateMode === 'view')
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

		const defaultCurrency = this.$root.$app.options.currencyCode || '';

		this.$store.dispatch('orderCreation/setCurrency', defaultCurrency);
		if (Type.isArray(this.$root.$app.options.basket))
		{
			const fields = [];
			this.$root.$app.options.basket.forEach((item) => {
				fields.push(item.fields);
			});
			this.$store.commit('orderCreation/setBasket', fields);
		}
		if (Type.isObject(this.$root.$app.options.totals))
		{
			this.$store.commit('orderCreation/setTotal', this.$root.$app.options.totals);
		}

		this.productForm = new ProductForm(
			{
				currencySymbol: this.$root.$app.options.currencySymbol,
				currency: defaultCurrency,
				iblockId: this.$root.$app.options.catalogIblockId,
				basePriceId: this.$root.$app.options.basePriceId,
				basket: Type.isArray(this.$root.$app.options.basket) ? this.$root.$app.options.basket : [],
				totals: this.$root.$app.options.totals,
				taxList: this.$root.$app.options.vatList,
				measures: this.$root.$app.options.measures,
				showDiscountBlock: this.$root.$app.options.showProductDiscounts,
				showTaxBlock: this.$root.$app.options.showProductTaxes,
				totalResultLabel: this.$root.$app.options.mode === 'delivery' ? Loc.getMessage('SALESCENTER_SHIPMENT_PRODUCT_BLOCK_TOTAL') : null,
				urlBuilderContext: this.$root.$app.options.urlProductBuilderContext,
				isCatalogPriceEditEnabled: this.$root.$app.options.isCatalogPriceEditEnabled,
				fieldHints: this.$root.$app.options.fieldHints,
				hideUnselectedProperties: (this.$root.$app.options.templateMode === 'view'),
				showCompilationModeSwitcher: (this.$root.$app.options.showCompilationModeSwitcher === 'Y'),
			}
		);

		this.checkProductErrors();

		EventEmitter.subscribe(
			this.productForm,
			'ProductForm:onBasketChange',
			Runtime.debounce(this.onBasketChange, 500, this)
		);

		EventEmitter.subscribe(
			this.productForm,
			'ProductForm:onErrorsChange',
			Runtime.debounce(this.checkProductErrors, 500, this)
		);
	},
	methods: {
		onBasketChange(event: BaseEvent)
		{
			let processRefreshRequest = (data) => {
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
			};

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
					data: {
						orderId : this.$root.$app.orderId,
						basketItems: fields
					}
				}
			)
				.then((result) => {
					if (this.refreshId !== requestId)
					{
						return;
					}

					const data = BX.prop.getObject(result,"data", {});
					processRefreshRequest({
						total: BX.prop.getObject(
							data,
							"total",
							{
								discount: 0,
								result: 0,
								sum: 0,
								//resultNumeric: 0,
							}
						),
						basket: BX.prop.get(data,"items",[])
					});
				})
				.catch((result) => {
					const data = BX.prop.getObject(result,"data", {});
					processRefreshRequest({
						errors: BX.prop.get(result,"errors", []),
						basket: BX.prop.get(data,"items",[])
					});
				});
		},
		checkProductErrors()
		{
			if (this.isNeedDisableSubmit())
			{
				this.$store.commit('orderCreation/disableSubmit');
			}
			else
			{
				this.$store.commit('orderCreation/enableSubmit')
			}
		},
		isNeedDisableSubmit()
		{
			const basket = this.$store.getters['orderCreation/getBasket']();

			if (
				basket.length <= 0
				//|| !this.$root.$app.hasClientContactInfo()
				|| (this.productForm && Type.isFunction(this.productForm.hasErrors) && this.productForm.hasErrors())
			)
			{
				return true;
			}

			const filledProducts = basket.filter((item) => {
				return (Type.isStringFilled(item.module) && item.productId > 0);
			});

			return filledProducts.length <= 0;
		}
	},
	template: `
		<div class="salescenter-app-payment-side">
			<div class="salescenter-app-page-content">
				<div class="salescenter-app-form-wrapper"></div>
				<slot name="footer"></slot>
			</div>
		</div>
	`,
}
