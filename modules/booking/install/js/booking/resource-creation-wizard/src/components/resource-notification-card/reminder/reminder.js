import { RichLoc } from 'ui.vue3.components.rich-loc';
import { mapGetters } from 'ui.vue3.vuex';
import 'ui.icon-set.main';

import { HelpDesk, Model } from 'booking.const';
import { ResourceNotification } from '../layout/resource-notification';
import { ResourceNotificationTextRow } from '../layout/resource-notification-text-row';
import { LabelDropdown } from '../label/label';

// @vue/component
export const Reminder = {
	name: 'ResourceNotificationCardReminder',
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
	computed: {
		...mapGetters({
			/** @type {ResourceModel} */
			resource: `${Model.ResourceCreationWizard}/getResource`,
			isCurrentSenderAvailable: `${Model.Notifications}/isCurrentSenderAvailable`,
		}),
		isReminderNotificationOn: {
			get(): boolean
			{
				return this.isCurrentSenderAvailable && this.resource.isReminderNotificationOn;
			},
			set(isReminderNotificationOn: boolean): void
			{
				void this.$store.dispatch(`${Model.ResourceCreationWizard}/updateResource`, { isReminderNotificationOn });
			},
		},
		reminderNotificationDelay: {
			get(): number
			{
				return this.resource.reminderNotificationDelay;
			},
			set(reminderNotificationDelay: number): void
			{
				void this.$store.dispatch(`${Model.ResourceCreationWizard}/updateResource`, { reminderNotificationDelay });
				this.$refs.card.$forceUpdate();
			},
		},
		locSendReminderTime(): string
		{
			return this.loc('BRCW_NOTIFICATION_CARD_REMINDER_HELPER_TEXT_SECOND')
				.replace('#time#', '[delay/]')
			;
		},
		helpDesk(): Object
		{
			return HelpDesk.ResourceNotificationReminder;
		},
	},
	template: `
		<ResourceNotification
			v-model:checked="isReminderNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_REMINDER_TITLE')"
			:description="loc('BRCW_NOTIFICATION_CARD_REMINDER_HELPER_TEXT_FIRST_MSGVER_1')"
			:helpDesk="helpDesk"
			ref="card"
		>
			<template #client>
				<ResourceNotificationTextRow icon="--clock-2">
					<RichLoc :text="locSendReminderTime" :placeholder="'[delay/]'">
						<template #delay>
							<LabelDropdown v-model:value="reminderNotificationDelay" :items="model.settings.notification.delayValues"/>
						</template>
					</RichLoc>
				</ResourceNotificationTextRow>
			</template>
		</ResourceNotification>
	`,
};
