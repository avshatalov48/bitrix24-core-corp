(() => {
	/**
	 * @class EntityEditorColumn
	 */
	class EntityEditorColumn extends EntityEditorBaseControl
	{
		constructor(props)
		{
			super(props);
			/** @type {EntityEditorSection[]} */
			this.sections = [];
		}

		/**
		 * @returns {EntityEditorSection[]}
		 */
		getControls()
		{
			return this.sections;
		}

		renderSections() {
			return this.renderFromModel((ref, index) => {
				this.sections[index] = ref
			});
		}

		render()
		{
			return View(
				{
					style: {
						style: {
							flexDirection: 'column'
						}
					}
				},
				...this.renderSections()
			)
		}

		validate(result)
		{
			const validator = EntityAsyncValidator.create();

			this.sections.forEach((field) => {
				validator.addResult(field.validate(result));
			})

			return validator.validate();
		}
	}

	jnexport(EntityEditorColumn);
})();