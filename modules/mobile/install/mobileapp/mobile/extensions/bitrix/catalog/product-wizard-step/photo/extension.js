(() => {
	const { FileType } = jn.require('layout/ui/fields/file');

	/**
	 * @class CatalogProductPhotoStep
	 */
	class CatalogProductPhotoStep extends CatalogProductWizardStep
	{
		prepareFields()
		{
			this.clearFields();

			this.addField(
				'MORE_PHOTO',
				FileType,
				BX.message('WIZARD_FIELD_PRODUCT_PHOTO'),
				this.entity.get('MORE_PHOTO', []),
				{
					disabled: !this.hasProductEditPermission(),
					placeholder: BX.message('WIZARD_FIELD_ACCESS_DENIED'),
					emptyValue: BX.message('WIZARD_FIELD_ACCESS_DENIED'),
					multiple: true,
					config: {
						buttonType: 'primary',
						mediaType: 'image',
						controller: {
							entityId: 'catalog-product',
							options: {
								productId: this.entity.get('ID'),
							},
						},
					},
				},
			);
		}

		onMoveToNextStep()
		{
			return (
				super.onMoveToNextStep()
					.then(() => this.waitUntilPhotosLoaded())
					.then(() => this.entity.save(true))
			);
		}

		waitUntilPhotosLoaded()
		{
			const photoFieldRef = this.getMorePhotoRef();
			if (!photoFieldRef)
			{
				return Promise.resolve();
			}

			return photoFieldRef.getValueWhileReady();
		}

		/**
		 * @returns {?Fields.FileField}
		 */
		getMorePhotoRef()
		{
			if (!this.editorRef)
			{
				return null;
			}

			return this.editorRef.getFieldRef('MORE_PHOTO');
		}
	}

	this.CatalogProductPhotoStep = CatalogProductPhotoStep;
})();
