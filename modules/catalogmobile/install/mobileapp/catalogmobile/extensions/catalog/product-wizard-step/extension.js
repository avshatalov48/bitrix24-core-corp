(() => {
	const require = (ext) => jn.require(ext);

	const { FieldEditorStep } = require('layout/ui/wizard/step/field-editor');
	const AppTheme = require('apptheme');

	const styles = {
		footer: {
			container: {
				paddingTop: 10,
				paddingLeft: 18,
				paddingRight: 18,
			},
			text: {
				fontSize: 14,
				color: AppTheme.colors.base4,
			},
			link: {
				fontSize: 14,
				color: AppTheme.colors.base4,
				textDecorationLine: 'underline',
				textDecorationUnderlineStyle: 'dot',
			},
		},
	};

	/**
	 * @class CatalogProductWizardStep
	 */
	class CatalogProductWizardStep extends FieldEditorStep
	{
		constructor(entity, options)
		{
			super();
			/** @var {BaseCatalogProductEntity} */
			this.entity = entity;
			this.options = options || {};
		}

		getTitle()
		{
			return this.entity.getTitle();
		}

		getPermissions()
		{
			return this.entity.getDictionaryValues('permissions');
		}

		hasPermission(permission)
		{
			const permissions = this.getPermissions();

			return (
				permissions.hasOwnProperty(permission)
				&& permissions[permission] === true
			);
		}

		hasProductEditPermission()
		{
			return this.hasPermission('catalog_product_edit');
		}

		notifyAboutDataChanges()
		{
			BX.postComponentEvent('onCatalogProductWizardProgress', [this.entity.getFields()]);
		}

		setDefaultValues(defaultValues)
		{
			Object.keys(defaultValues).forEach((fieldId) => {
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
