import { mapGetters, mapMutations } from 'ui.vue3.vuex';
import 'ui.icon-set.main';

import { Button, ButtonSize, ButtonColor } from 'booking.component.button';
import { HelpDesk, Model } from 'booking.const';
import { helpDesk } from 'booking.lib.help-desk';
import type { NotificationsTemplateModel } from 'booking.model.notifications';

import { replaceLabelMixin } from '../label/label';
import { NotificationCardMixin } from '../notification-card-mixin';
import { ResourceNotification } from '../layout/resource-notification';
import { ResourceNotificationTextRow } from '../layout/resource-notification-text-row';
import { ResourceNotificationCheckBoxRow } from '../layout/resource-notification-checkbox-row';
import { ChooseTemplatePopup } from '../choose-template-popup/choose-template-popup';
import { TemplateEmpty } from '../template-empty/template-empty';

export const Late = {
	name: 'ResourceNotificationCardLate',
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
	mixins: [replaceLabelMixin, NotificationCardMixin],
	data(): { isCheckedForAllLocal: boolean, originalValue: boolean }
	{
		return {
			ButtonSize,
			ButtonColor,
			isCheckedForAllLocal: true,
			originalValue: this.resourceType.isDelayedNotificationOn,
			originalTemplateType: this.resourceType.templateTypeDelayed,
			showTemplatePopup: false,
		};
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
				this.updateResource({ isDelayedNotificationOn });
				this.updateResourceType();
			},
		},
		resourceNotificationTitle(): string
		{
			return this.loc('BRCW_NOTIFICATION_CARD_LATE_TITLE');
		},
		template(): NotificationsTemplateModel
		{
			return this.model.templates.find((template) => template.type === this.resource.templateTypeDelayed);
		},
		locSendMessageAfter(): string
		{
			const hint = this.loc('BRCW_BOOKING_SOON_HINT');
			const minutes = this.loc('BRCW_NOTIFICATION_CARD_LABEL_TIME_AFTER_MINUTES');
			const replacements = {
				'#time#': this.getLabel(minutes, this.isDelayedNotificationOn, hint),
			};

			return this.loc('BRCW_NOTIFICATION_CARD_LATE_HELPER_TEXT_SECOND', replacements);
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
				? this.isDelayedNotificationOn
				: this.originalValue
			;

			const templateType = this.isCheckedForAll
				? this.resource.templateTypeDelayed
				: this.originalTemplateType
			;

			const resourceType = this.resourceType;
			this.$store.dispatch('resourceTypes/upsert', {
				...resourceType,
				isDelayedNotificationOn: checked,
				templateTypeDelayed: templateType,
			});
		},
		chooseTemplate()
		{
			this.showTemplatePopup = true;
		},
		handleTemplateTypeSelected(selectedType: string): void
		{
			this.updateResource({ templateTypeDelayed: selectedType });
			this.updateResourceType();
		},
		showHelpDesk(): void
		{
			helpDesk.show(
				HelpDesk.ResourceNotificationLate.code,
				HelpDesk.ResourceNotificationLate.anchorCode,
			);
		},
	},
	mounted(): void
	{
		if (!this.template)
		{
			console.warn('Notification template not found');
			this.isDelayedNotificationOn = false;
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
			v-model:checked="isDelayedNotificationOn"
			:view="'late'"
			:title="resourceNotificationTitle"
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
							:disabled="!isDelayedNotificationOn"
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
					:currentTemplateType="resource.templateTypeDelayed"
					@templateTypeSelected="handleTemplateTypeSelected"
					@close="showTemplatePopup = false"
				/>
			</template>

			<ResourceNotificationTextRow icon="--info-1">
				<div class="resource-creation-wizard__form-notification-text">
					<div>
						{{ loc('BRCW_NOTIFICATION_CARD_LATE_HELPER_TEXT_FIRST') }}
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
				<div class="resource-creation-wizard__form-notification-text" v-html="locSendMessageAfter"></div>
			</ResourceNotificationTextRow>
			<ResourceNotificationCheckBoxRow
				v-model:checked="isCheckedForAll"
				:disabled="!isDelayedNotificationOn"
			>
				<div class="resource-creation-wizard__form-notification-text">
					{{ loc('BRCW_NOTIFICATION_CARD_HELPER_TEXT_APPLY_FOR_ALL') }}
				</div>
			</ResourceNotificationCheckBoxRow>
		</ResourceNotification>
	`,
};
