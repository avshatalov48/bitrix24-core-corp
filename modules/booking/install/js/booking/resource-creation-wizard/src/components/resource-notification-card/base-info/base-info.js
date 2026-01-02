import { Type } from 'main.core';
import { RichLoc } from 'ui.vue3.components.rich-loc';
import { mapGetters } from 'ui.vue3.vuex';
import 'ui.icon-set.main';

import { Model, AhaMoment, HelpDesk } from 'booking.const';
import { ahaMoments } from 'booking.lib.aha-moments';

import { LabelDropdown } from '../label/label';
import { ResourceNotification } from '../layout/resource-notification';
import { ResourceNotificationTextRow } from '../layout/resource-notification-text-row';

// @vue/component
export const BaseInfo = {
	name: 'ResourceNotificationCardBaseInfo',
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
		infoNotificationDelay: {
			get(): number
			{
				return this.resource.infoNotificationDelay;
			},
			set(infoNotificationDelay: number): void
			{
				void this.$store.dispatch(`${Model.ResourceCreationWizard}/updateResource`, { infoNotificationDelay });
				this.$refs.card.$forceUpdate();
			},
		},
		locInfoTimeSend(): string
		{
			return this.loc('BRCW_NOTIFICATION_CARD_BASE_INFO_HELPER_TEXT_SECOND')
				.replace('#time#', '[delay/]')
			;
		},
		helpDesk(): Object
		{
			return HelpDesk.ResourceNotificationInfo;
		},
	},
	mounted(): void
	{
		if (ahaMoments.shouldShow(AhaMoment.MessageTemplate))
		{
			setTimeout(() => this.showAhaMoment(), 500);
		}
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
	template: `
		<ResourceNotification
			v-model:checked="isInfoNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_BASE_INFO_TITLE_MSGVER_1')"
			:description="loc('BRCW_NOTIFICATION_CARD_BASE_INFO_HELPER_TEXT_FIRST_MSGVER_2')"
			:helpDesk="helpDesk"
			ref="card"
		>
			<template #client>
				<ResourceNotificationTextRow icon="--clock-2">
					<RichLoc :text="locInfoTimeSend" :placeholder="'[delay/]'">
						<template #delay>
							<LabelDropdown v-model:value="infoNotificationDelay" :items="model.settings.notification.delayValues"/>
						</template>
					</RichLoc>
				</ResourceNotificationTextRow>
			</template>
		</ResourceNotification>
	`,
};
