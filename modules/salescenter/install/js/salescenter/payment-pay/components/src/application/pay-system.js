import { BitrixVue } from 'ui.vue';
import { Settings } from 'sale.payment-pay.lib';
import { StageType } from 'sale.payment-pay.const';
import { MixinMethods } from 'sale.payment-pay.mixins.application';
import { UserConsent as UserConsentManager } from 'salescenter.payment-pay.user-consent';
import { BackendProvider } from 'salescenter.payment-pay.backend-provider';

BitrixVue.component('salescenter-payment_pay-components-application-pay_system', {
	props: {
		options: Object,
	},
	mixins: [MixinMethods],
	data()
	{
		let settings = new Settings(this.options);

		return {
			stageType: StageType,
			stages: this.prepareParamsStages(),
			stage: this.setStageType(),
			loading: false,
			paymentProcess: this.prepareParamsPaymentProcess(settings),
			consent: this.prepareUserConsentSettings(settings),
		};
	},
	created()
	{
		this.initPayment();
		this.initUserConsent();
		this.subscribeToGlobalEvents();
	},
	methods: {
		initUserConsent()
		{
			this.userConsentManager = new UserConsentManager({
				containerId: this.consent.containerId,
				accepted: this.consent.accepted,
				eventName: this.consent.eventName
			});
		},
		initBackendProvider()
		{
			this.backendProvider = new BackendProvider({
				returnUrl: this.paymentProcess.returnUrl,
				orderId: this.paymentProcess.orderId,
				paymentId: this.paymentProcess.paymentId,
				accessCode: this.paymentProcess.accessCode,
			});
		},
		startPayment(paySystemId)
		{
			if (this.loading)
			{
				return false;
			}

			this.userConsentManager.askUserToPerform(() => {
				this.loading = true;
				this.stages.paySystemList.selectedPaySystem = paySystemId;
				this.backendProvider.paySystemId = paySystemId;
				this.paymentProcess.start();
			});
		},
		prepareParamsStages()
		{
			let settings = new Settings(this.options);
			return {
				paySystemList: {
					paySystems: settings.get('app.paySystems', []),
					selectedPaySystem: null,
					title: settings.get('app.title'),
				},
				paySystemErrors: {
					errors: [],
				},
				paySystemResult: {
					html: null,
					fields: null,
				},
			};
		},
		setStageType()
		{
			return StageType.list;
		},
		prepareUserConsentSettings(settings)
		{
			return {
				id: settings.get('consent.id'),
				title: settings.get('consent.title'),
				eventName: settings.get('consent.eventName'),
				accepted: settings.get('consent.accepted'),
				containerId: settings.get('consent.containerId'),
			};
		},
	},
	// language=Vue
	template: `
		<div class="salescenter-payment-pay-app">
			<salescenter-payment_pay-components-payment_system-pay_system_list
				v-if="stage === stageType.list"
				:paySystems="stages.paySystemList.paySystems"
				:selectedPaySystem="stages.paySystemList.selectedPaySystem"
				:loading="loading"
                :title="stages.paySystemList.title"
				@start-payment="startPayment($event)">
				<template v-slot:user-consent>
					<salescenter-payment_pay-components-payment_system-user_consent
						:id="consent.id"
						:title="consent.title"
						:checked="consent.accepted"
						:submitEventName="consent.eventName"/>
				</template>
			</salescenter-payment_pay-components-payment_system-pay_system_list>
			<salescenter-payment_pay-components-payment_system-error_box
				v-if="stage === stageType.errors"
				:errors="stages.paySystemErrors.errors">
				<salescenter-payment_pay-components-payment_system-reset_panel @reset="resetView()"/>
			</salescenter-payment_pay-components-payment_system-error_box>
			<salescenter-payment_pay-components-payment_system-pay_system_result
				v-if="stage === stageType.result"
				:html="stages.paySystemResult.html"
				:fields="stages.paySystemResult.fields">
				<salescenter-payment_pay-components-payment_system-reset_panel @reset="resetView()"/>
			</salescenter-payment_pay-components-payment_system-pay_system_result>
		</div>
	`,
});