import { BitrixVueComponentProps } from 'ui.vue3';
import {
	Field,
	FieldTitle,
	FieldValue,
	FieldValueList,
	ValueEllipsis,
} from '../../layout/index';

import './common-field.css';

export const CommonField: BitrixVueComponentProps = {
	name: 'CommonField',
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
		values: {
			type: Array,
			required: true,
		},
	},

	template: `
		<Field class="--common">
			<FieldTitle>{{ title }}</FieldTitle>
			<FieldValueList>
				<FieldValue v-for="value in values">
					<ValueEllipsis :title="value">{{ value }}</ValueEllipsis>
				</FieldValue>
			</FieldValueList>
		</Field>
	`,
};
