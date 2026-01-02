import { BitrixVueComponentProps } from 'ui.vue3';

import {
	Field,
	FieldTitle,
	FieldValue,
	FieldValueList,
	ValueEllipsis,
} from '../../layout/index';

import './money-field.css';

export const MoneyField: BitrixVueComponentProps = {
	name: 'MoneyField',
	components: {
		Field,
		FieldTitle,
		FieldValueList,
		FieldValue,
		ValueEllipsis,
	},
	props: {
		title: {
			type: String,
			required: true,
		},
		moneyList: {
			type: Array,
			required: true,
		},
	},
	template: `
		<Field class="--money">
			<FieldTitle>{{ title }}</FieldTitle>
			<FieldValueList>
				<FieldValue v-for="money in moneyList">
					<ValueEllipsis v-html="money"/>
				</FieldValue>
			</FieldValueList>
		</Field>
	`,
};
