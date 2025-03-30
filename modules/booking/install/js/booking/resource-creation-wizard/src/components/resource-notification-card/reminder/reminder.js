import { mapGetters } from 'ui.vue3.vuex';
import 'ui.icon-set.main';

import { HelpDesk, Model } from 'booking.const';
import { ResourceNotification } from '../layout/resource-notification';
import { ResourceNotificationTextRow } from '../layout/resource-notification-text-row';
import { replaceLabelMixin } from '../label/label';

export const Reminder = {
	name: 'ResourceNotificationCardReminder',
	mixins: [replaceLabelMixin],
	props: {
		/** @type {NotificationsModel} */
		model: {
			type: Object,
			required: true,
		},
	},
	computed: {
		...mapGetters({
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
		locSendReminderTime(): string
		{
			const hint = this.loc('BRCW_BOOKING_SOON_HINT');
			const chooseTime = this.loc('BRCW_NOTIFICATION_CARD_LABEL_CHOOSE_TIME');

			return this.loc('BRCW_NOTIFICATION_CARD_REMINDER_HELPER_TEXT_SECOND', {
				'#time#': this.getLabel(chooseTime, this.isReminderNotificationOn, hint),
			});
		},
		helpDesk(): Object
		{
			return HelpDesk.ResourceNotificationReminder;
		},
	},
	components: {
		ResourceNotification,
		ResourceNotificationTextRow,
	},
	template: `
		<ResourceNotification
			v-model:checked="isReminderNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_REMINDER_TITLE')"
			:description="loc('BRCW_NOTIFICATION_CARD_REMINDER_HELPER_TEXT_FIRST_MSGVER_1')"
			:helpDesk="helpDesk"
		>
			<ResourceNotificationTextRow icon="--clock-2">
				<div class="resource-creation-wizard__form-notification-text" v-html="locSendReminderTime"></div>
			</ResourceNotificationTextRow>
		</ResourceNotification>
	`,
};
