import { Dom, Text } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';
import { hint } from 'ui.vue3.directives.hint';
import { BIcon } from 'ui.icon-set.api.vue';
import { Actions, CRM } from 'ui.icon-set.api.core';
import 'ui.icon-set.actions';
import 'ui.icon-set.crm';
import 'ui.hint';

import { Button as UiButton, ButtonSize, ButtonColor } from 'booking.component.button';
import { Switcher } from 'booking.component.switcher';
import { Model, NotificationChannel, NotificationFieldsMap } from 'booking.const';
import { resourceCreationWizardService } from 'booking.provider.service.resource-creation-wizard-service';
import type { NotificationsModel, NotificationsTemplateModel } from 'booking.model.notifications';

import { ChannelMenu } from '../channel-menu/channel-menu';
import { ChooseTemplatePopup } from '../choose-template-popup/choose-template-popup';
import { TemplateEmpty } from '../template-empty/template-empty';
import { CheckedForAll } from './components/checked-for-all';
import { Description } from './components/description';
import { MessageTemplate } from './components/message-tempalte';
import { ManagerNotification } from './components/manager-notification';

// eslint-disable-next-line no-promise-executor-return
const sleep = (timeout: number) => new Promise((resolve) => setTimeout(resolve, timeout));

// @vue/component
export const ResourceNotification = {
	name: 'ResourceNotification',
	components: {
		Switcher,
		BIcon,
		UiButton,
		Description,
		ChannelMenu,
		ChooseTemplatePopup,
		TemplateEmpty,
		CheckedForAll,
		MessageTemplate,
		ManagerNotification,
	},
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
		managerDescription: {
			type: String,
			default: '',
		},
		scrollToCard: {
			type: String,
			default: null,
		},
	},
	emits: ['update:checked'],
	setup(): Object
	{
		return {
			ButtonSize,
			ButtonColor,
			Actions,
			CRM,
		};
	},
	data(): Object
	{
		return {
			messenger: NotificationChannel.WhatsApp,
			showTemplatePopup: false,
		};
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
		template(): NotificationsTemplateModel | undefined
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
		ordinal(): string
		{
			return NotificationFieldsMap.Ordinal[this.type];
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

		void this.animateHeight(false);
	},
	updated(): void
	{
		this.hintManager.init(this.$el);

		void this.animateHeight(true);
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
		expand(): void
		{
			void resourceCreationWizardService.updateNotificationExpanded(this.type, !this.model.isExpanded);
		},
		async animateHeight(withAnimation: boolean): Promise<void>
		{
			Dom.style(this.$el, 'transition', null);
			if (withAnimation)
			{
				await sleep(10);
			}

			const prevHeight = this.$el.offsetHeight;
			Dom.style(this.$el, 'height', null);
			if (!this.model.isExpanded)
			{
				Dom.style(this.$refs.main, 'display', 'none');
				Dom.style(this.$refs.manager?.$el, 'display', 'none');
			}

			const height = this.$el.offsetHeight;
			Dom.style(this.$refs.main, 'display', null);
			Dom.style(this.$refs.manager?.$el, 'display', null);
			Dom.style(this.$el, 'height', `${prevHeight}px`);

			if (withAnimation)
			{
				Dom.style(this.$el, 'transition', 'height 0.2s');
				await sleep(10);
				Dom.style(this.$el, 'height', `${height}px`);
			}
			else
			{
				Dom.style(this.$el, 'height', `${height}px`);
			}
		},
	},
	template: `
		<div class="booking-resource-creation-wizard-notification-container" :class="{'--disabled': !checked}">
			<div class="booking-resource-creation-wizard-notification">
				<div class="booking-resource-creation-wizard-notification-header" @click="expand">
					<div class="booking-resource-creation-wizard-notification-number">{{ ordinal }}</div>
					<div class="booking-resource-creation-wizard-notification-title">{{ title }}</div>
					<BIcon :name="model.isExpanded ? Actions.CHEVRON_UP : Actions.CHEVRON_DOWN"/>
				</div>
				<div class="booking-resource-creation-wizard-notification-main" ref="main">
					<div class="resource-creation-wizard__form-notification-info-title-row --main">
						<BIcon :name="CRM.CHAT_LINE"/>
						<div class="resource-creation-wizard__form-notification-info-title">
							{{ loc('BRCW_NOTIFICATION_CARD_MESSAGE') }}
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
					<div class="resource-creation-wizard__form-notification-info --message">
						<div class="resource-creation-wizard__form-notification-info-text-row">
							{{ loc('BRCW_NOTIFICATION_CARD_MESSAGE_TEXT') }}
							<ChannelMenu
								:current-channel="messenger"
								@updateChannel="handleChannelChange"
							/>
						</div>
						<template v-if="hasTemplate">
							<MessageTemplate :text="messageTemplate"/>
							<div class="resource-creation-wizard__form-notification-info-template-choose-buttons">
								<div class="booking-resource-creation-wizard-choose-template-button" ref="chooseTemplateBtn">
									<UiButton
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
					<slot name="client"/>
					<CheckedForAll :type="type" :disabled="!checked"/>
				</div>
			</div>
			<ManagerNotification
				v-if="$slots.manager"
				:description="managerDescription"
				:text="model.managerNotification"
				:helpDesk="helpDesk"
				:scrollToCard="scrollToCard"
				ref="manager"
			>
				<slot name="manager"/>
			</ManagerNotification>
		</div>
	`,
};
