import { DateTimeFormat } from 'main.date';
import { Popup as MainPopup, PopupOptions } from 'main.popup';
import { mapGetters } from 'ui.vue3.vuex';

import { Popup } from 'booking.component.popup';
import { BusySlot, Model } from 'booking.const';
import type { ResourceModel } from 'booking.model.resources';
import './busy-popup.css';

export const BusyPopup = {
	emits: ['close'],
	props: {
		busySlot: {
			type: Object,
			required: true,
		},
	},
	computed: {
		...mapGetters({
			offset: `${Model.Interface}/offset`,
			mousePosition: `${Model.Interface}/mousePosition`,
		}),
		resource(): ResourceModel
		{
			const resourceId = (
				this.busySlot.type === BusySlot.Intersection
					? this.busySlot.intersectingResourceId
					: this.busySlot.resourceId
			);

			return this.$store.getters[`${Model.Resources}/getById`](resourceId);
		},
		popupId(): string
		{
			return `booking-booking-busy-popup-${this.busySlot.resourceId}`;
		},
		config(): PopupOptions
		{
			const width = 200;

			const angleLeft = MainPopup.getOption('angleMinBottom');
			const angleOffset = width / 2 - angleLeft;

			return {
				bindElement: this.mousePosition,
				width,
				background: '#2878ca',
				offsetTop: -5,
				offsetLeft: -angleOffset + angleLeft,
				bindOptions: {
					forceBindPosition: true,
					position: 'top',
				},
				angle: {
					offset: angleOffset,
					position: 'bottom',
				},
				angleBorderRadius: '4px 0',
				autoHide: false,
			};
		},
		textFormatted(): string
		{
			const timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');

			const messageId = (
				this.busySlot.type === BusySlot.Intersection
					? 'BOOKING_BOOKING_INTERSECTING_RESOURCE_IS_BUSY'
					: 'BOOKING_BOOKING_RESOURCE_IS_BUSY'
			);

			return this.loc(messageId, {
				'#RESOURCE#': this.resource.name,
				'#TIME_FROM#': DateTimeFormat.format(timeFormat, (this.busySlot.fromTs + this.offset) / 1000),
				'#TIME_TO#': DateTimeFormat.format(timeFormat, (this.busySlot.toTs + this.offset) / 1000),
			});
		},
	},
	methods: {
		adjustPosition(): void
		{
			const popup: MainPopup = this.$refs.popup?.getPopupInstance();
			if (!popup)
			{
				return;
			}

			popup.setBindElement(this.mousePosition);
			popup.adjustPosition();
		},
		closePopup(): void
		{
			this.$emit('close');
		},
	},
	watch: {
		mousePosition: {
			handler(): void
			{
				this.adjustPosition();
			},
			deep: true,
		},
	},
	components: {
		Popup,
	},
	template: `
		<Popup
			:id="popupId"
			:config="config"
			ref="popup"
			@close="closePopup"
		>
			<div class="booking-booking-busy-popup">
				{{ textFormatted }}
			</div>
		</Popup>
	`,
};
