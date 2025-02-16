import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { HelpDesk, Model } from 'booking.const';
import { helpDesk } from 'booking.lib.help-desk';
import { Loader } from 'booking.component.loader';
import type { BookingModel } from 'booking.model.bookings';
import { VisitMenu } from './visit-menu/visit-menu';
import './visit.css';

export const Visit = {
	emits: ['freeze', 'unfreeze'],
	name: 'BookingActionsPopupVisit',
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	components: {
		Icon,
		Loader,
		VisitMenu,
	},
	data(): Object
	{
		return {
			IconSet,
			isLoading: true,
		};
	},
	async mounted()
	{
		this.isLoading = false;
	},
	methods: {
		showHelpDesk(): void
		{
			helpDesk.show(
				HelpDesk.BookingActionsVisit.code,
				HelpDesk.BookingActionsVisit.anchorCode,
			);
		},
	},
	computed: {
		...mapGetters({
			dictionary: `${Model.Dictionary}/getBookingVisitStatuses`,
		}),
		booking(): BookingModel
		{
			return this.$store.getters['bookings/getById'](this.bookingId);
		},
		getLocVisitStatus(): string {
			switch (this.booking.visitStatus)
			{
				case this.dictionary.Visited:
					return this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_VISITED');
				case this.dictionary.NotVisited:
					return this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_NOT_VISITED');
				default:
					return (this.booking.clients.length === 0)
						? this.loc('BB_ACTIONS_POPUP_VISIT_ADD_LABEL')
						: this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_UNKNOWN')
					;
			}
		},
		getVisitInfoStyles(): string
		{
			switch (this.booking.visitStatus)
			{
				case this.dictionary.Visited:
					return '--visited';
				case this.dictionary.NotVisited:
					return '--not-visited';
				default:
					return '--unknown';
			}
		},
		cardIconColor(): string
		{
			switch (this.booking.visitStatus)
			{
				case this.dictionary.NotVisited:
				case this.dictionary.Visited:
					return 'var(--ui-color-palette-white-base)';
				default:
					return 'var(--ui-color-palette-gray-20)';
			}
		},
		iconClass(): string
		{
			switch (this.booking.visitStatus)
			{
				case this.dictionary.Visited:
					return '--visited';
				case this.dictionary.NotVisited:
					return '--not-visited';
				default:
					return '';
			}
		},
	},
	template: `
		<div class="booking-actions-popup__item booking-actions-popup__item-visit-content">
			<Loader v-if="isLoading" class="booking-actions-popup__item-visit-loader" />
			<template v-else>
				<div :class="['booking-actions-popup-item-icon', iconClass]">
					<Icon :name="IconSet.CUSTOMER_CARD" :color="cardIconColor"/>
				</div>
				<div class="booking-actions-popup-item-info">
					<div class="booking-actions-popup-item-title">
						<span>{{ loc('BB_ACTIONS_POPUP_VISIT_LABEL') }}</span>
						<Icon :name="IconSet.HELP" @click="showHelpDesk" />
					</div>
					<div
						:class="['booking-actions-popup-item-subtitle', getVisitInfoStyles]"
						data-element="booking-menu-visit-status"
						:data-booking-id="bookingId"
						:data-visit-status="booking.visitStatus"
					>
						{{ getLocVisitStatus }}
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<VisitMenu
						:bookingId="bookingId"
						@popupShown="$emit('freeze')"
						@popupClosed="$emit('unfreeze')"
					/>
				</div>
			</template>
		</div>
	`,
};
