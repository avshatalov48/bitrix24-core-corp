import { Type } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';
import 'ui.icon-set.main';

import { Model, AhaMoment, HelpDesk } from 'booking.const';
import { ahaMoments } from 'booking.lib.aha-moments';

import { replaceLabelMixin } from '../label/label';
import { ResourceNotification } from '../layout/resource-notification';
import { ResourceNotificationTextRow } from '../layout/resource-notification-text-row';

export const BaseInfo = {
	name: 'ResourceNotificationCardBaseInfo',
	mixins: [replaceLabelMixin],
	props: {
		/** @type {NotificationsModel} */
		model: {
			type: Object,
			required: true,
		},
	},
	mounted(): void
	{
		if (ahaMoments.shouldShow(AhaMoment.MessageTemplate))
		{
			setTimeout(() => this.showAhaMoment(), 500);
		}
	},
	computed: {
		...mapGetters({
			resource: `${Model.ResourceCreationWizard}/getResource`,
			isCurrentSenderAvailable: `${Model.Notifications}/isCurrentSenderAvailable`,
		}),
		isInfoNotificationOn: {
			get(): boolean
			{
				return this.isCurrentSenderAvailable && this.resource.isInfoNotificationOn;
			},
			set(isInfoNotificationOn: boolean): void
			{
				void this.$store.dispatch(`${Model.ResourceCreationWizard}/updateResource`, { isInfoNotificationOn });
			},
		},
		locInfoTimeSend(): string
		{
			const hint = this.loc('BRCW_BOOKING_SOON_HINT');
			const immediately = this.loc('BRCW_NOTIFICATION_CARD_LABEL_IMMEDIATELY');

			return this.loc('BRCW_NOTIFICATION_CARD_BASE_INFO_HELPER_TEXT_SECOND', {
				'#time#': this.getLabel(immediately, this.isInfoNotificationOn, hint),
			});
		},
		helpDesk(): Object
		{
			return HelpDesk.ResourceNotificationInfo;
		},
	},
	methods: {
		async showAhaMoment(): Promise<void>
		{
			const target = this.$refs.card.getChooseTemplateButton();
			if (Type.isNull(target))
			{
				return;
			}

			await ahaMoments.showGuide({
				id: 'booking-message-template',
				title: this.loc('BOOKING_AHA_MESSAGE_TEMPLATE_TITLE'),
				text: this.loc('BOOKING_AHA_MESSAGE_TEMPLATE_TEXT'),
				article: HelpDesk.AhaMessageTemplate,
				target: this.$refs.card.getChooseTemplateButton(),
				targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
			});

			ahaMoments.setShown(AhaMoment.MessageTemplate);
		},
	},
	components: {
		ResourceNotification,
		ResourceNotificationTextRow,
	},
	template: `
		<ResourceNotification
			v-model:checked="isInfoNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_BASE_INFO_TITLE')"
			:description="loc('BRCW_NOTIFICATION_CARD_BASE_INFO_HELPER_TEXT_FIRST_MSGVER_1')"
			:helpDesk="helpDesk"
			ref="card"
		>
			<ResourceNotificationTextRow icon="--clock-2">
				<div class="resource-creation-wizard__form-notification-text" v-html="locInfoTimeSend"></div>
			</ResourceNotificationTextRow>
		</ResourceNotification>
	`,
};
