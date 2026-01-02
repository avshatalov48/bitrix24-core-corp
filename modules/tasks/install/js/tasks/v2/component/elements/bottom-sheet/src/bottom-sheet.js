import type { PopupOptions } from 'main.popup';

import { Popup } from 'ui.vue3.components.popup';

import './bottom-sheet.css';

// @vue/component
export const BottomSheet = {
	components: {
		Popup,
	},
	props: {
		isExpanded: {
			type: Boolean,
			default: false,
		},
		padding: {
			type: Number,
			default: 24,
		},
		getBindElement: {
			type: Function,
			default: null,
		},
		getTargetContainer: {
			type: Function,
			default: null,
		},
	},
	computed: {
		popupOptions(): PopupOptions
		{
			return {
				id: 'b24-bottom-sheet',
				bindElement: this.getBindElement?.(),
				targetContainer: this.getTargetContainer?.(),
				className: `b24-bottom-sheet ${this.isExpanded ? '--expanded' : ''}`,
				borderRadius: '18px 18px 0 0',
				padding: this.padding,
				animation: {
					showClassName: '--show',
					closeClassName: '--close',
					closeAnimationType: 'animation',
				},
				autoHide: false,
			};
		},
	},
	methods: {
		adjustPosition(): void {},
		freeze(): void {},
		unfreeze(): void {},
	},
	template: `
		<Popup :options="popupOptions">
			<div class="b24-bottom-sheet-content" :style="{ '--padding': padding + 'px' }">
				<slot></slot>
			</div>
		</Popup>
	`,
};
