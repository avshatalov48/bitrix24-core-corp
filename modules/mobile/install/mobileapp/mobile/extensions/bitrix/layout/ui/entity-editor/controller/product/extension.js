/**
 * @module layout/ui/entity-editor/controller/product
 */
jn.define('layout/ui/entity-editor/controller/product', (require, exports, module) => {

	const { Alert } = require('alert');
	const { EntityEditorBaseController } = require('layout/ui/entity-editor/controller/base');
	const { EntityEditorOpportunityField } = require('layout/ui/entity-editor/control/opportunity');

	const isManualPriceFieldName = 'IS_MANUAL_OPPORTUNITY';

	/**
	 * @class EntityEditorProductController
	 */
	class EntityEditorProductController extends EntityEditorBaseController
	{
		constructor(props)
		{
			super(props);

			this.onChangeProduct = this.onChangeProduct.bind(this);
			this.onProductTotalChanged = this.onProductTotalChanged.bind(this);
			this.onChangeFieldValue = this.onChangeFieldValue.bind(this);
			this.handleChangeManualOpportunity = this.handleChangeManualOpportunity.bind(this);
		}

		initialize(id, uid, settings)
		{
			super.initialize(id, uid, settings);

			this.fieldUpdateInProgress = false;

			this.amount = 0;
			this.currency = '';

			this.previousProductCount = 0;
		}

		bindEvents()
		{
			this.customEventEmitter
				.on(CatalogStoreEvents.ProductList.ListChanged, this.onChangeProduct)
				.on(CatalogStoreEvents.ProductList.TotalChanged, this.onProductTotalChanged)
				.on('UI.EntityEditor.Field::onChangeState', this.onChangeFieldValue)
				.on('EntityDetails::onChangeManualOpportunity', this.handleChangeManualOpportunity)
			;

			this.eventsBound = true;
		}

		unbindEvents()
		{
			this.customEventEmitter
				.off(CatalogStoreEvents.ProductList.ListChanged, this.onChangeProduct)
				.off(CatalogStoreEvents.ProductList.TotalChanged, this.onProductTotalChanged)
				.off('UI.EntityEditor.Field::onChangeState', this.onChangeFieldValue)
				.off('EntityDetails::onChangeManualOpportunity', this.handleChangeManualOpportunity)
			;
		}

		getValuesToSave()
		{
			const values = super.getValuesToSave();

			if (this.isManualPriceSwitchingEnabled())
			{
				values[isManualPriceFieldName] = this.model.getField(isManualPriceFieldName, 'N');
			}

			return values;
		}

		loadFromModel()
		{
			this.currency = this.model.getField(this.getCurrencyFieldName(), '');
			this.previousProductCount = this.model.getField(this.getProductSummaryFieldName(), { count: 0 })['count'];

			if (this.isManualPriceSwitchingEnabled())
			{
				this.amount = this.getPriceFromModel();
				this.adjustLocks();

				this
					.getPriceWithCurrencyField()
					.setCustomAmountClickHandler(this.onClickAmountField.bind(this))
				;
			}

			this.customEventEmitter.emit('UI.EntityEditor.ProductController::onModelLoad', [{
				count: this.previousProductCount,
				amount: this.amount,
				currency: this.currency,
			}]);
		}

		isManualPrice()
		{
			return this.model.getField(isManualPriceFieldName, 'N') === 'Y';
		}

		/**
		 * @param {Boolean} isManual
		 * @param {Boolean} focus
		 * @return {Promise<void>|*}
		 */
		setManualPrice(isManual, focus = false)
		{
			const isManualRightNow = this.model.getField(isManualPriceFieldName, 'N') === 'Y';
			if (isManualRightNow === isManual)
			{
				return Promise.resolve();
			}

			this.model.setField(isManualPriceFieldName, isManual ? 'Y' : 'N');

			return this.adjustLocks(focus, true);
		}

		getPriceFieldName()
		{
			return BX.prop.getString(this.settings, 'priceFieldName', null);
		}

		getPriceFromModel()
		{
			const priceFieldName = this.getPriceFieldName();
			if (!priceFieldName || !this.model.hasField(priceFieldName))
			{
				return 0;
			}

			return this.model.getField(priceFieldName, 0);
		}

		/**
		 * @return {EntityEditorOpportunityField|null}
		 */
		getPriceWithCurrencyField()
		{
			const priceWithCurrencyFieldName = this.getPriceWithCurrencyFieldName();
			if (!priceWithCurrencyFieldName)
			{
				return null;
			}

			return this.editor.getControlByIdRecursive(priceWithCurrencyFieldName);
		}

		getPriceWithCurrencyFieldName()
		{
			return BX.prop.getString(this.settings, 'priceWithCurrencyFieldName', null);
		}

		getCurrencyFieldName()
		{
			return BX.prop.getString(this.settings, 'currencyFieldName', null);
		}

		/**
		 * @return {ProductSummarySection|null}
		 */
		getProductSummaryField()
		{
			const productSummaryFieldName = this.getProductSummaryFieldName();
			if (!productSummaryFieldName)
			{
				return null;
			}

			return this.editor.getControlByIdRecursive(productSummaryFieldName);
		}

		getProductSummaryFieldName()
		{
			return BX.prop.getString(this.settings, 'productSummaryFieldName', null);
		}

		onClickAmountField()
		{
			if (!this.isManualPriceSwitchingEnabled())
			{
				return;
			}

			const priceWithCurrencyField = this.getPriceWithCurrencyField();
			if (!priceWithCurrencyField || !priceWithCurrencyField.isAmountLocked())
			{
				return;
			}

			this.showEnableManualOpportunityAlert(true);
		}

		getEntityTypeName()
		{
			return this.model.getField('ENTITY_TYPE_ID');
		}

		getEntityDescription(code)
		{
			let description;

			const entityTypeName = this.getEntityTypeName();
			if (entityTypeName)
			{
				const entityDescription = BX.message(`${code}_${entityTypeName}`);
				if (entityDescription)
				{
					description = entityDescription;
				}
			}

			if (!description)
			{
				description = BX.message(code);
			}

			return description;
		}

		showEnableManualOpportunityAlert(focus = false)
		{
			Alert.confirm(
				BX.message('M_CRM_ENABLE_MANUAL_OPPORTUNITY_CONFIRMATION_TITLE2'),
				this.getEntityDescription('M_CRM_ENABLE_MANUAL_OPPORTUNITY_CONFIRMATION_TEXT3'),
				[
					{
						text: BX.message('M_CRM_ENABLE_MANUAL_OPPORTUNITY_CONFIRMATION_BUTTON'),
						type: 'default',
						onPress: () => {
							this
								.setManualPrice(true, focus)
								.then(() => this.notifyHowToEnableAutoPrice())
							;
						},
					},
					{
						type: 'cancel',
					},
				],
			);
		}

		showDisableManualOpportunityAlert()
		{
			Alert.confirm(
				BX.message('M_CRM_DISABLE_MANUAL_OPPORTUNITY_CONFIRMATION_TITLE'),
				this.getEntityDescription('M_CRM_DISABLE_MANUAL_OPPORTUNITY_CONFIRMATION_TEXT2'),
				[
					{
						text: BX.message('M_CRM_DISABLE_MANUAL_OPPORTUNITY_CONFIRMATION_BUTTON'),
						type: 'default',
						onPress: () => {
							this
								.setManualPrice(false, false)
								.then(() => this.notifyHowToEnableManualPrice())
							;
						},
					},
					{
						type: 'cancel',
					},
				],
			);
		}

		notifyHowToEnableAutoPrice()
		{
			Notify.showUniqueMessage(
				BX.message('M_CRM_CHANGE_MANUAL_OPPORTUNITY_NOTIFY_HINT_TEXT'),
				this.getEntityDescription('M_CRM_CHANGE_MANUAL_OPPORTUNITY_NOTIFY_HINT_TITLE2'),
				{ time: 5 },
			);
		}

		notifyHowToEnableManualPrice()
		{
			Notify.showUniqueMessage(
				BX.message('M_CRM_CHANGE_AUTO_OPPORTUNITY_NOTIFY_HINT_TEXT'),
				BX.message('M_CRM_CHANGE_AUTO_OPPORTUNITY_NOTIFY_HINT_TITLE'),
				{ time: 5 },
			);
		}

		updateProductCount(data)
		{
			if (data && data.hasOwnProperty('count'))
			{
				this.previousProductCount = BX.prop.getNumber(data, 'count', 0);
			}
		}

		onChangeProduct(data)
		{
			return (
				this
					.updateProductSummarySection(data)
					.then(() => this.adjustLocks(false, true))
					.then(() => this.updateProductCount(data))
			);
		}

		onProductTotalChanged(data)
		{
			return (
				this
					.updateProductSummarySection(data)
					.then(() => this.checkTotalSumField(data))
					.then(() => this.updateProductCount(data))
			);
		}

		adjustLocks(focus = false, recalculatePrice = false)
		{
			if (!this.isManualPriceSwitchingEnabled())
			{
				return Promise.resolve();
			}

			if (this.isManualPrice())
			{
				return this.adjustLocksForManualPrice(focus);
			}

			return this.adjustLocksForAutoPrice(recalculatePrice);
		}

		adjustLocksForManualPrice(focus = false)
		{
			const priceWithCurrencyField = this.getPriceWithCurrencyField();
			if (!priceWithCurrencyField || !(priceWithCurrencyField instanceof EntityEditorOpportunityField))
			{
				return Promise.resolve();
			}

			if (focus)
			{
				return priceWithCurrencyField.unlockAmountAndFocus();
			}

			return priceWithCurrencyField.unlockAmount();
		}

		adjustLocksForAutoPrice(recalculatePrice = false)
		{
			const productSummaryField = this.getProductSummaryField();
			if (!productSummaryField)
			{
				return Promise.resolve();
			}

			const priceWithCurrencyField = this.getPriceWithCurrencyField();
			if (!priceWithCurrencyField || !(priceWithCurrencyField instanceof EntityEditorOpportunityField))
			{
				return Promise.resolve();
			}

			const { count, total } = productSummaryField.getValue();

			let promise;

			if (count > 0)
			{
				promise = priceWithCurrencyField.lockAmount();
			}
			else
			{
				promise = priceWithCurrencyField.unlockAmount();
			}

			if (recalculatePrice)
			{
				return promise.then(() => this.updateTotalSumField({ total }));
			}

			return promise;
		}

		onChangeFieldValue({ fieldName, fieldValue })
		{
			if (fieldName !== this.getPriceWithCurrencyFieldName())
			{
				return;
			}

			let { amount, currency } = fieldValue;
			amount = parseFloat(amount);
			currency = currency || this.currency;

			if (this.currency !== currency)
			{
				this.currency = currency;
				this.customEventEmitter.emit('EntityEditorProductController::onChangeCurrency', [currency]);
			}

			if (this.fieldUpdateInProgress)
			{
				this.amount = amount;
			}
			else if (this.amount !== amount)
			{
				this.amount = amount;

				if (this.isManualPriceSwitchingEnabled())
				{
					if (this.amount > 0 && !this.isManualPrice())
					{
						this.setManualPrice(true);
					}
					else if (this.amount === 0 && this.previousProductCount === 0)
					{
						this.setManualPrice(false);
					}
				}
			}
		}

		handleChangeManualOpportunity()
		{
			if (this.isManualPrice())
			{
				this.showDisableManualOpportunityAlert();
			}
			else
			{
				this.showEnableManualOpportunityAlert();
			}
		}

		isManualPriceSwitchingEnabled()
		{
			return this.model.hasField(isManualPriceFieldName) && Boolean(this.getPriceWithCurrencyField());
		}

		checkTotalSumField(data)
		{
			return (
				this
					.checkShouldChangePriceControl(data)
					.then(() => this.updateTotalSumField(data))
			);
		}

		shouldAskToChangePriceControl(data)
		{
			if (!this.isManualPriceSwitchingEnabled())
			{
				return false;
			}

			const productCount = BX.prop.get(data, 'count', 0);

			return (
				this.isManualPrice()
				&& this.previousProductCount === 0
				&& productCount > 0
			);
		}

		checkShouldChangePriceControl(data)
		{
			if (!this.shouldAskToChangePriceControl(data))
			{
				return this.adjustLocks(false, true);
			}

			return this.askChangePriceControl();
		}

		askChangePriceControl()
		{
			return new Promise((resolve) => {
				Alert.confirm(
					BX.message('M_CRM_DISABLE_MANUAL_OPPORTUNITY_CONFIRMATION_TITLE'),
					this.getEntityDescription('M_CRM_DISABLE_MANUAL_OPPORTUNITY_CONFIRMATION_TEXT2'),
					[
						{
							text: BX.message('M_CRM_DISABLE_MANUAL_OPPORTUNITY_CONFIRMATION_BUTTON'),
							type: 'default',
							onPress: () => {
								if (this.isManualPrice())
								{
									this.setManualPrice(false).then(() => {
										this.notifyHowToEnableManualPrice();
										resolve();
									});
								}
								else
								{
									resolve();
								}
							},
						},
						{
							type: 'cancel',
							onPress: () => {
								if (!this.isManualPrice())
								{
									this.setManualPrice(true).then(() => {
										this.notifyHowToEnableAutoPrice();
										resolve();
									});
								}
								else
								{
									resolve();
								}
							},
						},
					],
				);
			});
		}

		updateTotalSumField({ total })
		{
			if ((this.isManualPriceSwitchingEnabled() && this.isManualPrice()))
			{
				return Promise.resolve();
			}

			//IS_MANUAL OPPORTUNITY is not passed with conversion, so check if it exists
			if(!this.model.hasField(isManualPriceFieldName))
			{
				return Promise.resolve();
			}

			const priceWithCurrencyField = this.getPriceWithCurrencyField();
			if (!priceWithCurrencyField)
			{
				return Promise.resolve();
			}

			this.fieldUpdateInProgress = true;

			const amount = BX.prop.getNumber(total, 'amount', 0);
			const currency = BX.prop.getString(total, 'currencyId', '') || this.currency;

			return (
				priceWithCurrencyField
					.setValue({ amount, currency })
					.then(() => new Promise((resolve) => {
						// @todo fix: UI.EntityEditor.Field::onChangeState called asynchronously so this callback executed earlier than necessary
						setTimeout(() => {
							this.fieldUpdateInProgress = false;
							resolve();
						}, 50);
					}))
			);
		}

		updateProductSummarySection(data)
		{
			const productSummaryField = this.getProductSummaryField();
			if (!productSummaryField)
			{
				return Promise.resolve();
			}

			const previousValue = productSummaryField.getValue();
			const { count, total: { amount, currency } } = previousValue;

			const newValue = {
				count: data.hasOwnProperty('count') ? BX.prop.getNumber(data, 'count', 0) : count,
				total: {
					amount: data.hasOwnProperty('total') ? BX.prop.getNumber(data.total, 'amount', amount) : amount,
					currency: data.hasOwnProperty('total') ? BX.prop.getString(data.total, 'currency', currency) : currency,
				},
			};

			return productSummaryField.setValue(newValue);
		}
	}

	module.exports = { EntityEditorProductController };
});
