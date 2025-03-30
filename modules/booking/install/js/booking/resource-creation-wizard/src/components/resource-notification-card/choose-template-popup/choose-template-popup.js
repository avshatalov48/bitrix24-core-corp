import { Event, Text } from 'main.core';
import { DateTimeFormat } from 'main.date';
import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';

import { Model, NotificationChannel } from 'booking.const';
import { Popup } from 'booking.component.popup';
import { ButtonSize, ButtonColor } from 'booking.component.button';
import type { NotificationsTemplateModel } from 'booking.model.notifications';

import { ChannelMenu } from '../channel-menu/channel-menu';

import './choose-template-popup.css';

export const ChooseTemplatePopup = {
	name: 'ResourceNotificationChooseTemplatePopup',
	emits: ['close', 'templateTypeSelected'],
	props: {
		bindElement: {
			type: HTMLElement,
			required: true,
		},
		model: {
			type: Object,
			required: true,
		},
		currentChannel: {
			type: String,
			required: true,
		},
		currentTemplateType: {
			type: String,
			required: true,
		},
		buttonSize: {
			type: String,
			default: ButtonSize.EXTRA_SMALL,
		},
		buttonColors: {
			type: Object,
			default: () => ({
				selected: ButtonColor.BASE_LIGHT,
				default: ButtonColor.PRIMARY,
			}),
		},
	},
	data(): Object
	{
		return {
			IconSet,
			messenger: NotificationChannel.WhatsApp,
			selectedTemplateType: this.currentTemplateType,
		};
	},
	components: {
		Popup,
		ChannelMenu,
		Icon,
	},
	beforeMount(): void
	{
		this.messenger = this.currentChannel;
	},
	mounted(): void
	{
		this.adjustPosition();
		Event.bind(document, 'scroll', this.adjustPosition, { capture: true });
	},
	beforeUnmount(): void
	{
		Event.unbind(document, 'scroll', this.adjustPosition, { capture: true });
	},
	computed: {
		...mapGetters({
			templateTypes: `${Model.Dictionary}/getNotificationTemplates`,
		}),
		popupId(): string
		{
			return `booking-resource-wizard-choose-template-popup-${Text.getRandom(4)}`;
		},
		config(): Object
		{
			return {
				className: 'booking-resource-wizard-choose-template-popup',
				bindElement: this.bindElement,
				bindOptions: {
					forceBindPosition: true,
					forceTop: true,
				},
				width: 350,
				offsetLeft: (this.bindElement.childNodes[0]?.offsetWidth ?? 146) + 10,
				offsetTop: -149,
				animation: 'fading-slide',
				angle: {
					offset: 120,
					position: 'left',
				},
				angleBorderRadius: '4px 0',
				padding: 15,
				targetContainer: document.querySelector('div.resource-creation-wizard__wrapper'),
			};
		},
		getFormattedTime(): string
		{
			return DateTimeFormat.format(DateTimeFormat.getFormat('SHORT_TIME_FORMAT'), Date.now() / 1000);
		},
	},
	methods: {
		getMessageTemplate(messenger: string, templateType: string): string
		{
			const templateModel = this.model.templates.find((template: NotificationsTemplateModel): boolean => {
				return template.type === templateType;
			});

			if (!templateModel)
			{
				return '';
			}

			switch (messenger)
			{
				case NotificationChannel.WhatsApp:
					return templateModel.text;
				case NotificationChannel.Sms:
					return templateModel.textSms;
				default:
					return '';
			}
		},
		handleChannelChange(selectedChannel): void
		{
			this.messenger = selectedChannel;
		},
		chooseType(selectedType): void
		{
			this.selectedTemplateType = selectedType;
			this.$emit('templateTypeSelected', this.selectedTemplateType);
		},
		adjustPosition(): void
		{
			this.$refs.popup.adjustPosition({
				forceBindPosition: true,
				forceTop: true,
			});
		},
		getButtonText(templateType): string
		{
			return (this.selectedTemplateType === templateType)
				? this.loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_BTN_SELECTED')
				: this.loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_BTN_SELECT_TYPE');
		},
		getButtonColor(templateType: string): string
		{
			return (this.selectedTemplateType === templateType)
				? this.buttonColors.selected
				: this.buttonColors.default;
		},
		getTemplateTitle(templateType: string): string
		{
			const typeDictionary = Object.values(this.templateTypes)
				.find((templateDictionary: { name: string, value: string }): boolean => {
					return templateDictionary.value === templateType;
				});

			return (typeDictionary)
				? typeDictionary.name
				: '';
		},
	},
	template: `
		<Popup
			v-slot="{freeze, unfreeze}"
			:id="popupId"
			:config="config"
			ref="popup"
			@close="$emit('close')"
		>
			<div class="booking-resource-wizard-choose-template-popup">
				<div class="booking-resource-wizard-choose-template-popup-header">
					<div class="booking-resource-wizard-choose-template-popup-header-title">
						{{ loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_HEADER_TITLE') }}
						<ChannelMenu
							:current-channel="messenger"
							@popupShown="freeze"
							@popupClosed="unfreeze"
							@updateChannel="handleChannelChange"
						/>
						<Icon
							:class="['--close-btn']"
							:name="IconSet.CROSS_25"
							:color="'var(--ui-color-palette-gray-20)'"
							@click="$emit('close')"
						/>
					</div>
					<div class="booking-resource-wizard-choose-template-popup-header-close-btn"></div>
					<div class="booking-resource-wizard-choose-template-popup-header-line"></div>
				</div>
				<div class="booking-resource-wizard-choose-template-popup-container">
					<div v-for="template in this.model.templates" :key="template.type"
						 class="booking-resource-wizard-choose-template-popup-content">
						<div class="booking-resource-wizard-choose-template-popup-content-desc">
							{{ getTemplateTitle(template.type) }}
						</div>
						<div class="booking-resource-wizard-choose-template-popup-content-msg-example">
							<div
								:class="['booking-resource-wizard-choose-template-popup-content-msg-example-body', messenger]">
								<div
									:class="['booking-resource-wizard-choose-template-popup-content-msg-example-body-text', messenger]">
									<div v-html="getMessageTemplate(messenger, template.type)"></div>
									<div
										:class="['booking-resource-wizard-choose-template-popup-content-msg-example-body-text-time', messenger]">
										{{ getFormattedTime }}
									</div>
								</div>
								<div
									:class="['booking-resource-wizard-choose-template-popup-content-msg-example-body-text-tail', messenger]"></div>
							</div>
							<div class="booking-resource-wizard-choose-template-popup-content-msg-example-actions">
								<Icon
									:name="IconSet.CIRCLE_CHECK"
									:color="'var(--ui-color-palette-green-55)'"
									:size="24"
									v-if="selectedTemplateType === template.type"
								/>
								<button
									:class="['ui-btn ui-btn-round', buttonSize, getButtonColor(template.type), {'--selected': selectedTemplateType === template.type}]"
									type="button"
									@click="chooseType(template.type)"
								>
									{{ getButtonText(template.type) }}
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</Popup>
	`,
};
