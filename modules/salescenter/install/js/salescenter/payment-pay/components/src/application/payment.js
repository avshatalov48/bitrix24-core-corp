import { BitrixVue } from 'ui.vue';
import { Settings } from 'sale.payment-pay.lib';
import { StageType } from 'sale.payment-pay.const';
import { MixinMethods } from 'sale.payment-pay.mixins.application';
import { UserConsent as UserConsentManager } from 'salescenter.payment-pay.user-consent';
import { BackendProvider } from 'salescenter.payment-pay.backend-provider';

BitrixVue.component('salescenter-payment_pay-components-application-payment', {
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
				this.backendProvider.paySystemId = paySystemId;
				this.paymentProcess.start();
			});
		},
		prepareParamsStages()
		{
			let settings = new Settings(this.options);

			return {
				paymentInfo: {
					paySystem: settings.get('app.paySystems', [])[0],
					title: settings.get('app.title'),
					sum: settings.get('payment.sumFormatted'),
					paid: settings.get('payment.paid'),
					checks: settings.get('payment.checks', []),
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
			return StageType.paymentInfo;
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
			<salescenter-payment_pay-components-payment_system-payment_info
                v-if="stage === stageType.paymentInfo"
				:paySystem="stages.paymentInfo.paySystem"
                :title="stages.paymentInfo.title"
				:sum="stages.paymentInfo.sum"
				:paid="stages.paymentInfo.paid"
				:loading="loading"
				:checks="stages.paymentInfo.checks"
                @start-payment="startPayment($event)">
				<template v-slot:user-consent>
					<salescenter-payment_pay-components-payment_system-user_consent
						:id="consent.id"
						:title="consent.title"
						:checked="consent.accepted"
						:submitEventName="consent.eventName"/>
				</template>
			</salescenter-payment_pay-components-payment_system-payment_info>
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