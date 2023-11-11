/**
 * @module catalog/product-wizard-step/photo
 */
jn.define('catalog/product-wizard-step/photo', (require, exports, module) => {
	const { FileType } = require('layout/ui/fields/file');

	/**
	 * @class CatalogProductPhotoStep
	 */
	class CatalogProductPhotoStep extends CatalogProductWizardStep
	{
		constructor(entity, options)
		{
			super(entity, options);

			this.isSaving = false;
		}

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
			if (this.isSaving)
			{
				return false;
			}

			this.isSaving = true;

			return (
				super.onMoveToNextStep()
					.then(() => this.waitUntilPhotosLoaded())
					.then(() => this.entity.save(true))
					.then(() => {
						this.isSaving = false;
					})
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

	module.exports = { CatalogProductPhotoStep };
});
