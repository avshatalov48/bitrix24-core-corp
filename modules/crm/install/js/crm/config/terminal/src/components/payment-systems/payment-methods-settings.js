import { mapGetters, mapMutations } from 'ui.vue3.vuex';
import { SettingsSection } from '../settings-section';
import { SettingsContainer } from '../settings-container';
import { RequiredPaysystemCodes } from '../../const/required-paysystem-codes';
import { Label, LabelColor, LabelSize } from 'ui.label';
import { ajax } from 'main.core';

export const PaymentMethodsSettings = {

	components: {
		SettingsSection,
		SettingsContainer,
	},

	computed: {
		...mapGetters({
			getAvailablePaysystems: 'getAvailablePaysystems',
			getTerminalDisabledPaysystems: 'getTerminalDisabledPaysystems',
			getIsLinkPaymentEnabled: 'getIsLinkPaymentEnabled',
			getIsSbpEnabled: 'getIsSbpEnabled',
			getIsSberQrEnabled: 'getIsSberQrEnabled',
			getIsSbpConnected: 'getIsSbpConnected',
			getIsSberQrConnected: 'getIsSberQrConnected',
			getIsRuZone: 'getIsRuZone',
			getSbpConnectPath: 'getSbpConnectPath',
			getSberQrConnectPath: 'getSberQrConnectPath',
			getIsAnyPaysystemActive: 'getIsAnyPaysystemActive',
			getPaysystemPanelPath: 'getPaysystemPanelPath',
			getIsPaysystemsCollapsed: 'getIsPaysystemsCollapsed',
			getPaysystemsArticleUrl: 'getPaysystemsArticleUrl',
		}),

		getRequiredPaysystemCodes(): void
		{
			return RequiredPaysystemCodes;
		},

		getLinkPaymentHint(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_LINK_PAYMENT_HINT');
		},
	},

	methods: {
		...mapMutations([
			'setTerminalPaysystemDisabled',
			'setLinkPaymentEnabled',
			'setRequiredPaysystemDisabled',
			'updateSbpConnectPath',
			'updateSberQrConnectPath',
			'updateIsSbpConnected',
			'updateIsSberQrConnected',
			'updateIsPaysystemsCollapsed',
			'updateIsAnyPaysystemActive',
			'updateAvailablePaysystems',
		]),

		onPaysystemToggled(paysystemId: number)
		{
			this.setTerminalPaysystemDisabled(paysystemId);
		},

		onRequiredPaysystemToggled(paysystemCode: string)
		{
			this.setRequiredPaysystemDisabled(paysystemCode);
		},

		onLinkPaymentToggled()
		{
			this.setLinkPaymentEnabled(!this.getIsLinkPaymentEnabled);
		},
		openPaysystemSlider(psMode: string, link: string = '')
		{
			const options = {
				cacheable: false,
				allowChangeHistory: false,
				requestMethod: 'get',
				width: psMode === RequiredPaysystemCodes.paysystemPanel ? null : 1000,
				events: {
					onClose: () => {
						this.onPaysystemSliderClosed();
					},
				},
			};
			const url = psMode === RequiredPaysystemCodes.rest ? link : this.getPaysystemUrl(psMode);
			BX.SidePanel.Instance.open(url, options);
		},
		getPaysystemUrl(psMode: string): string
		{
			switch (psMode)
			{
				case RequiredPaysystemCodes.sbp:
					return this.getSbpConnectPath;
				case RequiredPaysystemCodes.sberQr:
					return this.getSberQrConnectPath;
				case RequiredPaysystemCodes.paysystemPanel:
					return this.getPaysystemPanelPath;
				default:
					return '';
			}
		},
		onPaysystemSliderClosed()
		{
			ajax.runComponentAction('bitrix:crm.config.terminal.settings', 'updatePaysystemPaths')
				.then((response) => {
					this.updateSbpConnectPath(response.data.sbp);
					this.updateSberQrConnectPath(response.data.sberbankQr);
					this.updateIsSbpConnected(response.data.isSbpConnected);
					this.updateIsSberQrConnected(response.data.isSberQrConnected);
					this.updateIsAnyPaysystemActive(response.data.isAnyPaysystemActive);
					this.updateAvailablePaysystems(response.data.availablePaysystems);
				})
				.catch(() => {});
		},

		getStatusLabel(connected = false)
		{
			const text = this.$Bitrix.Loc.getMessage(
				connected
					? 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_CONNECTED'
					: 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_NOT_CONNECTED'
			).toUpperCase();
			const label = new Label({
				text,
				color: connected ? LabelColor.LIGHT_GREEN : LabelColor.LIGHT,
				size: LabelSize.LG,
				fill: true,
			});

			return label.render().outerHTML;
		},

		onTitleClick()
		{
			this.updateIsPaysystemsCollapsed(!this.getIsPaysystemsCollapsed);
			ajax.runComponentAction(
				'bitrix:crm.config.terminal.settings',
				'updatePaysystemsCollapsed',
				{
					data: {
						collapsed: this.getIsPaysystemsCollapsed,
					},
				},
			);
		},
	},
	// language=Vue
	template: `
		<SettingsContainer
			:title="$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_TITLE_MSGVER_1')"
			iconStyle="settings-section-icon-payment-methods"
			:collapsed="getIsPaysystemsCollapsed"
			v-on:titleClick="onTitleClick"
			v-bind:style="{ 'padding-bottom: 0px;' : !getIsPaysystemsCollapsed }"
		>

			<div
				class="payment-systems-subtitle"
				v-html="$Bitrix.Loc.getMessage(
					'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SUBTITLE',
					{'#MORE_INFO_LINK#': getPaysystemsArticleUrl})"
			></div>

			<div class="payment-systems-section-container">
				<div class="payment-systems-container">
					<div v-if="getIsRuZone" class="payment-system-wrapper">
						<SettingsSection
							:title="$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SBP')"
							:switchable="true"
							:active="getIsSbpEnabled"
							leftIconClass="payment-method-icon-sbp"
							v-on:toggle="onRequiredPaysystemToggled(getRequiredPaysystemCodes.sbp)"
							v-on:titleClick="openPaysystemSlider(getRequiredPaysystemCodes.sbp)"
						/>
						<div class="payment-system-status-container">
							<span v-html="getStatusLabel(this.getIsSbpConnected)"></span>
							<span
								class="payment-system-set"
								:class="getIsSbpConnected ? 'payment-system-set-connected' : 'payment-system-set-not-connected'"
								v-on:click="openPaysystemSlider(getRequiredPaysystemCodes.sbp)"
							>
								{{ $Bitrix.Loc.getMessage(this.getIsSbpConnected
								? 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SET'
								: 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_CONNECT') }}
							</span>
						</div>
					</div>

					<div v-if="getIsRuZone" class="payment-system-wrapper">
						<SettingsSection
							:title="$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SBER_QR')"
							:switchable="true"
							:active="getIsSberQrEnabled"
							leftIconClass="payment-method-icon-sber"
							v-on:toggle="onRequiredPaysystemToggled(getRequiredPaysystemCodes.sberQr)"
							v-on:titleClick="openPaysystemSlider(getRequiredPaysystemCodes.sberQr)"
						/>
						<div class="payment-system-status-container">
							<span v-html="getStatusLabel(this.getIsSberQrConnected)"></span>
							<span
								class="payment-system-set"
								:class="getIsSberQrConnected ? 'payment-system-set-connected' : 'payment-system-set-not-connected'"
								v-on:click="openPaysystemSlider(getRequiredPaysystemCodes.sberQr)"
							>
								{{ $Bitrix.Loc.getMessage(this.getIsSberQrConnected
								? 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SET'
								: 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_CONNECT') }}
							</span>
						</div>
					</div>

					<div
						v-if="getAvailablePaysystems.length > 0"
						v-for="paysystem in getAvailablePaysystems"
						class="payment-system-wrapper"
					>
						<SettingsSection
							:key="paysystem.type"
							:title="paysystem.title"
							:switchable="paysystem.id > 0"
							:active="paysystem.id > 0 && !getTerminalDisabledPaysystems.includes(paysystem.id)"
							v-on:toggle="onPaysystemToggled(paysystem.id)"
						/>
						<div class="payment-system-status-container">
							<span v-html="getStatusLabel(paysystem.isConnected)"></span>
							<span
								class="payment-system-set"
								:class="paysystem.isConnected ? 'payment-system-set-connected' : 'payment-system-set-not-connected'"
								v-on:click="openPaysystemSlider(getRequiredPaysystemCodes.rest, paysystem.path)"
							>
								{{ $Bitrix.Loc.getMessage(paysystem.isConnected
								? 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SET'
								: 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_CONNECT') }}
							</span>
						</div>
					</div>

					<div class="payment-system-wrapper">
						<SettingsSection
							:title="$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_LINK_PAYMENT')"
							:switchable="true"
							:active="getIsLinkPaymentEnabled"
							v-on:toggle="onLinkPaymentToggled()"
							:hint="getLinkPaymentHint"
						/>
						<div class="payment-system-status-container">
							<span v-html="getStatusLabel(this.getIsAnyPaysystemActive)"></span>
							<span
								class="payment-system-set payment-system-set-connected"
								v-on:click="openPaysystemSlider(getRequiredPaysystemCodes.paysystemPanel)"
							>
								{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_PAYMENT_METHOD') }}
							</span>
						</div>
					</div>
				</div>
				<div class="terminal-image-wrapper"></div>
			</div>

		</SettingsContainer>
	`,
};
