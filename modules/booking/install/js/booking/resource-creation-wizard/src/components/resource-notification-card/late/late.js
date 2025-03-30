import { mapGetters } from 'ui.vue3.vuex';
import 'ui.icon-set.main';

import { HelpDesk, Model } from 'booking.const';
import { replaceLabelMixin } from '../label/label';
import { ResourceNotification } from '../layout/resource-notification';
import { ResourceNotificationTextRow } from '../layout/resource-notification-text-row';

export const Late = {
	name: 'ResourceNotificationCardLate',
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
		locSendMessageAfter(): string
		{
			const hint = this.loc('BRCW_BOOKING_SOON_HINT');
			const minutes = this.loc('BRCW_NOTIFICATION_CARD_LABEL_TIME_AFTER_MINUTES');

			return this.loc('BRCW_NOTIFICATION_CARD_LATE_HELPER_TEXT_SECOND', {
				'#time#': this.getLabel(minutes, this.isDelayedNotificationOn, hint),
			});
		},
		helpDesk(): Object
		{
			return HelpDesk.ResourceNotificationLate;
		},
	},
	components: {
		ResourceNotification,
		ResourceNotificationTextRow,
	},
	template: `
		<ResourceNotification
			v-model:checked="isDelayedNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_LATE_TITLE')"
			:description="loc('BRCW_NOTIFICATION_CARD_LATE_HELPER_TEXT_FIRST_MSGVER_1')"
			:helpDesk="helpDesk"
		>
			<ResourceNotificationTextRow icon="--clock-2">
				<div class="resource-creation-wizard__form-notification-text" v-html="locSendMessageAfter"></div>
			</ResourceNotificationTextRow>
		</ResourceNotification>
	`,
};
