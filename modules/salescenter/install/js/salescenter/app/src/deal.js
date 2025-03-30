import { EventEmitter } from 'main.core.events';
import { Vuex } from 'ui.vue.vuex';
import StageBlocksList from './components/deal-receiving-payment/stage-blocks-list';
import EntityCreatePaymentStages from './components/crm-entity-create-payment/stage-blocks-list';
import StageBlocksListShipment from './components/deal-creating-shipment/stage-blocks-list';
import TerminalStageBlocksList from './components/deal-terminal-payment/stage-blocks-list';
import ComponentMixin from './component-mixin';
import { Loc, Runtime, userOptions as UserOptions } from 'main.core';
import { MixinTemplatesType } from './components/templates-type-mixin';
import Start from './start';
import { ModeDictionary } from './const/mode-dictionary';

import { SenderConfig } from 'salescenter.lib';

export default {
	mixins: [MixinTemplatesType, ComponentMixin],
	data()
	{
		let isPanelVisible = true;
		if (this.$root.$app.options.mode === ModeDictionary.terminalPayment && this.$root.$app.options.templateMode === 'view' && this.$root.$app.options.payment?.PAID === 'N')
		{
			isPanelVisible = false;
		}

		return {
			activeMenuItem: this.$root.$app.options.mode,
			isLoading: false,
			isPanelVisible,
			ModeDictionary,
		};
	},
	components: {
		'deal-receiving-payment': StageBlocksList,
		'crm-entity-create-payment': EntityCreatePaymentStages,
		'deal-creating-shipment': StageBlocksListShipment,
		'deal-terminal-payment': TerminalStageBlocksList,
		'start': Start,
	},
	methods: {
		reload(form)
		{
			if (this.isLoading || !this.editable)
			{
				return;
			}

			this.isLoading = true;
			this.activeMenuItem = form;
			this.$emit(
				'on-reload',
				{
					context: this.$root.$app.options.context,
					orderId: this.$root.$app.orderId,
					ownerTypeId: this.$root.$app.options.ownerTypeId,
					ownerId: this.$root.$app.options.ownerId,
					templateMode: 'create',
					mode: this.activeMenuItem,
					showModeList: this.$root.$app.options.showModeList,
					initialMode: this.$root.$app.options.initialMode,
				}
			);
		},
		onSuccessfullyConnected()
		{
			this.reload(this.activeMenuItem);
		},
		onReload()
		{
			this.reload(this.activeMenuItem);
		},
		sendPaymentDeliveryForm(event)
		{
			if (!this.isAllowedPaymentDeliverySubmitButton)
			{
				return;
			}

			if (!this.$root.$app.isPhoneConfirmed)
			{
				EventEmitter.subscribeOnce('BX.Salescenter.App::onPhoneConfirmed', () => this.sendPaymentDeliveryForm(event));
				this.$root.$app.showPhoneConfirmPopup();

				return;
			}

			if (this.$store.getters['orderCreation/isCompilationMode'])
			{
				this.$root.$app.sendCompilation(event.target);
			}
			else if (this.editable)
			{
				this.$root.$app.sendPayment(event.target);
			}
			else
			{
				this.$root.$app.resendPayment(event.target);
			}
		},
		sendDeliveryForm(event)
		{
			if (!this.isAllowedDeliverySubmitButton)
			{
				return;
			}

			this.$root.$app.sendShipment(event.target);
		},
		sendTerminalPaymentForm(event)
		{
			if (!this.isAllowedTerminalPaymentSubmitButton)
			{
				return;
			}

			if (this.templateMode === 'view')
			{
				this.$root.$app.updateTerminalPayment(event.target);

				return;
			}

			this.$root.$app.sendTerminalPayment(event.target);
		},
		// region menu item handlers
		specifyCompanyContacts()
		{
			BX.Salescenter.Manager.openSlider(this.$root.$app.options.urlSettingsCompanyContacts, {width: 1200} );
		},
		suggestScenario(event)
		{
			BX.Salescenter.Manager.openFeedbackPayOrderForm(event);
		},
		howItWorks(event)
		{
			if (this.mode === ModeDictionary.payment)
			{
				BX.Salescenter.Manager.openHowPaySmartInvoiceWorks(event);

				return;
			}

			if (this.mode === ModeDictionary.terminalPayment)
			{
				BX.Salescenter.Manager.openHowTerminalWorks(event);

				return;
			}

			BX.Salescenter.Manager.openHowPayDealWorks(event);
		},
		openIntegrationWindow(event)
		{
			BX.Salescenter.Manager.openIntegrationRequestForm(event);
		},
		freeMessages()
		{
			let senders = this.$root.$app.options.senders

			let sender = senders.filter(item => item.code === SenderConfig.BITRIX24)

			if(sender.length > 0)
			{
				let fixed = SenderConfig.openSliderFreeMessages(sender[0].connectUrl);
				fixed().then()
			}
		},
		onResponsibleChanged(event)
		{
			// it needs to be done like that and not with vue's @class attribute because is breaks BX.UI.Pinner's classes
			const buttonsPanel = this.$refs['buttonsPanel'];
			buttonsPanel.classList.remove('salescenter-button-panel-hidden');
			const pinnerAnchor = document.querySelector('.salescenter-app-pinner-anchor');
			pinnerAnchor.classList.remove('salescenter-app-pinner-anchor-hidden');
		},
		openTerminalToolDisabledSlider()
		{
			Runtime.loadExtension('ui.info-helper').then(() => {
				top.BX.UI.InfoHelper.show('limit_crm_terminal_off');
			});
		},
		openSalescanterToolDisabledSlider()
		{
			BX.loadExt('salescenter.tool-availability-manager').then(() => {
				BX.Salescenter.ToolAvailabilityManager.openSalescenterToolDisabledSlider();
			});
		},
		// endregion
	},
	computed: {
		mode()
		{
			return this.$root.$app.options.mode;
		},
		templateMode()
		{
			return this.$root.$app.options.templateMode;
		},
		initialMode()
		{
			return this.$root.$app.options.initialMode;
		},
		isAllowedFreeMessagesButton()
		{
			let senders = this.$root.$app.options.senders

			let sender = senders.filter(item => item.code === SenderConfig.BITRIX24)

			if(sender.length > 0)
			{
				return SenderConfig.needConfigure(sender[0])
			}
			return false;
		},
		isOnlyDeliveryItemVisible()
		{
			return (
				this.initialMode === ModeDictionary.delivery
				&& this.$root.$app.options.hasOwnProperty('deliveryList')
				&& this.$root.$app.options.deliveryList.hasOwnProperty('hasInstallable')
				&& this.$root.$app.options.deliveryList.hasInstallable
			);
		},
		isTerminalItemVisible()
		{
			return this.isPaymentItemVisible && this.$root.$app.options.isTerminalAvailable;
		},
		isTerminalToolEnabled()
		{
			return this.$root.$app.options.isTerminalToolEnabled;
		},
		isSalescenterToolEnabled()
		{
			return this.$root.$app.options.isSalescenterToolEnabled;
		},
		isPaymentItemVisible()
		{
			return (
				this.initialMode === ModeDictionary.payment
				|| this.initialMode === ModeDictionary.paymentDelivery
				|| this.initialMode === ModeDictionary.terminalPayment
			);
		},
		isAllowedPaymentDeliverySubmitButton()
		{
			if (!this.$root.$app.hasClientContactInfo())
			{
				return false;
			}

			const senders = this.$root.$app.options.senders;
			const filteredSenders = senders.filter(sender => (
				sender.code === this.$root.$app.options.currentSenderCode && sender.isConnected
			));
			if (filteredSenders.length === 0)
			{
				this.$store.commit('orderCreation/setIsSenderSelected', false);
			}

			return this.$store.getters['orderCreation/isAllowedSubmit'];
		},
		isAllowedTerminalPaymentSubmitButton()
		{
			return (
				this.$store.getters['orderCreation/isAllowedSubmit']
				|| this.isTerminalViewModeSaveAllowed
			);
		},
		isTerminalViewModeSaveAllowed()
		{
			return this.templateMode === 'view' && this.$root.$app.options.payment?.PAID === 'N';
		},
		isAllowedDeliverySubmitButton()
		{
			const deliveryId = this.$store.getters['orderCreation/getDeliveryId'];
			if (!deliveryId)
			{
				return false;
			}

			if (!this.$store.getters['orderCreation/isAllowedSubmit'])
			{
				return false;
			}

			return (deliveryId != this.$root.$app.options.emptyDeliveryServiceId);
		},
		isSuggestScenarioMenuItemVisible()
		{
			return this.$root.$app.options.isBitrix24;
		},
		isRequestIntegrationMenuItemVisible()
		{
			return this.$root.$app.options.isIntegrationButtonVisible;
		},
		isModeListVisible()
		{
			return this.$root.$app.options.showModeList ?? true;
		},
		needShowStoreConnection()
		{
			return !this.isOrderPublicUrlAvailable && this.mode !== ModeDictionary.delivery;
		},
		isSidebarEnabled()
		{
			return true; // not removing this just yet because who knows...
		},
		sendPaymentDeliveryFormButtonText()
		{
			return this.editable ? Loc.getMessage('SALESCENTER_SEND') : Loc.getMessage('SALESCENTER_RESEND');
		},
		title()
		{
			return this.$root.$app.options.title;
		},
		// classes region
		paymentDeliveryFormSubmitButtonClass()
		{
			return {'ui-btn-disabled': !this.isAllowedPaymentDeliverySubmitButton};
		},
		deliveryFormSubmitButtonClass()
		{
			return {'ui-btn-disabled': !this.isAllowedDeliverySubmitButton};
		},
		terminalPaymentFormSubmitButtonClass()
		{
			return {'ui-btn-disabled': !this.isAllowedTerminalPaymentSubmitButton};
		},
		paymentMenuItemClass()
		{
			return {
				'salescenter-app-sidebar-menu-active': this.activeMenuItem === ModeDictionary.payment,
			};
		},
		paymentTerminalMenuItemClass()
		{
			return {
				'salescenter-app-sidebar-menu-active': this.activeMenuItem === ModeDictionary.terminalPayment,
			};
		},
		paymentDeliveryMenuItemClass()
		{
			return {
				'salescenter-app-sidebar-menu-active': this.activeMenuItem === ModeDictionary.paymentDelivery,
			};
		},
		deliveryMenuItemClass()
		{
			return {
				'salescenter-app-sidebar-menu-active': this.activeMenuItem === ModeDictionary.delivery,
			};
		},
		// endregion
		...Vuex.mapState({
			application: state => state.application,
			order: state => state.orderCreation,
		})
	},
	//language=Vue
	template: `
		<div>
			<div
				:class="wrapperClass"
				:style="wrapperStyle"
				class="salescenter-app-wrapper"
			>
				<div class="ui-sidepanel-sidebar salescenter-app-sidebar" v-if="isSidebarEnabled">
					<ul class="ui-sidepanel-menu">
						<template v-if="templateMode === 'view'">
							<li class="ui-sidepanel-menu-item salescenter-app-sidebar-menu-active">
								<a class="ui-sidepanel-menu-link">
									<div class="ui-sidepanel-menu-link-text">{{title}}</div>
								</a>
							</li>
						</template>
						<template v-else-if="isModeListVisible">
							<template v-if="isOnlyDeliveryItemVisible">
								<li
									:class="deliveryMenuItemClass"
									class="ui-sidepanel-menu-item"
								>
									<a class="ui-sidepanel-menu-link">
										<div class="ui-sidepanel-menu-link-text">
											${Loc.getMessage('SALESCENTER_LEFT_CREATE_SHIPMENT_MSGVER_1')}
										</div>
									</a>
								</li>
							</template>
							<template v-else>
								<li
									v-if="isPaymentItemVisible"
									@click="isSalescenterToolEnabled ? reload(ModeDictionary.payment) : openSalescanterToolDisabledSlider()"
									:class="paymentMenuItemClass"
									class="ui-sidepanel-menu-item"
								>
									<a class="ui-sidepanel-menu-link">
										<div class="ui-sidepanel-menu-link-text">
											${Loc.getMessage('SALESCENTER_LEFT_TAKE_PAYMENT')}
										</div>
									</a>
								</li>
	
								<li
									v-if="isTerminalItemVisible"
									@click="isTerminalToolEnabled ? reload(ModeDictionary.terminalPayment) : openTerminalToolDisabledSlider()"
									:class="paymentTerminalMenuItemClass"
									class="ui-sidepanel-menu-item"
								>
									<a class="ui-sidepanel-menu-link salescenter-menu-terminal-payment">
										<div class="ui-sidepanel-menu-link-text">
											${Loc.getMessage('SALESCENTER_LEFT_TERMINAL_PAYMENT')}
										</div>
									</a>
								</li>
	
								<li
									v-if="isPaymentItemVisible"
									@click="isSalescenterToolEnabled ? reload(ModeDictionary.paymentDelivery) : openSalescanterToolDisabledSlider()"
									:class="paymentDeliveryMenuItemClass"
									class="ui-sidepanel-menu-item"
								>
									<a class="ui-sidepanel-menu-link">
										<div class="ui-sidepanel-menu-link-text">
											${Loc.getMessage('SALESCENTER_LEFT_TAKE_PAYMENT_AND_CREATE_SHIPMENT')}
										</div>
									</a>
								</li>
							</template>
						</template>
	
						<li class="ui-sidepanel-menu-item ui-sidepanel-menu-item-sm ui-sidepanel-menu-item-separate">
							<a
								@click="specifyCompanyContacts"
								class="ui-sidepanel-menu-link"
							>
								<div class="ui-sidepanel-menu-link-text">
									${Loc.getMessage('SALESCENTER_LEFT_PAYMENT_COMPANY_CONTACTS')}
								</div>
							</a>
						</li>
						<li
							v-if="isSuggestScenarioMenuItemVisible"
							class="ui-sidepanel-menu-item ui-sidepanel-menu-item-sm"
						>
							<a
								@click="suggestScenario($event)"
								class="ui-sidepanel-menu-link"
							>
								<div class="ui-sidepanel-menu-link-text">
									${Loc.getMessage('SALESCENTER_LEFT_PAYMENT_OFFER_SCRIPT')}
								</div>
							</a>
						</li>
						<li class="ui-sidepanel-menu-item ui-sidepanel-menu-item-sm">
							<a
								@click="howItWorks($event)"
								class="ui-sidepanel-menu-link"
							>
								<div class="ui-sidepanel-menu-link-text">
									${Loc.getMessage('SALESCENTER_LEFT_PAYMENT_HOW_WORKS')}
								</div>
							</a>
						</li>
						<li
							v-if="isAllowedFreeMessagesButton"
							class="ui-sidepanel-menu-item ui-sidepanel-menu-item-sm"
						>
							<a
								@click="freeMessages($event)"
								class="ui-sidepanel-menu-link"
							>
								<div class="ui-sidepanel-menu-link-text">
									${Loc.getMessage('SALESCENTER_LEFT_PAYMENT_FREE_MESSAGES')}
								</div>
							</a>
						</li>
						<li
							v-if="isRequestIntegrationMenuItemVisible"
							class="ui-sidepanel-menu-item ui-sidepanel-menu-item-sm">
							<a
								@click="openIntegrationWindow($event)"
								class="ui-sidepanel-menu-link"
							>
								<div class="ui-sidepanel-menu-link-text"
									 data-manager-openIntegrationRequestForm-params="sender_page:deal"
								>
									${Loc.getMessage('SALESCENTER_LEFT_PAYMENT_INTEGRATION_MSGVER_3')}
								</div>
							</a>
						</li>
					</ul>
				</div>
				<div class="salescenter-app-right-side">
					<start
						v-if="needShowStoreConnection && mode !== ModeDictionary.terminalPayment"
						@on-successfully-connected="onSuccessfullyConnected"
					>
					</start>
					<template v-else>
						<deal-receiving-payment
							v-if="mode === ModeDictionary.paymentDelivery"
							@stage-block-on-reload="onReload"
							@stage-block-send-on-send="sendPaymentDeliveryForm($event)"
							:sendAllowed="isAllowedPaymentDeliverySubmitButton"
						/>
						<deal-creating-shipment
							v-else-if="mode === ModeDictionary.delivery"
							@stage-block-send-on-send="sendDeliveryForm($event)"
							:sendAllowed="isAllowedDeliverySubmitButton"
						/>
						<crm-entity-create-payment
							v-if="mode === ModeDictionary.payment"
							@stage-block-send-on-send="sendPaymentDeliveryForm($event)"
							:sendAllowed="isAllowedPaymentDeliverySubmitButton"
						/>
						<deal-terminal-payment
							v-if="mode === ModeDictionary.terminalPayment"
							@stage-block-send-on-send="sendTerminalPaymentForm($event)"
							@on-responsible-changed="onResponsibleChanged($event)"
							:sendAllowed="isAllowedTerminalPaymentSubmitButton"
						/>
					</template>
				</div>
				<template v-if="!(mode === 'terminal_payment' && templateMode === 'view' && !isTerminalViewModeSaveAllowed)">
					<div class="ui-button-panel-wrapper salescenter-button-panel" ref="buttonsPanel" :class="{ 'salescenter-button-panel-hidden': !isPanelVisible }">
						<div class="ui-button-panel">
							<template v-if="mode === ModeDictionary.paymentDelivery || mode === ModeDictionary.payment">
								<button
									@click="sendPaymentDeliveryForm($event)"
									:class="paymentDeliveryFormSubmitButtonClass"
									class="ui-btn ui-btn-md ui-btn-success"
								>
									{{sendPaymentDeliveryFormButtonText}}
								</button>
								<button
									@click="close"
									class="ui-btn ui-btn-md ui-btn-link"
								>
									${Loc.getMessage('SALESCENTER_CANCEL')}
								</button>
							</template>
							<template v-else-if="mode === 'terminal_payment'">
								<button
									v-if="editable"
									@click="sendTerminalPaymentForm($event)"
									:class="terminalPaymentFormSubmitButtonClass"
									class="ui-btn ui-btn-md ui-btn-success"
								>
									${Loc.getMessage('SALESCENTER_CREATE_TERMINAL_PAYMENT')}
								</button>
								<button
									v-if="isTerminalViewModeSaveAllowed"
									@click="sendTerminalPaymentForm($event)"
									:class="terminalPaymentFormSubmitButtonClass"
									class="ui-btn ui-btn-md ui-btn-success"
								>
									${Loc.getMessage('SALESCENTER_SAVE')}
								</button>
								<button
									@click="close"
									class="ui-btn ui-btn-md ui-btn-link"
								>
									${Loc.getMessage('SALESCENTER_CANCEL')}
								</button>
							</template>
							<template v-else-if="mode === ModeDictionary.delivery">
								<template v-if="editable">
									<button
										@click="sendDeliveryForm($event)"
										:class="deliveryFormSubmitButtonClass"
										class="ui-btn ui-btn-md ui-btn-success"
									>
										${Loc.getMessage('SALESCENTER_CREATE_SHIPMENT')}
									</button>
									<button
										@click="close"
										class="ui-btn ui-btn-md ui-btn-link"
									>
										${Loc.getMessage('SALESCENTER_CANCEL')}
									</button>
								</template>
							</template>
						</div>
						<div v-if="this.order.errors.length > 0" ref="errorBlock"></div>
					</div>
				</template>
			</div>
			<div class="salescenter-app-pinner-anchor" :class="{ 'salescenter-app-pinner-anchor-hidden': !isPanelVisible }"></div>
		</div>
	`,
};
