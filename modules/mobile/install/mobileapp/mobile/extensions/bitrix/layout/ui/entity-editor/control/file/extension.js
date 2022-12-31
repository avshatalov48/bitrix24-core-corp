(() => {

	/**
	 * @class EntityEditorFileField
	 */
	class EntityEditorFileField extends EntityEditorField
	{
		prepareConfig()
		{
			return {
				...super.prepareConfig(),
				fileInfo: this.prepareFileInfo(),
				controller: this.prepareControllerOptions(),
				enableToEdit: this.parent.isInEditMode(),
			};
		}

		prepareFileInfo()
		{
			const fileInfo = this.schemeElement.getDataParam('fileInfo', null);
			if (fileInfo)
			{
				return fileInfo;
			}

			const fileInfoField = this.schemeElement.getDataParam('fileInfoField', null);
			if (fileInfoField)
			{
				return this.model.getField(fileInfoField, {});
			}

			return [];
		}

		prepareControllerOptions()
		{
			const controller = this.schemeElement.getDataParam('controller', {});

			const controllerOptions = {};
			const controllerOptionNames = this.schemeElement.getDataParam('controllerOptionNames', {});
			Object.keys(controllerOptionNames).forEach((optionName) => {
				controllerOptions[optionName] = this.model.getField(controllerOptionNames[optionName], null);
			});

			controller.options = controllerOptions;

			return controller;
		}
	}

	jnexport(EntityEditorFileField);
})();
