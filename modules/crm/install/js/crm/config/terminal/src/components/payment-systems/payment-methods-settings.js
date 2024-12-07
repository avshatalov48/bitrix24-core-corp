import { rest as Rest } from 'rest.client';
import { mapGetters, mapMutations } from 'ui.vue3.vuex';
import { SettingsSection } from '../settings-section';
import { PhoneVerify } from 'bitrix24.phoneverify';
import { MessageBox } from 'ui.dialogs.messagebox';
import { SettingsContainer } from '../settings-container';
import { RequiredPaysystemCodes } from '../../const/required-paysystem-codes';
import { Label, LabelColor, LabelSize } from 'ui.label';
import { ajax, Type, Uri } from 'main.core';
import { Backend } from 'landing.backend';
import { PageObject } from 'landing.pageobject';

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
			getIsPhoneConfirmed: 'getIsPhoneConfirmed',
			getConnectedSiteId: 'getConnectedSiteId',
			getIsConnectedSitePublished: 'getIsConnectedSitePublished',
			getIsConnectedSiteExists: 'getIsConnectedSiteExists',
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
			'updateIsPhoneConfirmed',
			'updateConnectedSiteId',
			'updateIsConnectedSitePublished',
			'updateIsConnectedSiteExists',
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

		onSiteSliderClosed()
		{
			this.loader.show(document.body);

			ajax.runComponentAction('bitrix:crm.config.terminal.settings', 'updateConnectedSiteParams')
				.then((response) => {
					this.loader.hide();

					this.updateIsConnectedSiteExists(response.data.isConnectedSiteExists);
					this.updateConnectedSiteId(response.data.connectedSiteId);
					this.updateIsPhoneConfirmed(response.data.isPhoneConfirmed);
					this.updateIsConnectedSitePublished(response.data.isConnectedSitePublished);

					this.connectSite();
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

		connectSite()
		{
			if (!this.loader)
			{
				this.loader = new BX.Loader({ size: 200 });
			}

			if (!this.getIsConnectedSiteExists)
			{
				this.createSite();

				return;
			}

			if (!this.getIsConnectedSitePublished)
			{
				this.publishSite();

				return;
			}

			if (!this.getIsPhoneConfirmed)
			{
				this.showPhoneConfirmationPopup();
			}
		},

		createSite(): void
		{
			this.loader.show(document.body);

			Rest.callMethod('salescenter.manager.getConfig').then((result) => {
				const {
					connectedSiteId,
					isSiteExists,
					isPhoneConfirmed,
					siteTemplateCode,
				} = result.answer.result;

				this.loader.hide();

				if (isSiteExists && connectedSiteId > 0)
				{
					this.updateIsConnectedSiteExists(isSiteExists);
					this.updateConnectedSiteId(connectedSiteId);
					this.updateIsPhoneConfirmed(isPhoneConfirmed);

					if (isPhoneConfirmed)
					{
						this.publishSite();

						return;
					}

					this.showPhoneConfirmationPopup();
				}
				else
				{
					const url = new Uri('/shop/stores/site/edit/0/');
					const params = {
						context: 'terminal',
						tpl: siteTemplateCode,
						no_redirect: 'Y',
					};

					url.setQueryParams(params);

					const options = {
						events: {
							onClose: () => {
								this.onSiteSliderClosed();
							},
						},
					};

					BX.SidePanel.Instance.open(url.toString(), options);
				}
			}).catch(() => this.loader.hide());
		},

		publishSite(): void
		{
			this.loader.show(document.body);

			Backend.getInstance().action('Site::publication', {
				id: this.getConnectedSiteId,
			})
				.then((publishedSiteId) => {
					this.loader.hide();

					if (publishedSiteId)
					{
						this.updateIsConnectedSitePublished(true);
					}
				})
				.catch((data) => {
					this.loader.hide();

					if (data.type === 'error' && !Type.isUndefined(data.result[0]))
					{
						const errorCode = data.result[0].error;

						if (errorCode === 'PHONE_NOT_CONFIRMED')
						{
							this.showPhoneConfirmationPopup();
						}
						else if (errorCode === 'EMAIL_NOT_CONFIRMED')
						{
							BX.UI.InfoHelper.show('limit_sites_confirm_email');
						}
						else
						{
							MessageBox.alert(
								data.result[0].error_description,
							);
						}
					}
				});
		},

		confirmPhoneNumber()
		{
			this.loader.show(document.body);

			PhoneVerify
				.getInstance()
				.setEntityType('landing_site')
				.setEntityId(this.getConnectedSiteId)
				.startVerify({
					mandatory: false,
					callback: (verified) => {
						this.loader.hide();

						if (!verified)
						{
							return;
						}

						this.updateIsPhoneConfirmed(verified);

						if (!this.getIsConnectedSitePublished)
						{
							this.publishSite();
						}
					},
				})
			;
		},

		showPhoneConfirmationPopup()
		{
			MessageBox.confirm(
				this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_PHONE_CONFIRMATION_POPUP_MESSAGE'),
				this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_PHONE_CONFIRMATION_POPUP_TITLE'),
				(messageBox) => {
					messageBox.close();
					this.confirmPhoneNumber();
				},
				this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_PHONE_CONFIRMATION_POPUP_OK_CAPTION'),
				(messageBox) => messageBox.close(),
				this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_PHONE_CONFIRMATION_POPUP_CANCEL_CAPTION'),
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
					'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SUBTITLE_MSGVER_1',
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
							:title="$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SBER_QR_MSGVER_1')"
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
							<span v-html="getStatusLabel(this.getIsAnyPaysystemActive && this.getIsConnectedSitePublished && getIsPhoneConfirmed)"></span>
							<span
								class="payment-system-set payment-system-set-connected"
								v-on:click="openPaysystemSlider(getRequiredPaysystemCodes.paysystemPanel)"
								v-if="getIsConnectedSitePublished && getIsPhoneConfirmed"
							>
								{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_PAYMENT_METHOD') }}
							</span>
							<span
								class="payment-system-set payment-system-set-not-connected"
								v-on:click="connectSite()"
								v-else
							>
								{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_CONNECT') }}
							</span>
						</div>
					</div>
				</div>
				<div class="terminal-image-wrapper"></div>
			</div>

		</SettingsContainer>
	`,
};
