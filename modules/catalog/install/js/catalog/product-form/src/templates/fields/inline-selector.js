import {Text, Type} from 'main.core';
import {Vue} from "ui.vue";
import {config} from "../../config";
import {FormInputCode} from "../../types/form-input-code";
import {ProductSelector} from "catalog.product-selector";
import {EventEmitter} from "main.core.events";
import type {BaseEvent} from "main.core.events";

Vue.component(config.templateFieldInlineSelector,
{
	/**
	 * @emits 'onProductChange' {fields: object}
	 */

	props: {
		editable: Boolean,
		options: Object,
		basketItem: Object,
	},
	data()
	{
		return {
			currencySymbol: null,
			productSelector: null,
			imageControlId: null,
			selectorId: this.basketItem.selectorId,
		};
	},
	created()
	{
		EventEmitter.subscribe('BX.Catalog.ProductSelector:onChange', this.onProductChange.bind(this));
		EventEmitter.subscribe('BX.Catalog.ProductSelector:onClear', this.onProductClear.bind(this))
	},
	mounted()
	{
		this.productSelector = new ProductSelector(this.selectorId, this.prepareSelectorParams());
		this.productSelector.renderTo(this.$refs.selectorWrapper);
	},
	methods:
	{
		prepareSelectorParams(): Object
		{
			const selectorOptions = {
				iblockId: this.options.iblockId,
				basePriceId: this.options.basePriceId,
				productId: this.getField('productId'),
				skuId: this.getField('skuId'),
				skuTree: this.getDefaultSkuTree(),
				fileInputId: '',
				morePhotoValues: [],
				fileInput: '',
				config: {
					DETAIL_PATH: this.basketItem.detailUrl || '',
					ENABLE_SEARCH: true,
					ENABLE_INPUT_DETAIL_LINK: true,
					ENABLE_IMAGE_CHANGE_SAVING: true,
					ENABLE_EMPTY_PRODUCT_ERROR: this.options.enableEmptyProductError || this.isRequiredField(FormInputCode.PRODUCT_SELECTOR),
					ENABLE_EMPTY_IMAGES_ERROR: this.isRequiredField(FormInputCode.IMAGE_EDITOR),
					ROW_ID: this.selectorId,
					ENABLE_SKU_SELECTION: this.editable,
					HIDE_UNSELECTED_ITEMS: this.options.hideUnselectedProperties,
					URL_BUILDER_CONTEXT: this.options.urlBuilderContext
				},
				mode: this.editable ? ProductSelector.MODE_EDIT : ProductSelector.MODE_VIEW,
				isSimpleModel:
					this.getField('name', '') !== ''
					&& this.getField('productId') <= 0
					&& this.getField('skuId') <= 0
				,
				fields: {
					NAME: this.getField('name') || '',
					PRICE: this.getField('basePrice') || 0,
					CURRENCY: this.options.currency,
				},
			};

			const formImage = this.basketItem.image;
			if (Type.isObject(formImage))
			{
				selectorOptions.fileView = formImage.preview;
				selectorOptions.fileInput = formImage.input;
				selectorOptions.fileInputId = formImage.id;
				selectorOptions.morePhotoValues = formImage.values;
			}

			return selectorOptions;
		},
		isRequiredField(code: string): boolean
		{
			return Type.isArray(this.options.requiredFields) && this.options.requiredFields.includes(code);
		},
		getDefaultSkuTree(): Object
		{
			let skuTree = this.basketItem.skuTree || {};
			if (Type.isStringFilled(skuTree))
			{
				skuTree = JSON.parse(skuTree);
			}

			return skuTree;
		},
		getField(name, defaultValue = null)
		{
			return this.basketItem.fields[name] || defaultValue;
		},
		onProductChange(event: BaseEvent)
		{
			const data = event.getData();
			if (Type.isStringFilled(data.selectorId) && data.selectorId === this.productSelector.getId())
			{
				const basePrice = Text.toNumber(data.fields.PRICE);

				let fields = {
					BASE_PRICE: basePrice,
					MODULE: 'catalog',
					NAME: data.fields.NAME,
					ID: data.fields.ID,
					PRODUCT_ID: data.fields.PRODUCT_ID,
					SKU_ID: data.fields.SKU_ID,
					PROPERTIES: data.fields.PROPERTIES,
					URL_BUILDER_CONTEXT: this.options.urlBuilderContext,
					CUSTOMIZED: Type.isNil(data.fields.PRICE) ? 'Y' : 'N',
					MEASURE_CODE: data.fields.MEASURE_CODE,
					MEASURE_NAME: data.fields.MEASURE_NAME,
				};

				this.$emit('onProductChange', fields);
			}
		},
		onProductClear(event: BaseEvent)
		{
			const data = event.getData();

			if (Type.isStringFilled(data.selectorId) && data.selectorId === this.productSelector.getId())
			{
				this.$emit('onProductClear');
			}
		},
	},
	// language=Vue
	template: `
		<div class="catalog-pf-product-item-section" :id="selectorId" ref="selectorWrapper"></div>
	`
});