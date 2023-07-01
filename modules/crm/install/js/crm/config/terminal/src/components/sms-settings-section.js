//@flow

import {SettingsSection} from "./settings-section";
import {NotificationLink} from "../types";
import {Sms} from "./sms";
import {ajax, Text} from "main.core";
import {Popup} from "main.popup";
import {SmsProviderSelect} from "./sms-provider-select";
import {mapGetters, mapMutations} from "ui.vue3.vuex";

export const SmsSettingsSection = {

	data() {
		return {
			popup: null,
		};
	},

	components: {
		'SettingsSection': SettingsSection,
		'Sms': Sms,
		'SmsProviderSelect': SmsProviderSelect,
		'Notification': Notification,
	},

	computed: {
		...mapGetters([
			'isSmsSendingActive',
			'isAnyServiceEnabled',
			'isNotificationsEnabled',
		]),

		getSectionTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_TITLE');
		},

		getSectionHint(): string
		{
			const replacements = {
				'#LINK_START#': `<a onclick="top.BX.Helper.show('redirect=detail&code=17399056')" style="cursor: pointer">`,
				'#LINK_END#': `</a>`,
			};
			return this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_HINT_TEXT', replacements);
		},
		getNotificationConnectHint(): string
		{
			const replacements = {
				'#LINK_START#': `<span onclick="top.BX.Helper.show('redirect=detail&code=17399068')" class="sms-provider-selector">`,
				'#LINK_END#': `</span>`,
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

		onServiceConnectSliderClosed()
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
				});
		},

		onSectionToggled()
		{
			this.setSmsSendingActive(!this.isSmsSendingActive);
		},

		getSmsMessage(): string
		{
			const link = `<span class="sms-link-path">${this.getPaymentSlipLinkScheme()}</span><span class="sms-link-plug">xxxxx</span>` + ` `;
			const text = this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_MESSAGE_TEMPLATE');

			return Text.encode(text).replace(/#PAYMENT_SLIP_LINK#/g, link);
		},

		getSmsProviderMessage(): string
		{
			const providerSelect = `<span>Dummy SMS</span>`;
			const text = this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_SELECT');

			return Text.encode(text).replace(/#SMS_SERVICE#/g, providerSelect);
		},

		onSmsMouseenter(event)
		{
			const target = event.target;
			const message = this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_MESSAGE_HINT');

			if(this.popup)
			{
				this.popup.destroy();
				this.popup = null;
			}

			this.popup = new Popup(null, target, {
				events: {
					onPopupClose: () => {
						this.popup.destroy();
						this.popup = null;
					}
				},
				darkMode: true,
				content: message,
				offsetLeft: target.offsetWidth,
			});

			this.popup.show();
		},

		onSmsMouseleave()
		{
			this.popup.destroy();
		},

		onNotificationsConnectLinkClick()
		{
			/** @var {NotificationLink} */
			const notificationLink = this.getNotificationsLink();
			if (notificationLink === null)
			{
				return;
			}

			if (notificationLink.type === 'connect_link')
			{
				const options = {
					cacheable: false,
					allowChangeHistory: false,
					requestMethod: "get",
					width: 700,
					events: {
						onClose: () => {
							this.onServiceConnectSliderClosed();
						},
					}
				};

				BX.SidePanel.Instance.open(notificationLink.value, options);
			}
			else if (notificationLink.type === 'ui_helper')
			{
				top.BX.UI.InfoHelper.show(notificationLink.value);
			}
		},

		onProviderSmsNotificationClick()
		{
			const options = {
				cacheable: false,
				allowChangeHistory: false,
				requestMethod: "get",
				width: 700,
				events: {
					onClose: () => {
						this.onServiceConnectSliderClosed();
					},
				}
			};

			BX.SidePanel.Instance.open(this.getServiceLink(), options);
		},
	},

	// language=Vue
	template: `
		<SettingsSection 
			:title="getSectionTitle"
			:switchable="true"
			v-on:toggle="onSectionToggled"
			:active="isSmsSendingActive"
			:hint="getSectionHint"
		>
			<div v-if="isAnyServiceEnabled">
				<div class="sms-message-info">
					<span class="sms-message-info-text">{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_INFO') }}</span>
					<Sms>
						<span 
							v-html="getSmsMessage()"
							v-on:mouseenter="onSmsMouseenter($event)"
							v-on:mouseleave="onSmsMouseleave"
						></span>
					</Sms>
					<div class="sms-provider-selector-block">
						<div 
							v-html="getNotificationConnectHint"
							v-if="isNotificationsEnabled"
						></div>
						<div v-else>
							{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_SELECT') }}
							<SmsProviderSelect v-on:onConnectSliderClosed="onServiceConnectSliderClosed"/>
						</div>
					</div>
				</div>
			</div>
			<div v-else>
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
		</SettingsSection>
	`,
};
