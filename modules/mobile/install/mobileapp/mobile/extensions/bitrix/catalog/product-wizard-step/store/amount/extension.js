(() =>
{
	class StoreCatalogProductAmountStep extends CatalogProductWizardStep
	{
		prepareFields()
		{
			this.clearFields();

			const measures = this.entity.getDictionaryValues('measures');
			const stores = this.entity.getDictionaryValues('stores');
			const defaultMeasure = measures.find(item => item.isDefault);
			const defaultStore =  stores.find(item => item.isDefault);

			this.setDefaultValues({
				'AMOUNT': '',
				'MEASURE_CODE': String(defaultMeasure ? defaultMeasure.value : ''),
				'STORE_TO': defaultStore,
			});

			this.addCombinedField(
				{
					id: 'AMOUNT',
					type: FieldFactory.Type.NUMBER,
					title: BX.message('WIZARD_FIELD_PRODUCT_AMOUNT'),
					placeholder: '0',
					value: this.entity.get('AMOUNT'),
					config: {
						selectionOnFocus: true,
						type: Fields.NumberField.Types.INTEGER,
					}
				},
				{
					id: 'MEASURE_CODE',
					type: FieldFactory.Type.SELECT,
					title: BX.message('WIZARD_FIELD_MEASURE_CODE'),
					value: this.entity.get('MEASURE_CODE'),
					required: true,
					showRequired: false,
					items: measures,
					config: {
						defaultListTitle: BX.message('WIZARD_FIELD_MEASURE_CODE'),
					},
				},
			);

			const storeTo = this.entity.get('STORE_TO');
			this.addField(
				'STORE_TO',
				FieldFactory.Type.ENTITY_SELECTOR,
				BX.message('WIZARD_FIELD_PRODUCT_STORE'),
				storeTo ? storeTo.id : null,
				{
					config: {
						selectorType: EntitySelectorFactory.Type.STORE,
						enableCreation: true,
						entityList: [storeTo],
						provider: {
							options: {
								'useAddressAsTitle': true,
							},
						},
					}
				}
			);
		}

		getNextStepButtonText()
		{
			return BX.message('WIZARD_STEP_BUTTON_FINISH_TEXT');
		}

		onChange(fieldId, fieldValue, options)
		{
			super.onChange(fieldId, fieldValue, options);
			if (fieldId === 'STORE_TO')
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

	this.StoreCatalogProductAmountStep = StoreCatalogProductAmountStep;
})();
