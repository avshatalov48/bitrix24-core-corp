import { Popup, PopupManager } from 'main.popup';
import type { PopupOptions } from 'main.popup';

import { PopupMaker } from 'booking.component.popup-maker';
import { StickyPopup } from 'booking.component.popup';
import type { PopupMakerItem } from 'booking.component.popup-maker';

import './actions-popup.css';

export type { PopupOptions, PopupMakerItem };

export const ActionsPopup = {
	name: 'ActionsPopup',
	emits: ['close'],
	props: {
		bindElement: {
			type: HTMLElement,
			required: true,
		},
		popupId: {
			type: [Number, String],
			required: true,
		},
		/**
		 * @type {Array<PopupMakerItem | PopupMakerItem[]>}
		 */
		contentStructure: {
			type: Array,
			required: true,
		},
		/**
		 * @type {PopupOptions}
		 */
		popupOptions: {
			type: Object,
			default: null,
		},
	},
	data(): Object
	{
		return {
			soonTmp: false,
		};
	},
	computed: {
		actionsPopupId(): string
		{
			return `booking-booking-actions-popup-${this.popupId}`;
		},
		config(): PopupOptions
		{
			return {
				className: 'booking-booking-actions-popup',
				bindElement: this.bindElement,
				width: 325,
				offsetLeft: this.bindElement.offsetWidth,
				offsetTop: -200,
				animation: 'fading-slide',
				...this.popupOptions,
			};
		},
	},
	beforeCreate(): void
	{
		PopupManager.getPopups()
			.filter((popup: Popup) => /booking-booking-actions-popup/.test(popup.getId()))
			.forEach((popup: Popup) => popup.destroy())
		;
	},
	components: {
		StickyPopup,
		PopupMaker,
	},
	template: `
		<StickyPopup
			v-slot="{freeze, unfreeze}"
			:id="actionsPopupId"
			:config="config"
			@close="$emit('close')"
		>
			<PopupMaker
				:contentStructure="contentStructure"
				@freeze="freeze"
				@unfreeze="unfreeze"
			/>
			<div class="booking-booking-actions-popup-footer">
				<slot name="footer" />
			</div>
		</StickyPopup>
	`,
};
