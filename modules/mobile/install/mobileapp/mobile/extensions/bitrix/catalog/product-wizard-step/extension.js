(() =>
{
	const styles = {
		footer: {
			container: {
				paddingTop: 10,
				paddingLeft: 18,
				paddingRight: 18,
			},
			text: {
				fontSize: 14,
				color: '#A8ADB4',
			},
			link: {
				fontSize: 14,
				color: '#A8ADB4',
				textDecorationLine: 'underline',
				textDecorationUnderlineStyle: 'dot',
			}
		},
	};

	/**
	 * @class CatalogProductWizardStep
	 */
	class CatalogProductWizardStep extends FieldEditorStep
	{
		constructor(entity)
		{
			super();
			this.entity = entity;
		}

		getTitle()
		{
			return this.entity.getTitle();
		}

		notifyAboutDataChanges()
		{
			BX.postComponentEvent("onCatalogProductWizardProgress", [this.entity.getFields()]);
		}

		setDefaultValues(defaultValues)
		{
			Object.keys(defaultValues).forEach(fieldId => {
				if (this.entity.get(fieldId, null) === null)
				{
					this.entity.set(fieldId, defaultValues[fieldId]);
				}
			});
		}

		onLeaveStep()
		{
			super.onLeaveStep();
			this.notifyAboutDataChanges();
		}

		onChange(fieldId, fieldValue, options)
		{
			super.onChange(fieldId, fieldValue, options);
			this.entity.set(fieldId, fieldValue);
		}
	}

	this.CatalogProductWizardStep = CatalogProductWizardStep;
	this.CatalogProductWizardStepStyles = styles;
})();
