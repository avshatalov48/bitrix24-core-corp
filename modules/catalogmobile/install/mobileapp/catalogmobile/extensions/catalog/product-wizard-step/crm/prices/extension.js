/**
 * @module catalog/product-wizard-step/crm/prices
 */
jn.define('catalog/product-wizard-step/crm/prices', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BooleanType } = require('layout/ui/fields/boolean');
	const { MoneyType } = require('layout/ui/fields/money');
	const { NumberType, NumberPrecision } = require('layout/ui/fields/number');
	const { SelectType } = require('layout/ui/fields/select');
	const { BannerButton } = require('layout/ui/banners');

	/**
	 * @class CrmProductPricesStep
	 */
	class CrmProductPricesStep extends CatalogProductWizardStep
	{
		prepareFields()
		{
			this.clearFields();

			const documentCurrency = this.entity.get('DOCUMENT_CURRENCY');

			const inventoryControlConfig = this.entity.getDictionaryValues('inventoryControl');

			const taxesConfig = this.entity.getDictionaryValues('taxes');
			const isTaxMode = BX.prop.getBoolean(taxesConfig, 'isTaxMode', false);
			const vatRates = BX.prop.getArray(taxesConfig, 'vatRates', []);
			const vatIncluded = BX.prop.getBoolean(taxesConfig, 'vatIncluded', false);
			const defaultVatId = isTaxMode ? '' : BX.prop.getNumber(taxesConfig, 'defaultVatId', 0);

			const measures = this.entity.getDictionaryValues('measures');
			const defaultMeasure = measures.find((item) => item.isDefault);

			this.setDefaultValues({
				BASE_PRICE: {
					amount: '',
					currency: documentCurrency,
				},
				QUANTITY: '',
				MEASURE_CODE: String(defaultMeasure ? defaultMeasure.value : ''),
				VAT_ID: defaultVatId,
				VAT_INCLUDED: vatIncluded,
			});

			this.addField(
				'BASE_PRICE',
				MoneyType,
				Loc.getMessage('CRM_PRODUCT_WIZARD_PRICES_BASE_PRICE'),
				this.entity.get('BASE_PRICE'),
				{
					config: {
						selectionOnFocus: true,
					},
				},
			);

			if (!isTaxMode)
			{
				this.addCombinedField(
					{
						id: 'VAT_ID',
						type: SelectType,
						title: Loc.getMessage('CRM_PRODUCT_WIZARD_PRICES_TAX_RATE'),
						placeholder: '0',
						value: this.entity.get('VAT_ID'),
						required: true,
						showRequired: false,
						config: {
							items: vatRates,
						},
					},
					{
						id: 'VAT_INCLUDED',
						type: BooleanType,
						title: Loc.getMessage('CRM_PRODUCT_WIZARD_PRICES_TAX_INCLUDED'),
						value: this.entity.get('VAT_INCLUDED'),
						required: false,
					},
				);
			}

			if (inventoryControlConfig.isQuantityControlEnabled && !inventoryControlConfig.isInventoryControlEnabled)
			{
				this.addCombinedField(
					{
						id: 'QUANTITY',
						type: NumberType,
						title: Loc.getMessage('CRM_PRODUCT_WIZARD_PRICES_QUANTITY'),
						placeholder: '0',
						value: this.entity.get('QUANTITY'),
						config: {
							selectionOnFocus: true,
							type: NumberPrecision.INTEGER,
						},
					},
					{
						id: 'MEASURE_CODE',
						type: SelectType,
						title: Loc.getMessage('CRM_PRODUCT_WIZARD_PRICES_MEASURE'),
						value: this.entity.get('MEASURE_CODE'),
						required: true,
						showRequired: false,
						config: {
							defaultListTitle: Loc.getMessage('CRM_PRODUCT_WIZARD_PRICES_MEASURE'),
							items: measures,
						},
					},
				);
			}
		}

		renderFooter()
		{
			const inventoryControlConfig = this.entity.getDictionaryValues('inventoryControl');
			if (inventoryControlConfig.isInventoryControlEnabled)
			{
				return View(
					{
						style: {
							marginTop: 12,
						},
					},
					BannerButton({
						title: Loc.getMessage('CRM_PRODUCT_WIZARD_PRICES_INVENTORY_CONTROL_TITLE'),
						description: Loc.getMessage('CRM_PRODUCT_WIZARD_PRICES_INVENTORY_CONTROL_BODY'),
						showArrow: false,
					}),
				);
			}

			return null;
		}

		onMoveToNextStep()
		{
			return super.onMoveToNextStep()
				.then(() => this.entity.save())
				.then(() => BX.postComponentEvent('onCatalogProductWizardFinish', [this.entity.getFields()]));
		}

		getNextStepButtonText()
		{
			return Loc.getMessage('WIZARD_STEP_BUTTON_FINISH_TEXT_MSGVER_1');
		}
	}

	module.exports = { CrmProductPricesStep };
});
