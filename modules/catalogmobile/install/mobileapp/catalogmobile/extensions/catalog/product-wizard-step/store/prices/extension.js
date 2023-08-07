/**
 * @module catalog/product-wizard-step/store/prices
 */
jn.define('catalog/product-wizard-step/store/prices', (require, exports, module) => {
	const { MoneyType } = require('layout/ui/fields/money');

	class StoreCatalogProductPricesStep extends CatalogProductWizardStep
	{
		prepareFields()
		{
			this.clearFields();

			const documentCurrency = this.entity.get('DOCUMENT_CURRENCY');

			/**
			 * Purchasing price
			 */
			const hasPurchasingPriceEditAccess = (
				this.hasProductEditPermission()
				&& this.hasPermission('catalog_purchas_info')
			);

			if (hasPurchasingPriceEditAccess)
			{
				this.setDefaultValues({
					'PURCHASING_PRICE': {
						amount: '',
						currency: documentCurrency,
					},
				});
			}

			this.addField(
				'PURCHASING_PRICE',
				MoneyType,
				BX.message('WIZARD_FIELD_PRODUCT_PURCHASING_PRICE'),
				this.entity.get('PURCHASING_PRICE'),
				{
					disabled: !hasPurchasingPriceEditAccess,
					placeholder: !hasPurchasingPriceEditAccess ? BX.message('WIZARD_FIELD_ACCESS_DENIED') : null,
					emptyValue: !hasPurchasingPriceEditAccess ? BX.message('WIZARD_FIELD_ACCESS_DENIED') : null,
					config: {
						selectionOnFocus: true,
						currencyReadOnly: true,
					},
				}
			);

			/**
			 * Base price
			 */
			const hasBasePriceEditAccess = (
				this.hasProductEditPermission()
				&& this.hasPermission('catalog_price')
			);
			if (hasBasePriceEditAccess)
			{
				this.setDefaultValues({
					'BASE_PRICE': {
						amount: '',
						currency: documentCurrency,
					},
				});
			}

			this.addField(
				'BASE_PRICE',
				MoneyType,
				BX.message('WIZARD_FIELD_PRODUCT_BASE_PRICE'),
				this.entity.get('BASE_PRICE'),
				{
					disabled: !hasBasePriceEditAccess,
					placeholder: !hasBasePriceEditAccess ? BX.message('WIZARD_FIELD_ACCESS_DENIED') : null,
					emptyValue: !hasBasePriceEditAccess ? BX.message('WIZARD_FIELD_ACCESS_DENIED') : null,
					config: {
						selectionOnFocus: true,
						currencyReadOnly: true,
					},
				}
			);
		}

		renderFooter()
		{
			return View({
					style: CatalogProductWizardStepStyles.footer.container,
				},
				Text({
					style: CatalogProductWizardStepStyles.footer.text,
					text: BX.message('WIZARD_STEP_FOOTER_TEXT_PRICE')
				})
			);
		}

		onMoveToNextStep()
		{
			return super.onMoveToNextStep()
				.then(() => this.entity.save())
				;
		}
	}

	module.exports = { StoreCatalogProductPricesStep };
});
