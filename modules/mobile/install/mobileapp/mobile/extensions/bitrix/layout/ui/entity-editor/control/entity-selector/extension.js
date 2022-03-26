(() => {
	/**
	 * @class EntitySelectorField
	 */
	class EntitySelectorField extends EntityEditorField
	{
		prepareConfig()
		{
			return {
				...super.prepareConfig(),
				selectorTitle: this.getTitle(),
				reloadEntityListFromProps: this.editor && this.editor.settings.loadFromModel,
				entityList: this.prepareEntityList()
			};
		}

		prepareEntityList()
		{
			const entityListField = this.schemeElement.getDataParam('entityListField', null);
			if (entityListField)
			{
				return this.model.getField(entityListField, []);
			}

			return [];
		}
	}

	jnexport(EntitySelectorField)
})();
