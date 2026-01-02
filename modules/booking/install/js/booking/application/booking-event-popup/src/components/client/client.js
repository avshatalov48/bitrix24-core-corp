import { mapGetters } from 'ui.vue3.vuex';
import { hint } from 'ui.vue3.directives.hint';
import { BIcon as UiIcon, Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

import { Model } from 'booking.const';
import { Avatar as UiAvatar } from 'booking.component.avatar';
import { Button as UiButton, AirButtonStyle, ButtonSize, ButtonStyle } from 'booking.component.button';
import { SidePanelInstance } from 'booking.lib.side-panel-instance';

import './client.css';

// @vue/component
export const BookingEventPopupClient = {
	name: 'BookingEventPopupClient',
	directives: {
		hint,
	},
	components: {
		UiAvatar,
		UiButton,
		UiIcon,
	},
	setup(): Object
	{
		return {
			AirButtonStyle,
			ButtonSize,
			ButtonStyle,
			Outline,
		};
	},
	computed: {
		...mapGetters({
			client: `${Model.BookingInfo}/client`,
		}),
		isPermitted(): boolean
		{
			return Boolean(this.client?.permissions?.read);
		},
		clientName(): string
		{
			if (!this.client)
			{
				return this.loc('BOOKING_EVENT_POPUP_CLIENT_IS_UNAVAILABLE');
			}

			if (!this.isPermitted)
			{
				return this.loc('BOOKING_EVENT_POPUP_NO_ACCESS');
			}

			return this.client.name;
		},
		clientImageLink(): string
		{
			return this.isPermitted ? this.client.image : '';
		},
	},
	methods: {
		soonHint(): Object
		{
			return {
				text: this.loc('BOOKING_EVENT_POPUP_SOON_HINT'),
				popupOptions: {},
			};
		},
		openClient(): void
		{
			if (!this.isPermitted || !this.client)
			{
				return;
			}

			const entity = this.client.type.toLowerCase();

			SidePanelInstance.open(`/crm/${entity}/details/${this.client.id}/`);
		},
	},
	template: `
		<div class="booking-event-popup__person-block">
			<div class="booking-event-popup__person">
				<div class="booking-event-popup__person_avatar">
					<UiAvatar
						:userName="isPermitted ? clientName : null"
						:userpicPath="clientImageLink"
					/>
				</div>
				<div class="booking-event-popup__person_data">
					<div v-if="client !== null" class="booking-event-popup__person_status">
						{{ loc('BOOKING_EVENT_POPUP_CLIENT') }}
					</div>
					<div
						:class="[
							'booking-event-popup__person_name',
							{ '--no-access': !isPermitted }
						]"
						@click="openClient"
					>
						{{ clientName }}
						<template v-if="client && !isPermitted">
							<UiIcon :name="Outline.LOCK_S" :size="20" color="rgb(var(--ui-color-palette-gray-50-rgb))"/>
						</template>
					</div>
				</div>
			</div>
			<UiButton
				v-hint="soonHint"
				:buttonClass="['--air', ButtonStyle.NO_CAPS, AirButtonStyle.OUTLINE_NO_ACCENT]"
				:text="loc('BOOKING_EVENT_POPUP_CALL_LABEL')"
				:icon="Outline.PHONE_UP"
				iconPosition="right"
				:size="ButtonSize.SMALL"
				disabled
				useAirDesign
			/>
		</div>
	`,
};
