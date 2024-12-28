import type { PopupOptions } from 'main.popup';
import { Popup } from '../layout/popup';
import '../css/generic-popup.css';

export const GenericPopup = {
	emits: ['close'],
	props: {
		title: {
			type: String,
			required: true,
		},
	},
	computed: {
		popupOptions(): PopupOptions
		{
			return {
				width: 440,
				closeIcon: true,
				noAllPaddings: true,
				overlay: true,
			};
		},
	},
	methods: {
		onClose()
		{
			this.$emit('close');
		},
	},
	components: {
		Popup,
	},
	// language=Vue
	template: `
		<Popup id="generic" @close="this.onClose" :options="popupOptions" wrapper-class="generic-popup">
			<h3 class="generic-popup__header">{{ title }}</h3>
			<div class="generic-popup__content">
				<slot name="content"></slot>
			</div>
			<div class="generic-popup__buttons-wrapper">
				<slot name="buttons"></slot>
			</div>
		</Popup>
	`,
};
