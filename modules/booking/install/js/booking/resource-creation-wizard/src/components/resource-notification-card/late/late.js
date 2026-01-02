import { RichLoc } from 'ui.vue3.components.rich-loc';
import { mapGetters } from 'ui.vue3.vuex';
import 'ui.icon-set.main';

import { HelpDesk, Model } from 'booking.const';
import { CardId } from 'booking.component.cycle-popup';

import { LabelDropdown } from '../label/label';
import { ResourceNotification } from '../layout/resource-notification';
import { ResourceNotificationTextRow } from '../layout/resource-notification-text-row';

// @vue/component
export const Late = {
	name: 'ResourceNotificationCardLate',
	components: {
		ResourceNotification,
		ResourceNotificationTextRow,
		LabelDropdown,
		RichLoc,
	},
	props: {
		/** @type {NotificationsModel} */
		model: {
			type: Object,
			required: true,
		},
	},
	setup(): Object
	{
		return {
			HelpDesk,
			CardId,
		};
	},
	computed: {
		...mapGetters({
			/** @type {ResourceModel} */
			resource: `${Model.ResourceCreationWizard}/getResource`,
			isCurrentSenderAvailable: `${Model.Notifications}/isCurrentSenderAvailable`,
		}),
		isDelayedNotificationOn: {
			get(): boolean
			{
				return this.isCurrentSenderAvailable && this.resource.isDelayedNotificationOn;
			},
			set(isDelayedNotificationOn: boolean): void
			{
				void this.$store.dispatch(`${Model.ResourceCreationWizard}/updateResource`, { isDelayedNotificationOn });
			},
		},
		delayedNotificationDelay: {
			get(): number
			{
				return this.resource.delayedNotificationDelay;
			},
			set(delayedNotificationDelay: number): void
			{
				void this.$store.dispatch(`${Model.ResourceCreationWizard}/updateResource`, { delayedNotificationDelay });
				this.$refs.card.$forceUpdate();
			},
		},
		delayedCounterDelay: {
			get(): number
			{
				return this.resource.delayedCounterDelay;
			},
			set(delayedCounterDelay: number): void
			{
				void this.$store.dispatch(`${Model.ResourceCreationWizard}/updateResource`, { delayedCounterDelay });
				this.$refs.card.$forceUpdate();
			},
		},
		locSendMessageAfter(): string
		{
			return this.loc('BRCW_NOTIFICATION_CARD_LATE_HELPER_TEXT_SECOND_MSGVER_1')
				.replace('#time#', '[delay/]')
			;
		},
		locNotifyManagerIn(): string
		{
			return this.loc('BRCW_NOTIFICATION_CARD_LATE_MANAGER_NOTIFY_IN')
				.replace('#time#', '[delay/]')
			;
		},
	},
	template: `
		<ResourceNotification
			v-model:checked="isDelayedNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_LATE_TITLE_MSGVER_1')"
			:description="loc('BRCW_NOTIFICATION_CARD_LATE_HELPER_TEXT_FIRST_MSGVER_2')"
			:helpDesk="HelpDesk.ResourceNotificationLate"
			:managerDescription="loc('BRCW_NOTIFICATION_CARD_LATE_MANAGER_HELPER')"
			:scrollToCard="CardId.Late"
			ref="card"
		>
			<template #client>
				<ResourceNotificationTextRow icon="--clock-2">
					<RichLoc :text="locSendMessageAfter" :placeholder="'[delay/]'">
						<template #delay>
							<LabelDropdown v-model:value="delayedNotificationDelay" :items="model.settings.notification.delayValues"/>
						</template>
					</RichLoc>
				</ResourceNotificationTextRow>
			</template>
			<template #manager>
				<ResourceNotificationTextRow icon="--clock-2">
					<RichLoc :text="locNotifyManagerIn" :placeholder="'[delay/]'">
						<template #delay>
							<LabelDropdown v-model:value="delayedCounterDelay" :items="model.settings.counter.delayValues"/>
						</template>
					</RichLoc>
				</ResourceNotificationTextRow>
			</template>
		</ResourceNotification>
	`,
};
