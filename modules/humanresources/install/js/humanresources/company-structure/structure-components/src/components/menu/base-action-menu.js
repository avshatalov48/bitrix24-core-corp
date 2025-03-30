import { BasePopup } from '../popup/base-popup';
import type { PopupOptions } from 'main.popup';

import './styles/base-action-menu.css';

export const BaseActionMenuPropsMixin = {
	props: {
		id: {
			type: String,
			required: true,
		},
		bindElement: {
			type: HTMLElement,
			required: true,
		},
		items: {
			type: Array,
			required: true,
			default: [],
		},
		titleBar: {
			type: String,
			required: false,
		},
	},
};

export const BaseActionMenu = {
	name: 'BaseActionMenu',

	mixins: [BaseActionMenuPropsMixin],

	props: {
		width: {
			type: Number,
			required: false,
			default: 260,
		},
		delimiter: {
			type: Boolean,
			required: false,
			default: true,
		},
		angleOffset: {
			type: Number,
			required: false,
			default: 0,
		},
		titleBar: {
			type: String,
			required: false,
		},
		className: {
			type: String,
			required: false,
		},
	},

	emits: ['action', 'close'],
	components: {
		BasePopup,
	},

	computed: {
		popupConfig(): PopupOptions
		{
			const options = {
				width: this.width,
				bindElement: this.bindElement,
				borderRadius: 12,
				contentNoPaddings: true,
				contentPadding: 0,
				padding: 0,
				offsetTop: 4,
			};

			if (this.angleOffset >= 0)
			{
				options.angleOffset = this.angleOffset;
			}

			if (this.titleBar)
			{
				options.titleBar = this.titleBar;
			}

			if (this.className)
			{
				options.className = this.className;
			}

			return options;
		},
	},

	methods: {
		onItemClick(event, item: Object, closePopup: function): void
		{
			event.stopPropagation();

			if (item.disabled ?? false)
			{
				return;
			}

			this.$emit('action', item.id);
			closePopup();
		},

		close(): void
		{
			this.$emit('close');
		},
	},

	template: `
		<BasePopup
			:config="popupConfig"
			v-slot="{closePopup}"
			:id="id"
			@close="close"
		>
			<div class="hr-structure-components-action-menu-container">
			<template v-for="(item, index) in items">
				<div
					class="hr-structure-components-action-menu-item-wrapper"
					:class="{ '--disabled': item.disabled ?? false }"
					@click="onItemClick($event, item, closePopup)"
				>
					<slot :item="item"></slot>
				</div>
				<span v-if="delimiter && index < items.length - 1"
					class="hr-structure-action-popup-menu-item-delimiter"
				></span>
			</template>
			</div>
		</BasePopup>
	`,
};
