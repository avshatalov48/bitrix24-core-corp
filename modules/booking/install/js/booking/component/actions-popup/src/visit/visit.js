import { ref } from 'ui.vue3';
import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { HelpDesk, Model } from 'booking.const';
import { helpDesk } from 'booking.lib.help-desk';
import { Loader } from 'booking.component.loader';

import { VisitMenu } from './visit-menu/visit-menu';
import type { VisitUpdateVisitStatusPayload } from './visit-menu/visit-menu';
import './visit.css';

export type { VisitUpdateVisitStatusPayload };

type VisitData = {
	iconSet: { [string]: string },
	isLoading: boolean,
}

export const Visit = {
	name: 'ActionsPopupVisit',
	emits: ['freeze', 'unfreeze', 'update:visitStatus'],
	props: {
		id: {
			type: [Number, String],
			required: true,
		},
		visitStatus: {
			type: String,
			required: true,
		},
		hasClients: {
			type: Boolean,
			default: false,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		dataId: {
			type: [Number, String],
			required: true,
		},
		dataElementPrefix: {
			type: String,
			default: '',
		},
	},
	setup(): VisitData
	{
		const iconSet = IconSet;
		const isLoading = ref(true);

		return {
			iconSet,
			isLoading,
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
		getLocVisitStatus(): string
		{
			switch (this.visitStatus)
			{
				case this.dictionary.Visited:
					return this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_VISITED');
				case this.dictionary.NotVisited:
					return this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_NOT_VISITED');
				default:
					return (this.hasClients)
						? this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_UNKNOWN')
						: this.loc('BB_ACTIONS_POPUP_VISIT_ADD_LABEL');
			}
		},
		getVisitInfoStyles(): string
		{
			switch (this.visitStatus)
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
			switch (this.visitStatus)
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
			switch (this.visitStatus)
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
	components: {
		Icon,
		Loader,
		VisitMenu,
	},
	template: `
		<div
			class="booking-actions-popup__item booking-actions-popup__item-visit-content"
			:class="{'--disabled': disabled}"
		>
			<Loader v-if="isLoading" class="booking-actions-popup__item-visit-loader"/>
			<template v-else>
				<div :class="['booking-actions-popup-item-icon', iconClass]">
					<Icon :name="iconSet.CUSTOMER_CARD" :color="cardIconColor"/>
				</div>
				<div class="booking-actions-popup-item-info">
					<div class="booking-actions-popup-item-title">
						<span>{{ loc('BB_ACTIONS_POPUP_VISIT_LABEL') }}</span>
						<Icon :name="iconSet.HELP" @click="showHelpDesk"/>
					</div>
					<div
						:class="['booking-actions-popup-item-subtitle', getVisitInfoStyles]"
						:data-element="dataElementPrefix + '-menu-visit-status'"
						:data-booking-id="id"
						:data-visit-status="visitStatus"
					>
						{{ getLocVisitStatus }}
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<VisitMenu
						:id
						:dataId
						:dataElementPrefix
						:disabled
						@popupShown="$emit('freeze')"
						@popupClosed="$emit('unfreeze')"
						@update:status="$emit('update:visitStatus', $event)"
					/>
				</div>
			</template>
		</div>
	`,
};
