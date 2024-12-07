// @flow

import { MessageBox } from 'ui.dialogs.messagebox';
import type { TerminalSettingsProps, NotificationLink, SmsService, TerminalPaysystem } from './types';
import { Type, ajax } from 'main.core';
import { BitrixVue, VueCreateAppResult } from 'ui.vue3';
import { SmsSettings } from './components/sms/sms-settings';
import { PaymentMethodsSettings } from './components/payment-systems/payment-methods-settings';
import { ButtonsPanel } from './components/buttons-panel';
import { RequiredPaysystemCodes } from './const/required-paysystem-codes';
import { createStore, mapGetters, mapMutations, Store } from 'ui.vue3.vuex';
import '../css/app.css';
import 'ui.notification';

type AppProps = {
	rootNodeId: string,
	terminalSettings: TerminalSettingsProps,
};

export class App
{
	#application: VueCreateAppResult;
	rootNode: HTMLElement;
	terminalSettings: TerminalSettingsProps;

	constructor(props: AppProps) {
		this.rootNode = document.getElementById(props.rootNodeId);
		this.terminalSettings = props.terminalSettings;

		this.store = this.#initStore(props.terminalSettings);
	}

	#initStore(terminalSettings: TerminalSettingsProps): Store
	{
		const terminalSettingsStore = {
			state(): Object
			{
				return {
					...terminalSettings,
					isSaving: false,
					changedValues: {},
				};
			},

			getters: {
				isChanged(state): boolean
				{
					return Object.keys(state.changedValues).length > 0;
				},

				isSmsSendingActive(state): boolean
				{
					if (state.changedValues.isSmsSendingEnabled !== undefined)
					{
						return state.changedValues.isSmsSendingEnabled;
					}

					return state.isSmsSendingEnabled;
				},

				isAnyServiceEnabled(state): boolean
				{
					return (state.activeSmsServices.length > 0 || state.isNotificationsEnabled);
				},

				isNotificationsEnabled(state): boolean
				{
					return state.isNotificationsEnabled;
				},

				getPaymentSlipLinkScheme(state): string
				{
					return state.paymentSlipLinkScheme;
				},

				getNotificationsLink(state): ?NotificationLink
				{
					if (Type.isObject(state.connectNotificationsLink))
					{
						return state.connectNotificationsLink;
					}

					return null;
				},

				isNotificationAvailableToConnect(state): string
				{
					return this.getNotificationsLink(state) !== null;
				},

				getServiceLink(state): string
				{
					if (Type.isString(state.connectServiceLink))
					{
						return state.connectServiceLink;
					}

					return '';
				},

				getSelectedService(state): SmsService
				{
					if (Type.isString(state.changedValues.selectedServiceId))
					{
						return state.activeSmsServices.find((element) => element.ID === state.changedValues.selectedServiceId);
					}

					return state.activeSmsServices.find((element) => element.SELECTED);
				},

				getIsAnyPaysystemEnabled(state, getters): boolean
				{
					let hasEnabledPaysystem = false;
					for (const paysystem: Object of getters.getAvailablePaysystems)
					{
						if (!getters.getTerminalDisabledPaysystems.includes(paysystem.id))
						{
							hasEnabledPaysystem = true;
							break;
						}
					}

					return getters.getIsSbpEnabled
						|| getters.getIsSberQrEnabled
						|| getters.getIsLinkPaymentEnabled
						|| hasEnabledPaysystem
					;
				},

				getActiveSmsServices(state): Array<SmsService>
				{
					return state.activeSmsServices;
				},

				getChangedValues(state): Object
				{
					return state.changedValues;
				},

				isSaving(state): boolean
				{
					return state.isSaving;
				},

				getAvailablePaysystems(state): Array<TerminalPaysystem>
				{
					return state.availablePaysystems;
				},

				getTerminalDisabledPaysystems(state): Array<number>
				{
					return state.terminalDisabledPaysystems;
				},

				getIsLinkPaymentEnabled(state): boolean
				{
					return state.isLinkPaymentEnabled;
				},

				getPaysystemPanelPath(state): string
				{
					return state.paysystemPanelPath;
				},

				getIsAnyPaysystemActive(state): boolean
				{
					return state.isAnyPaysystemActive;
				},

				getIsSbpEnabled(state): boolean
				{
					return state.isSbpEnabled;
				},

				getSbpConnectPath(state): boolean
				{
					return state.sbpConnectPath;
				},

				getIsSbpConnected(state): boolean
				{
					return state.isSbpConnected;
				},

				getIsSberQrEnabled(state): boolean
				{
					return state.isSberQrEnabled;
				},

				getSberQrConnectPath(state): boolean
				{
					return state.sberQrConnectPath;
				},

				getIsSberQrConnected(state): boolean
				{
					return state.isSberQrConnected;
				},

				getIsPaysystemsCollapsed(state): boolean
				{
					return state.isPaysystemsCollapsed;
				},

				getPaysystemsArticleUrl(state): boolean
				{
					return state.paysystemsArticleUrl;
				},

				getIsSmsCollapsed(state): boolean
				{
					return state.isSmsCollapsed;
				},

				getIsRuZone(state): boolean
				{
					return state.isRuZone;
				},

				getIsPhoneConfirmed(state): boolean
				{
					return state.isPhoneConfirmed;
				},

				getConnectedSiteId(state)
				{
					return state.connectedSiteId;
				},

				getIsConnectedSitePublished(state): boolean
				{
					return state.isConnectedSitePublished;
				},

				getIsConnectedSiteExists(state): boolean
				{
					return state.isConnectedSiteExists;
				},
			},

			mutations: {
				setSmsSendingActive(state, value): void
				{
					if (state.changedValues.isSmsSendingEnabled !== undefined && value === state.isSmsSendingEnabled)
					{
						delete state.changedValues.isSmsSendingEnabled;
					}
					else
					{
						state.isChanged = true;
						state.changedValues.isSmsSendingEnabled = value;
					}
				},

				selectSMSService(state, value): void
				{
					if (state.activeSmsServices.find((element) => element.SELECTED).ID === value)
					{
						delete state.changedValues.selectedServiceId;
					}
					else
					{
						state.changedValues.selectedServiceId = value;
					}
				},

				setTerminalPaysystemDisabled(state, paysystemId): void
				{
					if (!Array.isArray(state.changedValues.terminalDisabledPaysystems))
					{
						state.changedValues.terminalDisabledPaysystems = state.terminalDisabledPaysystems;
					}

					if (state.changedValues.terminalDisabledPaysystems.includes(paysystemId))
					{
						const currentDisabledPs = state.changedValues.terminalDisabledPaysystems ?? [];
						state.changedValues.terminalDisabledPaysystems = currentDisabledPs.filter((ps) => ps !== paysystemId);
						state.terminalDisabledPaysystems = state.changedValues.terminalDisabledPaysystems;
						if (state.changedValues.terminalDisabledPaysystems.length === 0)
						{
							state.changedValues.terminalPaysystemsAllEnabled = true;
							state.terminalPaysystemsAllEnabled = true;
						}
						state.isChanged = true;
					}
					else
					{
						state.isChanged = true;
						if (state.changedValues.terminalDisabledPaysystems)
						{
							state.changedValues.terminalDisabledPaysystems.push(paysystemId);
							state.terminalDisabledPaysystems.push(paysystemId);
						}
						else
						{
							state.changedValues.terminalDisabledPaysystems = [paysystemId];
							state.terminalDisabledPaysystems = [paysystemId];
						}

						delete state.changedValues.terminalPaysystemsAllEnabled;
						delete state.terminalPaysystemsAllEnabled;
					}
				},

				setRequiredPaysystemDisabled(state, paysystemCode): void
				{
					let paysystemKey;
					switch (paysystemCode)
					{
						case RequiredPaysystemCodes.sbp:
							paysystemKey = 'isSbpEnabled';
							break;
						case RequiredPaysystemCodes.sberQr:
							paysystemKey = 'isSberQrEnabled';
							break;
						default:
							paysystemKey = null;
					}

					if (!paysystemKey)
					{
						return;
					}

					state[paysystemKey] = !state[paysystemKey];
					state.changedValues[paysystemKey] = state[paysystemKey];
				},

				setLinkPaymentEnabled(state, value): void
				{
					if (state.changedValues.isLinkPaymentEnabled !== undefined && value === state.isLinkPaymentEnabled)
					{
						delete state.changedValues.isLinkPaymentEnabled;
					}
					else
					{
						state.isChanged = true;
						state.changedValues.isLinkPaymentEnabled = value;
						state.isLinkPaymentEnabled = value;
					}
				},

				setSaving(state, value): void
				{
					if (Type.isBoolean(value))
					{
						state.isSaving = value;
					}
				},

				updateServicesList(state, value): void
				{
					state.activeSmsServices = value;
				},

				updateNotificationsEnabled(state, value): void
				{
					state.isNotificationsEnabled = value;
				},

				updateSbpConnectPath(state, value): void
				{
					state.sbpConnectPath = value;
				},

				updateSberQrConnectPath(state, value): void
				{
					state.sberQrConnectPath = value;
				},

				updateIsSbpConnected(state, value): void
				{
					state.isSbpConnected = value;
				},

				updateIsSberQrConnected(state, value): void
				{
					state.isSberQrConnected = value;
				},

				updateIsPaysystemsCollapsed(state, value): void
				{
					state.isPaysystemsCollapsed = value;
				},

				updateIsSmsCollapsed(state, value): void
				{
					state.isSmsCollapsed = value;
				},

				updateIsAnyPaysystemActive(state, value): void
				{
					state.isAnyPaysystemActive = value;
				},

				updateAvailablePaysystems(state, value): void
				{
					state.availablePaysystems = value;
				},

				updateIsPhoneConfirmed(state, value): void
				{
					state.isPhoneConfirmed = value;
				},

				updateConnectedSiteId(state, value): void
				{
					state.connectedSiteId = value;
				},

				updateIsConnectedSitePublished(state, value): void
				{
					state.isConnectedSitePublished = value;
				},

				updateIsConnectedSiteExists(state, value): void
				{
					state.isConnectedSiteExists = value;
				},
			},
		};

		return createStore(terminalSettingsStore);
	}

