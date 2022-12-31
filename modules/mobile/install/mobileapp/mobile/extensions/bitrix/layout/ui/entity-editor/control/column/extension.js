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

		renderSections()
		{
			return this.renderFromModel((ref, index) => {
				this.sections[index] = ref;
			});
		}

		render()
		{
			const renderedSections = this.renderSections();
			if (!renderedSections || renderedSections.length === 0)
			{
				return null;
			}

			return View(
				{
					style: {
						style: {
							flexDirection: 'column',
						},
					},
				},
				...renderedSections,
			);
		}
	}

	jnexport(EntityEditorColumn);
})();