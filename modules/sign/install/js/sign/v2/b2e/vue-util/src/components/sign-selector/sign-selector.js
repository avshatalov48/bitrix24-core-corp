import type { PopupOptions } from 'main.popup';
import { RichMenuPopup } from 'ui.vue3.components.rich-menu';
import { BaseSelectedItemView } from './components/selected-item-view/base-selected-item-view';
import { BaseMenuItem } from './components/menu-item/base-menu-item';

import './style.css';

// @vue/component
export const SignSelector = {
	name: 'SignSelector',

	components: {
		BaseSelectedItemView,
		RichMenuPopup,
		BaseMenuItem,
	},

	props: {
		items: {
			/* @type Array<{ id: string | number, title: string }> */
			type: Array,
			required: true,
		},
		// eslint-disable-next-line vue/require-prop-types
		selectedId: {
			required: true,
		},
	},
	emits: ['onSelect'],

	data() {
		return {
			isMenuShow: false,
		};
	},

	computed: {
		selectedItemTitle(): string
		{
			return this.items.find((item) => item.id === this.selectedId)?.title ?? '';
		},
		popupOptions(): PopupOptions
		{
			return {
				bindElement: this.$refs.selector,
				maxWidth: 400,
				width: 290,
			};
		},
	},

	methods: {
		select(id: number): void
		{
			this.$emit('onSelect', id);
			this.isMenuShow = false;
		},
	},

	template: `
		<div class="sign-b2e-vue-util-sign-selector"
			 ref="selector"
			 @click="isMenuShow = true"
		>
			<slot :title="selectedItemTitle">
				<BaseSelectedItemView
					:title="selectedItemTitle"
				/>
			</slot>
			<RichMenuPopup
				v-if="isMenuShow"
				class="sign-b2e-vue-util-sign-selector-container"
				:popupOptions="popupOptions"
				@close="isMenuShow = false"
			>
				<template v-for="item in items">
					<BaseMenuItem
						:title="item.title"
						:selected="selectedId === item.id"
						@click="select(item.id)"
					/>
				</template>
			</RichMenuPopup>
		</div>
	`,
};
