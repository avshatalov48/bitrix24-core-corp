import {Vuex} from 'ui.vue.vuex';
import StageBlocksList from './components/deal-receiving-payment/stage-blocks-list';
import StageBlocksListShipment from './components/deal-creating-shipment/stage-blocks-list';
import ComponentMixin from './component-mixin';
import {Loc} from 'main.core';
import {MixinTemplatesType} from './components/templates-type-mixin';
import Start from './start';

export default {
	mixins: [MixinTemplatesType, ComponentMixin],
	data()
	{
		return {
			activeMenuItem: this.$root.$app.options.mode,
			isLoading: false,
		};
	},
	components: {
		'deal-receiving-payment': StageBlocksList,
		'deal-creating-shipment': StageBlocksListShipment,
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
					initialMode: this.$root.$app.options.initialMode,
				}
			);
		},
		onSuccessfullyConnected()
		{
			this.reload(this.activeMenuItem);
		},
		sendPaymentDeliveryForm(event)
		{
			if (!this.isAllowedPaymentDeliverySubmitButton)
			{
				return;
			}

			if (this.editable)
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
			BX.Salescenter.Manager.openHowPayDealWorks(event);
		},
		openIntegrationWindow(event)
		{
			BX.Salescenter.Manager.openIntegrationRequestForm(event);
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
		isOnlyDeliveryItemVisible()
		{
			return (
				this.$root.$app.options.hasOwnProperty('deliveryList')
				&& this.$root.$app.options.deliveryList.hasOwnProperty('hasInstallable')
				&& this.$root.$app.options.deliveryList.hasInstallable
			);
		},
		isAllowedPaymentDeliverySubmitButton()
		{
			if (this.$root.$app.options.contactPhone === '')
			{
				return false;
			}

			let isCurrentSenderConnected = false;
			for (let sender of this.$root.$app.options.senders)
			{
				if (sender.code !== this.$root.$app.options.currentSenderCode)
				{
					continue;
				}

				if (sender.isConnected)
				{
					isCurrentSenderConnected = true;
					break;
				}
			}
			if (!isCurrentSenderConnected)
			{
				return false;
			}

			return this.$store.getters['orderCreation/isAllowedSubmit'];
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
		needShowStoreConnection()
		{
			return !this.isOrderPublicUrlAvailable && this.mode !== 'delivery';
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
		paymentDeliveryMenuItemClass()
		{
			return {'salescenter-app-sidebar-menu-active': this.activeMenuItem === 'payment_delivery'};
		},
		deliveryMenuItemClass()
		{
			return {'salescenter-app-sidebar-menu-active': this.activeMenuItem === 'delivery'};
		},
		// endregion
		...Vuex.mapState({
			application: state => state.application,
			order: state => state.orderCreation,
		})
	},
	template: `
		<div
			:class="wrapperClass"
			:style="wrapperStyle"
			class="salescenter-app-wrapper"
		>
			<div class="ui-sidepanel-sidebar salescenter-app-sidebar">
				<ul class="ui-sidepanel-menu">
					<template v-if="templateMode === 'view'">
						<li class="ui-sidepanel-menu-item salescenter-app-sidebar-menu-active">
							<a class="ui-sidepanel-menu-link">
								<div class="ui-sidepanel-menu-link-text">{{title}}</div>
							</a>
						</li>
					</template>
					<template v-else>
						<li
							v-if="initialMode === 'payment_delivery'"
							@click="reload('payment_delivery')"
							:class="paymentDeliveryMenuItemClass"
							class="ui-sidepanel-menu-item"
						>
							<a class="ui-sidepanel-menu-link">
								<div class="ui-sidepanel-menu-link-text">
									${Loc.getMessage('SALESCENTER_LEFT_TAKE_PAYMENT_AND_CREATE_SHIPMENT')}
								</div>
							</a>
						</li>
						<li
							v-if="isOnlyDeliveryItemVisible"
							@click="reload('delivery')"
							:class="deliveryMenuItemClass"
							class="ui-sidepanel-menu-item"
						>
							<a class="ui-sidepanel-menu-link">
								<div class="ui-sidepanel-menu-link-text">
									${Loc.getMessage('SALESCENTER_LEFT_CREATE_SHIPMENT')}
								</div>
							</a>
						</li>
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
						v-if="isRequestIntegrationMenuItemVisible"
						class="ui-sidepanel-menu-item ui-sidepanel-menu-item-sm">
						<a
							@click="openIntegrationWindow($event)"
							class="ui-sidepanel-menu-link"
						>
							<div class="ui-sidepanel-menu-link-text">
								${Loc.getMessage('SALESCENTER_LEFT_PAYMENT_INTEGRATION')}
							</div>
						</a>
					</li>
				</ul>
			</div>
			<div class="salescenter-app-right-side">
				<start
					v-if="needShowStoreConnection"
					@on-successfully-connected="onSuccessfullyConnected"
				>
				</start>
		        <template v-else>
			        <deal-receiving-payment
			        	v-if="mode === 'payment_delivery'"
			        	@stage-block-send-on-send="sendPaymentDeliveryForm($event)"
			        	:sendAllowed="isAllowedPaymentDeliverySubmitButton"
			        />
			        <deal-creating-shipment
			        	v-else-if="mode === 'delivery'"
			        	@stage-block-send-on-send="sendDeliveryForm($event)"
			        	:sendAllowed="isAllowedDeliverySubmitButton"
			        />
		        </template>
			</div>
			<div class="ui-button-panel-wrapper salescenter-button-panel" ref="buttonsPanel">
				<div class="ui-button-panel">
					<template v-if="mode === 'payment_delivery'">
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
					<template v-else-if="mode === 'delivery'">
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
		</div>
	`,
}
