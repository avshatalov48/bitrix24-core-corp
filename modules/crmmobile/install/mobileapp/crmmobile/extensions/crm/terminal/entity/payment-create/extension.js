/**
 * @module crm/terminal/entity/payment-create
 */
jn.define('crm/terminal/entity/payment-create', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { EventEmitter } = require('event-emitter');
	const { Wizard } = require('layout/ui/wizard');
	const { Random } = require('utils/random');
	const { ResponsibleStep } = require('crm/terminal/entity/payment-create/steps/responsible');
	const { ProductsStep } = require('crm/salescenter/products-step');
	const { FinishStep } = require('crm/terminal/entity/payment-create/steps/finish');
	const { handleErrors } = require('crm/error');
	const { CrmProductTabShimmer } = require('layout/ui/detail-card/tabs/shimmer/crm-product');
	const { Loc } = require('loc');
	const { AnalyticsLabel } = require('analytics-label');
	const { PaymentService } = require('crm/terminal/services/payment');

	const Status = {
		LOADING: 1,
		DONE: 2,
	};

	const StepIds = {
		responsible: 'responsible',
		product: 'product',
		finish: 'finish',
	};

	const AnalyticsEvents = {
		stepShowed: 'terminal-entity-create-payment-step-showed',
		newPaymentCreated: 'terminal-entity-new-payment-created',
	};

	const PRODUCTS_FOR_LOADER_COUNT = 4;

	/**
	 * @class PaymentCreate
	 */
	class PaymentCreate extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				status: Status.LOADING,
			};

			this.responsible = null;
			this.products = [];

			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.stepProps = null;
			this.getStepForId = this.getStepForId.bind(this);

			this.paymentService = new PaymentService();
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
			return new Wizard({
				parentLayout: layout,
				steps: this.getSteps().map((step) => step.id),
				stepForId: this.getStepForId,
				isNavigationBarBorderEnabled: true,
			});
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
					id: StepIds.responsible,
					component: ResponsibleStep,
				},
				{
					id: StepIds.product,
					component: ProductsStep,
				},
				{
					id: StepIds.finish,
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

				if (stepId === StepIds.responsible)
				{
					props.onMoveToNextStep = (data) => {
						const { responsible } = data;

						this.responsible = responsible;
					};
					props.analytics = {
						onEnterStep: () => {
							AnalyticsLabel.send({
								event: AnalyticsEvents.stepShowed,
								step: StepIds.responsible,
							});
						},
					};
				}

				if (stepId === StepIds.product)
				{
					props.title = Loc.getMessage('M_CRM_TL_EPC_PRODUCT_WIZARD_TITLE');
					props.nextStepButtonText = Loc.getMessage('M_CRM_TL_EPC_PRODUCT_STEP_NEXT_STEP_BTN_TEXT');
					props.progressBarSettings = {
						title: {
							text: Loc.getMessage('M_CRM_TL_EPC_PRODUCT_STEP_PROGRESS_BAR_TITLE'),
						},
						number: 2,
						count: 2,
					};
					props.onMoveToNextStep = (data) => {
						const { products } = data;

						if (!Array.isArray(products))
						{
							return;
						}

						this.products = products;
					};

					props.analytics = {
						menuPrefix: 'create_terminal_payment',
						onProductRemoved: () => {
							AnalyticsLabel.send({
								event: 'terminal-entity-create-payment-product-removed',
							});
						},
						onEnterStep: (data) => {
							const { areProductsPreloaded } = data;

							AnalyticsLabel.send({
								event: AnalyticsEvents.stepShowed,
								step: StepIds.product,
								areProductsPreloaded,
							});
						},
					};
				}

				if (stepId === StepIds.finish)
				{
					props.createPayment = this.createPayment.bind(this);
					props.getResponsible = () => this.responsible;
					props.entityTypeId = this.entityTypeId;

					props.analytics = {
						onEnterStep: () => {
							AnalyticsLabel.send({
								event: AnalyticsEvents.stepShowed,
								step: StepIds.finish,
							});
						},
					};
				}

				return new step.component(props);
			}
		}

		createPayment()
		{
			return new Promise((resolve, reject) => {
				this.paymentService.createForEntity({
					entityId: this.entityId,
					entityTypeId: this.entityTypeId,
					responsibleId: this.responsible ? this.responsible.id : null,
					products: this.products,
				}).then((payment) => {
					AnalyticsLabel.send({ event: AnalyticsEvents.newPaymentCreated });

					this.customEventEmitter.emit('DetailCard::reloadTabs');

					resolve(payment);
				}).catch((error) => {
					reject();
					console.error(error);
				});
			});
		}

		initialize()
		{
			return new Promise((resolve, reject) => {
				const data = {
					entityId: this.entityId,
					entityTypeId: this.entityTypeId,
				};

				BX.ajax.runAction('crmmobile.Terminal.Entity.initialize', { json: data })
					.then((response) => {
						const {
							steps,
						} = response.data;

						this.stepProps = steps || {};

						this.setState({ status: Status.DONE }, resolve);
					})
					.catch(handleErrors);
			});
		}

		componentDidMount()
		{
			this.initialize().catch((response) => {
				this.layout.close();
			});
		}

		get layout()
		{
			return this.props.layout || {};
		}

		get productCount()
		{
			return Math.min(this.props.productCount, PRODUCTS_FOR_LOADER_COUNT);
		}

		get entityTypeId()
		{
			return BX.prop.getInteger(this.props, 'entityTypeId', 0);
		}

		get entityId()
		{
			return BX.prop.getNumber(this.props, 'entityId', null);
		}

		static open(params)
		{
			const {
				componentParams,
			} = params;

			ComponentHelper.openLayout({
				name: 'crm:crm.terminal.entity.paymentcreate',
				object: 'layout',
				widgetParams: {
					objectName: 'layout',
					title: Loc.getMessage('M_CRM_TL_EPC_PRODUCT_WIZARD_TITLE'),
					modal: true,
					backgroundColor: AppTheme.colors.bgSecondary,
					backdrop: {
						swipeAllowed: false,
						forceDismissOnSwipeDown: false,
						horizontalSwipeAllowed: false,
						bounceEnable: true,
						showOnTop: true,
						topPosition: 60,
						navigationBarColor: AppTheme.colors.bgSecondary,
					},
				},
				componentParams,
			});
		}
	}

	module.exports = { PaymentCreate };
});
