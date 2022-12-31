(() => {
	/**
	 * @class EntityScheme
	 */
	class EntityScheme
	{
		static create(id, settings)
		{
			const self = new EntityScheme();
			self.initialize(id, settings);
			return self;
		}

		constructor()
		{
			this.id = "";
			this.settings = {};
			this.elements = null;
			this.availableElements = null;
		}

		initialize(id, settings)
		{
			this.id = CommonUtils.isNotEmptyString(id) ? id : Random.getString();
			this.settings = settings ? settings : {};

			this.elements = [];
			this.availableElements = [];

			const currentData = BX.prop.getArray(this.settings, "current", []);
			currentData.forEach((data) => {
				this.elements.push(EntitySchemeElement.create(data));
			});

			const availableData = BX.prop.getArray(this.settings, "available", []);
			availableData.forEach((data) => {
				this.availableElements.push(EntitySchemeElement.create(data));
			});
		}

		getElements()
		{
			return [...this.elements];
		}

		getAvailableElements()
		{
			return [...this.availableElements];
		}
	}

	jnexport(EntityScheme)
})();