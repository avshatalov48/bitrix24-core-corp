import { mapGetters } from 'ui.vue3.vuex';
import 'ui.icon-set.main';
import 'ui.label';

import { HelpDesk, Model } from 'booking.const';
import { replaceLabelMixin } from '../label/label';
import { ResourceNotification } from '../layout/resource-notification';
import { ResourceNotificationTextRow } from '../layout/resource-notification-text-row';

export const Confirmation = {
	name: 'ResourceNotificationCardConfirmation',
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
		isConfirmationNotificationOn: {
			get(): boolean
			{
				return this.isCurrentSenderAvailable && this.resource.isConfirmationNotificationOn;
			},
			set(isConfirmationNotificationOn: boolean): void
			{
				void this.$store.dispatch(`${Model.ResourceCreationWizard}/updateResource`, { isConfirmationNotificationOn });
			},
		},
		locSendMessageBefore(): string
		{
			const hint = this.loc('BRCW_BOOKING_SOON_HINT');
			const daysBefore = this.loc('BRCW_NOTIFICATION_CARD_LABEL_DAYS_BEFORE');

			return this.loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_HELPER_TEXT_SECOND', {
				'#days_before#': this.getLabel(daysBefore, this.isConfirmationNotificationOn, hint),
			});
		},
		locRetryMessage(): string
		{
			const hint = this.loc('BRCW_BOOKING_SOON_HINT');
			const times = this.loc('BRCW_NOTIFICATION_CARD_LABEL_TIMES');
			const timeDelay = this.loc('BRCW_NOTIFICATION_CARD_LABEL_TIME_DELAY');

			return this.loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_HELPER_TEXT_THIRD', {
				'#times#': this.getLabel(times, this.isConfirmationNotificationOn, hint),
				'#time_delay#': this.getLabel(timeDelay, this.isConfirmationNotificationOn, hint),
			});
		},
		helpDesk(): Object
		{
			return HelpDesk.ResourceNotificationConfirmation;
		},
	},
	components: {
		ResourceNotification,
		ResourceNotificationTextRow,
	},
	template: `
		<ResourceNotification 
			v-model:checked="isConfirmationNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_TITLE')"
			:description="loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_HELPER_TEXT_FIRST_MSGVER_1')"
			:helpDesk="helpDesk"
		>
			<ResourceNotificationTextRow icon="--clock-2">
				<div class="resource-creation-wizard__form-notification-text" v-html="locSendMessageBefore"></div>
			</ResourceNotificationTextRow>
			<ResourceNotificationTextRow icon="--undo-1">
				<div class="resource-creation-wizard__form-notification-text" v-html="locRetryMessage"></div>
			</ResourceNotificationTextRow>
		</ResourceNotification>
	`,
};
