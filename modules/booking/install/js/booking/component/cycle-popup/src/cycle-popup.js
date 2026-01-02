import { Popup } from 'ui.vue3.components.popup';
import { CyclePopupContent } from './cycle-popup-content';
import { meta } from './meta';

// @vue/component
export const CyclePopup = {
	components: {
		Popup,
		CyclePopupContent,
	},
	props: {
		scrollToCard: {
			type: String,
			default: null,
		},
	},
	emits: ['close'],
	setup(): Object
	{
		return {
			meta,
		};
	},
	template: `
		<Popup :options="meta.popupOptions" @close="$emit('close')">
			<CyclePopupContent :scrollToCard="scrollToCard"/>
		</Popup>
	`,
};
