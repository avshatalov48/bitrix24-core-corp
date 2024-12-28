/**
 * @module crm/salescenter/products-step
 */
jn.define('crm/salescenter/products-step', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Random } = require('utils/random');
	const { EventEmitter } = require('event-emitter');
	const { ProductGrid } = require('crm/salescenter/products-step/product-grid');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { ProgressBarNumber } = require('crm/salescenter/progress-bar-number');
	const { WarningBlock } = require('layout/ui/warning-block');

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

		get requireSmsProvider()
		{
			return BX.prop.getBoolean(this.props, 'requireSmsProvider', false);
		}

		get hasSmsProviders()
		{
			return BX.prop.getBoolean(this.props, 'hasSmsProviders', false);
		}

		isNeedToSetSmsProvider()
		{
			return this.requireSmsProvider && !this.hasSmsProviders;
		}

		onEnterStep()
		{
			super.onEnterStep();

			if (!this.areAnalyticsSent)
			{
				const areProductsPreloaded = this.products.length > 0;

				const analytics = BX.prop.getObject(this.props, 'analytics', {});
				if (analytics.onEnterStep)
				{
					analytics.onEnterStep({
						areProductsPreloaded,
					});
				}

				this.areAnalyticsSent = true;
			}
		}

		isNextStepEnabled()
		{
			return this.products.length > 0;
		}

		subscribeToEvents()
		{
			this.customEventEmitter.on('SalescenterProductGrid::onUpdate', this.onGridUpdate);
		}

		onGridUpdate(products)
		{
			this.products = Array.isArray(products) ? products.map((item) => item.props) : [];

			this.stepAvailabilityChangeCallback(this.products.length > 0);
			this.progressBarNumberRef.setState({ isCompleted: this.products.length > 0 });
		}

		renderNumberBlock()
		{
			const progressBarSettings = this.getProgressBarSettings();

			return new ProgressBarNumber({
				number: progressBarSettings.number.toString(),
				isCompleted: this.products.length > 0,
				ref: (ref) => {
					this.progressBarNumberRef = ref;
				},
			});
		}

		getProgressBarSettings()
		{
			return {
				...super.getProgressBarSettings(),
				isEnabled: true,
			};
		}

		createLayout(props)
		{
			if (this.isNeedToSetSmsProvider())
			{
				return new WarningBlock({
					title: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_NO_SMS_PROVIDERS_TITLE'),
					description: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_NO_SMS_PROVIDERS_TEXT'),
					layout: PageManager,
					redirectUrl: '/saleshub/',
					analyticsSection: 'crm',
				});
			}

			const grid = BX.prop.getObject(this.props, 'grid', {});
			const editable = BX.prop.getBoolean(this.props, 'editable', true);
			const analytics = BX.prop.getObject(this.props, 'analytics', {});

			return View(
				{
					style: {
						height: '100%',
					},
				},
				new ProductGrid({
					...grid,
					showFloatingButton: editable,
					uid: this.uid,
					editable,
					menuAnalyticsPrefix: analytics.menuPrefix || '',
					onRemoveItemConfirm: () => {
						if (analytics.onProductRemoved)
						{
							analytics.onProductRemoved();
						}
					},
				}),
			);
		}

		getTitle()
		{
			return BX.prop.getString(this.props, 'title', '');
		}

		getNextStepButtonText()
		{
			const nextStepButtonText = BX.prop.getString(this.props, 'nextStepButtonText', '');

			if (nextStepButtonText)
			{
				return nextStepButtonText;
			}

			return Loc.getMessage('SALESCENTER_PRODUCT_STEP_CONTINUE');
		}

		onMoveToNextStep()
		{
			const onMoveToNextStep = BX.prop.getFunction(this.props, 'onMoveToNextStep', null);
			if (onMoveToNextStep)
			{
				onMoveToNextStep({
					products: this.products,
				});
			}

			return Promise.resolve();
		}
	}

	module.exports = { ProductsStep };
});
