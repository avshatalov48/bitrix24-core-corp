//@flow

import type {TerminalSettingsProps, NotificationLink, SmsService} from "./types";
import {Type, ajax} from "main.core";
import {BitrixVue, createApp, VueCreateAppResult} from "ui.vue3";
import {TerminalSettings} from "./components/terminal-settings";
import '../css/app.css';
import {createStore, mapGetters, mapMutations, Store} from "ui.vue3.vuex";
import "ui.notification";

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
					return Object.keys(state.changedValues).length !== 0;
				},

				isSmsSendingActive(state): boolean
				{
					if (state.changedValues.isSmsSendingEnabled !== undefined)
					{
						return state.changedValues.isSmsSendingEnabled;
					}
					else
					{
						return state.isSmsSendingEnabled;
					}
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
						return state.activeSmsServices.find(element => element['ID'] === state.changedValues.selectedServiceId);
					}

					return state.activeSmsServices.find(element => element['SELECTED']);
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
			},

			mutations: {
				setSmsSendingActive(state, value)
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

				selectSMSService(state, value)
				{
					if (state.activeSmsServices.find(element => element['SELECTED'])['ID'] === value)
					{
						delete state.changedValues.selectedServiceId;
					}
					else
					{
						state.changedValues.selectedServiceId = value;
					}
				},

				setSaving(state, value)
				{
					if (Type.isBoolean(value))
					{
						state.isSaving = value;
					}
				},

				updateServicesList(state, value)
				{
					state.activeSmsServices = value;
				},

				updateNotificationsEnabled(state, value)
				{
					state.isNotificationsEnabled = value;
				},
			},
		};

		return createStore(terminalSettingsStore);
	}

	attachTemplate(): void
	{
		this.#application = BitrixVue.createApp({
			components: {
				'TerminalSettings': TerminalSettings,
			},

			computed: {
				...mapGetters([
					'isChanged',
					'isSaving',
					'getChangedValues',
				]),
			},

			methods: {
				...mapMutations([
					'setSaving',
				]),

				onSettingsSave() {
					if (!this.isChanged || this.isSaving)
					{
						return;
					}
					this.setSaving(true);

					ajax
						.runComponentAction('bitrix:crm.config.terminal.settings', 'saveSettings', {
							data: {
								changedValues: this.getChangedValues,
							}
						}).then(
							() => {
								this.setSaving(false);
								BX.SidePanel.Instance.close();
							},
							() => {
								this.setSaving(false);
								BX.UI.Notification.Center.notify({
									content: this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_ON_SAVE_ERROR'),
									width: 350,
									autoHideDelay: 4000,
								});
							}
						);
				},

				onSettingsCancel() {
					BX.SidePanel.Instance.close();
				},
			},

			//language=Vue
			template: `
				<TerminalSettings @onSave="onSettingsSave" @onCancel="onSettingsCancel"/>
			`,
		});

		this.#application.use(this.store);
		this.#application.mount(this.rootNode);
	}
}