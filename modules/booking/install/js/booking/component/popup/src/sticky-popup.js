import { Dom, Event } from 'main.core';
import type { PopupOptions } from 'main.popup';
import { Popup } from './popup';

export const StickyPopup = {
	emits: ['close', 'adjustPosition'],
	props: {
		id: {
			type: String,
			required: true,
		},
		config: {
			type: Object,
			default: {},
		},
	},
	mounted(): void
	{
		this.adjustPosition();
		Event.bind(document, 'scroll', this.adjustPosition, true);
	},
	beforeUnmount(): void
	{
		Event.unbind(document, 'scroll', this.adjustPosition, true);
	},
	computed: {
		options(): PopupOptions
		{
			return {
				padding: 0,
				background: 'transparent',
				bindOptions: {
					forceBindPosition: true,
					forceLeft: true,
				},
				...this.config,
				className: `booking-booking-sticky-popup ${this.config.className ?? ''}`,
			};
		},
	},
	methods: {
		contains(element: HTMLElement): boolean
		{
			return this.$refs.popup.contains(element);
		},
		adjustPosition(): void
		{
			this.$refs.popup.adjustPosition();

			const top = this.config.bindElement.getBoundingClientRect().top + this.config.offsetTop;
			Dom.style(this.$refs.stickyContent, 'top', `${top}px`);
			Dom.style(this.$refs.stickyContent, 'width', `${this.config.width}px`);
			Dom.style(this.$refs.popup.container, 'top', 0);

			this.$emit('adjustPosition');
		},
	},
	components: {
		Popup,
	},
	template: `
		<Popup
			v-slot="{freeze, unfreeze}"
			:id="id"
			:config="options"
			ref="popup"
			@close="$emit('close')"
		>
			<div class="booking-booking-sticky-popup-content popup-window" ref="stickyContent">
				<slot :freeze="freeze" :unfreeze="unfreeze" :adjustPosition="adjustPosition"></slot>
			</div>
		</Popup>
	`,
};
