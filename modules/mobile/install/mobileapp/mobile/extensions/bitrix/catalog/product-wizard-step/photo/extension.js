(() =>
{
	class CatalogProductPhotoStep extends CatalogProductWizardStep
	{
		prepareFields()
		{
			this.clearFields();

			this.addField(
				'MORE_PHOTO',
				FieldFactory.Type.FILE,
				BX.message('WIZARD_FIELD_PRODUCT_PHOTO'),
				this.entity.get('MORE_PHOTO', []),
				{
					multiple: true,
					config: {
						buttonType: 'primary',
						mediaType: 'image',
					},
				}
			);
		}

		onMoveToNextStep()
		{
			return super.onMoveToNextStep()
				.then(() => this.entity.save())
			;
		}
	}

	this.CatalogProductPhotoStep = CatalogProductPhotoStep;
})();
