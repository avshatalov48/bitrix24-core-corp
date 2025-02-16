import { mapGetters, mapMutations } from 'ui.vue3.vuex';
import 'ui.icon-set.main';

import { Button, ButtonSize, ButtonColor } from 'booking.component.button';
import { HelpDesk, Model } from 'booking.const';
import { helpDesk } from 'booking.lib.help-desk';
import type { NotificationsTemplateModel } from 'booking.model.notifications';

import { ResourceNotification } from '../layout/resource-notification';
import { ResourceNotificationCheckBoxRow } from '../layout/resource-notification-checkbox-row';
import { ResourceNotificationTextRow } from '../layout/resource-notification-text-row';
import { replaceLabelMixin } from '../label/label';
import { NotificationCardMixin } from '../notification-card-mixin';
import { ChooseTemplatePopup } from '../choose-template-popup/choose-template-popup';
import { TemplateEmpty } from '../template-empty/template-empty';

export const Reminder = {
	name: 'ResourceNotificationCardReminder',
	mixins: [replaceLabelMixin, NotificationCardMixin],
	props: {
		model: {
			type: Object,
			required: true,
		},
		resourceType: {
			type: Object,
			required: true,
		},
	},
	data(): { isCheckedForAllLocal: boolean, originalValue: boolean, originalTemplateType: string }
	{
		return {
			ButtonSize,
			ButtonColor,
			isCheckedForAllLocal: true,
			originalValue: this.resourceType.isReminderNotificationOn,
			originalTemplateType: this.resourceType.templateTypeReminder,
			showTemplatePopup: false,
		};
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
			set(isReminderNotificationOn: boolean)
			{
				this.updateResource({ isReminderNotificationOn });
				this.updateResourceType();
			},
		},
		locSendReminderTime(): string
		{
			const hint = this.loc('BRCW_BOOKING_SOON_HINT');
			const chooseTime = this.loc('BRCW_NOTIFICATION_CARD_LABEL_CHOOSE_TIME');
			const replacements = {
				'#time#': this.getLabel(chooseTime, this.isReminderNotificationOn, hint),
			};

			return this.loc('BRCW_NOTIFICATION_CARD_REMINDER_HELPER_TEXT_SECOND', replacements);
		},
		template(): NotificationsTemplateModel
		{
			return this.model.templates.find((template) => template.type === this.resource.templateTypeReminder);
		},
	},
	methods: {
		...mapMutations(
			'resource-creation-wizard',
			['updateResource'],
		),
		updateResourceType(): void
		{
			const checked = this.isCheckedForAll
				? this.isReminderNotificationOn
				: this.originalValue
			;

			const templateType = this.isCheckedForAll
				? this.resource.templateTypeReminder
				: this.originalTemplateType
			;

			const resourceType = this.resourceType;
			this.$store.dispatch('resourceTypes/upsert', {
				...resourceType,
				isReminderNotificationOn: checked,
				templateTypeReminder: templateType,
			});
		},
		chooseTemplate()
		{
			this.showTemplatePopup = true;
		},
		handleTemplateTypeSelected(selectedType: string): void
		{
			this.updateResource({ templateTypeReminder: selectedType });
			this.updateResourceType();
		},
		showHelpDesk(): void
		{
			helpDesk.show(
				HelpDesk.ResourceNotificationReminder.code,
				HelpDesk.ResourceNotificationReminder.anchorCode,
			);
		},
	},
	mounted(): void
	{
		if (!this.template)
		{
			console.warn('Notification template not found');
			this.isReminderNotificationOn = false;
		}
	},
	components: {
		ResourceNotification,
		ResourceNotificationTextRow,
		ResourceNotificationCheckBoxRow,
		ChooseTemplatePopup,
		Button,
		TemplateEmpty,
	},
	template: `
		<ResourceNotification
			v-model:checked="isReminderNotificationOn"
			:view="'reminder'"
			:title="loc('BRCW_NOTIFICATION_CARD_REMINDER_TITLE')"
			:disableSwitcher="!isCurrentSenderAvailable || !template"
		>
			<template #notification-template="{ messenger }">
				<div
					v-if="isCurrentSenderAvailable"
					class="resource-creation-wizard__form-notification-info-template"
					v-html="getMessageTemplate(messenger)"
				></div>
				<TemplateEmpty v-else/>
				<div
					v-if="isCurrentSenderAvailable && model.templates.length > 1"
					class="resource-creation-wizard__form-notification-info-template-choose-buttons"
				>
					<div ref="chooseTemplateBtn">
						<Button
							:disabled="!isReminderNotificationOn"
							:text="loc('BRCW_NOTIFICATION_CARD_CHOOSE_TEMPLATE_TYPE')"
							:size="ButtonSize.EXTRA_SMALL"
							:color="ButtonColor.LIGHT_BORDER"
							:round="true"
							@click="chooseTemplate"
						/>
					</div>
				</div>
				<ChooseTemplatePopup
					v-if="showTemplatePopup"
					:bindElement="$refs.chooseTemplateBtn"
					:model="model"
					:current-channel="messenger"
					:currentTemplateType="resource.templateTypeReminder"
					@templateTypeSelected="handleTemplateTypeSelected"
					@close="showTemplatePopup = false"
				/>
			</template>

			<ResourceNotificationTextRow icon="--info-1">
				<div class="resource-creation-wizard__form-notification-text">
					<div>
						{{ loc('BRCW_NOTIFICATION_CARD_REMINDER_HELPER_TEXT_FIRST') }}
						<span
							class="resource-creation-wizard__form-notification-text --more"
							@click="showHelpDesk"
						>
							{{ loc('BRCW_NOTIFICATION_CARD_MORE') }}
						</span>
					</div>
				</div>
			</ResourceNotificationTextRow>
			<ResourceNotificationTextRow icon="--clock-2">
				<div class="resource-creation-wizard__form-notification-text" v-html="locSendReminderTime"></div>
			</ResourceNotificationTextRow>
			<ResourceNotificationCheckBoxRow
				v-model:checked="isCheckedForAll"
				:disabled="!isReminderNotificationOn"
			>
				<div class="resource-creation-wizard__form-notification-text">
					{{ loc('BRCW_NOTIFICATION_CARD_HELPER_TEXT_APPLY_FOR_ALL') }}
				</div>
			</ResourceNotificationCheckBoxRow>
		</ResourceNotification>
	`,
};
