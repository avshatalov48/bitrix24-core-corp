(() => {
	class EntityConfigSection extends EntityConfigBaseItem
	{
		static create(settings)
		{
			const self = new EntityConfigSection();
			self.initialize(settings);
			return self;
		}

		constructor()
		{
			super();
			this.fields = [];
		}

		doInitialize()
		{
			const elements = BX.prop.getArray(this.data, "elements", []);
			elements.forEach((element, index) => {
				const field = EntityConfigField.create({data: element});
				field.setIndex(index);
				this.fields.push(field);
			});
		}
	}

	jnexport(EntityConfigSection)
})();