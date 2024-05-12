(() => {
	const require = (ext) => jn.require(ext);

	const AppTheme = require('apptheme');
	const { CrmProductTabShimmer } = require('layout/ui/detail-card/tabs/shimmer/crm-product');
	const { ContactStep } = require('crm/receive-payment/steps/contact');
	const { ProductsStep } = require('crm/salescenter/products-step');
	const { PaymentSystemsStep } = require('crm/receive-payment/steps/payment-systems');
	const { SendMessageStep } = require('crm/receive-payment/steps/send-message');
	const { FinishStep } = require('crm/receive-payment/steps/finish');
	const { EventEmitter } = require('event-emitter');
	const { Wizard } = require('layout/ui/wizard');
	const { handleErrors } = require('crm/error');
	const { Loc } = require('loc');
	const { AnalyticsLabel } = require('analytics-label');

	const Status = {
		LOADING: 1,
		DONE: 2,
	};

	const PRODUCTS_FOR_LOADER_COUNT = 4;

	/**
	 * @class SalescenterReceivePaymentComponent
	 */
	class SalescenterReceivePaymentComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.stepProps = null;
			this.state.status = Status.LOADING;
			this.dataForSending = {};
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.resendMessageMode = BX.prop.getBoolean(props, 'resendMessageMode', false);
			this.entityHasContact = BX.prop.getBoolean(props, 'entityHasContact', false);

			this.stepsCount = 3;
			this.currentStep = 1;

			this.getStepForId = this.getStepForId.bind(this);
		}

		get productCount()
		{
			return Math.min(this.props.productCount, PRODUCTS_FOR_LOADER_COUNT);
		}

		/**
		 * @returns {number}
		 */
		get paymentId()
		{
			return BX.prop.getInteger(this.props, 'paymentId', 0);
		}

		/**
		 * @returns {{
		 *     id: string,
		 *     component: WizardStep,
		 * }[]}
		 */
		getSteps()
		{
			const steps = [];

			if (!this.entityHasContact)
			{
				this.stepsCount = 4;
				steps.push(
					{
						id: 'contact',
						component: ContactStep,
					},
				);
			}

			steps.push(
				{
					id: 'product',
					component: ProductsStep,
				},
				{
					id: 'paySystems',
					component: PaymentSystemsStep,
				},
				{
					id: 'sendMessage',
					component: SendMessageStep,
				},
				{
					id: 'finish',
					component: FinishStep,
				},
			);

			return steps;
		}

		getStepForId(stepId)
		{
			const step = this.getSteps().find((step) => step.id === stepId);

			if (step)
			{
				const props = this.stepProps[stepId] || {};
				props.uid = this.uid;

				if (stepId === 'contact')
				{
					props.onMoveToNextStep = (data) => {
						this.dataForSending.selectedContact = data.selectedContact;
					};

					props.progressBarSettings = {
						number: this.currentStep,
						count: this.stepsCount,
					};
					this.currentStep++;
				}

				if (stepId === 'product')
				{
					props.editable = !this.resendMessageMode;
					props.title = Loc.getMessage('SALESCENTER_RECEIVE_PAYMENT_RECEIVE_PAYMENT');
					props.progressBarSettings = {
						title: {
							text: Loc.getMessage('SALESCENTER_RECEIVE_PAYMENT_PRODUCT_STEP_PROGRESS_BAR_TITLE'),
						},
						number: this.currentStep,
						count: this.stepsCount,
					};
					this.currentStep++;
					props.onMoveToNextStep = (data) => {
						const { products } = data;

						if (!Array.isArray(products))
						{
							return;
						}

						this.dataForSending.products = products;
					};
					props.analytics = {
						menuPrefix: 'receive_payment',
						onProductRemoved: () => {
							AnalyticsLabel.send({
								event: 'onReceivePaymentProductRemoved',
							});
						},
						onEnterStep: (data) => {
							const { areProductsPreloaded } = data;

							AnalyticsLabel.send({
								event: 'onReceivePaymentProductsStepOpen',
								areProductsPreloaded,
							});
						},
					};
				}

				if (stepId === 'paySystems')
				{
					props.resendMessageMode = this.resendMessageMode;
					props.progressBarSettings = {
						number: this.currentStep,
						count: this.stepsCount,
					};
					this.currentStep++;
				}

				if (stepId === 'sendMessage')
				{
					props.onMoveToNextStep = (data) => {
						const {
							sendingMethod,
							sendingMethodDesc,
						} = data;

						this.dataForSending.sendingMethod = sendingMethod;
						this.dataForSending.sendingMethodDesc = sendingMethodDesc;
					};
					props.progressBarSettings = {
						number: this.currentStep,
						count: this.stepsCount,
					};
					props.root = this;
					this.currentStep++;
				}

				if (stepId === 'finish')
				{
					props.sendMessage = this.sendMessage.bind(this);
					props.sendMessageProps = this.stepProps.sendMessage;
				}

				return new step.component(props);
			}

			return null;
		}

		sendMessage()
		{
			const action = this.resendMessageMode
				? 'crmmobile.ReceivePayment.Payment.resendMessage'
				: 'crmmobile.ReceivePayment.Payment.createPayment';

			return new Promise((resolve, reject) => {
				BX.ajax.runAction(
					action,
					{
						json: {
							options: this.dataForSending,
						},
					},
				)
					.then(() => {
						resolve();
						this.customEventEmitter.emit('DetailCard::reloadTabs');
					})
					.catch(reject);
			});
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgSecondary,
					},
				},
				this.state.status === Status.LOADING && this.renderLoader(),
				this.state.status === Status.DONE && this.renderWizard(),
			);
		}

		renderLoader()
		{
			return new CrmProductTabShimmer({
				animating: true,
				productCount: this.productCount,
			});
		}

		renderWizard()
		{
			const wizard = new Wizard({
				parentLayout: layout,
				steps: this.getSteps().map((step) => step.id),
				stepForId: this.getStepForId,
				isNavigationBarBorderEnabled: true,
			});

			if (this.resendMessageMode)
			{
				if (this.entityHasContact)
				{
					wizard.openStepWidget('paySystems');
					wizard.openStepWidget('sendMessage');
				}

				this.dataForSending.paymentId = this.paymentId;
				this.dataForSending.shipmentId = 0;
			}

			return wizard;
		}

		/**
		 * @return Promise
		 */
		init(entityTypeId, entityId)
		{
			this.dataForSending = {
				ownerTypeId: entityTypeId,
				ownerId: entityId,
			};

			return new Promise((resolve, reject) => {
				if (this.resendMessageMode && !this.paymentId)
				{
					console.error('Payment id is not specified');
					reject();

					return;
				}

				const data = {
					entityId,
					entityTypeId,
					resendData: {
						resendMessageMode: this.resendMessageMode,
						documentId: this.paymentId,
					},
				};

				BX.ajax.runAction('crmmobile.ReceivePayment.Wizard.initialize', { json: data })
					.then((response) => {
						this.stepProps = response.data.steps || {};
						this.setState({ status: Status.DONE }, resolve);
					})
					.catch(handleErrors);
			});
		}
	}

	BX.onViewLoaded(() => {
		const receivePayment = new SalescenterReceivePaymentComponent(
			{
				uid: BX.componentParameters.get('uid'),
				productCount: BX.componentParameters.get('productCount', 0),
				resendMessageMode: BX.componentParameters.get('resendMessageMode', false),
				paymentId: BX.componentParameters.get('paymentId', 0),
				entityHasContact: BX.componentParameters.get('entityHasContact', false),
			},
		);
		layout.enableNavigationBarBorder(true);
		layout.showComponent(receivePayment);

		receivePayment.init(
			BX.componentParameters.get('entityTypeId'),
			BX.componentParameters.get('entityId'),
			BX.componentParameters.get('resendMessageMode', false),
		).catch((response) => {
			layout.close();
		});
	});
})();