	attachTemplate(): void
	{
		this.#application = BitrixVue.createApp({
			components: {
				SmsSettings,
				PaymentMethodsSettings,
				ButtonsPanel,
			},

			computed: {
				...mapGetters([
					'isChanged',
					'isSaving',
					'getChangedValues',
					'getIsPaysystemsCollapsed',
					'getIsSbpEnabled',
					'getIsSberQrEnabled',
					'getIsLinkPaymentEnabled',
					'getAvailablePaysystems',
					'getTerminalDisabledPaysystems',
					'getIsAnyPaysystemEnabled',
				]),
			},

			mounted()
			{
				this.$Bitrix.eventEmitter.subscribe('crm:terminal:onSettingsSave', this.onSettingsSave);
				this.$Bitrix.eventEmitter.subscribe('crm:terminal:onSettingsCancel', this.onSettingsCancel);
			},

			beforeUnmount()
			{
				this.$Bitrix.eventEmitter.unsubscribe('crm:terminal:onSettingsSave');
				this.$Bitrix.eventEmitter.unsubscribe('crm:terminal:onSettingsCancel');
			},

			methods: {
				...mapMutations([
					'setSaving',
				]),

				validateChangedValues(): Object
				{
					const values = this.getChangedValues;
					if (values && Object.keys(values).length > 0)
					{
						return values;
					}

					return {};
				},

				onSettingsSave()
				{
					if (this.getIsAnyPaysystemEnabled)
					{
						this.save();
					}
					else
					{
						MessageBox.confirm(
							this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SAVING_CONFIRM_TITLE'),
							(messageBox) => messageBox.close(),
							this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SAVING_CONFIRM_OK'),
							(messageBox) => {
								messageBox.close();
								this.save();
							},
							this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SAVING_CONFIRM_SAVE_AND_CLOSE'),
						);
					}
				},

				save()
				{
					if (!this.isChanged || this.isSaving)
					{
						return;
					}
					this.setSaving(true);
					ajax
						.runComponentAction('bitrix:crm.config.terminal.settings', 'saveSettings', {
							data: {
								changedValues: this.validateChangedValues(),
							},
						}).then(
							() => {
								this.setSaving(false);
								BX.SidePanel.Instance.close();
								top.BX.UI.Notification.Center.notify({
									content: this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_ON_SAVE_SUCCESS'),
								});
							},
							() => {
								this.setSaving(false);
								BX.UI.Notification.Center.notify({
									content: this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_ON_SAVE_ERROR'),
									width: 350,
									autoHideDelay: 4000,
								});
							},
						);
				},

				onSettingsCancel()
				{
					BX.SidePanel.Instance.close();
				},
			},

			// language=Vue
			template: `
				<div style="position: relative; overflow: hidden;">
					<div class="ui-side-panel-wrap-workarea payment-methods-settings-wrapper">
						<PaymentMethodsSettings/>
					</div>
					<div
						class="terminal-image"
						v-bind:class="{ 'terminal-image-collapsed': this.getIsPaysystemsCollapsed }"
					>
						<div class="terminal-image-title">{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_IMAGE_TITLE') }}</div>
						<div class="terminal-image-paysystems-container">
							<div v-if="getIsSbpEnabled" class="terminal-image-paysystem terminal-image-paysystem-sbp">
								<span>{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SBP') }}</span>
							</div>
							<div v-if="getIsSberQrEnabled" class="terminal-image-paysystem terminal-image-paysystem-sber-qr">
								<span>{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SBER_QR_MSGVER_1') }}</span>
							</div>
							<template v-if="getAvailablePaysystems.length > 0" v-for="paysystem in getAvailablePaysystems">
								<div class="terminal-image-paysystem terminal-image-paysystem-wallet" v-if="getTerminalDisabledPaysystems.indexOf(paysystem.id) === -1">
									<span>{{ paysystem.title }}</span>
								</div>
							</template>
							<div v-if="getIsLinkPaymentEnabled" class="terminal-image-paysystem terminal-image-paysystem-link">
								<span>{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_LINK_PAYMENT') }}</span>
							</div>
							<div class="terminal-image-no-paysystems-stub" v-if="!getIsAnyPaysystemEnabled">
								{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_NO_PAY_METHODS') }}
							</div>
						</div>
					</div>
				</div>

				<div class="ui-side-panel-wrap-workarea" style="margin-bottom: 70px;">
					<SmsSettings/>
				</div>

				<ButtonsPanel/>
			`,
		});

		this.#application.use(this.store);
		this.#application.mount(this.rootNode);
	}
}
