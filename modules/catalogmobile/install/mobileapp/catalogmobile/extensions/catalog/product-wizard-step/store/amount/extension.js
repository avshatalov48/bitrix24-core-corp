/**
 * @module catalog/product-wizard-step/store/amount
 */
jn.define('catalog/product-wizard-step/store/amount', (require, exports, module) => {
	const { NumberType } = require('layout/ui/fields/number');
	const { SelectType } = require('layout/ui/fields/select');
	const { EntitySelectorType } = require('layout/ui/fields/entity-selector');
	const { DocumentType } = require('catalog/store/document-type');

	class StoreCatalogProductAmountStep extends CatalogProductWizardStep
	{
		prepareFields()
		{
			this.clearFields();

			const stores = this.entity.getDictionaryValues('stores');
			const hasStoreReadAccess = stores.length > 0;
			const measures = this.entity.getDictionaryValues('measures');

			const defaultMeasure = measures.find(item => item.isDefault);
			const defaultStore = this.getDefaultStores(stores);

			const documentType = this.entity.get('DOCUMENT_TYPE');

			if (hasStoreReadAccess)
			{
				const defaultValues = {
					'AMOUNT': '',
					'MEASURE_CODE': String(defaultMeasure ? defaultMeasure.value : ''),
				};

				if (documentType === DocumentType.Moving || documentType === DocumentType.Deduct)
				{
					defaultValues.STORE_FROM = defaultStore;
				}
				if (
					documentType === DocumentType.Arrival
					|| documentType === DocumentType.StoreAdjustment
					|| documentType === DocumentType.Moving
				)
				{
					defaultValues.STORE_TO = defaultStore;
				}

				this.setDefaultValues(defaultValues);
			}

			this.addCombinedField(
				{
					id: 'AMOUNT',
					type: NumberType,
					title: BX.message('WIZARD_FIELD_PRODUCT_AMOUNT'),
					disabled: !hasStoreReadAccess,
					placeholder: !hasStoreReadAccess ? BX.message('WIZARD_FIELD_ACCESS_DENIED') : null,
					emptyValue: !hasStoreReadAccess ? BX.message('WIZARD_FIELD_ACCESS_DENIED') : null,
					value: this.entity.get('AMOUNT'),
					config: {
						selectionOnFocus: true,
					},
				},
				{
					id: 'MEASURE_CODE',
					type: SelectType,
					title: BX.message('WIZARD_FIELD_MEASURE_CODE'),
					disabled: !hasStoreReadAccess,
					placeholder: !hasStoreReadAccess ? BX.message('WIZARD_FIELD_ACCESS_DENIED') : null,
					emptyValue: !hasStoreReadAccess ? BX.message('WIZARD_FIELD_ACCESS_DENIED') : null,
					value: this.entity.get('MEASURE_CODE'),
					required: true,
					showRequired: false,
					config: {
						defaultListTitle: BX.message('WIZARD_FIELD_MEASURE_CODE'),
						items: measures,
					},
				}
			);

			const storeFrom = this.entity.get('STORE_FROM');
			if (storeFrom)
			{
				this.addField(
					'STORE_FROM',
					EntitySelectorType,
					documentType === DocumentType.Moving
						? BX.message('WIZARD_FIELD_PRODUCT_STORE_FROM')
						: BX.message('WIZARD_FIELD_PRODUCT_STORE')
					,
					storeFrom ? storeFrom.id : null,
					{
						disabled: !hasStoreReadAccess,
						placeholder: !hasStoreReadAccess ? BX.message('WIZARD_FIELD_ACCESS_DENIED') : null,
						emptyValue: !hasStoreReadAccess ? BX.message('WIZARD_FIELD_ACCESS_DENIED') : null,
						config: {
							selectorType: EntitySelectorFactory.Type.STORE,
							enableCreation: this.hasPermission('catalog_store_modify'),
							entityList: [storeFrom],
							provider: {
								options: {
									'useAddressAsTitle': true,
								},
							},
						},
					},
				);
			}

			const storeTo = this.entity.get('STORE_TO');
			if (storeTo)
			{
				this.addField(
					'STORE_TO',
					EntitySelectorType,
					documentType === DocumentType.Moving
						? BX.message('WIZARD_FIELD_PRODUCT_STORE_TO')
						: BX.message('WIZARD_FIELD_PRODUCT_STORE')
					,
					storeTo ? storeTo.id : null,
					{
						disabled: !hasStoreReadAccess,
						placeholder: !hasStoreReadAccess ? BX.message('WIZARD_FIELD_ACCESS_DENIED') : null,
						emptyValue: !hasStoreReadAccess ? BX.message('WIZARD_FIELD_ACCESS_DENIED') : null,
						config: {
							selectorType: EntitySelectorFactory.Type.STORE,
							enableCreation: this.hasPermission('catalog_store_modify'),
							entityList: [storeTo],
							provider: {
								options: {
									'useAddressAsTitle': true,
								},
							},
						},
					},
				);
			}
		}

		getDefaultStores(stores)
		{
			if (stores.length === 0)
			{
				return null;
			}

			let defaultStore = stores.find(item => item.isDefault);
			if (defaultStore)
			{
				return defaultStore;
			}

			return stores[0];
		}

		getNextStepButtonText()
		{
			return BX.message('WIZARD_STEP_BUTTON_FINISH_TEXT_MSGVER_1');
		}

		onChange(fieldId, fieldValue, options)
		{
			super.onChange(fieldId, fieldValue, options);
			if (fieldId === 'STORE_FROM')
			{
				this.entity.set('STORE_FROM', options ? options[0] : null);
			}
			else if (fieldId === 'STORE_TO')
			{
				this.entity.set('STORE_TO', options ? options[0] : null);
			}
		}

		onMoveToNextStep()
		{
			return super.onMoveToNextStep()
				.then(() => {
					BX.postComponentEvent("onCatalogProductWizardFinish", [this.entity.getFields()]);
				})
				;
		}
	}

	module.exports = { StoreCatalogProductAmountStep };
});
