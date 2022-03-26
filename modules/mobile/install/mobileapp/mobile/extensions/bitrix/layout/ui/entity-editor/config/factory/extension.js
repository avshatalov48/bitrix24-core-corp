(() => {
	const COLUMN = 'column';
	const SECTION = 'section';
	class EntityConfigFactory
	{
		static createByType(type, settings)
		{
			let config;
			if (type === COLUMN)
			{
				config = EntityConfigColumn.create(settings);
			}
			else if (type === SECTION)
			{
				config = EntityConfigSection.create(settings);
			}
			else
			{
				config = EntityConfigField.create(settings);
			}

			return config;
		}
	}

	jnexport(EntityConfigFactory);
})();