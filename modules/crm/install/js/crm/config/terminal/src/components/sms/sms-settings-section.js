// @flow

import { SettingsSection } from '../settings-section';
import { NotificationLink } from '../../types';
import { Sms } from './sms';
import { ajax, Text } from 'main.core';
import { Popup } from 'main.popup';
import { SmsProviderSelect } from './sms-provider-select';
import { mapGetters, mapMutations } from 'ui.vue3.vuex';

export const SmsSettingsSection = {

	data() {
		return {
			popup: null,
		};
	},

	components: {
		SettingsSection,
		Sms,
		SmsProviderSelect,
	},

	computed: {
		...mapGetters([
			'isSmsSendingActive',
			'isAnyServiceEnabled',
			'isNotificationsEnabled',
			'getSelectedService',
		]),

		getSectionTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_TITLE');
		},

		getSectionHint(): string
		{
			const replacements = {
				'#LINK_START#': '<a onclick="top.BX.Helper.show(\'redirect=detail&code=17399056\')" style="cursor: pointer">',
				'#LINK_END#': '</a>',
			};

			return this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_HINT_TEXT', replacements);
		},
		getNotificationConnectHint(): string
		{
			const replacements = {
				'#LINK_START#': '<span onclick="top.BX.Helper.show(\'redirect=detail&code=17399068\')" class="sms-provider-selector">',
				'#LINK_END#': '</span>',
			};

			return this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_PROVIDER_UNC_CONNECTED', replacements);
		},
	},

	methods: {
		...mapMutations([
			'setSmsSendingActive',
			'updateServicesList',
			'updateNotificationsEnabled',
		]),
		...mapGetters([
			'getPaymentSlipLinkScheme',
			'getNotificationsLink',
			'getServiceLink',
		]),

		onServiceConnectSliderClosed(): void
		{
			ajax
				.runComponentAction('bitrix:crm.config.terminal.settings', 'updateServicesList')
				.then((response) => {
					if (response.status === 'success')
					{
						const data = response.data;
						if (data?.isUCNEnabled)
						{
							this.updateNotificationsEnabled(data.isUCNEnabled);
						}

						this.updateServicesList(data.activeSmsServices);
					}
				})
				.catch((error) => {
					console.error(error);
				});
		},

		onSectionToggled(): void
		{
			this.setSmsSendingActive(!this.isSmsSendingActive);
		},

		getSmsMessage(): string
		{
			const link = `<br /><span class="sms-link-path">${this.getPaymentSlipLinkScheme()}</span><span class="sms-link-plug">xxxxx</span> `;
			const text = this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_MESSAGE_TEMPLATE');

			return Text.encode(text).replaceAll('#PAYMENT_SLIP_LINK#', link);
		},

		onSmsMouseenter(event): void
		{
			const target = event.target;
			const message = this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_MESSAGE_HINT');

			if (this.popup)
			{
				this.popup.destroy();
				this.popup = null;
			}

			this.popup = new Popup(null, target, {
				events: {
					onPopupClose: () => {
						this.popup.destroy();
						this.popup = null;
					},
				},
				darkMode: true,
				content: message,
				offsetLeft: target.offsetWidth,
			});

			this.popup.show();
		},

		onSmsMouseleave(): void
		{
			this.popup.destroy();
		},

		onNotificationsConnectLinkClick(): void
		{
			const notificationLink: NotificationLink = this.getNotificationsLink();
			if (notificationLink === null)
			{
				return;
			}

			if (notificationLink.type === 'connect_link')
			{
				const options = {
					cacheable: false,
					allowChangeHistory: false,
					requestMethod: 'get',
					width: 700,
					events: {
						onClose: () => {
							this.onServiceConnectSliderClosed();
						},
					},
				};

				BX.SidePanel.Instance.open(notificationLink.value, options);
			}
			else if (notificationLink.type === 'ui_helper')
			{
				top.BX.UI.InfoHelper.show(notificationLink.value);
			}
		},

		onProviderSmsNotificationClick(): void
		{
			const options = {
				cacheable: false,
				allowChangeHistory: false,
				requestMethod: 'get',
				width: 700,
				events: {
					onClose: () => {
						this.onServiceConnectSliderClosed();
					},
				},
			};

			BX.SidePanel.Instance.open(this.getServiceLink(), options);
		},
	},

	// language=Vue
	template: `
		<div v-if="isAnyServiceEnabled" style="display: flex; justify-content: space-between; margin-bottom: 24px;">
			<div>
				<SettingsSection
					:title="getSectionTitle"
					:switchable="true"
					v-on:toggle="onSectionToggled"
					:active="isSmsSendingActive"
					:hint="getSectionHint"
				/>
				<div style="margin-left: 53px;">
					<div
						v-html="getNotificationConnectHint"
						v-if="isNotificationsEnabled"
						class="sms-provider-name"
					></div>
					<div v-else>
						<div class="sms-provider-name" style="padding: 3px 0;">
							{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_SELECT_MSGVER_2', {'%PROVIDER_NAME%': getSelectedService['NAME']}) }}
						</div>
						<SmsProviderSelect v-on:onConnectSliderClosed="onServiceConnectSliderClosed"/>
					</div>
				</div>
			</div>
			<div>
				<div class="sms-provider-message-title">
					{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_MESSAGE_TITLE') }}
				</div>
				<div>
					<Sms>
						<span
							v-html="getSmsMessage()"
							v-on:mouseenter="onSmsMouseenter($event)"
							v-on:mouseleave="onSmsMouseleave"
						></span>
					</Sms>
				</div>
			</div>
		</div>
		<div style="margin-bottom: 24px;" v-else>
			<span class="sms-provider-empty-provider-list-text" v-if="(getNotificationsLink() !== null) && (getServiceLink() !== '')">
				{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_ONBOARDING_TEXT') }}
			</span>
			<span class="sms-provider-empty-provider-list-text" v-else-if="(getNotificationsLink() === null) && (getServiceLink() !== '')">
				{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_ONBOARDING_ONLY_SMS_SERVICES_TEXT') }}
			</span>
			<button class="ui-btn ui-btn-md ui-btn-primary" @click="onNotificationsConnectLinkClick" v-if="getNotificationsLink() !== null">
				{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_UNC_CONNECT_BTN') }}
			</button>

			<button class="ui-btn ui-btn-md ui-btn-light-border" @click="onProviderSmsNotificationClick" v-if="(getNotificationsLink() !== null) && (getServiceLink() !== '')">
				{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_PROVIDER_CONNECT_BTN') }}
			</button>
			<button class="ui-btn ui-btn-md ui-btn-primary" @click="onProviderSmsNotificationClick" v-else-if="(getNotificationsLink() === null) && (getServiceLink() !== '')">
				{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_PROVIDER_CONNECT_BTN') }}
			</button>
		</div>
	`,
};
