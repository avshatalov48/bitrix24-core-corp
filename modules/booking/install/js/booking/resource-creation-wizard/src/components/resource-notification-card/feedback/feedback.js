import { RichLoc } from 'ui.vue3.components.rich-loc';
import { mapGetters } from 'ui.vue3.vuex';
import 'ui.icon-set.main';

import { HelpDesk, Model } from 'booking.const';
import { LabelDropdown } from '../label/label';
import { ResourceNotification } from '../layout/resource-notification';
import { ResourceNotificationTextRow } from '../layout/resource-notification-text-row';

// @vue/component
export const Feedback = {
	name: 'ResourceNotificationCardFeedback',
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
	data(): Object
	{
		return {
			labelValue: this.model.settings.notification.delayValues[0].value,
		};
	},
	computed: {
		...mapGetters({
			/** @type {ResourceModel} */
			resource: `${Model.ResourceCreationWizard}/getResource`,
			isCurrentSenderAvailable: `${Model.Notifications}/isCurrentSenderAvailable`,
		}),
		isFeedbackNotificationOn: {
			get(): boolean
			{
				return false;
			},
			set(isFeedbackNotificationOn: boolean): void
			{
				void this.$store.dispatch(`${Model.ResourceCreationWizard}/updateResource`, { isFeedbackNotificationOn });
			},
		},
		locSendFeedbackTime(): string
		{
			return this.loc('BRCW_NOTIFICATION_CARD_FEEDBACK_HELPER_TEXT_SECOND')
				.replace('#time#', '[delay/]')
			;
		},
		helpDesk(): Object
		{
			return HelpDesk.ResourceNotificationFeedback;
		},
	},
	template: `
		<ResourceNotification
			v-model:checked="isFeedbackNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_FEEDBACK_TITLE')"
			:description="loc('BRCW_NOTIFICATION_CARD_FEEDBACK_HELPER_TEXT_FIRST_MSGVER_2')"
			:helpDesk="helpDesk"
			:disabled="true"
		>
			<template #client>
				<ResourceNotificationTextRow icon="--clock-2">
					<RichLoc :text="locSendFeedbackTime" :placeholder="'[delay/]'">
						<template #delay>
							<LabelDropdown v-model:value="labelValue" :items="model.settings.notification.delayValues"/>
						</template>
					</RichLoc>
				</ResourceNotificationTextRow>
			</template>
		</ResourceNotification>
	`,
};
