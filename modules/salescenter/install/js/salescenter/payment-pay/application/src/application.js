import {BitrixVue} from 'ui.vue';
import {EventEmitter} from 'main.core.events';
import {PaymentProcess, VirtualForm} from 'sale.payment-pay.lib';
import {EventType} from 'sale.payment-pay.const';
import {UserConsent as UserConsentManager} from 'salescenter.payment-pay.user-consent';
import {OptionMixin} from 'salescenter.payment-pay.mixins';
import {BackendProvider} from 'salescenter.payment-pay.backend-provider';
import ErrorBox from './components/error-box';
import ResetPanel from './components/reset-panel';
import PaySystemResult from './components/pay-system-result';
import PaySystemList from './components/pay-system-list';
import PaySystemInfo from './components/pay-system-info';
import PaymentInfo from './components/payment-info';
import UserConsentComponent from './components/user-consent';

BitrixVue.component('salescenter-payment-pay-app', {
	props: {
		options: Object,
	},
	mixins: [OptionMixin],
	components: {
		ErrorBox,
		ResetPanel,
		PaySystemResult,
		PaySystemList,
		PaySystemInfo,
		PaymentInfo,
		UserConsentComponent,
	},
	data() {
		return {
			stages: this.getStageDefaults(),
			stage: this.getDefaultStage(),
			loading: false,
			consent: {
				id: this.option('consent.id'),
				title: this.option('consent.title'),
				eventName: this.option('consent.eventName'),
				accepted: this.option('consent.accepted'),
			},
		};
	},
	created() {
		this.initPaymentProcess();
		this.initUserConsent();
		this.subscribeToGlobalEvents();
	},
	methods: {
		initPaymentProcess() {
			this.backendProvider = new BackendProvider({
				returnUrl: this.option('paymentProcess.returnUrl'),
				orderId: this.option('paymentProcess.orderId'),
				paymentId: this.option('paymentProcess.paymentId'),
				accessCode: this.option('paymentProcess.accessCode'),
			});

			this.paymentProcess = new PaymentProcess({
				backendProvider: this.backendProvider,
				allowPaymentRedirect: this.option('paymentProcess.allowPaymentRedirect'),
			});
		},
		initUserConsent() {
			this.userConsentManager = new UserConsentManager({
				containerId: this.option('consent.containerId'),
				accepted: this.option('consent.accepted', false),
				eventName: this.option('consent.eventName', false),
			});
		},
		subscribeToGlobalEvents() {
			EventEmitter.subscribe(EventType.payment.error, (e) => { this.handlePaymentError(e.getData()) });
			EventEmitter.subscribe(EventType.payment.success, (e) => { this.handlePaymentSuccess(e.getData()) });
			EventEmitter.subscribe(EventType.global.paySystemAjaxError, (e) => { this.handlePaySystemAjaxError(e.getData()) });
			EventEmitter.subscribe(EventType.global.paySystemUpdateTemplate, (e) => { this.handlePaySystemUpdateTemplate(e.getData()) });
		},
		startPayment(paySystemId) {
			if (this.loading) {
				return false;
			}

			this.userConsentManager.askUserToPerform(() => {
				this.loading = true;
				this.stages.paySystemList.selectedPaySystem = paySystemId;
				this.backendProvider.paySystemId = paySystemId;
				this.paymentProcess.start();
			});
		},
		handlePaymentError(response) {
			this.stages.paySystemErrors.errors = response.data.errors || [];
			this.stage = 'paySystemErrors';
		},
		handlePaymentSuccess(response) {
			this.stages.paySystemResult.html = response.data.html || null;
			this.stages.paySystemResult.fields = response.data.fields || null;
			this.stage = 'paySystemResult';
		},
		handlePaySystemAjaxError(data) {
			this.stages.paySystemErrors.errors = data || [];
			this.stage = 'paySystemErrors';
		},
		handlePaySystemUpdateTemplate(data) {
			VirtualForm.createFromNode(this.$el).submit();
		},
		resetView() {
			this.stages = this.getStageDefaults();
			this.stage = this.getDefaultStage();
			this.loading = false;
		},
		getStageDefaults() {
			return {
				paySystemList: {
					paySystems: this.option('app.paySystems', []),
					selectedPaySystem: null,
					title: this.option('app.title'),
				},
				paySystemInfo: {
					paySystems: this.option('app.paySystems', []),
				},
				paymentInfo: {
					paySystem: this.option('app.paySystems', [])[0],
					title: this.option('app.title'),
					sum: this.option('payment.sumFormatted'),
					paid: this.option('payment.paid'),
					checks: this.option('payment.checks', []),
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
		getDefaultStage() {
			return this.option('app.template', 'paySystemList');
		},
	},
	// language=Vue
	template: `
		<div class="salescenter-payment-pay-app">
			<PaySystemList 
				v-if="stage === 'paySystemList'"
				:paySystems="stages.paySystemList.paySystems"
				:selectedPaySystem="stages.paySystemList.selectedPaySystem"
				:loading="loading"
				:title="stages.paySystemList.title"
				@start-payment="startPayment($event)">
				<template v-slot:user-consent>
					<UserConsentComponent 
						:id="consent.id"
						:title="consent.title"
						:checked="consent.accepted"
						:submitEventName="consent.eventName"/>
                </template>
			</PaySystemList>
			<PaySystemInfo
				v-if="stage === 'paySystemInfo'"
				:paySystems="stages.paySystemInfo.paySystems">
			</PaySystemInfo>
			<PaymentInfo 
				v-if="stage === 'paymentInfo'"
				:paySystem="stages.paymentInfo.paySystem"
                :title="stages.paymentInfo.title"
				:sum="stages.paymentInfo.sum"
				:paid="stages.paymentInfo.paid"
				:loading="loading"
				:checks="stages.paymentInfo.checks"
                @start-payment="startPayment($event)">
              	<template v-slot:user-consent>
                	<UserConsentComponent
                    	:id="consent.id"
                    	:title="consent.title"
                    	:checked="consent.accepted"
                    	:submitEventName="consent.eventName"/>
              	</template>
			</PaymentInfo>
			<ErrorBox
				v-if="stage === 'paySystemErrors'" 
				:errors="stages.paySystemErrors.errors">
				<ResetPanel @reset="resetView()"/>
			</ErrorBox>
			<PaySystemResult
				v-if="stage === 'paySystemResult'" 
				:html="stages.paySystemResult.html" 
				:fields="stages.paySystemResult.fields">
				<ResetPanel @reset="resetView()"/>
			</PaySystemResult>
		</div>
	`,
});