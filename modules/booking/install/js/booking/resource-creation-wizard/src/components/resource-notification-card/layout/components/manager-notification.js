import { RichLoc } from 'ui.vue3.components.rich-loc';
import { BIcon } from 'ui.icon-set.api.vue';
import { Main } from 'ui.icon-set.api.core';
import 'ui.icon-set.main';

import { CyclePopup } from 'booking.component.cycle-popup';
import { Description } from './description';

export const ManagerNotification = {
	components: {
		BIcon,
		Description,
		CyclePopup,
		RichLoc,
	},
	props: {
		text: {
			type: String,
			required: true,
		},
		description: {
			type: String,
			required: true,
		},
		helpDesk: {
			type: Object,
			required: true,
		},
		scrollToCard: {
			type: String,
			default: null,
		},
	},
	setup(): Object
	{
		return {
			Main,
		};
	},
	data(): Object
	{
		return {
			isPopupShown: false,
		};
	},
	computed: {
		notificationText(): string
		{
			return this.text.replaceAll('\n', '[br/]');
		},
	},
	template: `
		<div class="booking-resource-creation-wizard-notification --manager">
			<div class="booking-resource-creation-wizard-notification-main">
				<div class="resource-creation-wizard__form-notification-info-title-row">
					<BIcon :name="Main.BELL_1"/>
					<div class="resource-creation-wizard__form-notification-info-title">
						{{ loc('BRCW_NOTIFICATION_CARD_MANAGER') }}
					</div>
					<div class="booking-resource-creation-wizard-manager-see" @click="isPopupShown = true">
						{{ loc('BRCW_NOTIFICATION_CARD_MANAGER_SEE') }}
					</div>
				</div>
				<div class="resource-creation-wizard__form-notification-info">
					<div class="resource-creation-wizard__form-notification-info-text-row">
						{{ loc('BRCW_NOTIFICATION_CARD_MANAGER_TEXT') }}
					</div>
					<RichLoc
						class="resource-creation-wizard__form-notification-info-template"
						:placeholder="'[br/]'"
						:text="notificationText"
					>
						<template #br><br/></template>
					</RichLoc>
				</div>
				<Description :description="description" :helpDesk="helpDesk"/>
				<slot/>
			</div>
			<CyclePopup v-if="isPopupShown" :scrollToCard="scrollToCard" @close="isPopupShown = false"/>
		</div>
	`,
};
