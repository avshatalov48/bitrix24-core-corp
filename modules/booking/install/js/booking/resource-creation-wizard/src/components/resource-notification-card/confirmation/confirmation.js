import { mapGetters, mapMutations } from 'ui.vue3.vuex';
import 'ui.icon-set.main';
import 'ui.label';

import { Button, ButtonSize, ButtonColor } from 'booking.component.button';
import { HelpDesk, Model } from 'booking.const';
import { helpDesk } from 'booking.lib.help-desk';
import type { NotificationsTemplateModel } from 'booking.model.notifications';

import { replaceLabelMixin } from '../label/label';
import { ResourceNotification } from '../layout/resource-notification';
import { ResourceNotificationTextRow } from '../layout/resource-notification-text-row';
import { ResourceNotificationCheckBoxRow } from '../layout/resource-notification-checkbox-row';
import { ChooseTemplatePopup } from '../choose-template-popup/choose-template-popup';
import { NotificationCardMixin } from '../notification-card-mixin';
import { TemplateEmpty } from '../template-empty/template-empty';

export const Confirmation = {
	name: 'ResourceNotificationCardConfirmation',
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
			originalValue: this.resourceType.isConfirmationNotificationOn,
			originalTemplateType: this.resourceType.templateTypeConfirmation,
			showTemplatePopup: false,
		};
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
			set(isConfirmationNotificationOn: boolean)
			{
				this.updateResource({ isConfirmationNotificationOn });
				this.updateResourceType();
			},
		},
		locSendMessageBefore(): string
		{
			const hint = this.loc('BRCW_BOOKING_SOON_HINT');
			const daysBefore = this.loc('BRCW_NOTIFICATION_CARD_LABEL_DAYS_BEFORE');
			const replacements = {
				'#days_before#': this.getLabel(daysBefore, this.isConfirmationNotificationOn, hint),
			};

			return this.loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_HELPER_TEXT_SECOND', replacements);
		},
		locRetryMessage(): string
		{
			const hint = this.loc('BRCW_BOOKING_SOON_HINT');
			const times = this.loc('BRCW_NOTIFICATION_CARD_LABEL_TIMES');
			const timeDelay = this.loc('BRCW_NOTIFICATION_CARD_LABEL_TIME_DELAY');
			const replacements = {
				'#times#': this.getLabel(times, this.isConfirmationNotificationOn, hint),
				'#time_delay#': this.getLabel(timeDelay, this.isConfirmationNotificationOn, hint),
			};

			return this.loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_HELPER_TEXT_THIRD', replacements);
		},
		template(): NotificationsTemplateModel
		{
			return this.model.templates.find((template) => template.type === this.resource.templateTypeConfirmation);
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
				? this.isConfirmationNotificationOn
				: this.originalValue
			;

			const templateType = this.isCheckedForAll
				? this.resource.templateTypeConfirmation
				: this.originalTemplateType
			;

			const resourceType = this.resourceType;
			this.$store.dispatch('resourceTypes/upsert', {
				...resourceType,
				isConfirmationNotificationOn: checked,
				templateTypeConfirmation: templateType,
			});
		},
		chooseTemplate()
		{
			this.showTemplatePopup = true;
		},
		handleTemplateTypeSelected(selectedType: string): void
		{
			this.updateResource({ templateTypeConfirmation: selectedType });
			this.updateResourceType();
		},
		showHelpDesk(): void
		{
			helpDesk.show(
				HelpDesk.ResourceNotificationConfirmation.code,
				HelpDesk.ResourceNotificationConfirmation.anchorCode,
			);
		},
	},
	mounted(): void
	{
		if (!this.template)
		{
			console.warn('Notification template not found');
			this.isConfirmationNotificationOn = false;
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
			v-model:checked="isConfirmationNotificationOn"
			:view="'confirmation'"
			:title="loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_TITLE')"
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
					v-if="isCurrentSenderAvailable"
					class="resource-creation-wizard__form-notification-info-template-choose-buttons"
				>
					<div ref="chooseTemplateBtn">
						<Button
							:disabled="!isConfirmationNotificationOn"
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
					:currentTemplateType="resource.templateTypeConfirmation"
					@templateTypeSelected="handleTemplateTypeSelected"
					@close="showTemplatePopup = false"
				/>
			</template>

			<ResourceNotificationTextRow icon="--info-1">
				<div class="resource-creation-wizard__form-notification-text">
					<div>
						{{ loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_HELPER_TEXT_FIRST') }}
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
				<div class="resource-creation-wizard__form-notification-text" v-html="locSendMessageBefore"></div>
			</ResourceNotificationTextRow>
			<ResourceNotificationTextRow icon="--undo-1">
				<div class="resource-creation-wizard__form-notification-text" v-html="locRetryMessage"></div>
			</ResourceNotificationTextRow>
			<ResourceNotificationCheckBoxRow 
				v-model:checked="isCheckedForAll"
				:disabled="!isConfirmationNotificationOn"
			>
				<div class="resource-creation-wizard__form-notification-text">
					{{ loc('BRCW_NOTIFICATION_CARD_HELPER_TEXT_APPLY_FOR_ALL') }}
				</div>
			</ResourceNotificationCheckBoxRow>
		</ResourceNotification>
	`,
};
