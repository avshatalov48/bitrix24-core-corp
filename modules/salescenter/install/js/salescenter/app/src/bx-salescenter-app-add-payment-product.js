import {config} from "./config";
import {Vue} from "ui.vue";

import "ui.dropdown";
import "ui.common";
import "ui.alerts";

Vue.component(config.templateAddPaymentProductName,
{
	/**
	 * @emits 'changeBasketItem' {index: number, fields: object}
	 * @emits 'refreshBasket' {timeout: number}
	 * @emits 'removeItem' {index: number}
	 */

	props: ['basketItem', 'basketItemIndex', 'countItems', 'selectedProductIds'],
	data()
	{
		return {
			timer: null,
			productSelector: null,
			isNeedRebindSearch: false,
		};
	},
	created()
	{
		this.currencyName = this.$root.$app.options.currencyName || null;
		this.defaultMeasure = {
			name: '',
			id: null,
		};
		this.measures = this.$root.$app.options.measures || [];
		if (BX.type.isArray(this.measures) && this.measures)
		{
			this.measures.map((measure) => {
				if (measure['IS_DEFAULT'] === 'Y')
				{
					this.defaultMeasure.name = measure.SYMBOL;
					this.defaultMeasure.code = measure.CODE;
					this.changeData({
						measureCode: this.defaultMeasure.code,
						measureName: this.defaultMeasure.name
					});
				}
			});
		}
	},
	mounted()
	{
		this.productSelector = new BX.UI.Dropdown(
			{
				searchAction: "salescenter.api.order.searchProduct",
				searchOptions: {
					restrictedSearchIds: this.selectedProductIds
				},
				enableCreation: true,
				searchResultRenderer: null,
				targetElement: this.$refs.searchProductLine,
				items: [{
					title: '',
					subTitle: this.localize.SALESCENTER_PRODUCT_BEFORE_SEARCH_TITLE
				}],
				messages:
				{
					creationLegend: this.localize.SALESCENTER_PRODUCT_CREATE,
					notFound: this.localize.SALESCENTER_PRODUCT_NOT_FOUND,
				},
				events:
				{
					onSelect: this.selectCatalogItem.bind(this),
					onAdd: this.showCreationForm.bind(this),
					onReset: this.resetSearchForm(this)
				}
			}
		);
	},
	directives:
	{
		'bx-search-product':
		{
			inserted(element, binding)
			{
				if (binding.value.selector instanceof BX.UI.Dropdown)
				{
					const restrictedSearchIds = binding.value.restrictedIds;
					binding.value.selector.targetElement = element;
					if (BX.type.isArray(restrictedSearchIds))
					{
						binding.value.selector.searchOptions = {restrictedSearchIds};
						binding.value.selector.items = binding.value.selector.items.filter(
							item => !restrictedSearchIds.includes(item.id)
						);
					}
					binding.value.selector.init()
				}
			}
		}
	},
	methods:
	{
		toggleDiscount(value)
		{
			this.changeData(
				{showDiscount: value}
			);
		},
		changeData(fields)
		{
			this.$emit('changeBasketItem', {
				index: this.basketItemIndex,
				fields: fields
			});
		},
		isNeedRefreshAfterChanges()
		{
			if (this.isCreationMode)
			{
				return this.basketItem.name.length > 0
					&& this.basketItem.quantity > 0
					&& this.basketItem.basePrice > 0
			}

			return true;
		},
		refreshBasket()
		{
			if (this.isNeedRefreshAfterChanges())
				this.$emit('refreshBasket');
		},
		debouncedRefresh(delay)
		{
			if (this.timer)
			{
				clearTimeout(this.timer);
			}

			this.timer = setTimeout(() => {
				this.refreshBasket();
				this.timer = null;
			}, delay);
		},
		changeQuantity(event)
		{
			let newQuantity = parseFloat(event.target.value);
			if (!newQuantity)
			{
				return;
			}

			let fields = this.basketItem;
			fields.quantity = newQuantity;
			this.changeData(fields);

			this.debouncedRefresh(300);
		},
		changeName(event)
		{
			let newName = event.target.value;
			let fields = this.basketItem;
			fields.name = newName;
			this.changeData(fields);
			this.refreshBasket();
		},
		changeBasePrice(event)
		{
			let newPrice = Number(event.target.value);
			if (newPrice < 0)
			{
				return;
			}

			let fields = this.basketItem;
			fields.basePrice = newPrice;
			if (fields.module !== 'catalog')
			{
				fields.catalogPrice = newPrice;
			}
			fields.isCustomPrice = 'Y';
			this.changeData(fields);

			this.debouncedRefresh(300);
		},
		changeDiscountType(event)
		{
			let type = (event.target.value === 'currency') ? 'currency' : 'percent';
			let fields = this.basketItem;
			fields.discountType = type;
			fields.isCustomPrice = 'Y';
			this.changeData(fields);

			if (parseFloat(this.basketItem.discount) > 0)
			{
				this.refreshBasket();
			}
		},
		changeDiscount(event)
		{
			let discountValue = parseFloat(event.target.value) || 0;
			if (discountValue === parseFloat(this.basketItem.discount))
			{
				return;
			}

			let fields = this.basketItem;
			fields.discount = discountValue;
			fields.isCustomPrice = 'Y';
			this.changeData(fields);

			this.debouncedRefresh(300);
		},
		changeMeasureValue(event)
		{
			let measureCode = parseInt(event.target.value);
			let measureName = '';
			this.measures.forEach ((measure) => {
				if (parseInt(measure.CODE) === measureCode)
				{
					measureName = measure.SYMBOL;
				}
			});

			this.changeData({
				measureCode: measureCode,
				measureName: measureName
			});
		},
		showCreationForm()
		{
			if (!(this.productSelector instanceof BX.UI.Dropdown))
				return true;

			const value = this.productSelector.targetElement.value;
			this.changeData({
				productId: 0,
				quantity: 1,
				module: null,
				sort: this.basketItemIndex,
				isCreatedProduct: 'Y',
				name: value,
				encodedFields: null,
				isCustomPrice: 'Y',
				discountInfos: []
			});

			this.productSelector.destroyPopupWindow();
		},
		resetSearchForm()
		{
			if (!(this.productSelector instanceof BX.UI.Dropdown))
				return true;

			this.productSelector.targetElement.value = '';
			this.productSelector.items = [{
				title: '',
				subTitle: this.localize.SALESCENTER_PRODUCT_BEFORE_SEARCH_TITLE
			}];
			this.changeData({
				productId: 0,
				name: '',
				encodedFields: null,
				quantity: 0,
				basePrice: 0,
				formattedPrice: 0,
				catalogPrice: 0,
				formattedCatalogPrice: null,
				discount: 0,
				discountInfos: [],
				errors:[],
			});
			this.productSelector.destroyPopupWindow();
		},
		hideCreationForm()
		{
			if (!(this.productSelector instanceof BX.UI.Dropdown))
				return true;

			this.changeData({
				isCreatedProduct: 'N',
				productId: 0,
				name: '',
				encodedFields: null,
				quantity: 0,
				basePrice: 0,
				formattedPrice: 0,
				catalogPrice: 0,
				formattedCatalogPrice: null,
				discount: 0,
				discountInfos: [],
				errors:[],
			});
			this.refreshBasket();

			this.isNeedRebindSearch = true;
		},
		removeItem()
		{
			this.$emit('removeItem', {
				index: this.basketItemIndex
			});
		},
		selectCatalogItem(sender, item)
		{
			if (!sender instanceof BX.UI.Dropdown)
				return true;

			if (item.id === undefined || parseInt(item.id) <= 0)
				return true;

			let fields = {
				name: item.title,
				productId: item.id,
				sort: this.basketItemIndex,
				module: 'catalog',
				quantity: this.basketItem.quantity > 0 ? this.basketItem.quantity : 1,
			};

			if (this.basketItemIndex.productId !== item.id)
			{
				fields.encodedFields = null;
				fields.discount = 0;
				fields.isCustomPrice = 'N';
			}

			BX.ajax.runAction(
				"salescenter.api.order.getBaseProductPrice",
				{ data: { productId: item.id } }
			).then((result) => {
				fields.basePrice = BX.prop.getNumber(result,"data", 0);
				fields.catalogPrice = BX.prop.getNumber(result,"data", 0);
				this.changeData(fields);
				this.$emit('refreshBasket', 0);
			});
			sender.destroyPopupWindow();
		},
		openDiscountEditor(e, url)
		{
			if(!(window.top.BX.SidePanel && window.top.BX.SidePanel.Instance))
			{
				return;
			}

			window.top.BX.SidePanel.Instance.open (
				BX.util.add_url_param( url, { "IFRAME": "Y", "IFRAME_TYPE": "SIDE_SLIDER", "publicSidePanel": "Y" } ),
				{ allowChangeHistory: false }
			);

			e.preventDefault ? e.preventDefault() : (e.returnValue = false);
		},
	},
	computed:
	{
		localize()
		{
			return Vue.getFilteredPhrases('SALESCENTER_PRODUCT_');
		},
		showDiscount()
		{
			return this.basketItem.showDiscount === 'Y';
		},
		showCatalogPrice()
		{
			return this.basketItem.discount > 0 || parseFloat(this.basketItem.basePrice) !== parseFloat(this.basketItem.catalogPrice);
		},
		getMeasureName()
		{
			return this.basketItem.measureName || this.defaultMeasure.name;
		},
		getMeasureCode()
		{
			return this.basketItem.measureCode || this.defaultMeasure.code;
		},
		restrictedSearchIds()
		{
			let restrictedSearchIds = this.selectedProductIds;
			if (this.basketItem.module === 'catalog')
			{
				restrictedSearchIds = restrictedSearchIds.filter(id => id !== this.basketItem.productId);
			}

			return restrictedSearchIds;
		},
		isCreationMode()
		{
			return this.basketItem.isCreatedProduct === 'Y';
		},
		isNotEnoughQuantity()
		{
			return this.basketItem.errors.includes('SALE_BASKET_AVAILABLE_QUANTITY');
		},
		hasPriceError()
		{
			return this.basketItem.errors.includes('SALE_BASKET_ITEM_WRONG_PRICE');
		},
		isEmptyProductName()
		{
			return (this.basketItem.name.length === 0);
		},
	},
	template: `
		<div>
			<div class="salescenter-app-counter" v-if="countItems > 1">{{basketItemIndex + 1}}</div>
			<div class="salescenter-app-remove" @click="removeItem" v-if="countItems > 1"></div>
			<div class="salescenter-app-form-container" v-if="!isCreationMode">

				<div class="salescenter-app-form-row">
					<div class="salescenter-app-form-col" style="flex:8">

						<label class="salescenter-app-ctl-label-text ui-ctl-label-text">{{localize.SALESCENTER_PRODUCT_TITLE}}</label>
						<div class="ui-ctl ui-ctl-md ui-ctl-w100 ui-ctl-after-icon">
							<button class="ui-ctl-after ui-ctl-icon-clear" @click="resetSearchForm" v-if="basketItem.name.length > 0"> </button>
							<input 
								type="text"
								ref="searchProductLine" 
								class="ui-ctl-element ui-ctl-textbox salescenter-app-product-search" 
								:value="basketItem.name"
								v-bx-search-product="{selector: productSelector, restrictedIds: restrictedSearchIds}"
							>
						</div>
					</div>
					<div class="salescenter-app-form-col" style="flex:4">

						<label class="salescenter-app-ctl-label-text ui-ctl-label-text">
							{{localize.SALESCENTER_PRODUCT_QUANTITY.replace('#MEASURE_NAME#', getMeasureName)}}
						</label>
						<div class="ui-ctl ui-ctl-md ui-ctl-w100" :class="isNotEnoughQuantity ? 'ui-ctl-danger' : ''">
							<input type="text" class="ui-ctl-element ui-ctl-textbox" :value="basketItem.quantity" @input="changeQuantity" @change="refreshBasket">
						</div>
						<div class="salescenter-form-error" v-if="isNotEnoughQuantity">{{localize.SALESCENTER_PRODUCT_IS_NOT_AVAILABLE}}</div>
					</div>
				</div>
				<div class="salescenter-app-form-row">
					<div class="salescenter-app-form-col" style="flex:12">

						<label class="salescenter-app-ctl-label-text ui-ctl-label-text">
							{{localize.SALESCENTER_PRODUCT_PRICE.replace('#CURRENCY_NAME#', currencyName)}}
						</label>
						<div class="ui-ctl ui-ctl-md ui-ctl-w100" :class="hasPriceError ? 'ui-ctl-danger' : ''">
							<input type="text" class="ui-ctl-element ui-ctl-textbox"  :value="basketItem.basePrice"  @input="changeBasePrice" @change="refreshBasket">
						</div>

					</div>
				</div>

			</div>
			<div class="salescenter-app-form-container" v-else>
				<div class="salescenter-app-form-row">
					<div class="salescenter-app-form-col" style="flex:8">

						<label class="salescenter-app-ctl-label-text ui-ctl-label-text">{{localize.SALESCENTER_PRODUCT_TITLE}}</label>
						<div>
							<div class="ui-ctl ui-ctl-md ui-ctl-w100 ui-ctl-after-icon" :class="{'ui-ctl-danger' : this.isEmptyProductName}">
								<button class="ui-ctl-after ui-ctl-icon-clear" @click="hideCreationForm"> </button>
								<input type="text" class="ui-ctl-element ui-ctl-textbox" @change="changeName" :value="basketItem.name">
								<div class="salescenter-ctl-label">
									<div class="salescenter-ctl-label-text">{{localize.SALESCENTER_PRODUCT_NEW_LABEL}}</div>
								</div>
							</div>
						</div>
					</div>
					<div class="salescenter-app-form-col" style="flex:4">

						<label class="salescenter-app-ctl-label-text ui-ctl-label-text">
							{{localize.SALESCENTER_PRODUCT_QUANTITY.replace('#MEASURE_NAME#', getMeasureName)}}
						</label>
						<div class="ui-ctl ui-ctl-md ui-ctl-w100">
							<input type="text" class="ui-ctl-element ui-ctl-textbox" :value="basketItem.quantity" @input="changeQuantity" @change="refreshBasket">
						</div>

					</div>
				</div>
				<div class="salescenter-app-form-row" style="align-items: flex-end">
					<div class="salescenter-app-form-col" style="flex:8">

						<label class="salescenter-app-ctl-label-text ui-ctl-label-text">
							{{localize.SALESCENTER_PRODUCT_PRICE.replace('#CURRENCY_NAME#', currencyName)}}
						</label>
						<div class="ui-ctl ui-ctl-md ui-ctl-w100">
							<input type="text" class="ui-ctl-element ui-ctl-textbox" :value="basketItem.basePrice"  @input="changeBasePrice" @change="refreshBasket">
						</div>

					</div>
					<div class="salescenter-app-form-col" style="flex:4;">
						<label class="salescenter-app-ctl-label-text ui-ctl-label-text">{{localize.SALESCENTER_PRODUCT_MEASURE}}</label>
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-dropdown">
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select class="ui-ctl-element" @change="changeMeasureValue" :value="getMeasureCode">
								<option v-for="item in measures" :value="item.CODE">{{item.SYMBOL}}</option>
							</select>
						</div>
					</div>
				</div>

			</div>
			<div class="salescenter-app-sale-container" v-if="showDiscount">

				<div class="salescenter-app-form-collapse-container">

					<div class="salescenter-app-form-col">
						<label class="salescenter-app-ctl-label-text ui-ctl-label-text">{{localize.SALESCENTER_PRODUCT_DISCOUNT_TITLE}}</label>
					</div>

					<div class="salescenter-app-form-row">

						<div class="salescenter-app-form-col" style="flex:1;max-width: 112px;">
							<div class="ui-ctl ui-ctl-md ui-ctl-w100">
								<input type="text" class="ui-ctl-element ui-ctl-textbox" :value="basketItem.discount" @input="changeDiscount"  @change="refreshBasket">
							</div>
						</div>

						<div class="salescenter-app-form-col" style="flex:1.5;max-width: 84px;">
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-dropdown">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select class="ui-ctl-element" :value="basketItem.discountType" @change="changeDiscountType">
									<option value="percent">%</option>
									<option value="currency">{{currencyName}}</option>
								</select>
							</div>
						</div>

						<div class="salescenter-app-form-col" style="flex:auto; text-align: right;">
							<div style="margin-bottom: 0;" class="ui-text-4 ui-color-light">{{localize.SALESCENTER_PRODUCT_DISCOUNT_PRICE_TITLE}}</div>
							<div class="salescenter-app-form-text">
								<span class="salescenter-app-text-line-through" v-if="showCatalogPrice" v-html="basketItem.formattedCatalogPrice"></span>
								<span v-html="basketItem.formattedPrice"></span>
							</div>
						</div>

					</div>
					
					<div class="salescenter-app-form-row" style="margin-bottom: 0;">
						<div class="salescenter-app-form-col" v-for="discount in basketItem.discountInfos"">
							<span class="ui-text-4 ui-color-light"> {{discount.name}} 
							<a :href="discount.editPageUrl" @click="openDiscountEditor(event, discount.editPageUrl)">
								{{localize.SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE}}
							</a></span>
						</div>
					</div>
					
				</div>

				<div class="salescenter-app-collapse-link-container">
					<a class="salescenter-app-collapse-link" @click="toggleDiscount('N')">{{localize.SALESCENTER_PRODUCT_HIDE_DISCOUNT}}</a>
				</div>

			</div>
			<div class="salescenter-app-sale-container" v-else>
				<div class="salescenter-app-collapse-link-container">
					<a class="salescenter-app-collapse-link"  @click="toggleDiscount('Y')">{{localize.SALESCENTER_PRODUCT_ADD_DISCOUNT}}</a>
				</div>
			</div>
		</div>
	`,
});