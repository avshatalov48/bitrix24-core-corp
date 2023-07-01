/**
 * @module crm/receive-payment/steps/products
 */
jn.define('crm/receive-payment/steps/products', (require, exports, module) => {
	/** @var ReceivePaymentProductGrid */
	const { ReceivePaymentProductGrid: ProductGrid } = require('crm/receive-payment/steps/products/product-grid');
	const { EventEmitter } = require('event-emitter');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { Loc } = require('loc');
	const { ProgressBarNumber } = require('crm/receive-payment/progress-bar-number');
	const { AnalyticsLabel } = require('analytics-label');

	/**
	 * @class ProductsStep
	 */
	class ProductsStep extends WizardStep
	{
		constructor(props)
		{
			super();

			this.props = props;
			this.products = [];
			const { grid } = this.props;
			if (grid && Array.isArray(grid.products))
			{
				this.products = grid.products;
			}

			this.onGridUpdate = this.onGridUpdate.bind(this);

			this.uid = Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.subscribeToEvents();

			this.areAnalyticsSent = false;
		}

		onEnterStep()
		{
			super.onEnterStep();

			if (!this.areAnalyticsSent)
			{
				const areProductsPreloaded = this.products.length > 0;
				AnalyticsLabel.send({
					event: 'onReceivePaymentProductsStepOpen',
					areProductsPreloaded,
				});

				this.areAnalyticsSent = true;
			}
		}

		isNextStepEnabled()
		{
			return this.products.length > 0;
		}

		subscribeToEvents()
		{
			this.customEventEmitter.on('ReceivePaymentProductGrid::onUpdate', this.onGridUpdate);
		}

		onGridUpdate(products)
		{
			this.products = Array.isArray(products) ? products.map((item) => item.props) : [];

			this.stepAvailabilityChangeCallback(this.products.length > 0);
			this.progressBarNumberRef.setState({ isCompleted: this.products.length > 0 });
		}

		renderNumberBlock()
		{
			return new ProgressBarNumber({
				number: '1',
				isCompleted: this.products.length > 0,
				ref: (ref) => this.progressBarNumberRef = ref,
			});
		}

		getProgressBarSettings()
		{
			return {
				...super.getProgressBarSettings(),
				isEnabled: true,
				title: {
					text: Loc.getMessage('RECEIVE_PAYMENT_PRODUCT_STEP_PROGRESS_BAR_TITLE'),
				},
				number: 1,
				count: 3,
			};
		}

		createLayout(props)
		{
			const { grid, resendMessageMode } = this.props;

			return View(
				{
					style: {
						height: '100%',
					},
				},
				new ProductGrid({
					...grid,
					showFloatingButton: !resendMessageMode,
					showSummaryTax: false,
					uid: this.uid,
					editable: !resendMessageMode,
				}),
			);
		}

		getTitle()
		{
			return Loc.getMessage('RECEIVE_PAYMENT_PRODUCT_STEP_TITLE');
		}

		getNextStepButtonText()
		{
			return Loc.getMessage('RECEIVE_PAYMENT_PRODUCT_STEP_NEXT');
		}

		onMoveToNextStep()
		{
			if (this.props.parent)
			{
				this.props.parent.saveProductForSending(this.products);
			}

			return Promise.resolve();
		}
	}

	module.exports = { ProductsStep };
});
