import { mapGetters, mapMutations } from 'ui.vue3.vuex';
import 'ui.icon-set.main';

import { Button, ButtonSize, ButtonColor } from 'booking.component.button';
import { HelpDesk, Model } from 'booking.const';
import { helpDesk } from 'booking.lib.help-desk';
import type { NotificationsTemplateModel } from 'booking.model.notifications';

import { replaceLabelMixin } from '../label/label';
import { ResourceNotification } from '../layout/resource-notification';
import { ResourceNotificationCheckBoxRow } from '../layout/resource-notification-checkbox-row';
import { ResourceNotificationTextRow } from '../layout/resource-notification-text-row';
import { ChooseTemplatePopup } from '../choose-template-popup/choose-template-popup';
import { NotificationCardMixin } from '../notification-card-mixin';
import { TemplateEmpty } from '../template-empty/template-empty';

export const Feedback = {
	name: 'ResourceNotificationCardFeedback',
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
	components: {
		ResourceNotification,
		ResourceNotificationTextRow,
		ResourceNotificationCheckBoxRow,
		ChooseTemplatePopup,
		Button,
		TemplateEmpty,
	},
	data(): { isCheckedForAllLocal: boolean, originalValue: boolean }
	{
		return {
			ButtonSize,
			ButtonColor,
			isCheckedForAllLocal: true,
			originalValue: this.resourceType.isFeedbackNotificationOn,
			originalTemplateType: this.resourceType.templateTypeFeedback,
			showTemplatePopup: false,
		};
	},
	methods: {
		...mapMutations(
			'resource-creation-wizard',
			['updateResource'],
		),
		updateResourceType(): void
		{
			const checked = this.isCheckedForAll
				? this.isFeedbackNotificationOn
				: this.originalValue
			;

			const templateType = this.isCheckedForAll
				? this.resource.templateTypeFeedback
				: this.originalTemplateType
			;

			const resourceType = this.resourceType;
			this.$store.dispatch('resourceTypes/upsert', {
				...resourceType,
				isFeedbackNotificationOn: checked,
				templateTypeFeedback: templateType,
			});
		},
		chooseTemplate()
		{
			this.showTemplatePopup = true;
		},
		handleTemplateTypeSelected(selectedType: string): void
		{
			this.updateResource({ templateTypeFeedback: selectedType });
			this.updateResourceType();
		},
		showHelpDesk(): void
		{
			helpDesk.show(
				HelpDesk.ResourceNotificationFeedback.code,
				HelpDesk.ResourceNotificationFeedback.anchorCode,
			);
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
				// return this.resource.isFeedbackNotificationOn;
			},
			set(isFeedbackNotificationOn: boolean)
			{
				this.updateResource({ isFeedbackNotificationOn });
				this.updateResourceType();
			},
		},
		locSendFeedbackTime(): string
		{
			const hint = this.loc('BRCW_BOOKING_SOON_HINT');
			const immediately = this.loc('BRCW_NOTIFICATION_CARD_LABEL_IMMEDIATELY');
			const replacements = {
				'#time#': this.getLabel(immediately, this.isFeedbackNotificationOn, hint),
			};

			return this.loc('BRCW_NOTIFICATION_CARD_FEEDBACK_HELPER_TEXT_SECOND', replacements);
		},
		template(): NotificationsTemplateModel
		{
			return this.model.templates.find((template) => template.type === this.resource.templateTypeFeedback);
		},
	},
	template: `
		<ResourceNotification
			v-model:checked="isFeedbackNotificationOn"
			:view="'feedback'"
			:disableSwitcher="true"
			:title="loc('BRCW_NOTIFICATION_CARD_FEEDBACK_TITLE')"
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
							:disabled="!isFeedbackNotificationOn"
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
					:currentTemplateType="resource.templateTypeFeedback"
					@templateTypeSelected="handleTemplateTypeSelected"
					@close="showTemplatePopup = false"
				/>
			</template>

			<ResourceNotificationTextRow icon="--info-1">
				<div class="resource-creation-wizard__form-notification-text">
					<div>
						{{ loc('BRCW_NOTIFICATION_CARD_FEEDBACK_HELPER_TEXT_FIRST') }}
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
				<div class="resource-creation-wizard__form-notification-text" v-html="locSendFeedbackTime"></div>
			</ResourceNotificationTextRow>
			<ResourceNotificationCheckBoxRow 
				v-model:checked="isCheckedForAll"
				:disabled="!isFeedbackNotificationOn"
			>
				<div class="resource-creation-wizard__form-notification-text">
					{{ loc('BRCW_NOTIFICATION_CARD_HELPER_TEXT_APPLY_FOR_ALL') }}
				</div>
			</ResourceNotificationCheckBoxRow>
		</ResourceNotification>
	`,
};
