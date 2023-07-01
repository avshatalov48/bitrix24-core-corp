(() => {
	const require = (ext) => jn.require(ext);

	const { CrmProductTabShimmer } = require('layout/ui/detail-card/tabs/shimmer/crm-product');
	const { ProductsStep } = require('crm/receive-payment/steps/products');
	const { PaymentSystemsStep } = require('crm/receive-payment/steps/payment-systems');
	const { SendMessageStep } = require('crm/receive-payment/steps/send-message');
	const { FinishStep } = require('crm/receive-payment/steps/finish');
	const { EventEmitter } = require('event-emitter');
	const { Wizard } = require('layout/ui/wizard');
	const { handleErrors } = require('crm/error');

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
			this.document = props.document;

			this.getStepForId = this.getStepForId.bind(this);
		}

		get productCount()
		{
			return Math.min(this.props.productCount, PRODUCTS_FOR_LOADER_COUNT);
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
				props.parent = this;
				props.resendMessageMode = this.resendMessageMode;
				if (stepId === 'finish')
				{
					props.sendMessage = this.sendMessage.bind(this);
					props.sendMessageProps = this.stepProps.sendMessage;
				}

				return new step.component(props);
			}
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
						backgroundColor: '#eef2f4',
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
				useProgressBar: true,
				hideProgressBarInLastTab: true,
				isNavigationBarBorderEnabled: true,
			});

			if (this.resendMessageMode)
			{
				wizard.openStepWidget('sendMessage');
				if (this.document.TYPE === 'PAYMENT')
				{
					this.dataForSending.paymentId = this.document.ID;
					this.dataForSending.shipmentId = 0;
				}

				if (this.document.TYPE === 'SHIPMENT')
				{
					this.dataForSending.paymentId = 0;
					this.dataForSending.shipmentId = this.document.ID;
				}
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
				const data = {
					entityId,
					entityTypeId,
					resendData: {
						resendMessageMode: this.resendMessageMode,
						documentId: this.document.ID,
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

		saveProductForSending(products)
		{
			if (!Array.isArray(products))
			{
				return;
			}

			this.dataForSending.products = products;
		}

		saveSendingMethodForSending({ sendingMethod, sendingMethodDesc })
		{
			this.dataForSending.sendingMethod = sendingMethod;
			this.dataForSending.sendingMethodDesc = sendingMethodDesc;
		}
	}

	BX.onViewLoaded(() => {
		const receivePayment = new SalescenterReceivePaymentComponent(
			{
				uid: BX.componentParameters.get('uid'),
				productCount: BX.componentParameters.get('productCount', 0),
				resendMessageMode: BX.componentParameters.get('resendMessageMode', false),
				document: BX.componentParameters.get('document', {}),
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
