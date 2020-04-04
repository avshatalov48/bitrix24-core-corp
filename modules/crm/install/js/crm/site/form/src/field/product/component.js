import "./style.css"
import {FieldList, FieldListItem} from "../list/component";

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
					@click="item.decQuantity()"
					:style="{visibility: item.getNextDecQuantity() ? 'visible' : 'hidden'}"
				></div>
				<div class="b24-form-control-product-quantity-counter">
					{{ item.value.quantity }}
					{{ item.quantity.unit }}
				</div>
				<div class="b24-form-control-product-quantity-add"
					@click="item.incQuantity()"
					:style="{visibility: item.getNextIncQuantity() ? 'visible' : 'hidden'}"
				></div>
			</div>
			<div class="b24-form-control-product-price"
				v-if="item.price"
			>
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
const FieldProduct = {
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

export {
	FieldProduct,
}