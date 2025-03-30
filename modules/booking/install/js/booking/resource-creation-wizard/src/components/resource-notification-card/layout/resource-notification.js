import { Text } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';
import { hint } from 'ui.vue3.directives.hint';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.crm';
import 'ui.hint';

import { Button, ButtonSize, ButtonColor } from 'booking.component.button';
import { Switcher } from 'booking.component.switcher';
import { Model, NotificationChannel, NotificationFieldsMap } from 'booking.const';
import type { NotificationsModel, NotificationsTemplateModel } from 'booking.model.notifications';

import { ChannelMenu } from '../channel-menu/channel-menu';
import { ChooseTemplatePopup } from '../choose-template-popup/choose-template-popup';
import { TemplateEmpty } from '../template-empty/template-empty';
import { CheckedForAll } from './components/checked-for-all';
import { Description } from './components/description';

export const ResourceNotification = {
	name: 'ResourceNotification',
	emits: ['update:checked'],
	directives: { hint },
	props: {
		type: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		description: {
			type: String,
			required: true,
		},
		helpDesk: {
			type: Object,
			required: true,
		},
		checked: {
			type: Boolean,
			default: false,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
	},
	data(): Object
	{
		return {
			IconSet,
			ButtonSize,
			ButtonColor,
			messenger: NotificationChannel.WhatsApp,
			showTemplatePopup: false,
		};
	},
	components: {
		Switcher,
		Icon,
		Button,
		Description,
		ChannelMenu,
		ChooseTemplatePopup,
		TemplateEmpty,
		CheckedForAll,
	},
	created(): void
	{
		this.hintManager = BX.UI.Hint.createInstance({
			id: `brwc-notification-hint-${Text.getRandom(5)}`,
			popupParameters: {
				targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
			},
		});
	},
	mounted(): void
	{
		this.hintManager.init(this.$el);
	},
	updated(): void
	{
		this.hintManager.init(this.$el);
	},
	computed: {
		...mapGetters({
			resource: `${Model.ResourceCreationWizard}/getResource`,
			isCurrentSenderAvailable: `${Model.Notifications}/isCurrentSenderAvailable`,
		}),
		model(): NotificationsModel
		{
			return this.$store.getters[`${Model.Notifications}/getByType`](this.type);
		},
		template(): NotificationsTemplateModel
		{
			const templateName = this.resource[this.templateTypeField];

			return this.model.templates.find((template) => template.type === templateName);
		},
		messageTemplate(): string
		{
			return {
				[NotificationChannel.WhatsApp]: this.template?.text ?? '',
				[NotificationChannel.Sms]: this.template?.textSms ?? '',
			}[this.messenger] ?? '';
		},
		hasTemplate(): boolean
		{
			return this.isCurrentSenderAvailable && this.messageTemplate;
		},
		disableSwitcher(): boolean
		{
			return this.disabled || !this.isCurrentSenderAvailable || !this.template;
		},
		templateTypeField(): string
		{
			return NotificationFieldsMap.TemplateType[this.type];
		},
		soonHint(): Object
		{
			return {
				text: this.loc('BRCW_BOOKING_SOON_HINT'),
				popupOptions: {
					offsetLeft: 60,
					targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
				},
			};
		},
	},
	methods: {
		handleChannelChange(selectedChannel: string): void
		{
			this.messenger = selectedChannel;
		},
		handleTemplateTypeSelected(selectedType: string): void
		{
			void this.$store.dispatch(`${Model.ResourceCreationWizard}/updateResource`, { [this.templateTypeField]: selectedType });
		},
		getChooseTemplateButton(): HTMLElement
		{
			return this.$refs.chooseTemplateBtn || null;
		},
	},
	template: `
		<div class="ui-form resource-creation-wizard__form-notification" :class="{'--disabled': !checked}">
			<div class="resource-creation-wizard__form-notification-info">
				<div class="resource-creation-wizard__form-notification-info-title-row">
					<Icon :name="IconSet.CHAT_LINE"/>
					<div class="resource-creation-wizard__form-notification-info-title">
						{{ title }}
					</div>
					<Switcher
						v-hint="disableSwitcher && soonHint"
						class="resource-creation-wizard__form-notification-info-switcher"
						:data-id="'brcw-resource-notification-info-switcher-' + type"
						:model-value="checked"
						:disabled="disableSwitcher"
						@update:model-value="$emit('update:checked', $event)"
					/>
				</div>
				<div class="resource-creation-wizard__form-notification-info-text-row">
					<div class="resource-creation-wizard__form-notification__info-text-row-message-text">
						{{ loc('BRCW_NOTIFICATION_CARD_MESSAGE_TEXT') }}
						<ChannelMenu
							:current-channel="messenger"
							@updateChannel="handleChannelChange"
						/>
					</div>
				</div>
				<template v-if="hasTemplate">
					<div
						v-html="messageTemplate"
						class="resource-creation-wizard__form-notification-info-template"
					></div>
					<div class="resource-creation-wizard__form-notification-info-template-choose-buttons">
						<div class="booking-resource-creation-wizard-choose-template-button" ref="chooseTemplateBtn">
							<Button
								:disabled="!checked"
								:text="loc('BRCW_NOTIFICATION_CARD_CHOOSE_TEMPLATE_TYPE')"
								:size="ButtonSize.EXTRA_SMALL"
								:color="ButtonColor.LIGHT_BORDER"
								:round="true"
								@click="showTemplatePopup = true"
							/>
						</div>
					</div>
				</template>
				<TemplateEmpty v-else/>
				<ChooseTemplatePopup
					v-if="showTemplatePopup"
					:bindElement="$refs.chooseTemplateBtn"
					:model="model"
					:current-channel="messenger"
					:currentTemplateType="resource[templateTypeField]"
					@templateTypeSelected="handleTemplateTypeSelected"
					@close="showTemplatePopup = false"
				/>
			</div>
			<Description :description="description" :helpDesk="helpDesk"/>
			<slot/>
			<CheckedForAll :type="type" :disabled="!checked"/>
		</div>
	`,
};
