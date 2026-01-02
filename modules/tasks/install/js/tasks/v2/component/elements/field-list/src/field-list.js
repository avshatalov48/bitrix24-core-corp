import type { BitrixVueComponentProps } from 'ui.vue3';
import './field-list.css';

// eslint-disable-next-line no-unused-vars
type Field = {
	title: string,
	component: BitrixVueComponentProps,
	props: { [prop: string]: any },
	withSeparator: boolean,
};

// @vue/component
export const FieldList = {
	name: 'FieldList',
	props: {
		/** @type Field[] */
		fields: {
			type: Array,
			required: true,
		},
	},
	template: `
		<div class="b24-field-list">
			<template v-for="(field, index) in fields" :key="index">
				<div class="b24-field-list-title" :class="{ '--with-separator': field.withSeparator }">
					<div class="b24-field-list-title-text">{{ field.title }}</div>
				</div>
				<div class="b24-field-list-value" :class="{ '--with-separator': field.withSeparator }">
					<component :is="field.component" v-bind="field.props"></component>
				</div>
			</template>
		</div>
	`,
};
