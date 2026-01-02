import { BitrixVueComponentProps } from 'ui.vue3';

import {
	Field,
	FieldTitle,
	FieldValue,
	ValueEllipsis,
} from '../../layout/index';

declare type StageItem = {
	name: string,
	color: string,
};

import './stage-field.css';

export const StageField: BitrixVueComponentProps = {
	name: 'StageField',
	components: {
		Field,
		FieldTitle,
		FieldValue,
		ValueEllipsis,
	},
	props: {
		title: {
			type: String,
			required: true,
		},
		stage: {
			/** @type StageItem */
			type: Object,
			required: true,
		},
	},

	template: `
		<Field class="--stage">
			<FieldTitle>{{ title }}</FieldTitle>
			<FieldValue>
				<div class="crm-mini-card__stage-color" :style="{ backgroundColor: stage.color }"></div>
				<ValueEllipsis :title="stage.name">{{ stage.name }}</ValueEllipsis>
			</FieldValue>
		</Field>
	`,
};
