import { Type } from 'main.core';
import { mapGetters, mapMutations } from 'ui.vue3.vuex';
import 'ui.icon-set.main';

import { Button, ButtonSize, ButtonColor } from 'booking.component.button';
import { Model, AhaMoment, HelpDesk } from 'booking.const';
import { ahaMoments } from 'booking.lib.aha-moments';
import { helpDesk } from 'booking.lib.help-desk';
import type { NotificationsTemplateModel } from 'booking.model.notifications';

import { ChooseTemplatePopup } from '../choose-template-popup/choose-template-popup';
import { replaceLabelMixin } from '../label/label';
import { NotificationCardMixin } from '../notification-card-mixin';
import { ResourceNotification } from '../layout/resource-notification';
import { ResourceNotificationTextRow } from '../layout/resource-notification-text-row';
import { ResourceNotificationCheckBoxRow } from '../layout/resource-notification-checkbox-row';
import { TemplateEmpty } from '../template-empty/template-empty';

export const BaseInfo = {
	name: 'ResourceNotificationCardBaseInfo',
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
	components: {
		ResourceNotification,
		ResourceNotificationTextRow,
		ResourceNotificationCheckBoxRow,
		ChooseTemplatePopup,
		Button,
		TemplateEmpty,
	},
	data(): { isCheckedForAllLocal: boolean, originalValue: boolean, selectedTemplateType: string }
	{
		return {
			ButtonSize,
			ButtonColor,
			isCheckedForAllLocal: true,
			originalValue: this.resourceType.isInfoNotificationOn,
			originalTemplateType: this.resourceType.templateTypeInfo,
			showTemplatePopup: false,
		};
	},
	mounted(): void
	{
		if (ahaMoments.shouldShow(AhaMoment.MessageTemplate))
		{
			setTimeout(() => this.showAhaMoment(), 500);
		}

		if (!this.template)
		{
			console.warn('Notification template not found');
			this.isInfoNotificationOn = false;
		}
	},
	methods: {
		...mapMutations(
			Model.ResourceCreationWizard,
			['updateResource'],
		),
		updateResourceType(): void
		{
			const checked = this.isCheckedForAll
				? this.isInfoNotificationOn
				: this.originalValue
			;

			const templateType = this.isCheckedForAll
				? this.resource.templateTypeInfo
				: this.originalTemplateType
			;

			const resourceType = this.resourceType;
			this.$store.dispatch('resourceTypes/upsert', {
				...resourceType,
				isInfoNotificationOn: checked,
				templateTypeInfo: templateType,
			});
		},
		chooseTemplate()
		{
			this.showTemplatePopup = true;
		},
		handleTemplateTypeSelected(selectedType: string): void
		{
			this.updateResource({ templateTypeInfo: selectedType });
			this.updateResourceType();
		},
		async showAhaMoment(): Promise<void>
		{
			await ahaMoments.showGuide({
				id: 'booking-message-template',
				title: this.loc('BOOKING_AHA_MESSAGE_TEMPLATE_TITLE'),
				text: this.loc('BOOKING_AHA_MESSAGE_TEMPLATE_TEXT'),
				article: HelpDesk.AhaMessageTemplate,
				target: this.$refs.chooseTemplateButton.$el,
				targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
			});

			ahaMoments.setShown(AhaMoment.MessageTemplate);
		},
		showHelpDesk(): void
		{
			helpDesk.show(
				HelpDesk.ResourceNotificationInfo.code,
				HelpDesk.ResourceNotificationInfo.anchorCode,
			);
		},
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
				this.updateResource({ isInfoNotificationOn });
				this.updateResourceType();
			},
		},
		resourceNotificationTitle(): string
		{
			return this.loc('BRCW_NOTIFICATION_CARD_BASE_INFO_TITLE');
		},
		template(): NotificationsTemplateModel
		{
			return this.model.templates.find((template) => template.type === this.resource.templateTypeInfo);
		},
		locInfoTimeSend(): string
		{
			const hint = this.loc('BRCW_BOOKING_SOON_HINT');
			const immediately = this.loc('BRCW_NOTIFICATION_CARD_LABEL_IMMEDIATELY');
			const replacements = {
				'#time#': this.getLabel(immediately, this.isInfoNotificationOn, hint),
			};

			return this.loc('BRCW_NOTIFICATION_CARD_BASE_INFO_HELPER_TEXT_SECOND', replacements);
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
	template: `
		<ResourceNotification
			v-model:checked="isInfoNotificationOn"
			:view="'base-info'"
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
							ref="chooseTemplateButton"
							:disabled="!isInfoNotificationOn"
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
					:currentTemplateType="resource.templateTypeInfo"
					@templateTypeSelected="handleTemplateTypeSelected"
					@close="showTemplatePopup = false"
				/>
			</template>

			<ResourceNotificationTextRow icon="--info-1">
				<div class="resource-creation-wizard__form-notification-text">
					<div>
						{{ loc('BRCW_NOTIFICATION_CARD_BASE_INFO_HELPER_TEXT_FIRST') }}
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
				<div class="resource-creation-wizard__form-notification-text" v-html="locInfoTimeSend"></div>
			</ResourceNotificationTextRow>
			<ResourceNotificationCheckBoxRow 
				v-model:checked="isCheckedForAll"
				:disabled="!isInfoNotificationOn"
			>
				<div class="resource-creation-wizard__form-notification-text">
					{{ loc('BRCW_NOTIFICATION_CARD_HELPER_TEXT_APPLY_FOR_ALL') }}
				</div>
			</ResourceNotificationCheckBoxRow>
		</ResourceNotification>
	`,
};
