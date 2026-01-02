import { BitrixVueComponentProps } from 'ui.vue3';
import { Type } from 'main.core';

import {
	Field,
	FieldTitle,
	FieldValue,
	FieldValueList,
	ValueEllipsis,
} from '../../layout/index';

import { ShowMore } from '../../show-more/show-more';

declare type Product = {
	title: string,
	url: string,
};

export const ProductField: BitrixVueComponentProps = {
	name: 'ProductField',
	components: {
		Field,
		FieldTitle,
		FieldValue,
		FieldValueList,
		ValueEllipsis,
		ShowMore,
	},
	props: {
		title: {
			type: String,
			required: true,
		},
		products: {
			/** @type Product[] */
			type: Array,
			required: true,
		},
		productsLeftCount: Number,
		productsLeftUrl: String,
	},
	computed: {
		isDisplayShowMore(): boolean
		{
			return Type.isNumber(this.productsLeftCount)
				&& this.productsLeftCount > 0
				&& Type.isStringFilled(this.productsLeftUrl);
		},
	},
	template: `
		<Field class="--product">
			<FieldTitle>{{ title }}</FieldTitle>
			<FieldValueList>
				<FieldValue v-for="product in products">
					<a class="--value-link" :href="product.url">
						<ValueEllipsis :title="product.title">{{ product.title }}</ValueEllipsis>
					</a>
				</FieldValue>
				<a
					v-if="isDisplayShowMore"
					:href="productsLeftUrl"
				>
					<ShowMore :count="productsLeftCount" />
				</a>
			</FieldValueList>
		</Field>
	`,
};
