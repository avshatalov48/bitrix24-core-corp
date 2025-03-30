import { mapGetters } from 'ui.vue3.vuex';
import 'ui.icon-set.main';

import { HelpDesk, Model } from 'booking.const';
import { replaceLabelMixin } from '../label/label';
import { ResourceNotification } from '../layout/resource-notification';
import { ResourceNotificationTextRow } from '../layout/resource-notification-text-row';

export const Feedback = {
	name: 'ResourceNotificationCardFeedback',
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
			const hint = this.loc('BRCW_BOOKING_SOON_HINT');
			const immediately = this.loc('BRCW_NOTIFICATION_CARD_LABEL_IMMEDIATELY');

			return this.loc('BRCW_NOTIFICATION_CARD_FEEDBACK_HELPER_TEXT_SECOND', {
				'#time#': this.getLabel(immediately, this.isFeedbackNotificationOn, hint),
			});
		},
		helpDesk(): Object
		{
			return HelpDesk.ResourceNotificationFeedback;
		},
	},
	components: {
		ResourceNotification,
		ResourceNotificationTextRow,
	},
	template: `
		<ResourceNotification
			v-model:checked="isFeedbackNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_FEEDBACK_TITLE')"
			:description="loc('BRCW_NOTIFICATION_CARD_FEEDBACK_HELPER_TEXT_FIRST_MSGVER_1')"
			:helpDesk="helpDesk"
			:disabled="true"
		>
			<ResourceNotificationTextRow icon="--clock-2">
				<div class="resource-creation-wizard__form-notification-text" v-html="locSendFeedbackTime"></div>
			</ResourceNotificationTextRow>
		</ResourceNotification>
	`,
};
