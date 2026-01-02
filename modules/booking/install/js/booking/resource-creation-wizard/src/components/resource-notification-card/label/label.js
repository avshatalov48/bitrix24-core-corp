import { BMenu, type MenuOptions } from 'ui.vue3.components.menu';
import { BIcon } from 'ui.icon-set.api.vue';
import { Actions } from 'ui.icon-set.api.core';
import 'ui.icon-set.actions';

import './label.css';

export type Item = {
	name: string,
	value: number,
};

// @vue/component
export const LabelDropdown = {
	components: {
		BIcon,
		BMenu,
	},
	props: {
		value: {
			type: Number,
			required: true,
		},
		/** @type Item[] */
		items: {
			type: Array,
			required: true,
		},
	},
	setup(): Object
	{
		return {
			Actions,
		};
	},
	data(): Object
	{
		return {
			isMenuShown: false,
		};
	},
	computed: {
		text(): string
		{
			return this.items.find(({ value }) => value === this.value).name;
		},
		menuOptions(): MenuOptions
		{
			return {
				id: 'booking-resource-creation-wizard-label-dropdown-menu',
				bindElement: this.$refs.container,
				offsetTop: 8,
				items: this.items.map(({ name, value }) => ({
					title: name,
					onClick: () => this.$emit('update:value', value),
				})),
				targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
			};
		},
	},
	methods: {
		handleClick(): void
		{
			this.isMenuShown = true;
		},
	},
	template: `
		<div class="booking-resource-creation-wizard-label-dropdown" ref="container" @click="handleClick">
			<span>{{ text }}</span>
			<BIcon :name="Actions.CHEVRON_DOWN"/>
		</div>
		<BMenu v-if="isMenuShown" :options="menuOptions" @close="isMenuShown = false"/>
	`,
};
