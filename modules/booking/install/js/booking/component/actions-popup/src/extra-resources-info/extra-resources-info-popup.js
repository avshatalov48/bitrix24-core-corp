import { mapGetters } from 'ui.vue3.vuex';
import { PopupOptions } from 'main.popup';

import { Model } from 'booking.const';
import { Popup } from 'booking.component.popup';
import type { BookingModel } from 'booking.model.bookings';
import type { ResourceModel } from 'booking.model.resources';

import { ExtraResourcesInfoPopupItem } from './extra-resources-info-popup-item';

// @vue/component
export const ExtraResourcesInfoPopup = {
	name: 'ExtraResourcesInfoPopup',
	components: {
		Popup,
		ExtraResourcesInfoPopupItem,
	},
	props: {
		visible: {
			type: Boolean,
			default: false,
		},
		bindElement: {
			type: HTMLElement,
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
		},
		/**
		 * @type {BookingModel}
		 */
		booking: {
			type: Object,
			required: true,
		},
	},
	emits: ['update:visible'],
	computed: {
		...mapGetters({
			getResourcesByIds: `${Model.Resources}/getByIds`,
			getByInterval: `${Model.Bookings}/getByInterval`,
		}),
		config(): PopupOptions
		{
			return {
				bindElement: this.bindElement,
				offsetTop: 10,
				offsetLeft: -65,
				padding: 14,
				contentPadding: 30,
				height: 300,
				width: 340,
				minWidth: 340,
				maxWidth: 400,
				bindOptions: {
					forceBindPosition: true,
					position: 'bottom',
				},
			};
		},
		resources(): ResourceModel[]
		{
			const extraResourceIds = this.booking.resourcesIds.filter((resourceId) => resourceId !== this.resourceId);

			return this.getResourcesByIds(extraResourceIds);
		},
		bookings(): BookingModel[]
		{
			return this.getByInterval(this.booking.dateFromTs, this.booking.dateToTs);
		},
		resourceBookingsMap(): Map<number, Array<number | string>>
		{
			const map: Map<number, Array<number | string>> = new Map();

			this.bookings.forEach((booking) => {
				booking.resourcesIds.forEach((resourceId) => {
					const resourceBookingIds = map.get(resourceId) || [];
					resourceBookingIds.push(booking.id);
					map.set(resourceId, resourceBookingIds);
				});
			});

			return map;
		},
	},
	methods: {
		closePopup(): void
		{
			this.$emit('update:visible', false);
		},
		isOverbooking(resource: ResourceModel): boolean
		{
			const resourceBookingsCount = (this.resourceBookingsMap.get(resource.id) || []).length;

			return resourceBookingsCount > 1;
		},
	},
	template: `
		<Popup
			ref="popup"
			id="booking--booking--extra-resources-info"
			:config
			@close="closePopup"
		>
			<div class="booking--extra-resources-info_container">
				<div class="booking--extra-resources-info_content">
					<template v-for="resource in resources" :key="resource.id">
						<ExtraResourcesInfoPopupItem
							:title="resource.name"
							:overbooking="isOverbooking(resource)"
						/>
					</template>
				</div>
			</div>
		</Popup>
	`,
};
