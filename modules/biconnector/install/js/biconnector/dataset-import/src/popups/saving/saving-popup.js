import type { PopupOptions } from 'main.popup';
import { Popup } from '../../layout/popup';

export const SavingPopup = {
	emits: ['close'],
	props: {
		title: {
			type: String,
			required: true,
		},
		description: {
			type: String,
			required: false,
			default: '',
		},
		options: {
			type: Object,
			required: false,
			default: {},
		},
	},
	computed: {
		popupOptions(): PopupOptions
		{
			return {
				width: 500,
				minHeight: 299,
				closeIcon: true,
				noAllPaddings: true,
				overlay: true,
				...this.options,
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
		<Popup id="saveProgress" @close="this.onClose" :options="popupOptions" wrapper-class="biconnector-popup--full-height">
			<div class="biconnector-save-progress-popup">
				<div class="biconnector-save-progress-popup__content">
					<slot name="icon"></slot>
					<div class="biconnector-save-progress-popup__texts">
						<h3 class="biconnector-save-progress-popup__header">{{ title }}</h3>
						<p class="biconnector-save-progress-popup__description" v-if="description">{{ description }}</p>
					</div>
					<div class="biconnector-save-progress-popup__buttons"><slot name="buttons"></slot></div>
				</div>
			</div>
		</Popup>
	`,
};
