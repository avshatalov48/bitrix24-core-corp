(() => {
	class EntityConfigColumn extends EntityConfigBaseItem
	{
		static create(settings)
		{
			const self = new EntityConfigColumn();
			self.initialize(settings);
			return self;
		}

		constructor()
		{
			super();
			this.sections = [];
		}

		doInitialize()
		{
			const elements = BX.prop.getArray(this.data, "elements", []);
			elements.forEach(element => {
				if (element.type === "section")
				{
					const config = EntityConfigFactory.createByType(element.type, {data: element});
					this.addSection(config);
				}
			});
		}

		addSection(section)
		{
			this.sections.push(section);
		}
	}

	jnexport(EntityConfigColumn);
})();