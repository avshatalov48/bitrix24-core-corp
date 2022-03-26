import "./style.css"
import {FieldList, FieldListItem} from "../list/component";
import * as Mixins from "../base/components/mixins";


const FieldProductSubItem = {
	props: ['field', 'item',],
	template: `
		<div class="b24-form-control-product-info">
			<input type="hidden" 
				v-model="item.value.quantity"
			>
			<div class="b24-form-control-product-icon">
				<svg v-if="!pic" width="28px" height="24px" viewBox="0 0 28 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
					<g transform="translate(-14, -17)" fill="#333" stroke="none" stroke-width="1" fill-rule="evenodd" opacity="0.2">
						<path d="M29,38.5006415 C29,39.8807379 27.8807708,41 26.4993585,41 C25.1192621,41 24,39.8807708 24,38.5006415 C24,37.1192621 25.1192292,36 26.4993585,36 C27.8807379,36 29,37.1192292 29,38.5006415 Z M39,38.5006415 C39,39.8807379 37.8807708,41 36.4993585,41 C35.1192621,41 34,39.8807708 34,38.5006415 C34,37.1192621 35.1192292,36 36.4993585,36 C37.8807379,36 39,37.1192292 39,38.5006415 Z M20.9307332,21.110867 L40.9173504,21.0753348 C41.2504348,21.0766934 41.5636721,21.2250055 41.767768,21.4753856 C41.97328,21.7271418 42.046982,22.0537176 41.9704452,22.3639694 L39.9379768,33.1985049 C39.8217601,33.6666139 39.3866458,33.9972787 38.8863297,34 L22.7805131,34 C22.280197,33.9972828 21.8450864,33.6666243 21.728866,33.1985049 L18.2096362,19.0901297 L15,19.0901297 C14.4477153,19.0901297 14,18.6424144 14,18.0901297 L14,18 C14,17.4477153 14.4477153,17 15,17 L19.0797196,17 C19.5814508,17.0027172 20.0151428,17.3333757 20.1327818,17.8014951 L20.9307332,21.110867 Z" id="Icon"></path>
					</g>
				</svg>
				<img v-if="pic" :src="pic" style="height: 24px;">
			</div>
			
			<div class="b24-form-control-product-quantity"
				v-if="item.selected"
			>
				<div class="b24-form-control-product-quantity-remove"
					:style="{visibility: item.getNextDecQuantity() ? 'visible' : 'hidden'}"
					@click="item.decQuantity()"
				></div>
				<div class="b24-form-control-product-quantity-counter">
					{{ item.value.quantity }}
					<span
						v-if="item.quantity.unit"
					>{{ item.quantity.unit }}</span>
				</div>
				<div class="b24-form-control-product-quantity-add"
					:style="{visibility: item.getNextIncQuantity() ? 'visible' : 'hidden'}"
					@click="item.incQuantity()"
				></div>
			</div>
			<div class="b24-form-control-product-price">
				<div>
					<div class="b24-form-control-product-price-old"
						v-if="item.discount"
						v-html="field.formatMoney(item.getSummary())"
					></div>
					<div class="b24-form-control-product-price-current"
						v-html="field.formatMoney(item.getTotal())"
					></div>
				</div>
			</div>
		</div>
	`,
	computed: {
		pic()
		{
			return (
				!this.field.bigPic
				&& this.item
				&& this.item.pics
				&& this.item.pics.length > 0
			) ? this.item.pics[0] : '';
		}
	}
};
const FieldProductItem = {
	mixins: [FieldListItem],
	components: {
		'field-list-sub-item': FieldProductSubItem,
	},
};

const FieldProductPriceOnly = {
	mixins: [Mixins.MixinField],
	template: `
		<div class="b24-form-control-container">
			<span class="b24-form-control-label">
				{{ field.label }} 
				<span v-show="field.required" class="b24-form-control-required">*</span>
			</span>
			
			<label class="b24-form-control"
				v-for="(item, itemIndex) in field.items"
				:key="itemIndex"
				:class="{'b24-form-control-checked': item.selected, 'b24-form-control-product-custom-price': item.changeablePrice}"
				@click="onItemClick"
			>
				<input 
					:type="field.multiple ? 'checkbox' : 'radio'"
					:value="item.value"
					v-model="selected"
					@blur="$emit('input-blur')"
					@focus="$emit('input-focus')"
					v-show="!field.hasChangeablePrice()"
				>
				<span class="b24-form-control-desc"
					v-show="!item.changeablePrice"
					v-html="field.formatMoney(item.price)"
				></span> 

				<span class="b24-form-control-desc"
					v-if="item.changeablePrice && getCurrencyLeft()"
					:style="getCurrencyStyles(item)" 
					v-html="getCurrencyLeft()"
				></span>
				<input type="number" step="1" class="b24-form-control-input-text"
					v-if="item.changeablePrice"
					:placeholder="isFocused(item) ? '' : field.messages.get('fieldProductAnotherSum')"
					v-model="item.price"
					@input="onInput"
					@focus="onFocus(item)"
					@blur="onBlur"
					@keydown="onKeyDown"
				>
				<span class="b24-form-control-desc"
					v-if="item.changeablePrice && getCurrencyRight()"
					:style="getCurrencyStyles(item)"
					v-html="getCurrencyRight()"
				></span>
				
				<field-item-alert
					v-if="item.changeablePrice"
					:field="field"
					:item="item"
				></field-item-alert>
			</label>
		</div>
	`,
	data()
	{
		return {
			focusedItem: null,
		};
	},
	computed: {
		itemSubComponent ()
		{
			return null;
		},
	},
	methods: {
		onItemClick(e)
		{
			const node = e.target.querySelector('.b24-form-control-input-text');
			if (node)
			{
				node.focus();
			}
		},
		getCurrencyLeft()
		{
			return this.field.getCurrencyFormatArray()[0] || ''
		},
		getCurrencyRight()
		{
			return this.field.getCurrencyFormatArray()[1] || ''
		},
		getCurrencyStyles(item)
		{
			return {
				visibility: item.price || this.isFocused(item) ? null : 'hidden'
			};
		},
		isFocused(item)
		{
			return this.focusedItem === item;
		},
		onFocus(item)
		{
			this.selected = item.value;
			this.focusedItem = item;
		},
		onBlur()
		{
			this.focusedItem = null;
		},
		onInput(event)
		{
			let value = this.field.normalize(event.target.value);
			value = this.field.format(value);
			if (this.value !== value)
			{
				this.value = value;
			}
		},
		onKeyDown(e)
		{
			let val = e.key;
			if (!/[^\d]/.test(val || ''))
			{
				return;
			}
			if (val === 'Esc' || val === 'Delete' || val === 'Backspace')
			{
				return;
			}

			e.preventDefault();
		},
	}
};

const FieldProductStandard = {
	mixins: [FieldList],
	components: {
		'field-list-item': FieldProductItem,
	},
	computed: {
		itemSubComponent ()
		{
			return 'field-list-sub-item';
		}
	},
};

const FieldProduct = {
	mixins: [Mixins.MixinField],
	components: {FieldProductStandard, FieldProductPriceOnly},
	methods: {
		getProductComponent ()
		{
			return this.field.hasChangeablePrice() ? 'FieldProductPriceOnly' : 'FieldProductStandard';
		}
	},
	template: `<component :is="getProductComponent()" :field="field"></component>`,
};

export {
	FieldProduct,
}